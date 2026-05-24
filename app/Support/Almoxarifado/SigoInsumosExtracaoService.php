<?php

namespace App\Support\Almoxarifado;

use App\Models\Almoxarifado\SigoExtracao;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class SigoInsumosExtracaoService
{
    private const STORAGE_DIR = 'almoxarifado/sigo-extracoes';

    private const PYTHON_CHECK = 'from playwright.sync_api import Page, sync_playwright; import openpyxl; print("ok")';

    /** @var list<string> */
    private const WINDOWS_ENV_KEYS = [
        'PATH', 'PATHEXT', 'SYSTEMROOT', 'SystemRoot', 'WINDIR', 'Windir',
        'TEMP', 'TMP', 'USERPROFILE', 'LOCALAPPDATA', 'APPDATA', 'ComSpec',
        'PROCESSOR_ARCHITECTURE', 'NUMBER_OF_PROCESSORS',
    ];

    private function scriptVerificacaoDeps(): string
    {
        return (string) config('sigo.check_script', base_path('scripts/sigo_check_deps.py'));
    }

    public function iniciarExtracao(int $userId, string $usuario, string $senha): SigoExtracao
    {
        $this->verificarDependencias();

        $uuid = (string) Str::uuid();
        $dirRelativo = self::STORAGE_DIR.'/'.$uuid;
        File::ensureDirectoryExists(Storage::disk('local')->path($dirRelativo));

        return SigoExtracao::query()->create([
            'uuid' => $uuid,
            'user_id' => $userId,
            'sigo_usuario' => $usuario,
            'sigo_senha_criptografada' => Crypt::encryptString($senha),
            'status' => SigoExtracao::STATUS_PENDENTE,
            'diretorio_relativo' => $dirRelativo,
        ]);
    }

    public function processarExtracao(SigoExtracao $registro): void
    {
        if ($registro->sigo_senha_criptografada === null) {
            throw new RuntimeException('Credenciais SIGO indisponíveis para esta extração.');
        }

        $senha = Crypt::decryptString($registro->sigo_senha_criptografada);
        $dirRelativo = $registro->diretorio_relativo ?: self::STORAGE_DIR.'/'.$registro->uuid;
        $dirAbsoluto = Storage::disk('local')->path($dirRelativo);

        $registro->forceFill([
            'status' => SigoExtracao::STATUS_EXECUTANDO,
            'iniciado_em' => now(),
            'diretorio_relativo' => $dirRelativo,
        ])->save();

        $resultado = $this->executarScript(
            $registro->sigo_usuario,
            $senha,
            $dirAbsoluto,
            $dirRelativo,
            $registro->uuid,
        );

        $registro->limparSenha();

        if ($resultado['ok']) {
            $resumo = $resultado['resumo'];
            $registro->forceFill([
                'status' => SigoExtracao::STATUS_CONCLUIDO,
                'paginas_lidas' => (int) ($resumo['paginas_lidas'] ?? 0),
                'registros_brutos' => (int) ($resumo['registros_brutos'] ?? 0),
                'registros_unicos' => (int) ($resumo['registros_unicos'] ?? 0),
                'erro_tecnico' => null,
                'erro_usuario' => null,
                'finalizado_em' => now(),
            ])->save();

            return;
        }

        $erroTecnico = (string) ($resultado['resumo']['erro'] ?? 'Falha desconhecida na extração.');
        $registro->forceFill([
            'status' => SigoExtracao::STATUS_ERRO,
            'erro_tecnico' => $erroTecnico,
            'erro_usuario' => $this->formatarErroParaUsuario($erroTecnico),
            'paginas_lidas' => (int) ($resultado['resumo']['paginas_lidas'] ?? 0),
            'registros_brutos' => (int) ($resultado['resumo']['registros_brutos'] ?? 0),
            'registros_unicos' => (int) ($resultado['resumo']['registros_unicos'] ?? 0),
            'finalizado_em' => now(),
        ])->save();
    }

    /** @return array{ok: bool, resumo: array<string, mixed>} */
    private function executarScript(
        string $usuario,
        string $senha,
        string $dirAbsoluto,
        string $dirRelativo,
        string $uuid,
    ): array {
        File::ensureDirectoryExists($dirAbsoluto);
        $debugDir = $dirAbsoluto.'/debug';
        File::ensureDirectoryExists($debugDir);

        $comando = $this->montarComando($dirAbsoluto, $debugDir);
        $env = $this->montarAmbiente($usuario, $senha, $dirAbsoluto);

        $this->gravarDebugContexto($debugDir, $comando, $env);

        $process = $this->criarProcesso($comando, (int) config('sigo.timeout_seconds', 3600));

        try {
            $process->mustRun(null, $env);
        } catch (ProcessFailedException $e) {
            $this->gravarSaidaProcesso($debugDir, $process, $e);
            $resumo = $this->lerResumo($dirAbsoluto) ?? [
                'ok' => false,
                'erro' => $this->extrairErroProcesso($process, $e),
                'registros_unicos' => 0,
                'registros_brutos' => 0,
                'paginas_lidas' => 0,
            ];

            return ['ok' => false, 'resumo' => $resumo];
        }

        $this->gravarSaidaProcesso($debugDir, $process);
        $resumo = $this->lerResumo($dirAbsoluto) ?? $this->parseResultadoStdout($process->getOutput());
        if ($resumo === null) {
            throw new RuntimeException('Extração concluída, mas o resumo não foi encontrado.');
        }

        $resumo['uuid'] = $uuid;
        $resumo['dir'] = $dirRelativo;
        $resumo['token'] = $uuid;

        return [
            'ok' => (bool) ($resumo['ok'] ?? false),
            'resumo' => $resumo,
        ];
    }

    /** @param list<string> $comando */
    private function criarProcesso(array $comando, int $timeout): Process
    {
        if (PHP_OS_FAMILY === 'Windows' && count($comando) >= 2 && ! str_contains($comando[0], ' ')) {
            $linha = implode(' ', array_map(static fn (string $parte) => escapeshellarg($parte), $comando));

            return Process::fromShellCommandline($linha, base_path(), null, null, (float) $timeout);
        }

        return new Process($comando, base_path(), null, null, (float) $timeout);
    }

    public function caminhoArquivo(string $uuid, string $tipo, ?int $userId = null): ?string
    {
        if (! Str::isUuid($uuid)) {
            return null;
        }

        $query = SigoExtracao::query()->where('uuid', $uuid);
        if ($userId !== null) {
            $query->where('user_id', $userId);
        }
        $registro = $query->first();
        if (! $registro || $registro->diretorio_relativo === null) {
            return null;
        }

        $dir = Storage::disk('local')->path($registro->diretorio_relativo);
        $arquivo = match ($tipo) {
            'xlsx' => $dir.'/insumos_sigo_extraidos.xlsx',
            'csv' => $dir.'/insumos_sigo_extraidos.csv',
            'log' => $this->localizarLog($dir),
            'debug' => $dir.'/debug/erro.txt',
            default => null,
        };

        if ($arquivo === null || ! is_file($arquivo)) {
            return null;
        }

        $base = realpath(Storage::disk('local')->path(self::STORAGE_DIR));
        $real = realpath($arquivo);
        if ($base === false || $real === false || ! str_starts_with($real, $base)) {
            return null;
        }

        return $real;
    }

    public function formatarErroParaUsuario(?string $erro): string
    {
        $erro = trim((string) $erro);
        if ($erro === '') {
            return 'Nenhum insumo foi extraído. Verifique login, rede ou seletores do SIGO.';
        }

        if (str_contains($erro, '10106') || str_contains($erro, 'WSASYSNOTREADY')) {
            return 'Falha de rede do Windows ao iniciar o Python pelo PHP (WinError 10106). '
                .'Execute php artisan sigo:diagnostico e revise storage/.../debug/php_context.json.';
        }

        if (str_contains($erro, 'playwright.sync_api') || str_contains($erro, 'ModuleNotFoundError')) {
            return 'Playwright incompleto. Execute: python -m pip install --force-reinstall playwright openpyxl '
                .'&& python -m playwright install chromium';
        }

        if (str_contains($erro, 'Timeout') && str_contains($erro, 'wait_for')) {
            return 'Login SIGO pode ter funcionado, mas o campo de busca não apareceu. '
                .'Valide seletores/URL no F12 (Network → busca LAMPA).';
        }

        $linhas = preg_split('/\R/', $erro) ?: [$erro];
        $ultima = trim((string) end($linhas));

        return Str::limit($ultima !== '' ? $ultima : $erro, 400);
    }

    /** @return array<string, mixed> */
    public function diagnosticoCompleto(): array
    {
        $status = $this->statusDependencias();
        $env = $this->ambienteProcessoCompleto([]);

        return [
            'dependencias_ok' => ! isset($status['diagnostico']),
            'dependencias_erro' => $status['diagnostico'] ?? null,
            'python_configurado' => config('sigo.python'),
            'python_resolvido' => $status['python'] ?? null,
            'script_extracao' => config('sigo.script'),
            'script_verificacao' => $this->scriptVerificacaoDeps(),
            'php_binary' => PHP_BINARY,
            'php_sapi' => PHP_SAPI,
            'php_cwd' => getcwd(),
            'queue_connection' => config('queue.default'),
            'env_path' => $env['PATH'] ?? $env['Path'] ?? null,
            'env_systemroot' => $env['SYSTEMROOT'] ?? $env['SystemRoot'] ?? null,
            'env_windir' => $env['WINDIR'] ?? $env['Windir'] ?? null,
            'env_temp' => $env['TEMP'] ?? null,
            'env_localappdata' => $env['LOCALAPPDATA'] ?? null,
        ];
    }

    /** @return array{python: string, script: string, diagnostico?: string} */
    public function statusDependencias(): array
    {
        $script = (string) config('sigo.script', '');

        if ($script === '' || ! is_file($script)) {
            return [
                'python' => '',
                'script' => $script,
                'diagnostico' => 'Script não encontrado: scripts/extrair_insumos_sigo.py',
            ];
        }

        try {
            $python = $this->resolverPython();

            return ['python' => $python, 'script' => $script];
        } catch (RuntimeException $e) {
            return [
                'python' => (string) config('sigo.python', 'python'),
                'script' => $script,
                'diagnostico' => $e->getMessage(),
            ];
        }
    }

    /** @return list<string> */
    public function verificarDependencias(): array
    {
        $status = $this->statusDependencias();
        if (isset($status['diagnostico'])) {
            throw new RuntimeException($status['diagnostico']);
        }

        return [$status['python'], $status['script']];
    }

    private function resolverPython(): string
    {
        $configurado = trim((string) config('sigo.python', 'python'));
        if ($configurado !== '' && $configurado !== 'python' && is_file($configurado)) {
            $resultado = $this->testarPython($configurado);
            if ($resultado['ok']) {
                return $configurado;
            }

            throw new RuntimeException(
                'SIGO_PYTHON configurado, mas a verificação falhou em '.$configurado.': '.$resultado['erro']
            );
        }

        foreach (array_merge(
            [$configurado !== '' ? $configurado : null, 'python3', 'python'],
            $this->caminhosPythonWindows(),
        ) as $candidato) {
            if ($candidato === null || $candidato === '') {
                continue;
            }
            $resultado = $this->testarPython($candidato);
            if ($resultado['ok']) {
                return $candidato;
            }
        }

        throw new RuntimeException(
            'Dependências Python ausentes. Configure SIGO_PYTHON no .env com o caminho completo do python.exe.'
        );
    }

    /** @return list<string> */
    private function caminhosPythonWindows(): array
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            return [];
        }

        $caminhos = [];
        $where = Process::fromShellCommandline('where python', base_path());
        $where->run(null, $this->ambienteProcessoCompleto([]));
        if ($where->isSuccessful()) {
            foreach (preg_split('/\R/', trim($where->getOutput())) as $linha) {
                $linha = trim($linha);
                if ($linha !== '' && ! str_contains(strtolower($linha), 'windowsapps')) {
                    $caminhos[] = $linha;
                }
            }
        }

        $localApp = getenv('LOCALAPPDATA') ?: '';
        if ($localApp !== '') {
            foreach (glob($localApp.DIRECTORY_SEPARATOR.'Programs'.DIRECTORY_SEPARATOR.'Python'.DIRECTORY_SEPARATOR.'Python*'.DIRECTORY_SEPARATOR.'python.exe') ?: [] as $path) {
                $caminhos[] = $path;
            }
        }

        return array_values(array_unique($caminhos));
    }

    /** @return array{ok: bool, erro: string} */
    private function testarPython(string $python): array
    {
        $checkScript = $this->scriptVerificacaoDeps();
        if (is_file($checkScript)) {
            $comando = [$python, $checkScript];
        } else {
            $comando = [$python, '-c', self::PYTHON_CHECK];
        }

        $process = $this->criarProcesso($comando, 90);
        $process->run(null, $this->ambienteProcessoCompleto([]));

        if ($process->isSuccessful()) {
            return ['ok' => true, 'erro' => ''];
        }

        $erro = trim($process->getErrorOutput()) ?: trim($process->getOutput()) ?: 'falha ao importar playwright/openpyxl';

        return ['ok' => false, 'erro' => Str::limit($erro, 500)];
    }

    /** @return list<string> */
    private function pythonParaComando(): array
    {
        [$python] = $this->verificarDependencias();

        if (str_contains($python, ' ')) {
            return preg_split('/\s+/', $python) ?: [$python];
        }

        return [$python];
    }

    /** @return list<string> */
    private function montarComando(string $outputDir, string $debugDir): array
    {
        return array_merge($this->pythonParaComando(), [
            (string) config('sigo.script'),
            '--output-dir', $outputDir,
            '--debug-dir', $debugDir,
            '--base-url', (string) config('sigo.base_url'),
            '--login-path', (string) config('sigo.login_path'),
            '--target-path', (string) config('sigo.novo_pm_path'),
            '--headless', config('sigo.headless') ? '1' : '0',
        ]);
    }

    /** @return array<string, string> */
    private function montarAmbiente(string $usuario, string $senha, string $outputDir): array
    {
        return $this->ambienteProcessoCompleto(array_filter([
            'SIGO_USER' => $usuario,
            'SIGO_PASS' => $senha,
            'SIGO_OUTPUT_DIR' => $outputDir,
            'SIGO_BASE_URL' => (string) config('sigo.base_url'),
            'SIGO_LOGIN_PATH' => (string) config('sigo.login_path'),
            'SIGO_PM_PATH' => (string) config('sigo.novo_pm_path'),
            'SIGO_HEADLESS' => config('sigo.headless') ? '1' : '0',
            'PYTHONIOENCODING' => 'utf-8',
            'PYTHONUNBUFFERED' => '1',
        ], fn ($v) => $v !== null && $v !== ''));
    }

    /** @param array<string, string> $extra */
    /** @return array<string, string> */
    private function ambienteProcessoCompleto(array $extra): array
    {
        $env = [];

        foreach ($_SERVER as $chave => $valor) {
            if (! is_string($chave) || ! is_string($valor) || $valor === '') {
                continue;
            }
            if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $chave)) {
                $env[$chave] = $valor;
            }
        }

        foreach ($_ENV as $chave => $valor) {
            if (is_string($chave) && is_string($valor) && $valor !== '') {
                $env[$chave] = $valor;
            }
        }

        foreach (self::WINDOWS_ENV_KEYS as $chave) {
            $valor = getenv($chave);
            if (is_string($valor) && $valor !== '') {
                $env[$chave] = $valor;
            }
        }

        return array_merge($env, $extra);
    }

    /** @param list<string> $comando */
    /** @param array<string, string> $env */
    private function gravarDebugContexto(string $debugDir, array $comando, array $env): void
    {
        $sanitized = $env;
        foreach (['SIGO_PASS', 'SIGO_USER'] as $k) {
            if (isset($sanitized[$k])) {
                $sanitized[$k] = $k === 'SIGO_PASS' ? '***' : $sanitized[$k];
            }
        }

        File::put($debugDir.'/php_context.json', json_encode([
            'php_binary' => PHP_BINARY,
            'php_sapi' => PHP_SAPI,
            'cwd' => getcwd(),
            'comando' => $comando,
            'sigo_python' => config('sigo.python'),
            'env' => $sanitized,
            'timestamp' => now()->toIso8601String(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    private function gravarSaidaProcesso(string $debugDir, Process $process, ?ProcessFailedException $e = null): void
    {
        File::put($debugDir.'/stdout.log', $process->getOutput());
        File::put($debugDir.'/stderr.log', $process->getErrorOutput());
        File::put($debugDir.'/exit_code.txt', (string) $process->getExitCode());

        if ($e !== null) {
            File::put($debugDir.'/erro.txt', $this->extrairErroProcesso($process, $e));
        }
    }

    /** @return array<string, mixed>|null */
    private function lerResumo(string $dir): ?array
    {
        $path = $dir.'/extracao_sigo_resumo.json';
        if (! is_file($path)) {
            return null;
        }

        $json = json_decode((string) file_get_contents($path), true);

        return is_array($json) ? $json : null;
    }

    /** @return array<string, mixed>|null */
    private function parseResultadoStdout(string $output): ?array
    {
        if (! preg_match('/SIGO_RESULT:(\{.*\})/s', $output, $m)) {
            return null;
        }

        $json = json_decode($m[1], true);

        return is_array($json) ? $json : null;
    }

    private function extrairErroProcesso(Process $process, ProcessFailedException $e): string
    {
        $parsed = $this->parseResultadoStdout($process->getOutput());
        if (is_array($parsed) && ! empty($parsed['erro'])) {
            return (string) $parsed['erro'];
        }

        $stderr = trim($process->getErrorOutput());
        if ($stderr !== '') {
            return $stderr;
        }

        return $e->getMessage();
    }

    private function localizarLog(string $dir): ?string
    {
        $logs = glob($dir.'/extracao_sigo_*.log') ?: [];

        return $logs[0] ?? null;
    }
}

<?php

namespace App\Support\Almoxarifado;

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

    private function scriptVerificacaoDeps(): string
    {
        return (string) config('sigo.check_script', base_path('scripts/sigo_check_deps.py'));
    }

    /** @return array{ok: bool, token: string, resumo: array<string, mixed>} */
    public function extrair(string $usuario, string $senha): array
    {
        $this->verificarDependencias();

        $token = now()->format('Ymd_His').'_'.Str::lower(Str::random(8));
        $dirRelativo = self::STORAGE_DIR.'/'.$token;
        $dirAbsoluto = Storage::disk('local')->path($dirRelativo);
        File::ensureDirectoryExists($dirAbsoluto);

        $process = new Process(
            $this->montarComando($usuario, $senha, $dirAbsoluto),
            base_path(),
            null,
            null,
            (int) config('sigo.timeout_seconds', 3600),
        );

        $env = $this->montarAmbiente($usuario, $senha, $dirAbsoluto);

        try {
            $process->mustRun(function (string $type, string $buffer): void {
                if ($type === Process::ERR) {
                    return;
                }
            }, $env);
        } catch (ProcessFailedException $e) {
            $resumo = $this->lerResumo($dirAbsoluto) ?? [
                'ok' => false,
                'erro' => $this->extrairErroProcesso($process, $e),
                'registros_unicos' => 0,
                'registros_brutos' => 0,
                'paginas_lidas' => 0,
            ];

            return [
                'ok' => false,
                'token' => $token,
                'resumo' => $resumo,
            ];
        }

        $resumo = $this->lerResumo($dirAbsoluto) ?? $this->parseResultadoStdout($process->getOutput());
        if ($resumo === null) {
            throw new RuntimeException('Extração concluída, mas o resumo não foi encontrado.');
        }

        $resumo['token'] = $token;
        $resumo['dir'] = $dirRelativo;

        return [
            'ok' => (bool) ($resumo['ok'] ?? false),
            'token' => $token,
            'resumo' => $resumo,
        ];
    }

    public function caminhoArquivo(string $token, string $tipo): ?string
    {
        if (! preg_match('/^[a-zA-Z0-9_\-]+$/', $token)) {
            return null;
        }

        $dir = Storage::disk('local')->path(self::STORAGE_DIR.'/'.$token);
        $arquivo = match ($tipo) {
            'xlsx' => $dir.'/insumos_sigo_extraidos.xlsx',
            'csv' => $dir.'/insumos_sigo_extraidos.csv',
            'log' => $this->localizarLog($dir),
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

        if (str_contains($erro, 'playwright.sync_api') || str_contains($erro, 'ModuleNotFoundError')) {
            return 'Playwright instalado de forma incompleta. No PowerShell, na pasta do projeto, execute: '
                .'python -m pip install --force-reinstall playwright openpyxl && python -m playwright install chromium. '
                .'Reinicie o Laravel e tente novamente.';
        }

        if (str_contains($erro, 'Timeout') && str_contains($erro, 'wait_for')) {
            return 'Login no SIGO pode ter funcionado, mas a tela Novo PM não carregou o campo de busca de insumos. '
                .'Confirme usuário/senha, URL do SIGO e se a rede alcança sigo.omegaservice.com.br.';
        }

        $linhas = preg_split('/\R/', $erro) ?: [$erro];
        $ultima = trim((string) end($linhas));

        return Str::limit($ultima !== '' ? $ultima : $erro, 400);
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
                .'. Execute: '.$configurado.' -m pip install --force-reinstall playwright openpyxl && '
                .$configurado.' -m playwright install chromium'
            );
        }

        $candidatos = array_values(array_unique(array_filter([
            $configurado !== '' ? $configurado : null,
            PHP_OS_FAMILY === 'Windows' ? 'py -3' : null,
            'python3',
            'python',
        ])));

        $falhas = [];
        foreach ($candidatos as $candidato) {
            $resultado = $this->testarPython($candidato);
            if ($resultado['ok']) {
                return $candidato;
            }
            $falhas[] = $candidato.': '.$resultado['erro'];
        }

        foreach ($this->caminhosPythonWindows() as $caminho) {
            if (in_array($caminho, $candidatos, true)) {
                continue;
            }
            $resultado = $this->testarPython($caminho);
            if ($resultado['ok']) {
                return $caminho;
            }
            $falhas[] = $caminho.': '.$resultado['erro'];
        }

        throw new RuntimeException(
            'Dependências Python ausentes ou Python não encontrado pelo PHP. '
            .'Configure SIGO_PYTHON no .env com o caminho completo do python.exe. '
            .'Detalhes: '.implode(' | ', array_slice($falhas, 0, 3))
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
        $where->run();
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
            if (str_contains($python, ' ')) {
                $process = Process::fromShellCommandline(
                    $python.' '.escapeshellarg($checkScript),
                    base_path(),
                    null,
                    null,
                    90,
                );
            } else {
                $process = new Process(
                    [$python, $checkScript],
                    base_path(),
                    null,
                    null,
                    90,
                );
            }
        } elseif (str_contains($python, ' ')) {
            $process = Process::fromShellCommandline(
                $python.' -c "'.self::PYTHON_CHECK.'"',
                base_path(),
                null,
                null,
                90,
            );
        } else {
            $process = new Process(
                [$python, '-c', self::PYTHON_CHECK],
                base_path(),
                null,
                null,
                90,
            );
        }

        $process->run();

        if ($process->isSuccessful()) {
            return ['ok' => true, 'erro' => ''];
        }

        $erro = trim($process->getErrorOutput()) ?: trim($process->getOutput()) ?: 'falha ao importar playwright/openpyxl';

        return ['ok' => false, 'erro' => Str::limit($erro, 180)];
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
    private function montarComando(string $usuario, string $senha, string $outputDir): array
    {
        return array_merge($this->pythonParaComando(), [
            (string) config('sigo.script'),
            '--output-dir', $outputDir,
            '--base-url', (string) config('sigo.base_url'),
            '--login-path', (string) config('sigo.login_path'),
            '--target-path', (string) config('sigo.novo_pm_path'),
            '--headless', config('sigo.headless') ? '1' : '0',
        ]);
    }

    /** @return array<string, string> */
    private function montarAmbiente(string $usuario, string $senha, string $outputDir): array
    {
        return array_filter([
            'SIGO_USER' => $usuario,
            'SIGO_PASS' => $senha,
            'SIGO_OUTPUT_DIR' => $outputDir,
            'SIGO_BASE_URL' => (string) config('sigo.base_url'),
            'SIGO_LOGIN_PATH' => (string) config('sigo.login_path'),
            'SIGO_PM_PATH' => (string) config('sigo.novo_pm_path'),
            'SIGO_HEADLESS' => config('sigo.headless') ? '1' : '0',
            'PYTHONIOENCODING' => 'utf-8',
        ], fn ($v) => $v !== null && $v !== '');
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
            return Str::limit($stderr, 500);
        }

        return Str::limit($e->getMessage(), 500);
    }

    private function localizarLog(string $dir): ?string
    {
        $logs = glob($dir.'/extracao_sigo_*.log') ?: [];

        return $logs[0] ?? null;
    }
}

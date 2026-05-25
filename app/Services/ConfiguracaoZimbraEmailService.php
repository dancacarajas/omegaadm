<?php

namespace App\Services;

use App\Mail\LayoutHtmlMail;
use App\Models\SistemaConfiguracaoEmail;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * SMTP Zimbra separado do mailer central do sistema (zimbra_jarbas).
 * Usado apenas para envios que devem sair como jarbas.alves@omegaservice.com.br.
 */
final class ConfiguracaoZimbraEmailService
{
    public const MAILER = 'zimbra_jarbas';

    public const HOST_PADRAO = 'smtp.omegaservice.com.br';

    public function aplicarConfiguracaoRuntime(?SistemaConfiguracaoEmail $registro = null): void
    {
        $registro ??= $this->registroSeExistir();

        $host = $registro?->zimbra_host;
        $username = $registro?->zimbra_username;
        $password = $registro?->zimbra_password;
        $port = $registro?->zimbra_port;
        $encryption = $registro?->zimbra_encryption;
        $fromAddress = $registro?->zimbra_from_address;
        $fromName = $registro?->zimbra_from_name;

        if ($registro === null || ! filled($host)) {
            $host = config('mail.mailers.'.self::MAILER.'.host');
            $port = config('mail.mailers.'.self::MAILER.'.port', 587);
            $encryption = config('mail.mailers.'.self::MAILER.'.encryption', 'tls');
            $username = config('mail.mailers.'.self::MAILER.'.username');
            $password = config('mail.mailers.'.self::MAILER.'.password');
            $fromAddress = config('mail.beneficio_adesao_matriz.zimbra_from_address');
            $fromName = config('mail.beneficio_adesao_matriz.zimbra_from_name');
        }

        $scheme = match ($encryption) {
            'ssl' => 'smtps',
            'tls' => 'smtp',
            default => null,
        };

        Config::set('mail.mailers.'.self::MAILER.'.host', $host ?? self::HOST_PADRAO);
        Config::set('mail.mailers.'.self::MAILER.'.port', (int) ($port ?: 587));
        Config::set('mail.mailers.'.self::MAILER.'.username', $username);
        Config::set('mail.mailers.'.self::MAILER.'.password', $password);
        Config::set('mail.mailers.'.self::MAILER.'.scheme', $scheme);
        Config::set('mail.mailers.'.self::MAILER.'.encryption', in_array($encryption, ['tls', 'ssl'], true) ? $encryption : null);

        if (filled($fromAddress)) {
            Config::set('mail.beneficio_adesao_matriz.zimbra_from_address', $fromAddress);
        }
        if (filled($fromName)) {
            Config::set('mail.beneficio_adesao_matriz.zimbra_from_name', $fromName);
        }

    }

    public function registroSeExistir(): ?SistemaConfiguracaoEmail
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('sistema_configuracao_email')) {
            return null;
        }

        if (! \Illuminate\Support\Facades\Schema::hasColumn('sistema_configuracao_email', 'zimbra_host')) {
            return null;
        }

        return SistemaConfiguracaoEmail::query()->find(1);
    }

    public function configurado(): bool
    {
        $this->aplicarConfiguracaoRuntime();

        return filled(config('mail.mailers.'.self::MAILER.'.host'))
            && filled(config('mail.mailers.'.self::MAILER.'.username'))
            && filled(config('mail.mailers.'.self::MAILER.'.password'));
    }

    /** @return array<string, mixed> */
    public function dadosParaFormulario(): array
    {
        $registro = SistemaConfiguracaoEmail::registro();

        return [
            'zimbra_host' => $registro->zimbra_host ?? self::HOST_PADRAO,
            'zimbra_port' => $registro->zimbra_port ?: 587,
            'zimbra_encryption' => $registro->zimbra_encryption ?? 'tls',
            'zimbra_username' => $registro->zimbra_username ?? 'jarbas.alves@omegaservice.com.br',
            'zimbra_from_name' => $registro->zimbra_from_name ?? 'Jarbas Alves de Carvalho e Silva',
            'zimbra_from_address' => $registro->zimbra_from_address ?? 'jarbas.alves@omegaservice.com.br',
            'beneficio_adesao_copia_email' => $registro->beneficio_adesao_copia_email
                ?? config('mail.beneficio_adesao_matriz.copia_sistema', 'jarbas.alves@omegaservice.com.br'),
            'zimbra_senha_configurada' => $registro->senhaZimbraConfigurada(),
            'zimbra_ultima_atualizacao' => $registro->zimbra_updated_at,
        ];
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    public function salvar(array $dados, ?int $usuarioId = null): SistemaConfiguracaoEmail
    {
        $registro = SistemaConfiguracaoEmail::registro();

        $payload = [
            'zimbra_host' => $dados['zimbra_host'] ?? null,
            'zimbra_port' => (int) ($dados['zimbra_port'] ?? 587),
            'zimbra_encryption' => filled($dados['zimbra_encryption'] ?? null) ? $dados['zimbra_encryption'] : null,
            'zimbra_username' => $dados['zimbra_username'] ?? null,
            'zimbra_from_name' => $dados['zimbra_from_name'] ?? null,
            'zimbra_from_address' => $dados['zimbra_from_address'] ?? null,
            'beneficio_adesao_copia_email' => $dados['beneficio_adesao_copia_email'] ?? null,
            'zimbra_updated_at' => now(),
            'updated_by_id' => $usuarioId,
        ];

        if (filled($dados['zimbra_password'] ?? null)) {
            $payload['zimbra_password'] = $dados['zimbra_password'];
        }

        $registro->update($payload);
        $registro = $registro->fresh();

        $this->aplicarConfiguracaoRuntime($registro);

        return $registro;
    }

    public function enviarTeste(string $destinatario): void
    {
        $this->aplicarConfiguracaoRuntime();

        $fromAddress = (string) config('mail.beneficio_adesao_matriz.zimbra_from_address');
        $fromName = (string) config('mail.beneficio_adesao_matriz.zimbra_from_name');

        try {
            Mail::mailer(self::MAILER)
                ->to($destinatario)
                ->send(new LayoutHtmlMail(
                    '<p style="font-family:Arial,sans-serif;font-size:14px;">Teste de envio pelo SMTP Zimbra (mailer <strong>zimbra_jarbas</strong>) configurado no sistema.</p>',
                    'Teste SMTP Zimbra — '.config('app.name'),
                    [],
                    $fromAddress,
                    $fromName,
                ));
        } catch (\Throwable $e) {
            $this->registrarErroSmtp($e, $destinatario);

            throw $e;
        }
    }

    /**
     * Teste TCP simples (equivalente a nc -vz host porta) a partir do servidor PHP.
     *
     * @return array{ok: bool, host: string, port: int, errno: int, message: string, latency_ms: int|null}
     */
    public function testarConexaoTcp(?string $host = null, ?int $port = null, int $timeoutSegundos = 10): array
    {
        $this->aplicarConfiguracaoRuntime();

        $host = $host ?? (string) config('mail.mailers.'.self::MAILER.'.host', self::HOST_PADRAO);
        $port = $port ?? (int) config('mail.mailers.'.self::MAILER.'.port', 587);

        $inicio = microtime(true);
        $errno = 0;
        $errstr = '';
        $socket = @fsockopen($host, $port, $errno, $errstr, $timeoutSegundos);
        $latencyMs = (int) round((microtime(true) - $inicio) * 1000);

        if (is_resource($socket)) {
            fclose($socket);

            return [
                'ok' => true,
                'host' => $host,
                'port' => $port,
                'errno' => 0,
                'message' => "Conexão TCP estabelecida em {$latencyMs}ms.",
                'latency_ms' => $latencyMs,
            ];
        }

        return [
            'ok' => false,
            'host' => $host,
            'port' => $port,
            'errno' => $errno,
            'message' => $errstr !== '' ? $errstr : 'Falha ao abrir socket TCP.',
            'latency_ms' => $latencyMs,
        ];
    }

    /** @return array<string, mixed> */
    public function contextoMailerParaLog(): array
    {
        $this->aplicarConfiguracaoRuntime();

        return [
            'mailer' => self::MAILER,
            'host' => config('mail.mailers.'.self::MAILER.'.host'),
            'port' => config('mail.mailers.'.self::MAILER.'.port'),
            'encryption' => config('mail.mailers.'.self::MAILER.'.encryption'),
            'scheme' => config('mail.mailers.'.self::MAILER.'.scheme'),
            'username' => config('mail.mailers.'.self::MAILER.'.username'),
        ];
    }

    public function registrarErroSmtp(\Throwable $e, ?string $destinatario = null): void
    {
        Log::error('Erro técnico SMTP Zimbra', array_merge($this->contextoMailerParaLog(), [
            'destinatario' => $destinatario,
            'error_class' => $e::class,
            'error_message' => $e->getMessage(),
            'error_technical' => $this->extrairMensagemTecnica($e),
            'error_previous' => $e->getPrevious()?->getMessage(),
        ]));
    }

    public function mensagemErroAmigavel(\Throwable $e): string
    {
        return $this->mensagemErroParaUsuario($e);
    }

    public function mensagemErroParaUsuario(\Throwable $e): string
    {
        $this->aplicarConfiguracaoRuntime();

        $host = (string) config('mail.mailers.'.self::MAILER.'.host', self::HOST_PADRAO);
        $port = (int) config('mail.mailers.'.self::MAILER.'.port', 587);
        $encryption = (string) config('mail.mailers.'.self::MAILER.'.encryption', 'tls');
        $tecnico = $this->extrairMensagemTecnica($e);
        $dica = $this->dicaContextual($tecnico);

        $linhas = [
            "Falha no SMTP Zimbra ({$host}:{$port}, {$encryption} = STARTTLS no Outlook).",
            '',
            'Erro técnico: '.$tecnico,
        ];

        if ($dica !== null) {
            $linhas[] = '';
            $linhas[] = $dica;
        }

        return implode("\n", $linhas);
    }

    public function extrairMensagemTecnica(\Throwable $e): string
    {
        $partes = [];
        $atual = $e;

        while ($atual !== null && count($partes) < 4) {
            $msg = trim($atual->getMessage());
            if ($msg !== '' && ! in_array($msg, $partes, true)) {
                $partes[] = $msg;
            }
            $atual = $atual->getPrevious();
        }

        if ($partes !== []) {
            return implode(' | ', $partes);
        }

        return $e::class;
    }

    private function dicaContextual(string $tecnico): ?string
    {
        $t = strtolower($tecnico);

        if (str_contains($t, '535')
            || str_contains($t, 'authentication failed')
            || str_contains($t, 'auth')
            || str_contains($t, 'credentials')) {
            return 'Dica: use a senha de aplicativo Zimbra (conta com 2FA), não a senha de login. Usuário e remetente devem ser jarbas.alves@omegaservice.com.br.';
        }

        if (str_contains($t, '553')) {
            return 'Dica: o remetente deve ser igual ao usuário autenticado no SMTP.';
        }

        if (str_contains($t, 'ssl')
            || str_contains($t, 'starttls')
            || str_contains($t, 'tls')) {
            return 'Dica: no sistema, TLS na porta 587 equivale ao STARTTLS do Outlook.';
        }

        if (str_contains($t, 'timed out')
            || str_contains($t, 'could not be established')
            || str_contains($t, 'connection refused')
            || str_contains($t, 'unable to connect')
            || str_contains($t, 'getaddrinfo')
            || str_contains($t, 'network is unreachable')) {
            return 'Dica: teste a rede do servidor de hospedagem com: php artisan mail:diagnostico-zimbra-smtp — se o TCP falhar, a Hostinger pode estar bloqueando a porta 587 (firewall), não é senha nem Outlook.';
        }

        return null;
    }
}

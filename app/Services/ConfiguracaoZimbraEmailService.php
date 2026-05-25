<?php

namespace App\Services;

use App\Mail\LayoutHtmlMail;
use App\Models\SistemaConfiguracaoEmail;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;

/**
 * SMTP Zimbra separado do mailer central do sistema (zimbra_jarbas).
 * Usado apenas para envios que devem sair como jarbas.alves@omegaservice.com.br.
 */
final class ConfiguracaoZimbraEmailService
{
    public const MAILER = 'zimbra_jarbas';

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

        Config::set('mail.mailers.'.self::MAILER.'.host', $host ?? 'mail.omegaservice.com.br');
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

        if ($registro !== null && filled($registro->beneficio_adesao_copia_email)) {
            Config::set('mail.beneficio_adesao_matriz.copia_sistema', $registro->beneficio_adesao_copia_email);
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
            'zimbra_host' => $registro->zimbra_host ?? 'mail.omegaservice.com.br',
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

        Mail::mailer(self::MAILER)
            ->to($destinatario)
            ->send(new LayoutHtmlMail(
                '<p style="font-family:Arial,sans-serif;font-size:14px;">Teste de envio pelo SMTP Zimbra (mailer <strong>zimbra_jarbas</strong>) configurado no sistema.</p>',
                'Teste SMTP Zimbra — '.config('app.name'),
                [],
                $fromAddress,
                $fromName,
            ));
    }

    public function mensagemErroAmigavel(\Throwable $e): string
    {
        $raw = $e->getMessage();

        if (str_contains($raw, '535') || str_contains($raw, 'Authentication')) {
            return 'O Zimbra recusou usuário ou senha. Use a senha de aplicativo (não a senha de login) criada nas preferências da conta com 2FA ativo.';
        }

        if (str_contains($raw, 'Connection could not be established') || str_contains($raw, 'Connection timed out')) {
            return 'Não foi possível conectar ao SMTP Zimbra. Teste hosts: mail.omegaservice.com.br, webmail.omegaservice.com.br ou smtp.omegaservice.com.br na porta 587 com TLS.';
        }

        if (str_contains($raw, '553')) {
            return 'O Zimbra bloqueou o remetente. O e-mail remetente deve ser o mesmo do usuário SMTP autenticado (jarbas.alves@omegaservice.com.br).';
        }

        return 'Falha no envio Zimbra: '.$raw;
    }
}

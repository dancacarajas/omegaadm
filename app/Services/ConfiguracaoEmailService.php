<?php

namespace App\Services;

use App\Mail\LayoutHtmlMail;
use App\Models\SistemaConfiguracaoEmail;
use App\Support\EmailLayout;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

final class ConfiguracaoEmailService
{
    /** @return array<string, string> */
    public static function mailersDisponiveis(): array
    {
        return [
            'smtp' => 'SMTP',
            'log' => 'Log (desenvolvimento)',
            'array' => 'Array (teste em memória)',
        ];
    }

    /** @return array<string, string> */
    public static function criptografiasDisponiveis(): array
    {
        return [
            '' => 'Nenhuma',
            'tls' => 'TLS',
            'ssl' => 'SSL',
        ];
    }

    public function aplicarConfiguracaoRuntime(?SistemaConfiguracaoEmail $registro = null): void
    {
        $registro ??= $this->registroSeExistir();

        if ($registro === null) {
            return;
        }

        $mailer = $registro->mail_mailer ?: 'smtp';
        Config::set('mail.default', $mailer);

        if ($mailer === 'smtp') {
            $scheme = match ($registro->mail_encryption) {
                'ssl' => 'smtps',
                'tls' => 'smtp',
                default => null,
            };

            Config::set('mail.mailers.smtp.host', $registro->mail_host ?? '127.0.0.1');
            Config::set('mail.mailers.smtp.port', $registro->mail_port ?: 587);
            Config::set('mail.mailers.smtp.username', $registro->mail_username);
            Config::set('mail.mailers.smtp.password', $registro->mail_password);
            Config::set('mail.mailers.smtp.scheme', $scheme);
        }

        Config::set('mail.from.address', $registro->mail_from_address ?? config('mail.from.address'));
        Config::set('mail.from.name', $registro->mail_from_name ?? config('mail.from.name'));
    }

    public function registroSeExistir(): ?SistemaConfiguracaoEmail
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('sistema_configuracao_email')) {
            return null;
        }

        return SistemaConfiguracaoEmail::query()->find(1);
    }

    /** @return array<string, mixed> */
    public function dadosParaFormulario(): array
    {
        $registro = SistemaConfiguracaoEmail::registro()->loadMissing('updatedBy');

        return [
            'registro' => $registro,
            'mail_mailer' => $registro->mail_mailer,
            'mail_encryption' => $registro->mail_encryption ?? '',
            'mail_host' => $registro->mail_host,
            'mail_port' => $registro->mail_port,
            'mail_username' => $registro->mail_username,
            'mail_from_name' => $registro->mail_from_name,
            'mail_from_address' => $registro->mail_from_address,
            'senha_configurada' => $registro->senhaConfigurada(),
            'ultima_atualizacao' => $registro->updated_at,
            'atualizado_por' => $registro->updatedBy?->name,
        ];
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    public function salvar(array $dados, ?int $usuarioId = null): SistemaConfiguracaoEmail
    {
        $registro = SistemaConfiguracaoEmail::registro();

        $payload = [
            'mail_mailer' => $dados['mail_mailer'],
            'mail_encryption' => filled($dados['mail_encryption'] ?? null) ? $dados['mail_encryption'] : null,
            'mail_host' => $dados['mail_host'] ?? null,
            'mail_port' => (int) ($dados['mail_port'] ?? 587),
            'mail_username' => $dados['mail_username'] ?? null,
            'mail_from_name' => $dados['mail_from_name'] ?? null,
            'mail_from_address' => $dados['mail_from_address'] ?? null,
            'updated_by_id' => $usuarioId,
        ];

        if (filled($dados['mail_password'] ?? null)) {
            $payload['mail_password'] = $dados['mail_password'];
        }

        $registro->update($payload);
        $registro = $registro->fresh();

        $this->sincronizarEnv($registro);
        $this->aplicarConfiguracaoRuntime($registro);

        return $registro;
    }

    public function mensagemErroAmigavel(\Throwable $e): string
    {
        $raw = $e->getMessage();

        if (str_contains($raw, '535') || str_contains($raw, 'BadCredentials') || str_contains($raw, 'Username and Password not accepted')) {
            $gmail = str_contains(strtolower($raw), 'gsmtp') || str_contains(strtolower($raw), 'gmail');

            if ($gmail) {
                return 'O Gmail recusou usuário ou senha (erro 535). Não use a senha normal da conta: '
                    .'crie uma senha de app de 16 caracteres em '
                    .'https://myaccount.google.com/apppasswords (exige verificação em duas etapas ativa), '
                    .'cole no campo Senha SMTP, salve a configuração e teste de novo. '
                    .'Com Gmail, o e-mail remetente deve ser o mesmo do usuário SMTP (286omega@gmail.com) '
                    .'ou um alias autorizado em "Enviar e-mail como" no Gmail.';
            }

            return 'O servidor SMTP recusou usuário ou senha. Confira usuário, senha e se o provedor exige senha de aplicativo.';
        }

        if (str_contains($raw, 'Connection could not be established') || str_contains($raw, 'Connection timed out')) {
            return 'Não foi possível conectar ao servidor SMTP. Verifique host, porta, firewall e criptografia (TLS na porta 587).';
        }

        return 'Falha ao enviar: '.$raw;
    }

    public function enviarTeste(string $destinatario): void
    {
        $this->aplicarConfiguracaoRuntime();

        $html = EmailLayout::render('emails.teste-envio', [
            'enviadoEm' => now()->format('d/m/Y H:i:s'),
        ]);

        Mail::to($destinatario)->send(new LayoutHtmlMail(
            $html,
            'Teste de e-mail — '.config('app.name')
        ));
    }

    private function sincronizarEnv(SistemaConfiguracaoEmail $registro): void
    {
        $envPath = base_path('.env');

        if (! is_file($envPath)) {
            return;
        }

        $scheme = match ($registro->mail_encryption) {
            'ssl' => 'smtps',
            'tls' => 'smtp',
            default => null,
        };

        $vars = [
            'MAIL_MAILER' => $registro->mail_mailer,
            'MAIL_SCHEME' => $scheme ?? 'null',
            'MAIL_HOST' => $registro->mail_host ?? '',
            'MAIL_PORT' => (string) ($registro->mail_port ?: 587),
            'MAIL_USERNAME' => $registro->mail_username ?? '',
            'MAIL_FROM_ADDRESS' => $registro->mail_from_address ?? '',
            'MAIL_FROM_NAME' => $registro->mail_from_name ?? config('app.name'),
        ];

        if ($registro->senhaConfigurada()) {
            $vars['MAIL_PASSWORD'] = $registro->mail_password;
        }

        $content = file_get_contents($envPath);

        foreach ($vars as $key => $value) {
            $line = $key.'='.$this->quoteEnv($value);
            if (preg_match('/^'.preg_quote($key, '/').'=/m', $content)) {
                $content = preg_replace('/^'.preg_quote($key, '/').'=.*/m', $line, $content);
            } else {
                $content .= PHP_EOL.$line;
            }
        }

        file_put_contents($envPath, $content);
    }

    private function quoteEnv(string $value): string
    {
        if ($value === '' || Str::contains($value, [' ', '#', '"', "'"])) {
            return '"'.str_replace('"', '\"', $value).'"';
        }

        return $value;
    }
}

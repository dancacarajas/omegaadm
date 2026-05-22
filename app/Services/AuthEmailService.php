<?php

namespace App\Services;

use App\Mail\LayoutHtmlMail;
use App\Models\SistemaConfiguracaoEmail;
use App\Models\User;
use App\Support\EmailLayout;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

final class AuthEmailService
{
    /** @return array<string, string> slug => label */
    public static function tiposPreview(): array
    {
        return [
            'usuario-cadastrado' => 'Usuário cadastrado',
            'recuperacao-senha' => 'Recuperação de senha',
            'senha-redefinida' => 'Senha redefinida',
            'senha-alterada-admin' => 'Senha alterada pelo administrador',
        ];
    }

    public static function viewParaTipo(string $tipo): string
    {
        return match ($tipo) {
            'usuario-cadastrado' => 'emails.auth.usuario-cadastrado',
            'recuperacao-senha' => 'emails.auth.recuperacao-senha',
            'senha-redefinida' => 'emails.auth.senha-redefinida',
            'senha-alterada-admin' => 'emails.auth.senha-alterada-admin',
            default => throw new \InvalidArgumentException("Tipo de e-mail inválido: {$tipo}"),
        };
    }

    /** @return array<string, mixed> */
    public static function dadosPreview(string $tipo): array
    {
        $usuario = new User([
            'name' => 'Jarbas Alves',
            'email' => 'jarbas.alves@exemplo.com.br',
            'cargo' => 'Analista de RH',
        ]);
        $usuario->id = 1;

        $loginUrl = route('login');
        $tokenExemplo = Str::random(64);

        return match ($tipo) {
            'usuario-cadastrado' => [
                'usuario' => $usuario,
                'senhaTemporaria' => 'Omega@2026',
                'loginUrl' => $loginUrl,
                'cadastradoPor' => 'Administrador do Sistema',
            ],
            'recuperacao-senha' => [
                'usuario' => $usuario,
                'resetUrl' => route('password.reset', ['token' => $tokenExemplo, 'email' => $usuario->email]),
                'expiraEmMinutos' => (int) config('auth.passwords.users.expire', 60),
            ],
            'senha-redefinida' => [
                'usuario' => $usuario,
                'loginUrl' => $loginUrl,
                'redefinidoEm' => now()->format('d/m/Y H:i'),
            ],
            'senha-alterada-admin' => [
                'usuario' => $usuario,
                'loginUrl' => $loginUrl,
                'alteradoPor' => 'Maria Coordenadora',
                'alteradoEm' => now()->format('d/m/Y H:i'),
            ],
            default => throw new \InvalidArgumentException("Tipo de e-mail inválido: {$tipo}"),
        };
    }

    public function renderPreview(string $tipo): string
    {
        return EmailLayout::render(self::viewParaTipo($tipo), array_merge(
            self::dadosPreview($tipo),
            ['preview' => true]
        ));
    }

    public function podeEnviar(): bool
    {
        if (! config('mail.auth_emails_enabled', true)) {
            return false;
        }

        app(ConfiguracaoEmailService::class)->aplicarConfiguracaoRuntime();

        $mailer = config('mail.default');

        if (in_array($mailer, ['log', 'array'], true)) {
            return false;
        }

        if ($mailer !== 'smtp') {
            return true;
        }

        $password = config('mail.mailers.smtp.password');
        if (filled($password)) {
            return true;
        }

        $registro = SistemaConfiguracaoEmail::query()->find(1);

        return $registro?->senhaConfigurada() ?? false;
    }

    public function enviarUsuarioCadastrado(User $usuario, string $senhaPlana, ?string $cadastradoPor = null): bool
    {
        return $this->enviar($usuario->email, 'Acesso ao '.config('mail.brand_name', 'Omega Adm CT 286'), 'emails.auth.usuario-cadastrado', [
            'usuario' => $usuario,
            'senhaTemporaria' => $senhaPlana,
            'loginUrl' => $this->urlLogin(),
            'cadastradoPor' => $cadastradoPor,
        ]);
    }

    public function enviarRecuperacaoSenha(User $usuario, string $token): bool
    {
        return $this->enviar($usuario->email, 'Redefinição de senha — '.config('mail.brand_name', 'Omega Adm CT 286'), 'emails.auth.recuperacao-senha', [
            'usuario' => $usuario,
            'resetUrl' => $this->urlRedefinirSenha($token, $usuario->email),
            'expiraEmMinutos' => (int) config('auth.passwords.users.expire', 60),
        ]);
    }

    public function enviarSenhaRedefinida(User $usuario): bool
    {
        return $this->enviar($usuario->email, 'Senha alterada com sucesso', 'emails.auth.senha-redefinida', [
            'usuario' => $usuario,
            'loginUrl' => $this->urlLogin(),
            'redefinidoEm' => now()->format('d/m/Y H:i'),
        ]);
    }

    public function enviarSenhaAlteradaAdmin(User $usuario, ?string $alteradoPor = null): bool
    {
        return $this->enviar($usuario->email, 'Sua senha foi atualizada', 'emails.auth.senha-alterada-admin', [
            'usuario' => $usuario,
            'loginUrl' => $this->urlLogin(),
            'alteradoPor' => $alteradoPor,
            'alteradoEm' => now()->format('d/m/Y H:i'),
        ]);
    }

    private function urlLogin(): string
    {
        return $this->urlAbsoluta(route('login', [], false));
    }

    private function urlRedefinirSenha(string $token, string $email): string
    {
        return $this->urlAbsoluta(route('password.reset', [
            'token' => $token,
            'email' => $email,
        ], false));
    }

    private function urlAbsoluta(string $path): string
    {
        $root = rtrim((string) config('app.url'), '/');

        return $root.'/'.ltrim($path, '/');
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    private function enviar(string $destinatario, string $assunto, string $view, array $dados): bool
    {
        if (! $this->podeEnviar()) {
            Log::warning('E-mail de autenticação não enviado: SMTP não configurado ou mailer em modo log/array.', [
                'view' => $view,
                'destinatario' => $destinatario,
                'mailer' => config('mail.default'),
            ]);

            return false;
        }

        try {
            $rootUrl = rtrim((string) config('app.url'), '/');
            if ($rootUrl !== '') {
                URL::forceRootUrl($rootUrl);
            }

            app(ConfiguracaoEmailService::class)->aplicarConfiguracaoRuntime();

            $html = EmailLayout::render($view, $dados);

            Mail::to($destinatario)->send(new LayoutHtmlMail($html, $assunto));

            Log::info('E-mail de autenticação enviado.', [
                'view' => $view,
                'destinatario' => $destinatario,
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::error('Falha ao enviar e-mail de autenticação.', [
                'view' => $view,
                'destinatario' => $destinatario,
                'erro' => $e->getMessage(),
            ]);

            return false;
        }
    }
}

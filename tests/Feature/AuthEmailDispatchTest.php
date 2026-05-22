<?php

namespace Tests\Feature;

use App\Mail\LayoutHtmlMail;
use App\Models\SistemaConfiguracaoEmail;
use App\Models\User;
use App\Services\AuthEmailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AuthEmailDispatchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configurarSmtpParaTestes();
    }

    private function configurarSmtpParaTestes(): void
    {
        SistemaConfiguracaoEmail::query()->updateOrCreate(
            ['id' => 1],
            [
                'mail_mailer' => 'smtp',
                'mail_encryption' => 'tls',
                'mail_host' => 'smtp.test.local',
                'mail_port' => 587,
                'mail_username' => 'user@test.local',
                'mail_password' => 'senha-teste',
                'mail_from_name' => 'Omega Teste',
                'mail_from_address' => 'noreply@test.local',
            ]
        );

        app(\App\Services\ConfiguracaoEmailService::class)->aplicarConfiguracaoRuntime();

        $this->assertTrue(
            app(AuthEmailService::class)->podeEnviar(),
            'SMTP de teste não ficou disponível (mailer='.config('mail.default').')'
        );
    }

    public function test_cadastro_usuario_dispara_email(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['status' => 'ativo']);

        $this->actingAs($admin)->post(route('usuarios.store'), [
            'name' => 'Novo Operador',
            'email' => 'novo@example.test',
            'status' => 'ativo',
            'password' => 'senha123',
            'password_confirmation' => 'senha123',
            'todos_contratos' => '1',
        ])->assertRedirect();

        Mail::assertSent(LayoutHtmlMail::class, 1);
    }

    public function test_recuperacao_senha_dispara_email(): void
    {
        Mail::fake();

        User::factory()->create([
            'email' => 'recupera@example.test',
            'status' => 'ativo',
        ]);

        $this->post(route('password.email'), [
            'email' => 'recupera@example.test',
        ])->assertRedirect();

        Mail::assertSent(LayoutHtmlMail::class, 1);
    }

    public function test_redefinir_senha_dispara_confirmacao(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'confirma@example.test',
            'status' => 'ativo',
        ]);

        $token = Password::broker()->createToken($user);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'novaSenha1',
            'password_confirmation' => 'novaSenha1',
        ])->assertRedirect(route('login'));

        Mail::assertSent(LayoutHtmlMail::class, 1);
        $this->assertTrue(Hash::check('novaSenha1', $user->fresh()->password));
    }

    public function test_alteracao_senha_admin_dispara_email(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['status' => 'ativo']);
        $usuario = User::factory()->create(['status' => 'ativo']);

        $this->actingAs($admin)->put(route('usuarios.update', $usuario), [
            'name' => $usuario->name,
            'email' => $usuario->email,
            'status' => 'ativo',
            'password' => 'outraSenha9',
            'password_confirmation' => 'outraSenha9',
            'todos_contratos' => '1',
        ])->assertRedirect();

        Mail::assertSent(LayoutHtmlMail::class, 1);
    }

    public function test_nao_envia_sem_smtp_configurado(): void
    {
        Mail::fake();

        SistemaConfiguracaoEmail::query()->whereKey(1)->update([
            'mail_mailer' => 'log',
            'mail_password' => null,
        ]);

        config(['mail.default' => 'log']);

        $service = app(AuthEmailService::class);
        $user = User::factory()->make(['email' => 'x@test.local']);

        $this->assertFalse($service->podeEnviar());
        $this->assertFalse($service->enviarSenhaRedefinida($user));

        Mail::assertNothingSent();
    }
}

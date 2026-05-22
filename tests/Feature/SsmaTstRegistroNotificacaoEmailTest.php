<?php

namespace Tests\Feature;

use App\Mail\LayoutHtmlMail;
use App\Models\Colaborador;
use App\Models\SistemaConfiguracaoEmail;
use App\Models\SsmaTstRegistro;
use App\Models\User;
use App\Services\SsmaTstRegistroNotificacaoService;
use App\Support\SsmaTstRegistroService;
use App\Support\TstRegistroNotificacaoDestinatarios;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SsmaTstRegistroNotificacaoEmailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
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
    }

    public function test_salvar_destinatarios_colaborador_e_usuario(): void
    {
        $admin = User::factory()->create(['status' => 'ativo', 'email' => 'admin@test.local']);
        $colab = Colaborador::query()->create([
            'nome' => 'Colab SSMA',
            'matricula' => '9001',
            'email' => 'colab@test.local',
            'status' => 'ativo',
        ]);

        $json = json_encode([
            ['tipo' => 'colaborador', 'id' => $colab->id],
            ['tipo' => 'usuario', 'id' => $admin->id],
        ]);

        $this->actingAs($admin)->put(route('configuracoes.email.tst-destinatarios.update'), [
            'destinatarios_json' => $json,
        ])->assertRedirect(route('configuracoes.email.edit'));

        $registro = SistemaConfiguracaoEmail::query()->find(1);
        $this->assertCount(2, $registro->notificacao_registro_tst_destinatarios);
    }

    public function test_criar_registro_tst_dispara_email_para_destinatarios_configurados(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['status' => 'ativo', 'email' => 'ssma@test.local']);
        $gestor = User::factory()->create(['status' => 'ativo', 'email' => 'gestor@test.local']);

        SistemaConfiguracaoEmail::query()->find(1)?->update([
            'notificacao_registro_tst_destinatarios' => [
                ['tipo' => 'usuario', 'id' => $admin->id],
                ['tipo' => 'usuario', 'id' => $gestor->id],
            ],
        ]);

        $user = User::factory()->create();
        $colaborador = Colaborador::query()->create([
            'nome' => 'Operador TST',
            'matricula' => '1001',
            'status' => 'ativo',
        ]);

        $this->actingAs($user)->post(route('sesmt.registros-tst.registros.store'), [
            'colaborador_id' => $colaborador->id,
            'data' => '2026-05-22',
            'descricao' => 'Registro com notificação por e-mail',
            'arquivos' => [UploadedFile::fake()->image('foto.jpg')],
        ])->assertRedirect();

        Mail::assertSent(LayoutHtmlMail::class, 2);
    }

    public function test_sem_destinatarios_nao_envia_email(): void
    {
        Mail::fake();

        SistemaConfiguracaoEmail::query()->find(1)?->update([
            'notificacao_registro_tst_destinatarios' => [],
        ]);

        $path = UploadedFile::fake()->image('x.jpg')->store('ssma/tst/registros', 'public');
        $registro = SsmaTstRegistro::query()->create([
            'colaborador_id' => Colaborador::query()->create([
                'nome' => 'X',
                'matricula' => '2',
                'status' => 'ativo',
            ])->id,
            'data' => '2026-05-22',
            'descricao' => 'Teste',
            'arquivo_path' => $path,
            'arquivo_nome' => 'x.jpg',
            'arquivo_mime' => 'image/jpeg',
            'origem' => SsmaTstRegistroService::ORIGEM_SISTEMA,
        ]);

        app(SsmaTstRegistroNotificacaoService::class)->notificarRegistroConcluido($registro);

        Mail::assertNothingSent();
    }

    public function test_emails_para_envio_usa_cadastro_colaborador(): void
    {
        $colab = Colaborador::query()->create([
            'nome' => 'Com Email',
            'matricula' => '1',
            'email' => 'destino@colab.test',
            'status' => 'ativo',
        ]);

        $emails = TstRegistroNotificacaoDestinatarios::emailsParaEnvio([
            ['tipo' => 'colaborador', 'id' => $colab->id],
        ]);

        $this->assertSame(['destino@colab.test'], $emails);
    }

    public function test_preview_registro_tst_retorna_html(): void
    {
        $admin = User::factory()->create(['status' => 'ativo']);

        $this->actingAs($admin)
            ->get(route('configuracoes.email.preview.tst', 'registro-tst-novo'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/html; charset=UTF-8');
    }
}

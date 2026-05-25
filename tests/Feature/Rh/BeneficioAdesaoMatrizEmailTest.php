<?php

namespace Tests\Feature\Rh;

use App\Mail\LayoutHtmlMail;
use App\Models\Beneficio;
use App\Models\Colaborador;
use App\Models\ColaboradorBeneficio;
use App\Models\SistemaConfiguracaoEmail;
use App\Models\User;
use App\Services\Rh\BeneficioAdesaoMatrizNotificacaoService;
use App\Support\Rh\BeneficioAdesaoMatrizEmailTexto;
use App\Support\Rh\BeneficioAdesaoStatus;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use App\Support\PublicWebBase;
use Tests\TestCase;

class BeneficioAdesaoMatrizEmailTest extends TestCase
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

        config([
            'mail.beneficio_adesao_matriz.copia_sistema' => 'jarbas@test.local',
            'mail.mailers.zimbra_jarbas' => [
                'transport' => 'smtp',
                'host' => 'mail.zimbra.test.local',
                'port' => 587,
                'encryption' => 'tls',
                'username' => 'jarbas@test.local',
                'password' => 'zimbra-app-pass',
                'timeout' => null,
            ],
            'mail.beneficio_adesao_matriz.zimbra_from_address' => 'jarbas@test.local',
            'mail.beneficio_adesao_matriz.zimbra_from_name' => 'Jarbas Teste',
        ]);

        app(\App\Services\ConfiguracaoEmailService::class)->aplicarConfiguracaoRuntime();
    }

    public function test_salvar_destinatarios_beneficio_adesao_matriz(): void
    {
        $admin = User::factory()->create(['status' => 'ativo', 'email' => 'matriz@test.local']);

        $json = json_encode([
            ['tipo' => 'usuario', 'id' => $admin->id],
        ]);

        $this->actingAs($admin)->put(route('configuracoes.email.beneficio-adesao-matriz-destinatarios.update'), [
            'destinatarios_json' => $json,
        ])->assertRedirect(route('configuracoes.email.edit'));

        $registro = SistemaConfiguracaoEmail::query()->find(1);
        $this->assertCount(1, $registro->notificacao_beneficio_adesao_matriz_destinatarios);
    }

    public function test_enviar_solicitacao_matriz_com_anexo(): void
    {
        Mail::fake();

        $matriz = User::factory()->create(['status' => 'ativo', 'email' => 'celiamara@test.local', 'name' => 'Celiamara']);
        SistemaConfiguracaoEmail::query()->find(1)?->update([
            'notificacao_beneficio_adesao_matriz_destinatarios' => [
                ['tipo' => 'usuario', 'id' => $matriz->id],
            ],
        ]);

        $rh = User::factory()->create(['todos_contratos' => true]);
        $beneficio = Beneficio::query()->create([
            'nome' => 'Vale Alimentação',
            'status' => 'ativo',
            'requer_controle_adesao' => true,
        ]);
        $colaborador = Colaborador::query()->create(['nome' => 'João Silva', 'status' => 'ativo', 'matricula' => '100']);
        $vinculo = ColaboradorBeneficio::query()->create([
            'beneficio_id' => $beneficio->id,
            'colaborador_id' => $colaborador->id,
            'status_adesao' => BeneficioAdesaoStatus::FORMULARIO_RECEBIDO,
            'tem_direito' => true,
        ]);

        $path = UploadedFile::fake()->create('adesao.pdf', 50, 'application/pdf')
            ->store('rh/beneficios/formularios-adesao', 'public');
        $vinculo->update(['formulario_adesao_assinado_path' => $path]);

        $this->actingAs($rh)
            ->post(route('rh.beneficios.vinculos.enviar-solicitacao-matriz', [
                'beneficio' => $beneficio,
                'vinculo' => $vinculo,
            ]))
            ->assertRedirect()
            ->assertSessionHas('success');

        Mail::assertSent(LayoutHtmlMail::class, function (LayoutHtmlMail $mail) {
            return count($mail->anexos) === 1
                && ($mail->anexos[0]['path'] ?? '') !== ''
                && $mail->assunto === 'Solicitação de adesão à Matriz | Vale Alimentação | 100 - João Silva';
        });

        Mail::assertSent(LayoutHtmlMail::class, 2);

        Mail::assertSent(LayoutHtmlMail::class, function (LayoutHtmlMail $mail) {
            return $mail->fromAddress === null;
        });

        Mail::assertSent(LayoutHtmlMail::class, function (LayoutHtmlMail $mail) {
            return $mail->fromAddress === 'jarbas@test.local'
                && $mail->fromName === 'Jarbas Teste';
        });

        $vinculo->refresh();
        $this->assertNotNull($vinculo->data_envio_matriz);
        $this->assertNotNull($vinculo->email_solicitacao_matriz_enviado_em);
        $this->assertSame(
            now()->timezone('America/Sao_Paulo')->format('d/m/Y H:i'),
            $vinculo->email_solicitacao_matriz_enviado_em->timezone('America/Sao_Paulo')->format('d/m/Y H:i'),
        );
        $this->assertSame(BeneficioAdesaoStatus::AGUARDANDO_CARTAO, $vinculo->status_adesao);
    }

    public function test_enviar_sem_formulario_retorna_erro(): void
    {
        Mail::fake();

        $rh = User::factory()->create(['todos_contratos' => true]);
        $matriz = User::factory()->create(['status' => 'ativo', 'email' => 'm@test.local']);
        SistemaConfiguracaoEmail::query()->find(1)?->update([
            'notificacao_beneficio_adesao_matriz_destinatarios' => [
                ['tipo' => 'usuario', 'id' => $matriz->id],
            ],
        ]);

        $beneficio = Beneficio::query()->create([
            'nome' => 'Vale',
            'status' => 'ativo',
            'requer_controle_adesao' => true,
        ]);
        $colaborador = Colaborador::query()->create(['nome' => 'Ana', 'status' => 'ativo']);
        $vinculo = ColaboradorBeneficio::query()->create([
            'beneficio_id' => $beneficio->id,
            'colaborador_id' => $colaborador->id,
        ]);

        $this->actingAs($rh)
            ->post(route('rh.beneficios.vinculos.enviar-solicitacao-matriz', [
                'beneficio' => $beneficio,
                'vinculo' => $vinculo,
            ]))
            ->assertRedirect()
            ->assertSessionHas('error');

        Mail::assertNothingSent();
    }

    public function test_preview_solicitacao_adesao_retorna_html(): void
    {
        $admin = User::factory()->create(['status' => 'ativo']);

        $this->actingAs($admin)
            ->get(route('configuracoes.email.preview.beneficio-adesao', 'solicitacao-adesao-matriz'))
            ->assertOk()
            ->assertSee('Celiamara', false);
    }

    public function test_email_menciona_responsavel_matriz(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-22 09:30:00', 'America/Sao_Paulo'));

        $html = app(BeneficioAdesaoMatrizNotificacaoService::class)
            ->renderPreview('solicitacao-adesao-matriz');

        $this->assertStringContainsString('Prezada <strong>Celiamara</strong>, bom dia.', $html);
        $this->assertStringContainsString('CONTRATO 286', $html);
        $this->assertStringContainsString('Encaminho, em anexo, o formulário de adesão assinada pela colaboradora', $html);
        $this->assertStringContainsString('Dados da colaboradora', $html);
        $this->assertStringContainsString('Solicito, por gentileza, o registro do pedido.', $html);
        $this->assertStringNotContainsString('<strong>Anexo:</strong>', $html);
        $this->assertStringNotContainsString('Esta mensagem foi enviada automaticamente', $html);
        $this->assertStringNotContainsString('Não responda diretamente', $html);

        Carbon::setTestNow();
    }

    public function test_link_assinado_abre_formulario_sem_login(): void
    {
        $beneficio = Beneficio::query()->create([
            'nome' => 'Vale',
            'status' => 'ativo',
            'requer_controle_adesao' => true,
        ]);
        $colaborador = Colaborador::query()->create(['nome' => 'Ana', 'status' => 'ativo']);
        $vinculo = ColaboradorBeneficio::query()->create([
            'beneficio_id' => $beneficio->id,
            'colaborador_id' => $colaborador->id,
        ]);

        $path = UploadedFile::fake()->create('adesao.pdf', 50, 'application/pdf')
            ->store('rh/beneficios/formularios-adesao', 'public');
        $vinculo->update(['formulario_adesao_assinado_path' => $path]);

        config(['app.url' => 'https://omegaadm.feston.net.br', 'app.force_public_url' => true]);

        $url = PublicWebBase::temporarySignedRouteWithPublicPrefix(
            'rh.beneficios.vinculos.formulario-adesao.visualizar',
            now()->addHour(),
            ['beneficio' => $beneficio->id, 'vinculo' => $vinculo->id],
        );

        $this->assertStringContainsString('/public/', $url);

        $parts = parse_url($url);
        $pathInterno = substr($parts['path'], strlen('/public'));
        $query = $parts['query'] ?? '';

        $this->call('GET', $pathInterno.($query !== '' ? '?'.$query : ''), [], [], [], [
            'HTTP_HOST' => $parts['host'],
            'HTTPS' => 'on',
            'SCRIPT_NAME' => '/public/index.php',
            'REQUEST_URI' => $pathInterno.($query !== '' ? '?'.$query : ''),
            'OMEGA_REQUEST_USES_PUBLIC_URL' => '1',
        ])->assertOk();
    }

    public function test_email_enviado_inclui_botao_visualizar_pdf(): void
    {
        Mail::fake();

        $matriz = User::factory()->create(['status' => 'ativo', 'email' => 'm@test.local']);
        SistemaConfiguracaoEmail::query()->find(1)?->update([
            'notificacao_beneficio_adesao_matriz_destinatarios' => [
                ['tipo' => 'usuario', 'id' => $matriz->id],
            ],
        ]);

        $rh = User::factory()->create(['todos_contratos' => true]);
        $beneficio = Beneficio::query()->create([
            'nome' => 'Vale',
            'status' => 'ativo',
            'requer_controle_adesao' => true,
        ]);
        $colaborador = Colaborador::query()->create(['nome' => 'Ana', 'status' => 'ativo', 'matricula' => '99']);
        $vinculo = ColaboradorBeneficio::query()->create([
            'beneficio_id' => $beneficio->id,
            'colaborador_id' => $colaborador->id,
        ]);
        $path = UploadedFile::fake()->create('adesao.pdf', 50, 'application/pdf')
            ->store('rh/beneficios/formularios-adesao', 'public');
        $vinculo->update(['formulario_adesao_assinado_path' => $path]);

        $this->actingAs($rh)->post(route('rh.beneficios.vinculos.enviar-solicitacao-matriz', [
            'beneficio' => $beneficio,
            'vinculo' => $vinculo,
        ]))->assertRedirect();

        Mail::assertSent(LayoutHtmlMail::class, function (LayoutHtmlMail $mail) {
            return str_contains($mail->htmlBody, 'Abrir formulário de adesão (PDF)')
                && str_contains($mail->htmlBody, 'formulario-adesao/visualizar')
                && ! str_contains($mail->htmlBody, '<strong>Anexo:</strong>');
        });
    }

    public function test_diagnostico_detecta_smtp_log_e_sem_destinatarios(): void
    {
        config([
            'mail.default' => 'log',
            'mail.beneficio_adesao_matriz.copia_sistema' => '',
        ]);
        SistemaConfiguracaoEmail::query()->find(1)?->update([
            'notificacao_beneficio_adesao_matriz_destinatarios' => [],
        ]);

        $diag = app(BeneficioAdesaoMatrizNotificacaoService::class)->diagnosticoEnvio();

        $this->assertFalse($diag['pode_enviar']);
        $this->assertNotEmpty($diag['problemas']);
    }

    public function test_diagnostico_exige_zimbra_quando_ha_destinatario_matriz(): void
    {
        config(['mail.mailers.zimbra_jarbas.password' => null]);

        $matriz = User::factory()->create(['status' => 'ativo', 'email' => 'celiamara@test.local']);
        SistemaConfiguracaoEmail::query()->find(1)?->update([
            'notificacao_beneficio_adesao_matriz_destinatarios' => [
                ['tipo' => 'usuario', 'id' => $matriz->id],
            ],
        ]);

        $diag = app(BeneficioAdesaoMatrizNotificacaoService::class)->diagnosticoEnvio();

        $this->assertFalse($diag['pode_enviar']);
        $this->assertContains('celiamara@test.local', $diag['destinatarios_zimbra']);
    }

    public function test_assunto_do_email_enviado(): void
    {
        $assunto = BeneficioAdesaoMatrizEmailTexto::montarAssunto('Vale Alimentação', '012345', 'Maria da Silva');

        $this->assertSame(
            'Solicitação de adesão à Matriz | Vale Alimentação | 012345 - Maria da Silva',
            $assunto,
        );
    }
}

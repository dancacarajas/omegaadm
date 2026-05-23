<?php

namespace App\Services\Rh;

use App\Mail\LayoutHtmlMail;
use App\Models\ColaboradorBeneficio;
use App\Models\SistemaConfiguracaoEmail;
use App\Models\User;
use App\Services\ConfiguracaoEmailService;
use App\Support\EmailLayout;
use App\Support\PublicWebBase;
use App\Support\Rh\BeneficioAdesaoMatrizEmailTexto;
use App\Support\Rh\BeneficioAdesaoStatus;
use App\Support\TstRegistroNotificacaoDestinatarios;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class BeneficioAdesaoMatrizNotificacaoService
{
    public const RESPONSAVEL_MATRIZ = 'Celiamara';

    public function __construct(
        private readonly ConfiguracaoEmailService $configuracaoEmail,
    ) {}

    /** @return array<string, string> */
    public static function tiposPreview(): array
    {
        return [
            'solicitacao-adesao-matriz' => 'Solicitação de adesão à Matriz',
        ];
    }

    public function renderPreview(string $tipo): string
    {
        return EmailLayout::render('emails.rh.solicitacao-adesao-matriz', array_merge(
            $this->dadosPreview(),
            ['preview' => true],
        ));
    }

    /**
     * @return list<string>
     */
    public function destinatariosConfigurados(): array
    {
        $registro = $this->configuracaoEmail->registroSeExistir();

        if ($registro === null) {
            return [];
        }

        return TstRegistroNotificacaoDestinatarios::emailsParaEnvio(
            TstRegistroNotificacaoDestinatarios::normalizar(
                $registro->notificacao_beneficio_adesao_matriz_destinatarios ?? []
            )
        );
    }

    /**
     * @return array{enviados: int, destinatarios: list<string>}
     */
    public function enviarSolicitacao(ColaboradorBeneficio $vinculo, ?User $enviadoPor = null): array
    {
        $vinculo->loadMissing(['colaborador', 'beneficio']);

        if (! $vinculo->usaControleAdesao()) {
            throw ValidationException::withMessages([
                'vinculo' => 'Este benefício não utiliza controle de adesão à Matriz.',
            ]);
        }

        if (! $vinculo->temFormularioAdesaoAssinado()) {
            throw ValidationException::withMessages([
                'formulario_adesao_assinado' => 'Anexe o formulário de adesão assinado antes de enviar o e-mail à Matriz.',
            ]);
        }

        $destinatarios = $this->destinatariosConfigurados();

        if ($destinatarios === []) {
            throw ValidationException::withMessages([
                'destinatarios' => 'Configure os destinatários em Configurações → E-mail (seção Benefícios / Matriz).',
            ]);
        }

        $diagnostico = $this->diagnosticoEnvio();
        if (! $diagnostico['pode_enviar']) {
            throw ValidationException::withMessages([
                'email' => implode(' ', $diagnostico['problemas']),
            ]);
        }

        $path = str_replace('\\', '/', (string) $vinculo->formulario_adesao_assinado_path);
        $nomeAnexo = $this->nomeAnexoFormulario($vinculo);

        $assunto = BeneficioAdesaoMatrizEmailTexto::montarAssunto(
            $vinculo->beneficio?->nome ?? 'Benefício',
            $vinculo->colaborador?->matricula,
            $vinculo->colaborador?->nome ?? 'Colaborador',
        );

        $html = EmailLayout::render('emails.rh.solicitacao-adesao-matriz', $this->dadosDoVinculo($vinculo, $enviadoPor));

        $this->configuracaoEmail->aplicarConfiguracaoRuntime();

        $enviados = 0;

        try {
            foreach ($destinatarios as $email) {
                Mail::to($email)->send(new LayoutHtmlMail(
                    $html,
                    $assunto,
                    [['disk' => 'public', 'path' => $path, 'name' => $nomeAnexo]],
                ));
                $enviados++;
            }

            $this->marcarPedidoEnviadoMatriz($vinculo, $enviadoPor);

            Log::info('E-mail de solicitação de adesão à Matriz enviado.', [
                'vinculo_id' => $vinculo->id,
                'beneficio_id' => $vinculo->beneficio_id,
                'enviados' => $enviados,
            ]);
        } catch (\Throwable $e) {
            Log::error('Falha ao enviar solicitação de adesão à Matriz.', [
                'vinculo_id' => $vinculo->id,
                'erro' => $e->getMessage(),
            ]);

            throw ValidationException::withMessages([
                'email' => 'Não foi possível enviar o e-mail: '.$e->getMessage(),
            ]);
        }

        return [
            'enviados' => $enviados,
            'destinatarios' => $destinatarios,
        ];
    }

    /**
     * @return array{pode_enviar: bool, problemas: list<string>, destinatarios: list<string>, mailer: string|null}
     */
    public function diagnosticoEnvio(): array
    {
        $problemas = [];
        $destinatarios = $this->destinatariosConfigurados();

        if (! config('mail.auth_emails_enabled', true)) {
            $problemas[] = 'Envio de e-mails está desativado (MAIL_AUTH_EMAILS_ENABLED=false no servidor).';
        }

        $this->configuracaoEmail->aplicarConfiguracaoRuntime();
        $mailer = (string) config('mail.default');

        if (in_array($mailer, ['log', 'array'], true)) {
            $problemas[] = 'O servidor está com mailer "'.$mailer.'" (e-mails não saem). Configure SMTP em Configurações → E-mail e salve.';
        } elseif ($mailer === 'smtp') {
            $registro = SistemaConfiguracaoEmail::query()->find(1);
            if (! filled(config('mail.mailers.smtp.password')) && ! ($registro?->senhaConfigurada() ?? false)) {
                $problemas[] = 'SMTP sem senha configurada. Informe a senha em Configurações → E-mail.';
            }
            if (blank(config('mail.mailers.smtp.host')) || blank(config('mail.from.address'))) {
                $problemas[] = 'Host SMTP ou e-mail remetente não configurado em Configurações → E-mail.';
            }
        }

        if ($destinatarios === []) {
            $problemas[] = 'Nenhum destinatário em Configurações → E-mail → Benefícios / Matriz (adicione quem recebe o pedido, ex.: Celiamara).';
        }

        if (! \Illuminate\Support\Facades\Schema::hasColumn('sistema_configuracao_email', 'notificacao_beneficio_adesao_matriz_destinatarios')) {
            $problemas[] = 'Banco desatualizado: execute php artisan migrate --force no servidor.';
        }

        return [
            'pode_enviar' => $problemas === [],
            'problemas' => $problemas,
            'destinatarios' => $destinatarios,
            'mailer' => $mailer !== '' ? $mailer : null,
        ];
    }

    public function podeEnviar(): bool
    {
        return $this->diagnosticoEnvio()['pode_enviar'];
    }

    private function marcarPedidoEnviadoMatriz(ColaboradorBeneficio $vinculo, ?User $enviadoPor): void
    {
        $dados = [];

        if (empty($vinculo->data_envio_matriz)) {
            $dados['data_envio_matriz'] = now()->toDateString();
        }

        $statusAtual = $vinculo->status_adesao ?? BeneficioAdesaoStatus::PENDENTE_FORMULARIO;

        if (in_array($statusAtual, [
            BeneficioAdesaoStatus::PENDENTE_FORMULARIO,
            BeneficioAdesaoStatus::FORMULARIO_RECEBIDO,
        ], true)) {
            $dados['status_adesao'] = BeneficioAdesaoStatus::AGUARDANDO_CARTAO;
        } elseif ($statusAtual === BeneficioAdesaoStatus::ENVIADO_MATRIZ) {
            $dados['status_adesao'] = BeneficioAdesaoStatus::AGUARDANDO_CARTAO;
        }

        $dados['adesao_atualizado_por_id'] = $enviadoPor?->id;

        if ($dados !== []) {
            $vinculo->update($dados);
        }
    }

    private function nomeAnexoFormulario(ColaboradorBeneficio $vinculo): string
    {
        $path = (string) $vinculo->formulario_adesao_assinado_path;
        $base = basename($path);
        $ext = pathinfo($base, PATHINFO_EXTENSION);
        $slug = Str::slug($vinculo->colaborador?->nome ?? 'colaborador', '_');
        $beneficio = Str::slug($vinculo->beneficio?->nome ?? 'beneficio', '_');

        $nome = 'formulario_adesao_'.$beneficio.'_'.$slug;

        return $ext !== '' ? $nome.'.'.$ext : $nome;
    }

    /** @return array<string, mixed> */
    private function dadosDoVinculo(ColaboradorBeneficio $vinculo, ?User $enviadoPor, bool $preview = false): array
    {
        $colaborador = $vinculo->colaborador;
        $termos = BeneficioAdesaoMatrizEmailTexto::termosColaborador($colaborador);
        $momento = now('America/Sao_Paulo');

        return [
            'vinculo' => $vinculo,
            'responsavelMatriz' => self::RESPONSAVEL_MATRIZ,
            'saudacaoHorario' => BeneficioAdesaoMatrizEmailTexto::saudacaoHorarioBrasilia($momento),
            'headerTagline' => BeneficioAdesaoMatrizEmailTexto::taglineContrato($colaborador?->centro_custo),
            'termosColaborador' => $termos,
            'nomeBeneficio' => $vinculo->beneficio?->nome ?? 'Benefício',
            'enviadoPor' => $enviadoPor?->name ?? 'Equipe RH',
            'urlFormularioVisualizar' => $this->urlFormularioVisualizacaoAssinada($vinculo, $preview),
            'exibirRodape' => false,
            'preview' => $preview,
        ];
    }

    private function urlFormularioVisualizacaoAssinada(ColaboradorBeneficio $vinculo, bool $preview): ?string
    {
        if ($preview) {
            return '#';
        }

        if (! $vinculo->temFormularioAdesaoAssinado() || ! filled($vinculo->beneficio_id)) {
            return null;
        }

        URL::forceRootUrl(rtrim(PublicWebBase::assetUrl(''), '/'));

        return URL::temporarySignedRoute(
            'rh.beneficios.vinculos.formulario-adesao.visualizar',
            now()->addDays(120),
            [
                'beneficio' => $vinculo->beneficio_id,
                'vinculo' => $vinculo->id,
            ],
        );
    }

    /** @return array<string, mixed> */
    private function dadosPreview(): array
    {
        $vinculo = new ColaboradorBeneficio([
            'data_formulario_recebido' => now()->subDays(3),
            'data_envio_matriz' => now(),
            'protocolo_matriz' => 'E-mail ilustrativo',
            'status_adesao' => BeneficioAdesaoStatus::FORMULARIO_RECEBIDO,
        ]);
        $vinculo->id = 99;
        $vinculo->beneficio_id = 1;

        $vinculo->setRelation('colaborador', new \App\Models\Colaborador([
            'nome' => 'Maria da Silva',
            'matricula' => '012345',
            'cargo' => 'Auxiliar administrativo',
            'data_admissao' => now()->subYear()->setDate(2025, 5, 23),
            'centro_custo' => '286',
            'sexo' => 'Feminino',
        ]));
        $vinculo->setRelation('beneficio', new \App\Models\Beneficio([
            'nome' => 'Vale Alimentação',
        ]));

        return $this->dadosDoVinculo($vinculo, new User(['name' => 'Coordenador RH']), preview: true);
    }
}

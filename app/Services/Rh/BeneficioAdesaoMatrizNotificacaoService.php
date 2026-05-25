<?php

namespace App\Services\Rh;

use App\Mail\LayoutHtmlMail;
use App\Models\ColaboradorBeneficio;
use App\Models\SistemaConfiguracaoEmail;
use App\Models\User;
use App\Services\ConfiguracaoEmailService;
use App\Services\ConfiguracaoZimbraEmailService;
use App\Support\EmailLayout;
use App\Support\PublicWebBase;
use App\Support\Rh\BeneficioAdesaoMatrizEmailTexto;
use App\Support\Rh\BeneficioAdesaoStatus;
use App\Support\TstRegistroNotificacaoDestinatarios;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class BeneficioAdesaoMatrizNotificacaoService
{
    public const RESPONSAVEL_MATRIZ = 'Celiamara';

    /** Sua caixa corporativa: cópia com remetente do SMTP central (286omega@gmail.com / Omega). */
    public const EMAIL_NOTIFICACAO_INTERNA_JARBAS = 'jarbas.alves@omegaservice.com.br';

    public function __construct(
        private readonly ConfiguracaoEmailService $configuracaoEmail,
        private readonly ConfiguracaoZimbraEmailService $configuracaoZimbra,
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
            [
                'preview' => true,
                'assinaturaRodapeHtml' => $this->configuracaoZimbra->renderAssinaturaRodapeHtml(),
            ],
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
     * @return array{enviados: int, destinatarios: list<string>, copia_sistema: string|null, destinatarios_zimbra: list<string>}
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

        $destinatariosZimbra = $this->destinatariosComRemetenteJarbas();
        $notificacaoInternaJarbas = $this->emailNotificacaoInternaJarbas();

        if ($destinatariosZimbra === []) {
            throw ValidationException::withMessages([
                'destinatarios' => 'Configure o e-mail em “Cópia automática benefício” (bloco Zimbra) ou destinatários da Matriz.',
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
        $htmlComAssinaturaZimbra = $html.$this->configuracaoZimbra->renderAssinaturaRodapeHtml();
        $anexos = [['disk' => 'public', 'path' => $path, 'name' => $nomeAnexo]];

        $this->configuracaoEmail->aplicarConfiguracaoRuntime();
        $this->configuracaoZimbra->aplicarConfiguracaoRuntime();

        $mailerCentral = $this->mailerSmtpCentral();
        $mailerZimbra = (string) config('mail.beneficio_adesao_matriz.zimbra_mailer', 'zimbra_jarbas');
        $fromSistema = $this->remetenteSistemaCentral();
        $fromZimbra = [
            'address' => (string) config('mail.beneficio_adesao_matriz.zimbra_from_address'),
            'name' => (string) config('mail.beneficio_adesao_matriz.zimbra_from_name'),
        ];

        $enviados = 0;
        $destinatariosEnviados = [];

        try {
            Mail::mailer($mailerCentral)
                ->to($notificacaoInternaJarbas)
                ->send(new LayoutHtmlMail(
                    $html,
                    $assunto,
                    $anexos,
                    $fromSistema['address'],
                    $fromSistema['name'],
                ));
            $enviados++;
            $destinatariosEnviados[] = $notificacaoInternaJarbas;

            Log::info('Benefício Matriz: notificação interna Jarbas (SMTP central / Omega).', [
                'para' => $notificacaoInternaJarbas,
                'mailer' => $mailerCentral,
                'de' => $fromSistema['address'],
            ]);

            foreach ($destinatariosZimbra as $email) {
                try {
                    Mail::mailer($mailerZimbra)
                        ->to($email)
                        ->send(new LayoutHtmlMail(
                            $htmlComAssinaturaZimbra,
                            $assunto,
                            $anexos,
                            $fromZimbra['address'],
                            $fromZimbra['name'],
                        ));
                    $enviados++;
                    $destinatariosEnviados[] = $email;

                    Log::info('Benefício Matriz: pedido à Matriz (SMTP Zimbra).', [
                        'para' => $email,
                        'mailer' => $mailerZimbra,
                        'de' => $fromZimbra['address'],
                    ]);
                } catch (\Throwable $e) {
                    $this->configuracaoZimbra->registrarErroSmtp($e, $email);

                    throw ValidationException::withMessages([
                        'email' => $this->configuracaoZimbra->mensagemErroParaUsuario($e),
                    ]);
                }
            }

            $this->marcarPedidoEnviadoMatriz($vinculo, $enviadoPor);

            Log::info('E-mail de solicitação de adesão à Matriz enviado.', [
                'vinculo_id' => $vinculo->id,
                'beneficio_id' => $vinculo->beneficio_id,
                'enviados' => $enviados,
                'notificacao_interna_para' => $notificacaoInternaJarbas,
                'notificacao_interna_de' => $fromSistema['address'],
                'destinatarios_zimbra_para' => $destinatariosZimbra,
                'destinatarios_zimbra_de' => $fromZimbra['address'],
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Falha ao enviar solicitação de adesão à Matriz.', [
                'vinculo_id' => $vinculo->id,
                'erro' => $e->getMessage(),
                'erro_tecnico' => $this->configuracaoZimbra->extrairMensagemTecnica($e),
            ]);

            throw ValidationException::withMessages([
                'email' => $this->configuracaoZimbra->mensagemErroParaUsuario($e),
            ]);
        }

        return [
            'enviados' => $enviados,
            'destinatarios' => $destinatariosEnviados,
            'copia_sistema' => $notificacaoInternaJarbas,
            'destinatarios_zimbra' => $destinatariosZimbra,
        ];
    }

    /**
     * @return array{pode_enviar: bool, problemas: list<string>, destinatarios: list<string>, destinatarios_zimbra: list<string>, copia_sistema: string|null, mailer: string|null, zimbra_configurado: bool}
     */
    public function diagnosticoEnvio(): array
    {
        $problemas = [];
        $destinatariosZimbra = $this->destinatariosComRemetenteJarbas();
        $notificacaoInternaJarbas = $this->emailNotificacaoInternaJarbas();
        $destinatariosTodos = array_values(array_unique([
            $notificacaoInternaJarbas,
            ...$destinatariosZimbra,
        ]));

        if (! config('mail.auth_emails_enabled', true)) {
            $problemas[] = 'Envio de e-mails está desativado (MAIL_AUTH_EMAILS_ENABLED=false no servidor).';
        }

        $this->configuracaoEmail->aplicarConfiguracaoRuntime();
        $this->configuracaoZimbra->aplicarConfiguracaoRuntime();
        $mailer = (string) config('mail.default');
        $zimbraOk = $this->zimbraJarbasConfigurado();

        if (in_array($mailer, ['log', 'array'], true)) {
            $problemas[] = 'O servidor está com mailer "'.$mailer.'" (e-mails não saem). Configure SMTP em Configurações → E-mail e salve.';
        } elseif ($mailer === 'smtp') {
            $registro = SistemaConfiguracaoEmail::query()->find(1);
            if (! filled(config('mail.mailers.smtp.password')) && ! ($registro?->senhaConfigurada() ?? false)) {
                $problemas[] = 'SMTP do sistema sem senha configurada. Informe a senha em Configurações → E-mail.';
            }
            if (blank(config('mail.mailers.smtp.host')) || blank(config('mail.from.address'))) {
                $problemas[] = 'Host SMTP ou e-mail remetente do sistema não configurado em Configurações → E-mail.';
            }
        }

        if ($destinatariosZimbra === []) {
            $problemas[] = 'Informe “Cópia automática benefício” no bloco Zimbra e/ou destinatários da Matriz (quem recebe com remetente Jarbas).';
        }

        if ($destinatariosZimbra !== [] && ! $zimbraOk) {
            $problemas[] = 'SMTP Zimbra (bloco âmbar) incompleto: informe host, usuário e senha de aplicativo do Zimbra.';
        }

        if ($destinatariosZimbra !== [] && $zimbraOk) {
            $userZimbra = strtolower((string) config('mail.mailers.zimbra_jarbas.username'));
            $fromZimbra = strtolower((string) config('mail.beneficio_adesao_matriz.zimbra_from_address'));
            if ($userZimbra !== '' && $fromZimbra !== '' && $userZimbra !== $fromZimbra) {
                $problemas[] = 'No SMTP Zimbra, usuário e e-mail remetente devem ser o mesmo (jarbas.alves@omegaservice.com.br).';
            }
        }

        if (! \Illuminate\Support\Facades\Schema::hasColumn('sistema_configuracao_email', 'notificacao_beneficio_adesao_matriz_destinatarios')) {
            $problemas[] = 'Banco desatualizado: execute php artisan migrate --force no servidor.';
        }

        return [
            'pode_enviar' => $problemas === [],
            'problemas' => $problemas,
            'destinatarios' => $destinatariosTodos,
            'destinatarios_zimbra' => $destinatariosZimbra,
            'copia_sistema' => $notificacaoInternaJarbas,
            'mailer' => $mailer !== '' ? $mailer : null,
            'zimbra_configurado' => $zimbraOk,
        ];
    }

    public function podeEnviar(): bool
    {
        return $this->diagnosticoEnvio()['pode_enviar'];
    }

    /**
     * Sempre recebe cópia com remetente Omega (SMTP central — 286omega@gmail.com).
     */
    public function emailNotificacaoInternaJarbas(): string
    {
        return strtolower((string) config(
            'mail.beneficio_adesao_matriz.notificacao_interna_jarbas',
            self::EMAIL_NOTIFICACAO_INTERNA_JARBAS,
        ));
    }

    /**
     * E-mail do campo “Cópia automática benefício” — destinatário com remetente Jarbas (Zimbra).
     */
    public function emailCopiaBeneficioRemetenteJarbas(): ?string
    {
        $registro = $this->configuracaoEmail->registroSeExistir();
        $email = strtolower(trim((string) ($registro?->beneficio_adesao_copia_email ?? '')));

        if ($email === '' || $email === $this->emailNotificacaoInternaJarbas()) {
            return null;
        }

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    /**
     * Quem recebe o pedido com remetente Jarbas (Zimbra): campo “Cópia automática benefício” + lista Matriz.
     *
     * @return list<string>
     */
    public function destinatariosComRemetenteJarbas(): array
    {
        $interno = $this->emailNotificacaoInternaJarbas();
        $lista = [];

        $copiaBeneficio = $this->emailCopiaBeneficioRemetenteJarbas();
        if ($copiaBeneficio !== null) {
            $lista[$copiaBeneficio] = $copiaBeneficio;
        }

        foreach ($this->destinatariosConfigurados() as $email) {
            $email = strtolower(trim($email));
            if ($email === '' || $email === $interno) {
                continue;
            }
            $lista[$email] = $email;
        }

        return array_values($lista);
    }

    /** @deprecated Use destinatariosComRemetenteJarbas() */
    public function destinatariosMatrizViaZimbra(): array
    {
        return $this->destinatariosComRemetenteJarbas();
    }

    /** @deprecated Use emailNotificacaoInternaJarbas() */
    public function emailCopiaSistemaJarbas(): ?string
    {
        return $this->emailNotificacaoInternaJarbas();
    }

    private function mailerSmtpCentral(): string
    {
        $mailer = (string) config('mail.default');

        return in_array($mailer, ['smtp', 'log', 'array'], true) ? $mailer : 'smtp';
    }

    /** @return array{address: string, name: string} */
    private function remetenteSistemaCentral(): array
    {
        return [
            'address' => (string) config('mail.from.address'),
            'name' => (string) config('mail.from.name'),
        ];
    }

    public function zimbraJarbasConfigurado(): bool
    {
        return $this->configuracaoZimbra->configurado();
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
        $dados['email_solicitacao_matriz_enviado_em'] = now();

        $vinculo->update($dados);
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

        return PublicWebBase::temporarySignedRouteWithPublicPrefix(
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

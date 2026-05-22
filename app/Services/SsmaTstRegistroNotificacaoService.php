<?php

namespace App\Services;

use App\Mail\LayoutHtmlMail;
use App\Models\SistemaConfiguracaoEmail;
use App\Models\SsmaTstRegistro;
use App\Support\EmailLayout;
use App\Support\PublicWebBase;
use App\Support\SsmaTstRegistroService;
use App\Support\TstRegistroNotificacaoDestinatarios;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

final class SsmaTstRegistroNotificacaoService
{
    public function __construct(
        private readonly ConfiguracaoEmailService $configuracaoEmail,
    ) {}

    /** @return array<string, string> */
    public static function tiposPreview(): array
    {
        return [
            'registro-tst-novo' => 'Registro TST concluído',
        ];
    }

    public function renderPreview(string $tipo): string
    {
        return EmailLayout::render('emails.sesmt.registro-tst-novo', array_merge(
            $this->dadosPreview(),
            ['preview' => true]
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
            TstRegistroNotificacaoDestinatarios::normalizar($registro->notificacao_registro_tst_destinatarios ?? [])
        );
    }

    public function notificarRegistroConcluido(SsmaTstRegistro $registro): int
    {
        $destinatarios = $this->destinatariosConfigurados();

        if ($destinatarios === []) {
            return 0;
        }

        if (! $this->podeEnviar()) {
            Log::warning('Notificação de registro TST não enviada: SMTP indisponível.', [
                'registro_id' => $registro->id,
                'destinatarios' => $destinatarios,
            ]);

            return 0;
        }

        $registro->loadMissing(['colaborador', 'atividade', 'usuario']);

        $assunto = 'Novo registro TST — '.($registro->colaborador?->nome ?? 'Colaborador');
        $enviados = 0;

        try {
            URL::forceRootUrl(rtrim(PublicWebBase::assetUrl(''), '/'));
            $this->configuracaoEmail->aplicarConfiguracaoRuntime();

            $html = EmailLayout::render('emails.sesmt.registro-tst-novo', $this->dadosDoRegistro($registro));

            foreach ($destinatarios as $email) {
                Mail::to($email)->send(new LayoutHtmlMail($html, $assunto));
                $enviados++;
            }

            Log::info('Notificação de registro TST enviada.', [
                'registro_id' => $registro->id,
                'enviados' => $enviados,
            ]);
        } catch (\Throwable $e) {
            Log::error('Falha ao enviar notificação de registro TST.', [
                'registro_id' => $registro->id,
                'erro' => $e->getMessage(),
            ]);
        }

        return $enviados;
    }

    public function podeEnviar(): bool
    {
        if (! config('mail.auth_emails_enabled', true)) {
            return false;
        }

        $this->configuracaoEmail->aplicarConfiguracaoRuntime();

        $mailer = config('mail.default');

        if (in_array($mailer, ['log', 'array'], true)) {
            return false;
        }

        if ($mailer !== 'smtp') {
            return true;
        }

        if (filled(config('mail.mailers.smtp.password'))) {
            return true;
        }

        $registro = SistemaConfiguracaoEmail::query()->find(1);

        return $registro?->senhaConfigurada() ?? false;
    }

    /** @return array<string, mixed> */
    private function dadosDoRegistro(SsmaTstRegistro $registro): array
    {
        $origemLabel = match ($registro->origem) {
            SsmaTstRegistroService::ORIGEM_APP_COLABORADOR => 'App do colaborador (campo)',
            default => 'Sistema (painel SSMA)',
        };

        $registradoPor = $registro->origem === SsmaTstRegistroService::ORIGEM_APP_COLABORADOR
            ? ($registro->colaborador?->nome ?? 'Colaborador')
            : ($registro->usuario?->name ?? 'Usuário do sistema');

        return [
            'registro' => $registro,
            'origemLabel' => $origemLabel,
            'registradoPor' => $registradoPor,
            'urlRegistro' => PublicWebBase::assetUrl(
                'sesmt/registros-tst/'.$registro->id
            ),
        ];
    }

    /** @return array<string, mixed> */
    private function dadosPreview(): array
    {
        $registro = new SsmaTstRegistro([
            'data' => now(),
            'descricao' => 'Inspeção de área de trabalho com identificação de desvio corrigido no local. Registro ilustrativo para pré-visualização.',
            'origem' => SsmaTstRegistroService::ORIGEM_SISTEMA,
        ]);
        $registro->id = 42;

        $registro->setRelation('colaborador', new \App\Models\Colaborador([
            'nome' => 'Jarbas Alves',
            'matricula' => '022214',
        ]));
        $registro->setRelation('atividade', new \App\Models\SsmaTstAtividade([
            'nome' => 'Inspeção de segurança',
        ]));
        $registro->setRelation('usuario', new \App\Models\User([
            'name' => 'Coordenador SSMA',
        ]));

        return array_merge($this->dadosDoRegistro($registro), [
            'registro' => $registro,
        ]);
    }
}

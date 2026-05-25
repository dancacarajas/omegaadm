<?php

namespace App\Http\Controllers;

use App\Models\SistemaConfiguracaoEmail;
use App\Services\ConfiguracaoEmailService;
use App\Services\ConfiguracaoZimbraEmailService;
use App\Services\AuthEmailService;
use App\Services\Rh\BeneficioAdesaoMatrizNotificacaoService;
use App\Services\SsmaTstRegistroNotificacaoService;
use App\Support\EmailLayout;
use App\Support\TstRegistroNotificacaoDestinatarios;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ConfiguracaoEmailController extends Controller
{
    public function edit(ConfiguracaoEmailService $service, ConfiguracaoZimbraEmailService $zimbraService)
    {
        $dados = $service->dadosParaFormulario();
        $registro = $dados['registro'];
        $destinatariosTst = TstRegistroNotificacaoDestinatarios::normalizar(
            $registro->notificacao_registro_tst_destinatarios ?? []
        );
        $destinatariosBeneficioAdesao = TstRegistroNotificacaoDestinatarios::normalizar(
            $registro->notificacao_beneficio_adesao_matriz_destinatarios ?? []
        );

        return view('configuracoes.email', array_merge($dados, $zimbraService->dadosParaFormulario(), [
            'mailers' => ConfiguracaoEmailService::mailersDisponiveis(),
            'criptografiasZimbra' => ConfiguracaoEmailService::criptografiasDisponiveis(),
            'criptografias' => ConfiguracaoEmailService::criptografiasDisponiveis(),
            'authEmailPreviews' => AuthEmailService::tiposPreview(),
            'tstEmailPreviews' => SsmaTstRegistroNotificacaoService::tiposPreview(),
            'beneficioAdesaoEmailPreviews' => BeneficioAdesaoMatrizNotificacaoService::tiposPreview(),
            'tstDestinatariosCapsulas' => TstRegistroNotificacaoDestinatarios::resolverParaExibicao($destinatariosTst),
            'beneficioAdesaoDestinatariosCapsulas' => TstRegistroNotificacaoDestinatarios::resolverParaExibicao($destinatariosBeneficioAdesao),
            'colaboradoresEmailOpcoes' => TstRegistroNotificacaoDestinatarios::opcoesColaboradores(),
            'usuariosEmailOpcoes' => TstRegistroNotificacaoDestinatarios::opcoesUsuarios(),
        ]));
    }

    public function update(Request $request, ConfiguracaoEmailService $service)
    {
        $data = $request->validate([
            'mail_mailer' => ['required', Rule::in(array_keys(ConfiguracaoEmailService::mailersDisponiveis()))],
            'mail_encryption' => ['nullable', Rule::in(['', 'tls', 'ssl'])],
            'mail_host' => ['nullable', 'string', 'max:255'],
            'mail_port' => ['required', 'integer', 'min:1', 'max:65535'],
            'mail_username' => ['nullable', 'string', 'max:255'],
            'mail_password' => ['nullable', 'string', 'max:500'],
            'mail_from_name' => ['nullable', 'string', 'max:120'],
            'mail_from_address' => ['nullable', 'email', 'max:255'],
        ]);

        if ($data['mail_mailer'] === 'smtp') {
            $request->validate([
                'mail_host' => ['required', 'string', 'max:255'],
                'mail_from_address' => ['required', 'email', 'max:255'],
            ]);

            $registro = SistemaConfiguracaoEmail::registro();
            if (blank($data['mail_password'] ?? null) && ! $registro->senhaConfigurada()) {
                throw ValidationException::withMessages([
                    'mail_password' => 'Informe a senha SMTP para o primeiro cadastro.',
                ]);
            }
        }

        $service->salvar($data, $request->user()?->id);

        return redirect()
            ->route('configuracoes.email.edit')
            ->with('success', 'Configuração de e-mail salva com sucesso.');
    }

    public function updateTstDestinatarios(Request $request)
    {
        $data = $request->validate([
            'destinatarios_json' => ['nullable', 'string', 'max:16000'],
        ]);

        $payload = json_decode($data['destinatarios_json'] ?? '[]', true);
        if (! is_array($payload)) {
            $payload = [];
        }

        $registro = SistemaConfiguracaoEmail::registro();
        $registro->update([
            'notificacao_registro_tst_destinatarios' => TstRegistroNotificacaoDestinatarios::validarPayload($payload),
            'updated_by_id' => $request->user()?->id,
        ]);

        return redirect()
            ->route('configuracoes.email.edit')
            ->with('success', 'Destinatários do registro TST salvos.');
    }

    public function previewLayout()
    {
        $html = EmailLayout::render('emails.exemplo-aprovacao', ['preview' => true]);

        return response($html)->header('Content-Type', 'text/html; charset=UTF-8');
    }

    public function previewTst(string $tipo, SsmaTstRegistroNotificacaoService $service)
    {
        if (! array_key_exists($tipo, SsmaTstRegistroNotificacaoService::tiposPreview())) {
            abort(404);
        }

        $html = $service->renderPreview($tipo);

        return response($html)->header('Content-Type', 'text/html; charset=UTF-8');
    }

    public function updateBeneficioAdesaoMatrizDestinatarios(Request $request)
    {
        $data = $request->validate([
            'destinatarios_json' => ['nullable', 'string', 'max:16000'],
        ]);

        $payload = json_decode($data['destinatarios_json'] ?? '[]', true);
        if (! is_array($payload)) {
            $payload = [];
        }

        $registro = SistemaConfiguracaoEmail::registro();
        $registro->update([
            'notificacao_beneficio_adesao_matriz_destinatarios' => TstRegistroNotificacaoDestinatarios::validarPayload($payload),
            'updated_by_id' => $request->user()?->id,
        ]);

        return redirect()
            ->route('configuracoes.email.edit')
            ->with('success', 'Destinatários da solicitação de adesão (Matriz) salvos.');
    }

    public function previewBeneficioAdesao(string $tipo, BeneficioAdesaoMatrizNotificacaoService $service)
    {
        if (! array_key_exists($tipo, BeneficioAdesaoMatrizNotificacaoService::tiposPreview())) {
            abort(404);
        }

        $html = $service->renderPreview($tipo);

        return response($html)->header('Content-Type', 'text/html; charset=UTF-8');
    }

    public function updateZimbraJarbas(Request $request, ConfiguracaoZimbraEmailService $zimbraService)
    {
        $data = $request->validate([
            'zimbra_host' => ['required', 'string', 'max:255'],
            'zimbra_encryption' => ['nullable', Rule::in(['', 'tls', 'ssl'])],
            'zimbra_port' => ['required', 'integer', 'min:1', 'max:65535'],
            'zimbra_username' => ['required', 'email', 'max:255'],
            'zimbra_password' => ['nullable', 'string', 'max:500'],
            'zimbra_from_name' => ['required', 'string', 'max:120'],
            'zimbra_from_address' => ['required', 'email', 'max:255'],
            'beneficio_adesao_copia_email' => ['nullable', 'email', 'max:255'],
        ]);

        $registro = SistemaConfiguracaoEmail::registro();
        if (blank($data['zimbra_password'] ?? null) && ! $registro->senhaZimbraConfigurada()) {
            throw ValidationException::withMessages([
                'zimbra_password' => 'Informe a senha de aplicativo Zimbra (primeiro cadastro).',
            ]);
        }

        if (strtolower($data['zimbra_username']) !== strtolower($data['zimbra_from_address'])) {
            throw ValidationException::withMessages([
                'zimbra_from_address' => 'O e-mail remetente deve ser o mesmo do usuário SMTP (conta Zimbra autenticada).',
            ]);
        }

        $zimbraService->salvar($data, $request->user()?->id);

        return redirect()
            ->route('configuracoes.email.edit')
            ->with('success', 'SMTP Zimbra (envio como Jarbas) salvo. O SMTP central do sistema não foi alterado.');
    }

    public function testarZimbraJarbas(Request $request, ConfiguracaoZimbraEmailService $zimbraService)
    {
        $data = $request->validate([
            'email_teste_zimbra' => ['required', 'email', 'max:255'],
        ]);

        try {
            $zimbraService->enviarTeste($data['email_teste_zimbra']);

            return redirect()
                ->route('configuracoes.email.edit')
                ->with('success', 'E-mail de teste Zimbra enviado para '.$data['email_teste_zimbra'].'. Verifique se o remetente aparece como Jarbas.');
        } catch (\Throwable $e) {
            $zimbraService->registrarErroSmtp($e, $data['email_teste_zimbra']);

            return redirect()
                ->route('configuracoes.email.edit')
                ->withInput()
                ->with('error', $zimbraService->mensagemErroParaUsuario($e));
        }
    }

    public function testar(Request $request, ConfiguracaoEmailService $service)
    {
        $data = $request->validate([
            'email_teste' => ['required', 'email', 'max:255'],
        ]);

        try {
            $service->enviarTeste($data['email_teste']);

            return redirect()
                ->route('configuracoes.email.edit')
                ->with('success', 'E-mail de teste enviado para '.$data['email_teste'].'. Verifique a caixa de entrada e o spam.');
        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->route('configuracoes.email.edit')
                ->withInput()
                ->with('error', $service->mensagemErroAmigavel($e));
        }
    }
}

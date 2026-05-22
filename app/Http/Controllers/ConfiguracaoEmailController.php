<?php

namespace App\Http\Controllers;

use App\Models\SistemaConfiguracaoEmail;
use App\Services\ConfiguracaoEmailService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ConfiguracaoEmailController extends Controller
{
    public function edit(ConfiguracaoEmailService $service)
    {
        $dados = $service->dadosParaFormulario();

        return view('configuracoes.email', array_merge($dados, [
            'mailers' => ConfiguracaoEmailService::mailersDisponiveis(),
            'criptografias' => ConfiguracaoEmailService::criptografiasDisponiveis(),
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

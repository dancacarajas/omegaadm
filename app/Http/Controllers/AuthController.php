<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuthEmailService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin(Request $request)
    {
        $redirect = $request->query('redirect');
        if (is_string($redirect) && str_starts_with($redirect, '/') && ! str_starts_with($redirect, '//')) {
            session(['url.intended' => $redirect]);
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');

        if (! Auth::attempt($credentials, $remember)) {
            throw ValidationException::withMessages([
                'email' => 'E-mail ou senha inválidos.',
            ]);
        }

        $request->session()->regenerate();

        $user = $request->user();

        if ($user->status !== 'ativo') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => 'Usuário inativo ou bloqueado. Procure o administrador.',
            ]);
        }

        $user->forceFill(['ultimo_acesso_em' => now()])->save();

        $inicial = $user->urlInicialAposLogin();
        if ($inicial === null) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => 'Este perfil não tem nenhum módulo liberado. Solicite ajuste ao administrador.',
            ]);
        }

        return redirect()->intended($inicial);
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Sessão encerrada com sucesso.');
    }

    public function showForgot()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request, AuthEmailService $authEmail)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::where('email', $request->input('email'))->first();

        if ($user !== null) {
            $token = Password::broker()->createToken($user);
            if (! $authEmail->enviarRecuperacaoSenha($user, $token)) {
                Log::warning('Link de recuperação de senha não enviado por falha de SMTP.', [
                    'email' => $user->email,
                ]);
            }
        }

        return back()->with('success', 'Se o e-mail estiver cadastrado, você receberá um link para redefinir a senha em alguns minutos. Verifique também a caixa de spam.');
    }

    public function showReset(Request $request, string $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email', old('email')),
        ]);
    }

    public function resetPassword(Request $request, AuthEmailService $authEmail)
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email', 'exists:users,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $status = Password::broker()->reset(
            [
                'email' => $data['email'],
                'password' => $data['password'],
                'password_confirmation' => $data['password_confirmation'] ?? $data['password'],
                'token' => $data['token'],
            ],
            function (User $user, string $password) use ($authEmail) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                    'status' => $user->status === 'bloqueado' ? 'ativo' : $user->status,
                ])->save();

                event(new PasswordReset($user));

                if (! $authEmail->enviarSenhaRedefinida($user)) {
                    Log::warning('Confirmação de senha redefinida não enviada por falha de SMTP.', [
                        'email' => $user->email,
                    ]);
                }
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => 'Link inválido ou expirado. Solicite uma nova recuperação de senha.',
            ]);
        }

        return redirect()->route('login')->with('success', 'Senha redefinida com sucesso. Entre com a nova senha.');
    }
}

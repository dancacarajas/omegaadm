<?php

namespace App\Http\Controllers;

use App\Services\EmailAssinaturaJpegService;
use App\Services\EmailAssinaturaService;
use App\Support\Rh\DocumentoBr;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

final class AssinaturaPublicaController extends Controller
{
    private const RATE_MAX = 30;

    private const RATE_DECAY = 60;

    public function index(): View
    {
        return view('publico.assinatura', [
            'localFixo' => EmailAssinaturaService::LOCAL_FIXO,
            'telefonePrefixo' => EmailAssinaturaService::TELEFONE_PREFIXO,
        ]);
    }

    public function consultarCpf(Request $request, EmailAssinaturaService $service): JsonResponse
    {
        if ($resposta = $this->respostaSeExcesso('cpf', $request)) {
            return $resposta;
        }

        $data = $request->validate([
            'cpf' => ['required', 'string', 'max:20'],
        ]);

        $digitos = DocumentoBr::cpfDigitos($data['cpf']);
        if (! DocumentoBr::cpfTemOnzeDigitos($digitos)) {
            return response()->json([
                'message' => 'Informe um CPF válido com 11 dígitos.',
            ], 422);
        }

        $colaborador = $service->buscarColaboradorPorCpf($data['cpf']);

        if ($colaborador === null) {
            return response()->json([
                'encontrado' => false,
            ]);
        }

        return response()->json([
            'encontrado' => true,
            'dados' => $service->dadosDeColaborador($colaborador),
        ]);
    }

    public function jpeg(Request $request, EmailAssinaturaJpegService $jpegService): Response|JsonResponse
    {
        if ($resposta = $this->respostaSeExcesso('jpeg', $request)) {
            return $resposta;
        }

        $data = $request->validate([
            'nome' => ['nullable', 'string', 'max:255'],
            'funcao' => ['nullable', 'string', 'max:255'],
            'contrato' => ['nullable', 'string', 'max:255'],
            'telefone' => ['nullable', 'string', 'max:80'],
            'email' => ['nullable', 'string', 'max:255'],
        ]);

        $normalizado = app(EmailAssinaturaService::class)->normalizar($data);
        $vazio = $normalizado['nome'] === ''
            && $normalizado['funcao'] === ''
            && $normalizado['contrato'] === ''
            && $normalizado['telefone'] === ''
            && $normalizado['email'] === '';

        if ($vazio) {
            return response()->json([
                'message' => 'Preencha ao menos um campo da assinatura antes de baixar.',
            ], 422);
        }

        try {
            $conteudo = $jpegService->render($data);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Não foi possível gerar a assinatura. Tente novamente.',
            ], 500);
        }

        $slug = preg_replace('/[^\w-]+/', '-', strtolower($normalizado['nome'] ?: 'assinatura')) ?: 'assinatura';
        $nomeArquivo = 'assinatura-'.$slug.'.jpg';

        return response($conteudo, 200, [
            'Content-Type' => 'image/jpeg',
            'Content-Disposition' => 'attachment; filename="'.$nomeArquivo.'"',
            'Cache-Control' => 'no-store',
        ]);
    }

    private function respostaSeExcesso(string $acao, Request $request): ?JsonResponse
    {
        $key = 'assinatura-publica:'.$acao.':'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, self::RATE_MAX)) {
            $segundos = RateLimiter::availableIn($key);

            return response()->json([
                'message' => 'Muitas tentativas. Aguarde '.$segundos.' segundos e tente novamente.',
            ], 429);
        }

        RateLimiter::hit($key, self::RATE_DECAY);

        return null;
    }
}

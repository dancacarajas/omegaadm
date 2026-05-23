<?php

namespace App\Http\Controllers;

use App\Models\Colaborador;
use App\Services\EmailAssinaturaJpegService;
use App\Services\EmailAssinaturaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

final class EmailAssinaturaController extends Controller
{
    public function index(): View
    {
        $colaboradores = Colaborador::query()
            ->orderBy('nome')
            ->get(['id', 'nome', 'matricula', 'cargo', 'centro_custo', 'telefone', 'email', 'status']);

        return view('configuracoes.assinatura-eletronica', [
            'colaboradores' => $colaboradores,
            'localFixo' => EmailAssinaturaService::LOCAL_FIXO,
            'telefonePrefixo' => EmailAssinaturaService::TELEFONE_PREFIXO,
            'largura' => EmailAssinaturaService::LARGURA_PX,
            'altura' => EmailAssinaturaService::ALTURA_PX,
        ]);
    }

    public function dadosColaborador(Colaborador $colaborador, EmailAssinaturaService $service): JsonResponse
    {
        return response()->json($service->dadosDeColaborador($colaborador));
    }

    public function preview(Request $request, EmailAssinaturaService $service): JsonResponse
    {
        $data = $request->validate([
            'nome' => ['nullable', 'string', 'max:255'],
            'funcao' => ['nullable', 'string', 'max:255'],
            'contrato' => ['nullable', 'string', 'max:255'],
            'telefone' => ['nullable', 'string', 'max:80'],
            'email' => ['nullable', 'string', 'max:255'],
        ]);

        $html = $service->renderHtml($data);

        return response()->json(['html' => $html]);
    }

    public function jpeg(Request $request, EmailAssinaturaJpegService $jpegService): Response
    {
        $data = $request->validate([
            'nome' => ['nullable', 'string', 'max:255'],
            'funcao' => ['nullable', 'string', 'max:255'],
            'contrato' => ['nullable', 'string', 'max:255'],
            'telefone' => ['nullable', 'string', 'max:80'],
            'email' => ['nullable', 'string', 'max:255'],
        ]);

        $conteudo = $jpegService->render($data);
        $nomeArquivo = 'assinatura-'.now()->format('Ymd_His').'.jpg';

        return response($conteudo, 200, [
            'Content-Type' => 'image/jpeg',
            'Content-Disposition' => 'attachment; filename="'.$nomeArquivo.'"',
            'Cache-Control' => 'no-store',
        ]);
    }
}

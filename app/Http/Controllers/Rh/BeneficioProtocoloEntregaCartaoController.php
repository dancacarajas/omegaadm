<?php

namespace App\Http\Controllers\Rh;

use App\Http\Controllers\Controller;
use App\Models\Beneficio;
use App\Models\ColaboradorBeneficio;
use App\Services\Rh\BeneficioProtocoloEntregaCartaoPdfService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class BeneficioProtocoloEntregaCartaoController extends Controller
{
    public function pdf(
        Request $request,
        Beneficio $beneficio,
        BeneficioProtocoloEntregaCartaoPdfService $pdfService,
    ): Response {
        $data = $request->validate([
            'vinculo_ids' => ['required', 'array', 'min:1'],
            'vinculo_ids.*' => ['integer'],
            'entregador_nome' => ['nullable', 'string', 'max:255'],
            'entregador_funcao' => ['nullable', 'string', 'max:255'],
        ]);

        $ids = array_values(array_unique(array_map('intval', $data['vinculo_ids'])));

        $vinculos = ColaboradorBeneficio::query()
            ->where('beneficio_id', $beneficio->id)
            ->whereIn('id', $ids)
            ->with('colaborador')
            ->get();

        abort_if($vinculos->count() !== count($ids), 422, 'Um ou mais colaboradores selecionados não pertencem a este benefício.');

        $conteudo = $pdfService->render(
            $beneficio,
            $vinculos,
            $data['entregador_nome'] ?? null,
            $data['entregador_funcao'] ?? null,
        );

        $nomeArquivo = $pdfService->nomeArquivo($beneficio, $vinculos->count());

        return response($conteudo, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$nomeArquivo.'"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
            'Pragma' => 'no-cache',
        ]);
    }
}

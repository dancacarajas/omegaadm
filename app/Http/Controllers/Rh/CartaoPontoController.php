<?php

namespace App\Http\Controllers\Rh;

use App\Http\Controllers\Controller;
use App\Models\Colaborador;
use App\Models\Contrato;
use App\Support\ContratoAccess;
use App\Support\Rh\CartaoPontoPeriodo;
use App\Support\Rh\CartaoPontoService;
use App\Support\Rh\ColaboradorQueryPorContratoPainel;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CartaoPontoController extends Controller
{
    public function colaboradores(Request $request): JsonResponse
    {
        $contratoToken = trim((string) $request->get('contrato', ''));
        abort_if($contratoToken === '', 422, 'Contrato obrigatório.');

        $identificadores = $this->identificadoresContrato($contratoToken);

        $lista = Colaborador::query()
            ->where('status', 'ativo')
            ->tap(fn ($q) => ColaboradorQueryPorContratoPainel::aplicar($q, $identificadores))
            ->orderBy('nome')
            ->get(['id', 'nome', 'matricula', 'cargo'])
            ->map(fn (Colaborador $c) => [
                'id' => $c->id,
                'nome' => $c->nome,
                'matricula' => $c->matricula,
                'cargo' => $c->cargo,
                'label' => trim($c->nome.' · '.($c->matricula ?: 's/ matrícula')),
            ]);

        return response()->json(['colaboradores' => $lista]);
    }

    public function pdf(Request $request): Response
    {
        $validated = $request->validate([
            'contrato' => ['required', 'string', 'max:120'],
            'data_inicio' => ['required', 'date'],
            'data_fim' => ['required', 'date', 'after_or_equal:data_inicio'],
            'escopo' => ['required', 'in:contrato,colaborador,selecionados'],
            'colaborador_id' => ['nullable', 'integer', 'exists:colaboradores,id'],
            'colaborador_ids' => ['nullable', 'array'],
            'colaborador_ids.*' => ['integer', 'exists:colaboradores,id'],
        ]);

        $identificadores = $this->identificadoresContrato($validated['contrato']);

        $query = Colaborador::query()
            ->where('status', 'ativo')
            ->with(['horarioEscala.dias'])
            ->orderBy('nome');

        ColaboradorQueryPorContratoPainel::aplicar($query, $identificadores);

        match ($validated['escopo']) {
            'colaborador' => $query->whereKey($validated['colaborador_id'] ?? 0),
            'selecionados' => $query->whereIn('id', $validated['colaborador_ids'] ?? []),
            default => null,
        };

        $colaboradores = $query->get();

        if ($colaboradores->isEmpty()) {
            return back()->with('error', 'Nenhum colaborador ativo encontrado para os filtros informados.');
        }

        $inicio = Carbon::parse($validated['data_inicio']);
        $fim = Carbon::parse($validated['data_fim']);

        $cartoes = app(CartaoPontoService::class)->montarCartoes(
            $colaboradores,
            $inicio->toDateString(),
            $fim->toDateString()
        );

        $totalPaginas = count($cartoes);
        $geradoEm = now();

        return $this->downloadPdf('rh.frequencia.pdf.cartao-ponto', [
            'cartoes' => $cartoes,
            'periodoTitulo' => 'DE '.$inicio->format('d/m/Y').' ATÉ '.$fim->format('d/m/Y'),
            'empresaRazao' => config('frequencia.empresa.razao_social'),
            'empresaFantasia' => config('frequencia.empresa.nome_fantasia'),
            'logo' => $this->logoBase64(),
            'geradoEm' => $geradoEm,
            'totalPaginas' => $totalPaginas,
        ], 'cartao-ponto-'.$inicio->format('Ymd').'-'.$fim->format('Ymd').'.pdf');
    }

    /**
     * @return list<string>
     */
    private function identificadoresContrato(string $contratoToken): array
    {
        $contratos = ContratoAccess::applyContratoModel(Contrato::query())->get();

        $contratoModel = $contratos->first(function (Contrato $c) use ($contratoToken) {
            foreach ([$c->centro_custo, $c->numero, $c->nome] as $campo) {
                if (trim((string) $campo) === $contratoToken) {
                    return true;
                }
            }

            return false;
        });

        abort_unless($contratoModel, 404);

        if (ContratoAccess::shouldRestrict()) {
            abort_unless(in_array($contratoToken, ContratoAccess::contratoValores(), true), 404);
        }

        return collect([$contratoModel->centro_custo, $contratoModel->numero, $contratoModel->nome])
            ->map(fn ($v) => trim((string) $v))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function downloadPdf(string $view, array $data, string $filename): Response
    {
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);

        $pdf = new Dompdf($options);
        $pdf->loadHtml(view($view, $data)->render(), 'UTF-8');
        $pdf->setPaper('letter', 'portrait');
        $pdf->render();

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    private function logoBase64(): ?string
    {
        $path = public_path('logo.png');
        if (! is_file($path)) {
            return null;
        }

        return 'data:image/png;base64,'.base64_encode(file_get_contents($path));
    }
}

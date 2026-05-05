<?php

namespace App\Http\Controllers;

use App\Models\MedicaoContratualItem;
use App\Support\ContratoAccess;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MedicaoContratualController extends Controller
{
    public function index(Request $request)
    {
        $mes = $request->input('mes', now()->format('Y-m'));
        $inicio = Carbon::createFromFormat('Y-m', $mes)->startOfMonth();
        $fim = $inicio->copy()->endOfMonth();

        $query = ContratoAccess::applyContratoString(
            MedicaoContratualItem::query()->whereBetween('competencia', [$inicio->toDateString(), $fim->toDateString()]),
            'contrato'
        )->when($request->input('busca'), function ($q, $busca) {
            $q->where(function ($sub) use ($busca) {
                $sub->where('item_contratual', 'like', "%{$busca}%")
                    ->orWhere('descricao', 'like', "%{$busca}%")
                    ->orWhere('justificativa', 'like', "%{$busca}%")
                    ->orWhere('contrato', 'like', "%{$busca}%");
            });
        });

        $itens = (clone $query)->orderByDesc('competencia')->paginate(12)->withQueryString();
        $base = (clone $query)->get();

        $indicadores = [
            'receita_prevista' => (float) $base->sum('valor_previsto'),
            'receita_medida' => (float) $base->sum('valor_medido'),
            'glosado' => (float) $base->sum('valor_glosado'),
            'nao_executado' => (float) $base->sum('valor_nao_executado'),
            'executado_nao_medido' => (float) $base->sum('valor_executado_nao_medido'),
            'hora_extra' => (float) $base->sum('valor_hora_extra'),
            'adicionais' => (float) $base->sum('valor_adicional'),
            'mobilizacao' => (float) $base->sum('valor_mobilizacao'),
            'nao_programado' => (float) $base->sum('valor_nao_programado'),
        ];

        return view('medicao.contratual.index', compact('itens', 'indicadores', 'mes'));
    }

    public function create()
    {
        return view('medicao.contratual.create', [
            'item' => new MedicaoContratualItem(['competencia' => now()->startOfMonth()->toDateString()]),
        ]);
    }

    public function store(Request $request)
    {
        MedicaoContratualItem::create($this->validated($request));

        return redirect()
            ->route('medicao.contratual.index')
            ->with('success', 'Item de medição contratual cadastrado.');
    }

    public function edit(MedicaoContratualItem $contratual)
    {
        $this->authorizeContrato($contratual->contrato);

        return view('medicao.contratual.edit', ['item' => $contratual]);
    }

    public function update(Request $request, MedicaoContratualItem $contratual)
    {
        $this->authorizeContrato($contratual->contrato);
        $contratual->update($this->validated($request, $contratual));

        return redirect()
            ->route('medicao.contratual.index')
            ->with('success', 'Item de medição contratual atualizado.');
    }

    public function destroy(MedicaoContratualItem $contratual)
    {
        $this->authorizeContrato($contratual->contrato);
        if ($contratual->evidencia_path) {
            Storage::disk('public')->delete($contratual->evidencia_path);
        }
        $contratual->delete();

        return redirect()
            ->route('medicao.contratual.index')
            ->with('success', 'Item de medição contratual removido.');
    }

    private function validated(Request $request, ?MedicaoContratualItem $item = null): array
    {
        $data = $request->validate([
            'competencia' => ['required', 'date'],
            'contrato' => ['nullable', 'string', 'max:255'],
            'item_contratual' => ['required', 'string', 'max:255'],
            'descricao' => ['nullable', 'string', 'max:255'],
            'valor_unitario_previsto' => ['nullable', 'numeric', 'min:0'],
            'quantidade_prevista' => ['nullable', 'numeric', 'min:0'],
            'valor_previsto' => ['nullable', 'numeric', 'min:0'],
            'quantidade_medida' => ['nullable', 'numeric', 'min:0'],
            'valor_medido' => ['nullable', 'numeric', 'min:0'],
            'diferenca' => ['nullable', 'numeric'],
            'desvio_percentual' => ['nullable', 'numeric'],
            'justificativa' => ['nullable', 'string'],
            'evidencia' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx,csv', 'max:10240'],
            'valor_glosado' => ['nullable', 'numeric', 'min:0'],
            'valor_nao_executado' => ['nullable', 'numeric', 'min:0'],
            'valor_executado_nao_medido' => ['nullable', 'numeric', 'min:0'],
            'valor_hora_extra' => ['nullable', 'numeric', 'min:0'],
            'valor_adicional' => ['nullable', 'numeric', 'min:0'],
            'valor_mobilizacao' => ['nullable', 'numeric', 'min:0'],
            'valor_nao_programado' => ['nullable', 'numeric', 'min:0'],
        ]);

        foreach ([
            'valor_unitario_previsto', 'quantidade_prevista', 'valor_previsto', 'quantidade_medida', 'valor_medido',
            'valor_glosado', 'valor_nao_executado', 'valor_executado_nao_medido', 'valor_hora_extra', 'valor_adicional',
            'valor_mobilizacao', 'valor_nao_programado',
        ] as $field) {
            $data[$field] = (float) ($data[$field] ?? 0);
        }

        if (($data['valor_previsto'] ?? 0) == 0 && ($data['valor_unitario_previsto'] ?? 0) > 0 && ($data['quantidade_prevista'] ?? 0) > 0) {
            $data['valor_previsto'] = $data['valor_unitario_previsto'] * $data['quantidade_prevista'];
        }
        if (blank($data['diferenca'] ?? null)) {
            $data['diferenca'] = (float) $data['valor_medido'] - (float) $data['valor_previsto'];
        }
        if (blank($data['desvio_percentual'] ?? null)) {
            $base = (float) $data['valor_previsto'];
            $data['desvio_percentual'] = $base > 0 ? (($data['diferenca'] / $base) * 100) : 0;
        }

        if ($request->hasFile('evidencia')) {
            if ($item?->evidencia_path) {
                Storage::disk('public')->delete($item->evidencia_path);
            }
            $data['evidencia_path'] = $request->file('evidencia')->store('medicao/contratual/evidencias', 'public');
        }
        unset($data['evidencia']);

        return $data;
    }

    private function authorizeContrato(?string $contrato): void
    {
        if (! ContratoAccess::shouldRestrict()) {
            return;
        }
        abort_unless($contrato && in_array($contrato, ContratoAccess::contratoValores(), true), 404);
    }
}

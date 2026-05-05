<?php

namespace App\Http\Controllers;

use App\Models\VeiculoManutencao;
use App\Models\VeiculoSolicitacao;
use App\Support\ContratoAccess;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VeiculoManutencaoController extends Controller
{
    public function create()
    {
        return view('veiculos.manutencoes.create', [
            'manutencao' => new VeiculoManutencao([
                'data_solicitacao' => now()->toDateString(),
                'status' => 'aberto',
                'motivo' => 'preventiva',
            ]),
            'solicitacoes' => $this->solicitacoes(),
        ]);
    }

    public function store(Request $request)
    {
        VeiculoManutencao::create($this->validated($request));

        return redirect()
            ->route('veiculos.frota.index')
            ->with('success', 'Manutenção registrada.');
    }

    public function edit(VeiculoManutencao $manutencao)
    {
        $this->authorizeContrato($manutencao->contrato);

        return view('veiculos.manutencoes.edit', [
            'manutencao' => $manutencao,
            'solicitacoes' => $this->solicitacoes(),
        ]);
    }

    public function update(Request $request, VeiculoManutencao $manutencao)
    {
        $this->authorizeContrato($manutencao->contrato);
        $manutencao->update($this->validated($request, $manutencao));

        return redirect()
            ->route('veiculos.frota.index')
            ->with('success', 'Manutenção atualizada.');
    }

    public function destroy(VeiculoManutencao $manutencao)
    {
        $this->authorizeContrato($manutencao->contrato);
        if ($manutencao->evidencia_path) {
            Storage::disk('public')->delete($manutencao->evidencia_path);
        }
        $manutencao->delete();

        return redirect()
            ->route('veiculos.frota.index')
            ->with('success', 'Manutenção removida.');
    }

    private function solicitacoes()
    {
        return ContratoAccess::applyContratoString(
            VeiculoSolicitacao::query()->where('status', 'concluido')->whereNotNull('placa'),
            'contrato'
        )->orderByDesc('id')->get(['id', 'placa', 'marca', 'modelo', 'tipo', 'contrato']);
    }

    private function validated(Request $request, ?VeiculoManutencao $manutencao = null): array
    {
        $data = $request->validate([
            'veiculo_solicitacao_id' => ['nullable', 'exists:veiculo_solicitacoes,id'],
            'contrato' => ['nullable', 'string', 'max:255'],
            'veiculo_equipamento' => ['required', 'string', 'max:255'],
            'placa_tag' => ['nullable', 'string', 'max:60'],
            'tipo' => ['nullable', 'string', 'max:120'],
            'data_solicitacao' => ['required', 'date'],
            'responsavel_solicitacao' => ['nullable', 'string', 'max:255'],
            'motivo' => ['required', 'in:preventiva,corretiva,falha,quebra'],
            'data_envio' => ['nullable', 'date'],
            'data_retorno' => ['nullable', 'date'],
            'dias_parado' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'in:aberto,em_andamento,concluido'],
            'evidencia' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx,csv', 'max:10240'],
            'impacto_operacao' => ['nullable', 'string'],
            'impacto_financeiro' => ['nullable', 'numeric', 'min:0'],
            'observacao' => ['nullable', 'string'],
        ]);

        if (blank($data['dias_parado'] ?? null) && filled($data['data_envio'] ?? null) && filled($data['data_retorno'] ?? null)) {
            $envio = Carbon::parse($data['data_envio']);
            $retorno = Carbon::parse($data['data_retorno']);
            $data['dias_parado'] = max(0, $envio->diffInDays($retorno));
        }
        $data['dias_parado'] = (int) ($data['dias_parado'] ?? 0);

        if ($request->hasFile('evidencia')) {
            if ($manutencao?->evidencia_path) {
                Storage::disk('public')->delete($manutencao->evidencia_path);
            }
            $data['evidencia_path'] = $request->file('evidencia')->store('veiculos/manutencoes/evidencias', 'public');
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

<?php

namespace App\Http\Controllers\Rh;

use App\Http\Controllers\Controller;
use App\Models\Beneficio;
use App\Models\Colaborador;
use App\Models\ColaboradorBeneficio;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BeneficioColaboradorController extends Controller
{
    public function store(Request $request, Beneficio $beneficio)
    {
        $data = $this->validatedData($request, $beneficio);
        $data['beneficio_id'] = $beneficio->id;
        $data['data_direito'] = Colaborador::findOrFail($data['colaborador_id'])->data_admissao;

        ColaboradorBeneficio::create($data);

        return redirect()
            ->route('rh.beneficios.show', $beneficio)
            ->with('success', 'Colaborador vinculado ao beneficio com sucesso.');
    }

    public function update(Request $request, Beneficio $beneficio, ColaboradorBeneficio $vinculo)
    {
        abort_unless($vinculo->beneficio_id === $beneficio->id, 404);

        $vinculo->update($this->validatedData($request, $beneficio, $vinculo));

        return redirect()
            ->route('rh.beneficios.show', $beneficio)
            ->with('success', 'Situacao do beneficio atualizada.');
    }

    public function destroy(Beneficio $beneficio, ColaboradorBeneficio $vinculo)
    {
        abort_unless($vinculo->beneficio_id === $beneficio->id, 404);

        $vinculo->delete();

        return redirect()
            ->route('rh.beneficios.show', $beneficio)
            ->with('success', 'Vinculo removido do beneficio.');
    }

    private function validatedData(Request $request, Beneficio $beneficio, ?ColaboradorBeneficio $vinculo = null): array
    {
        return $request->validate([
            'colaborador_id' => [
                $vinculo ? 'sometimes' : 'required',
                'integer',
                Rule::exists('colaboradores', 'id'),
                Rule::unique('colaborador_beneficios', 'colaborador_id')
                    ->where('beneficio_id', $beneficio->id)
                    ->ignore($vinculo),
            ],
            'tem_direito' => ['sometimes', 'boolean'],
            'cartao_entregue' => ['sometimes', 'boolean'],
            'beneficio_ativo' => ['sometimes', 'boolean'],
            'data_entrega_cartao' => ['nullable', 'date'],
            'numero_cartao' => ['nullable', 'string', 'max:255'],
            'observacoes' => ['nullable', 'string'],
        ]);
    }
}

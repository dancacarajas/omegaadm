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
        $colaborador = Colaborador::query()->findOrFail($data['colaborador_id']);

        ColaboradorBeneficio::create([
            ...$data,
            'beneficio_id' => $beneficio->id,
            'data_direito' => $colaborador->data_admissao,
            'tem_direito' => $request->boolean('tem_direito'),
            'cartao_entregue' => $request->boolean('cartao_entregue'),
            'beneficio_ativo' => $request->boolean('beneficio_ativo'),
        ]);

        return $this->redirectAposAcao($beneficio)
            ->with('success', 'Colaborador vinculado ao beneficio com sucesso.');
    }

    public function update(Request $request, Beneficio $beneficio, ColaboradorBeneficio $vinculo)
    {
        abort_unless($vinculo->beneficio_id === $beneficio->id, 404);

        $payload = $this->validatedData($request, $beneficio, $vinculo);
        $vinculo->update([
            ...$payload,
            'tem_direito' => $request->boolean('tem_direito'),
            'cartao_entregue' => $request->boolean('cartao_entregue'),
            'beneficio_ativo' => $request->boolean('beneficio_ativo'),
        ]);

        return $this->redirectAposAcao($beneficio)
            ->with('success', 'Situacao do beneficio atualizada.');
    }

    public function destroy(Beneficio $beneficio, ColaboradorBeneficio $vinculo)
    {
        abort_unless($vinculo->beneficio_id === $beneficio->id, 404);

        $vinculo->delete();

        return $this->redirectAposAcao($beneficio)
            ->with('success', 'Vinculo removido do beneficio.');
    }

    private function redirectAposAcao(Beneficio $beneficio): \Illuminate\Http\RedirectResponse
    {
        $anterior = url()->previous();
        $destino = route('rh.beneficios.show', $beneficio);

        if ($anterior !== '' && $anterior !== url()->current() && str_contains($anterior, '/rh/beneficios/')) {
            return redirect()->to($anterior);
        }

        return redirect()->to($destino);
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

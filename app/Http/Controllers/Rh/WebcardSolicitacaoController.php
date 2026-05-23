<?php

namespace App\Http\Controllers\Rh;

use App\Http\Controllers\Controller;
use App\Models\Beneficio;
use App\Models\ColaboradorBeneficio;
use App\Models\ColaboradorBeneficioWebcardSolicitacao;
use App\Support\Rh\WebcardRegraConfig;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class WebcardSolicitacaoController extends Controller
{
    public function store(Request $request, Beneficio $beneficio)
    {
        abort_unless($beneficio->status === 'ativo', 404);

        $data = $request->validate([
            'colaborador_beneficio_id' => ['required', 'integer', 'exists:colaborador_beneficios,id'],
            'data_solicitacao' => ['required', 'date'],
            'valor' => ['required', 'numeric', 'min:0.01'],
            'observacao' => ['nullable', 'string', 'max:500'],
        ]);

        $vinculo = ColaboradorBeneficio::query()
            ->where('beneficio_id', $beneficio->id)
            ->whereKey($data['colaborador_beneficio_id'])
            ->with('colaborador')
            ->firstOrFail();

        $config = $this->configParaBeneficio($beneficio);
        $valor = round((float) $data['valor'], 2);
        $salario = filled($vinculo->colaborador?->salario_inicial)
            ? (float) $vinculo->colaborador->salario_inicial
            : null;
        $limiteSolicitacao = $config->limitePorSolicitacaoParaSalario($salario);

        if ($salario === null || $salario <= 0) {
            throw ValidationException::withMessages([
                'valor' => 'Cadastre o salário do colaborador na ficha do efetivo para calcular o limite do WebCard.',
            ]);
        }

        if ($valor > $limiteSolicitacao + 0.001) {
            throw ValidationException::withMessages([
                'valor' => 'Valor máximo por solicitação: '.number_format($config->percentualLimitePorSolicitacao(), 0, ',', '.').'% do salário (R$ '.number_format($limiteSolicitacao, 2, ',', '.').').',
            ]);
        }

        $dataSol = Carbon::parse($data['data_solicitacao']);
        $usadoMes = (float) ColaboradorBeneficioWebcardSolicitacao::query()
            ->where('colaborador_beneficio_id', $vinculo->id)
            ->whereBetween('data_solicitacao', [
                $dataSol->copy()->startOfMonth()->toDateString(),
                $dataSol->copy()->endOfMonth()->toDateString(),
            ])
            ->sum('valor');

        if ($usadoMes + $valor > $config->limiteMensal() + 0.001) {
            $disponivel = max(0, $config->limiteMensal() - $usadoMes);
            throw ValidationException::withMessages([
                'valor' => 'Limite mensal de R$ '.number_format($config->limiteMensal(), 2, ',', '.').' — disponível neste mês: R$ '.number_format($disponivel, 2, ',', '.').'.',
            ]);
        }

        ColaboradorBeneficioWebcardSolicitacao::query()->create([
            'colaborador_beneficio_id' => $vinculo->id,
            'data_solicitacao' => $dataSol->toDateString(),
            'valor' => $valor,
            'observacao' => $data['observacao'] ?? null,
            'registrado_por_id' => $request->user()?->id,
        ]);

        return redirect()
            ->route('rh.beneficios.show', $beneficio)
            ->with('success', 'Solicitação WebCard registrada. Será descontada na folha de '.$dataSol->format('m/Y').'.');
    }

    public function destroy(Beneficio $beneficio, ColaboradorBeneficioWebcardSolicitacao $solicitacao)
    {
        $vinculo = $solicitacao->vinculo;
        abort_unless($vinculo && (int) $vinculo->beneficio_id === (int) $beneficio->id, 404);

        $solicitacao->delete();

        return redirect()
            ->route('rh.beneficios.show', $beneficio)
            ->with('success', 'Solicitação WebCard removida.');
    }

    private function configParaBeneficio(Beneficio $beneficio): WebcardRegraConfig
    {
        $regra = $beneficio->extratoRegra()->first();

        return $regra?->configWebcard() ?? WebcardRegraConfig::resolver(null);
    }
}

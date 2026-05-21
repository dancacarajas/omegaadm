<?php

namespace App\Http\Controllers\Rh;

use App\Http\Controllers\Controller;
use App\Models\Beneficio;
use App\Models\BeneficioExtratoRegra;
use App\Models\Colaborador;
use App\Services\Rh\BeneficioExtratoCalculoService;
use App\Support\Rh\CafeDaManhaRegraConfig;
use App\Support\Rh\ValeAlimentacaoRegraConfig;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BeneficioExtratoController extends Controller
{
    public function config()
    {
        $beneficios = Beneficio::query()
            ->where('status', 'ativo')
            ->orderBy('nome')
            ->get();

        $regrasPorBeneficio = BeneficioExtratoRegra::query()
            ->get()
            ->keyBy('beneficio_id');

        return view('rh.beneficios.extrato.config', compact('beneficios', 'regrasPorBeneficio'));
    }

    public function salvarConfig(Request $request)
    {
        $request->validate([
            'beneficios' => ['nullable', 'array'],
            'beneficios.*.ativo' => ['nullable', 'boolean'],
            'beneficios.*.tipo_regra' => ['nullable', 'string', Rule::in(BeneficioExtratoRegra::TIPOS)],
        ]);

        $idsAtivos = Beneficio::query()->where('status', 'ativo')->pluck('id');
        $selecionadosIds = [];
        $payload = $request->input('beneficios', []);

        foreach ($idsAtivos as $beneficioId) {
            if (! $request->boolean("beneficios.{$beneficioId}.ativo")) {
                continue;
            }

            $beneficio = Beneficio::query()->find($beneficioId);
            if ($beneficio === null) {
                continue;
            }

            $item = $payload[$beneficioId] ?? $payload[(string) $beneficioId] ?? [];
            $tipo = (string) ($item['tipo_regra'] ?? BeneficioExtratoRegra::inferirTipoRegra($beneficio));
            if (! in_array($tipo, BeneficioExtratoRegra::TIPOS, true)) {
                $tipo = BeneficioExtratoRegra::inferirTipoRegra($beneficio);
            }
            $padroes = match ($tipo) {
                BeneficioExtratoRegra::TIPO_ASSIDUIDADE => ValeAlimentacaoRegraConfig::padroes(),
                BeneficioExtratoRegra::TIPO_CAFE_MANHA => CafeDaManhaRegraConfig::padroes(),
                default => [],
            };

            $regra = BeneficioExtratoRegra::query()->firstOrNew(['beneficio_id' => (int) $beneficioId]);
            $tipoAnterior = $regra->tipo_regra;
            $regra->tipo_regra = $tipo;
            $regra->ativo = true;

            if (in_array($tipo, [BeneficioExtratoRegra::TIPO_ASSIDUIDADE, BeneficioExtratoRegra::TIPO_CAFE_MANHA], true)) {
                if ($tipoAnterior !== $tipo || empty($regra->parametros)) {
                    $regra->parametros = $padroes;
                    $regra->ano_vigencia = (int) ($padroes['ano_vigencia'] ?? date('Y'));
                    $regra->configurado = false;
                }
            }

            if ($tipo === BeneficioExtratoRegra::TIPO_VALOR_FIXO) {
                $regra->configurado = true;
                $regra->parametros = [];
                $regra->ano_vigencia = null;
            }
            $regra->save();

            $selecionadosIds[] = (int) $beneficioId;
        }

        BeneficioExtratoRegra::query()
            ->when($selecionadosIds !== [], fn ($q) => $q->whereNotIn('beneficio_id', $selecionadosIds))
            ->delete();

        if ($selecionadosIds === []) {
            return redirect()
                ->route('rh.beneficios.extrato.config')
                ->withErrors(['beneficios' => 'Selecione ao menos um benefício para gerar extrato.'])
                ->withInput();
        }

        return redirect()
            ->route('rh.beneficios.extrato.regras')
            ->with('success', 'Benefícios selecionados. Configure as regras de cada um antes de gerar o extrato.');
    }

    public function regras()
    {
        $regras = BeneficioExtratoRegra::query()
            ->where('ativo', true)
            ->with('beneficio')
            ->orderBy('id')
            ->get();

        if ($regras->isEmpty()) {
            return redirect()
                ->route('rh.beneficios.extrato.config')
                ->withErrors(['config' => 'Selecione os benefícios que entrarão no extrato.']);
        }

        return view('rh.beneficios.extrato.regras', compact('regras'));
    }

    public function salvarRegraBeneficio(Request $request, Beneficio $beneficio)
    {
        $regra = BeneficioExtratoRegra::query()
            ->where('beneficio_id', $beneficio->id)
            ->where('ativo', true)
            ->firstOrFail();

        if ($regra->tipo_regra === BeneficioExtratoRegra::TIPO_VALOR_FIXO) {
            $regra->update(['configurado' => true]);

            return redirect()
                ->route('rh.beneficios.extrato.regras')
                ->with('success', 'Benefício marcado como configurado.');
        }

        if ($regra->tipo_regra === BeneficioExtratoRegra::TIPO_CAFE_MANHA) {
            return $this->salvarRegraCafeDaManha($request, $beneficio, $regra);
        }

        $request->validate([
            'ano_vigencia' => ['required', 'integer', 'min:2020', 'max:2100'],
            'desconto_faltas' => ['required', 'array', 'min:1'],
            'desconto_faltas.*.de' => ['required', 'integer', 'min:1'],
            'desconto_faltas.*.ate' => ['nullable', 'integer', 'min:1'],
            'desconto_faltas.*.percentual' => ['required', 'numeric', 'min:0', 'max:100'],
            'proporcional_admissao_demissao' => ['nullable', 'boolean'],
            'afastamento_acidente_trabalho.ativo' => ['nullable', 'boolean'],
            'afastamento_acidente_trabalho.meses_limite_integral' => ['nullable', 'integer', 'min:1', 'max:24'],
            'recarga_natal.ativo' => ['nullable', 'boolean'],
            'recarga_natal.valor_integral' => ['nullable', 'numeric', 'min:0'],
            'recarga_natal.data_pagamento_limite' => ['nullable', 'string'],
            'recarga_natal.periodo_atestados_inicio' => ['nullable', 'string'],
            'recarga_natal.periodo_atestados_fim' => ['nullable', 'string'],
            'recarga_natal.faixas_atestados' => ['nullable', 'array'],
            'recarga_natal.faixas_atestados.*.de' => ['nullable', 'integer', 'min:0'],
            'recarga_natal.faixas_atestados.*.ate' => ['nullable', 'integer', 'min:0'],
            'recarga_natal.faixas_atestados.*.percentual_valor' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'recarga_natal.perda_uma_falta_injustificada_percentual' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'recarga_natal.exige_sindicalizado' => ['nullable', 'boolean'],
            'recarga_natal.cargos_excluidos_texto' => ['nullable', 'string'],
        ]);

        $parametros = ValeAlimentacaoRegraConfig::mesclar(
            ValeAlimentacaoRegraConfig::padroes((int) $request->input('ano_vigencia')),
            [
                'ano_vigencia' => (int) $request->input('ano_vigencia'),
                'desconto_faltas' => $request->input('desconto_faltas', []),
                'proporcional_admissao_demissao' => $request->boolean('proporcional_admissao_demissao'),
                'afastamento_acidente_trabalho' => $request->input('afastamento_acidente_trabalho', []),
                'recarga_natal' => $request->input('recarga_natal', []),
            ]
        );

        $regra->update([
            'ano_vigencia' => (int) $parametros['ano_vigencia'],
            'parametros' => $parametros,
            'configurado' => true,
        ]);

        return redirect()
            ->route('rh.beneficios.extrato.regras')
            ->with('success', "Regras de «{$beneficio->nome}» salvas para vigência {$regra->ano_vigencia}.");
    }

    private function salvarRegraCafeDaManha(Request $request, Beneficio $beneficio, BeneficioExtratoRegra $regra)
    {
        $request->validate([
            'ano_vigencia' => ['required', 'integer', 'min:2020', 'max:2100'],
            'valor_mensal_cheio' => ['required', 'numeric', 'min:0'],
            'valor_diario' => ['required', 'numeric', 'min:0'],
            'valor_diario_fds_feriado' => ['nullable', 'numeric', 'min:0'],
            'periodo_vigencia_inicio' => ['required', 'string'],
            'periodo_vigencia_fim' => ['required', 'string'],
            'minutos_minimos_dia_trabalhado' => ['nullable', 'integer', 'min:1'],
            'teto_mensal_ativo' => ['nullable', 'boolean'],
        ]);

        $parametros = CafeDaManhaRegraConfig::mesclar(
            CafeDaManhaRegraConfig::padroes((int) $request->input('ano_vigencia')),
            [
                'ano_vigencia' => (int) $request->input('ano_vigencia'),
                'valor_mensal_cheio' => $request->input('valor_mensal_cheio'),
                'valor_diario' => $request->input('valor_diario'),
                'valor_diario_fds_feriado' => $request->input('valor_diario_fds_feriado'),
                'periodo_vigencia_inicio' => $request->input('periodo_vigencia_inicio'),
                'periodo_vigencia_fim' => $request->input('periodo_vigencia_fim'),
                'minutos_minimos_dia_trabalhado' => $request->input('minutos_minimos_dia_trabalhado', 1),
                'teto_mensal_ativo' => $request->boolean('teto_mensal_ativo'),
            ]
        );

        $regra->update([
            'ano_vigencia' => (int) $parametros['ano_vigencia'],
            'parametros' => $parametros,
            'configurado' => true,
        ]);

        return redirect()
            ->route('rh.beneficios.extrato.regras')
            ->with('success', "Regras de café da manhã «{$beneficio->nome}» salvas (vigência {$regra->ano_vigencia}).");
    }

    public function gerar(Request $request, BeneficioExtratoCalculoService $calculo)
    {
        $regras = $calculo->regrasAtivas();

        if ($regras->isEmpty()) {
            return redirect()
                ->route('rh.beneficios.extrato.config')
                ->withErrors(['config' => 'Configure ao menos um benefício antes de gerar o extrato.']);
        }

        $pendentes = $regras->where('configurado', false);
        if ($pendentes->isNotEmpty() && ! $request->boolean('forcar')) {
            return redirect()
                ->route('rh.beneficios.extrato.regras')
                ->withErrors([
                    'config' => 'Configure todos os benefícios antes de gerar: '.$pendentes->map(fn ($r) => $r->beneficio?->nome)->filter()->join(', '),
                ]);
        }

        [$periodoInicio, $periodoFim] = $this->periodoExtrato($request);
        $colaboradorId = (int) $request->input('colaborador_id', 0);

        $colaboradores = Colaborador::query()
            ->orderBy('nome')
            ->get(['id', 'nome', 'cargo', 'matricula']);

        $extrato = null;
        $colaborador = null;

        if ($colaboradorId > 0) {
            $colaborador = Colaborador::query()->find($colaboradorId);
            if ($colaborador !== null) {
                $extrato = $calculo->montarExtratoColaborador(
                    $colaborador,
                    $periodoInicio,
                    $periodoFim,
                    $regras
                );
            }
        }

        return view('rh.beneficios.extrato.gerar', compact(
            'regras',
            'colaboradores',
            'colaborador',
            'colaboradorId',
            'periodoInicio',
            'periodoFim',
            'extrato'
        ));
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function periodoExtrato(Request $request): array
    {
        $hoje = Carbon::now();
        $inicioPadrao = $hoje->copy()->startOfMonth();
        $fimPadrao = $hoje->copy();

        $inicio = $this->parseDataBrasileira($request->input('periodo_inicio'), $inicioPadrao);
        $fim = $this->parseDataBrasileira($request->input('periodo_fim'), $fimPadrao);

        if ($fim->lt($inicio)) {
            [$inicio, $fim] = [$fim, $inicio];
        }

        return [$inicio, $fim];
    }

    private function parseDataBrasileira(mixed $valor, Carbon $padrao): Carbon
    {
        $texto = trim((string) $valor);
        if ($texto === '') {
            return $padrao->copy()->startOfDay();
        }

        $digitos = preg_replace('/\D/', '', $texto) ?? '';
        if (strlen($digitos) === 8) {
            $texto = substr($digitos, 0, 2).'/'.substr($digitos, 2, 2).'/'.substr($digitos, 4, 4);
        }

        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $texto)) {
            try {
                return Carbon::createFromFormat('d/m/Y', $texto)->startOfDay();
            } catch (\Exception) {
                return $padrao->copy()->startOfDay();
            }
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $texto)) {
            return Carbon::parse($texto)->startOfDay();
        }

        return $padrao->copy()->startOfDay();
    }
}

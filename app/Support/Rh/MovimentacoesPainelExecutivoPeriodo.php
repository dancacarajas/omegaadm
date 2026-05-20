<?php

namespace App\Support\Rh;

use App\Models\Colaborador;
use App\Models\ColaboradorMovimentacao;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Consolida movimentações do período a partir de colaborador_movimentacoes
 * e datas de admissão/desligamento no cadastro (retrocompatível).
 */
final class MovimentacoesPainelExecutivoPeriodo
{
    /**
     * @param  list<string>  $identificadoresContrato
     * @return array{
     *     admitidos: int,
     *     desligados: int,
     *     transferencia_entrada: int,
     *     transferencia_saida: int,
     *     promocoes: int,
     *     mudanca_funcao: int,
     *     ferias: int,
     *     afastamento_inss: int,
     *     total_eventos: int,
     *     motivos: list<array{label: string, value: int, icon: string}>,
     *     transferencias: array{entrada: int, saida: int}
     * }
     */
    public static function resumo(array $identificadoresContrato, Carbon $periodoInicio, Carbon $periodoFim): array
    {
        $ini = $periodoInicio->copy()->startOfDay()->toDateString();
        $fim = $periodoFim->copy()->endOfDay()->toDateString();

        $transferencias = TransferenciasEfetivoPeriodo::resumo($identificadoresContrato, $periodoInicio, $periodoFim);

        $colaboradorIds = self::idsColaboradoresContrato($identificadoresContrato);

        $movimentacoes = ColaboradorMovimentacao::query()
            ->when($colaboradorIds !== [], fn ($q) => $q->whereIn('colaborador_id', $colaboradorIds))
            ->whereDate('data_inicio', '>=', $ini)
            ->whereDate('data_inicio', '<=', $fim)
            ->get();

        $porTipo = $movimentacoes->groupBy('tipo');

        $idsDesligMov = $porTipo
            ->get(ColaboradorMovimentacaoTipos::DESLIGAMENTO, collect())
            ->pluck('colaborador_id');

        $idsDesligData = self::idsDesligadosPorDataDemissao($identificadoresContrato, $ini, $fim);

        $desligados = $idsDesligMov->merge($idsDesligData)->unique()->count();

        $admitidos = self::contarAdmitidosPorData($identificadoresContrato, $ini, $fim);

        $promocoes = (int) $porTipo->get(ColaboradorMovimentacaoTipos::PROMOCAO, collect())->count();
        $mudancaFuncao = (int) $porTipo->get(ColaboradorMovimentacaoTipos::MUDANCA_FUNCAO, collect())->count();
        $ferias = (int) $porTipo->get(ColaboradorMovimentacaoTipos::FERIAS, collect())->count();
        $afastamentoInss = (int) $porTipo->get(ColaboradorMovimentacaoTipos::AFASTAMENTO_INSS, collect())->count();

        $contagens = [
            'admitidos' => $admitidos,
            'desligados' => $desligados,
            'transferencia_entrada' => (int) ($transferencias['entrada'] ?? 0),
            'transferencia_saida' => (int) ($transferencias['saida'] ?? 0),
            'promocoes' => $promocoes,
            'mudanca_funcao' => $mudancaFuncao,
            'ferias' => $ferias,
            'afastamento_inss' => $afastamentoInss,
        ];

        $contagens['total_eventos'] = $admitidos
            + $desligados
            + $contagens['transferencia_entrada']
            + $contagens['transferencia_saida']
            + $promocoes
            + $mudancaFuncao
            + $ferias
            + $afastamentoInss;

        $desligamentosVoluntarios = (int) $porTipo
            ->get(ColaboradorMovimentacaoTipos::DESLIGAMENTO, collect())
            ->where('tipo_rescisao', 'pedido_demissao')
            ->pluck('colaborador_id')
            ->unique()
            ->count();

        $contagens['desligamentos_voluntarios'] = $desligamentosVoluntarios;
        $contagens['motivos'] = self::montarMotivos($porTipo, $contagens);
        $contagens['transferencias'] = $transferencias;

        return $contagens;
    }

    /**
     * @param  list<string>  $identificadoresContrato
     * @return list<int>
     */
    private static function idsColaboradoresContrato(array $identificadoresContrato): array
    {
        $q = Colaborador::query();
        ColaboradorQueryPorContratoPainel::aplicar($q, $identificadoresContrato);

        return $q->pluck('id')->all();
    }

    /**
     * @param  Collection<int, ColaboradorMovimentacao>  $registros
     */
    private static function contarColaboradoresUnicos(Collection $registros): int
    {
        return $registros->pluck('colaborador_id')->unique()->count();
    }

    /**
     * @param  list<string>  $identificadoresContrato
     */
    private static function contarAdmitidosPorData(array $identificadoresContrato, string $ini, string $fim): int
    {
        $q = Colaborador::query();
        ColaboradorQueryPorContratoPainel::aplicar($q, $identificadoresContrato);

        return (int) $q
            ->whereNotNull('data_admissao')
            ->whereDate('data_admissao', '>=', $ini)
            ->whereDate('data_admissao', '<=', $fim)
            ->count();
    }

    /**
     * @param  list<string>  $identificadoresContrato
     * @return Collection<int, int>
     */
    private static function idsDesligadosPorDataDemissao(array $identificadoresContrato, string $ini, string $fim): Collection
    {
        $q = Colaborador::query();
        ColaboradorQueryPorContratoPainel::aplicar($q, $identificadoresContrato);

        return $q
            ->whereNotNull('data_demissao')
            ->whereDate('data_demissao', '>=', $ini)
            ->whereDate('data_demissao', '<=', $fim)
            ->pluck('id');
    }

    /**
     * @param  Collection<string, Collection<int, ColaboradorMovimentacao>>  $porTipo
     * @param  array<string, int>  $contagens
     * @return list<array{label: string, value: int, icon: string}>
     */
    private static function montarMotivos(Collection $porTipo, array $contagens): array
    {
        $motivos = [];

        foreach (ColaboradorMovimentacaoTipos::tiposRescisao() as $codigo => $label) {
            $n = (int) $porTipo
                ->get(ColaboradorMovimentacaoTipos::DESLIGAMENTO, collect())
                ->where('tipo_rescisao', $codigo)
                ->count();
            if ($n > 0) {
                $motivos[] = ['label' => $label, 'value' => $n, 'icon' => 'file-minus'];
            }
        }

        $desligSemMotivo = (int) $porTipo
            ->get(ColaboradorMovimentacaoTipos::DESLIGAMENTO, collect())
            ->filter(fn (ColaboradorMovimentacao $m) => blank($m->tipo_rescisao))
            ->count();
        if ($desligSemMotivo > 0) {
            $motivos[] = ['label' => 'Desligamento (sem tipo informado)', 'value' => $desligSemMotivo, 'icon' => 'user-x'];
        }

        $desligSoData = max(0, $contagens['desligados'] - self::contarColaboradoresUnicos(
            $porTipo->get(ColaboradorMovimentacaoTipos::DESLIGAMENTO, collect())
        ));
        if ($desligSoData > 0) {
            $motivos[] = ['label' => 'Desligamento (data no cadastro)', 'value' => $desligSoData, 'icon' => 'calendar-off'];
        }

        foreach (ColaboradorMovimentacaoTipos::especiesInss() as $codigo => $label) {
            $n = (int) $porTipo
                ->get(ColaboradorMovimentacaoTipos::AFASTAMENTO_INSS, collect())
                ->where('especie_beneficio_inss', $codigo)
                ->count();
            if ($n > 0) {
                $motivos[] = ['label' => $label, 'value' => $n, 'icon' => 'heart-pulse'];
            }
        }

        if ($contagens['ferias'] > 0) {
            $motivos[] = ['label' => 'Férias registradas', 'value' => $contagens['ferias'], 'icon' => 'palmtree'];
        }

        if ($contagens['promocoes'] > 0) {
            $motivos[] = ['label' => 'Promoções', 'value' => $contagens['promocoes'], 'icon' => 'trending-up'];
        }

        if ($contagens['mudanca_funcao'] > 0) {
            $motivos[] = ['label' => 'Mudanças de função', 'value' => $contagens['mudanca_funcao'], 'icon' => 'briefcase'];
        }

        if ($contagens['transferencia_entrada'] > 0) {
            $motivos[] = ['label' => 'Transferência para o contrato', 'value' => $contagens['transferencia_entrada'], 'icon' => 'log-in'];
        }

        if ($contagens['transferencia_saida'] > 0) {
            $motivos[] = ['label' => 'Transferência para outro contrato', 'value' => $contagens['transferencia_saida'], 'icon' => 'log-out'];
        }

        if ($contagens['admitidos'] > 0) {
            $motivos[] = ['label' => 'Admissões (data no cadastro)', 'value' => $contagens['admitidos'], 'icon' => 'user-plus'];
        }

        usort($motivos, fn ($a, $b) => $b['value'] <=> $a['value']);

        return array_slice($motivos, 0, 8);
    }
}

<?php

namespace App\Support;

use App\Models\Colaborador;
use App\Models\MedicaoPresencaObraAnexo;
use App\Models\MedicaoPresencaObraRegistro;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PresencaObraService
{
    public function podeConfirmar(Colaborador $colaborador): bool
    {
        return $colaborador->status === 'ativo' && (bool) $colaborador->presenca_obra_liberado;
    }

    /**
     * @return Collection<int, Colaborador>
     */
    public function colaboradoresParaConfirmacao(?string $busca = null, ?string $centroCusto = null): Collection
    {
        $query = Colaborador::query()
            ->where('status', 'ativo')
            ->orderBy('nome');

        if ($centroCusto !== null && trim($centroCusto) !== '') {
            $query->where('centro_custo', $centroCusto);
        }

        if ($busca !== null && trim($busca) !== '') {
            $termo = '%'.trim($busca).'%';
            $query->where(function ($q) use ($termo) {
                $q->where('nome', 'like', $termo)
                    ->orWhere('matricula', 'like', $termo)
                    ->orWhere('cpf', 'like', $termo);
            });
        }

        return $query->get([
            'id',
            'nome',
            'matricula',
            'cpf',
            'cargo',
            'centro_custo',
        ]);
    }

    /**
     * @return array<int, string> colaborador_id => status
     */
    public function statusDoDia(CarbonInterface|string $data): array
    {
        $ymd = Carbon::parse($data)->toDateString();

        return MedicaoPresencaObraRegistro::query()
            ->whereDate('data', $ymd)
            ->pluck('status', 'colaborador_id')
            ->map(fn ($s) => (string) $s)
            ->all();
    }

    /**
     * @return array<int, MedicaoPresencaObraRegistro>
     */
    public function registrosDoDia(CarbonInterface|string $data): array
    {
        $ymd = Carbon::parse($data)->toDateString();

        return MedicaoPresencaObraRegistro::query()
            ->with(['anexos:id,registro_id,nome_original,caminho,mime,tamanho'])
            ->withCount('anexos')
            ->whereDate('data', $ymd)
            ->get()
            ->keyBy('colaborador_id')
            ->all();
    }

    /**
     * @param  array<int|string, array{status?: string, observacao?: string|null}>  $itens
     */
    public function salvarConfirmacao(
        Colaborador $confirmador,
        CarbonInterface|string $data,
        array $itens
    ): int {
        if (! $this->podeConfirmar($confirmador)) {
            throw ValidationException::withMessages([
                'acesso' => 'Seu acesso para confirmar presença na obra não está liberado.',
            ]);
        }

        $ymd = Carbon::parse($data)->toDateString();
        $agora = now();
        $salvos = 0;

        $idsValidos = Colaborador::query()
            ->where('status', 'ativo')
            ->whereIn('id', collect($itens)->keys()->map(fn ($id) => (int) $id)->all())
            ->get(['id', 'centro_custo'])
            ->keyBy('id');

        DB::transaction(function () use ($itens, $idsValidos, $ymd, $confirmador, $agora, &$salvos) {
            foreach ($itens as $colaboradorId => $row) {
                $colaboradorId = (int) $colaboradorId;
                $colab = $idsValidos->get($colaboradorId);
                if ($colab === null) {
                    continue;
                }

                $status = (string) ($row['status'] ?? '');
                if (! in_array($status, [
                    MedicaoPresencaObraRegistro::STATUS_PRESENTE,
                    MedicaoPresencaObraRegistro::STATUS_AUSENTE,
                ], true)) {
                    continue;
                }

                MedicaoPresencaObraRegistro::query()->updateOrCreate(
                    [
                        'data' => $ymd,
                        'colaborador_id' => $colaboradorId,
                    ],
                    [
                        'status' => $status,
                        'confirmado_por_id' => $confirmador->id,
                        'centro_custo' => $colab->centro_custo,
                        'observacao' => isset($row['observacao']) && trim((string) $row['observacao']) !== ''
                            ? mb_substr(trim((string) $row['observacao']), 0, 500)
                            : null,
                        'confirmado_em' => $agora,
                    ]
                );
                $salvos++;
            }
        });

        return $salvos;
    }

    /**
     * @param  array<int|string, list<UploadedFile>>  $anexosPorColaborador
     */
    public function salvarAnexos(CarbonInterface|string $data, array $anexosPorColaborador): int
    {
        $ymd = Carbon::parse($data)->toDateString();
        $salvos = 0;

        foreach ($anexosPorColaborador as $colaboradorId => $arquivos) {
            $registro = MedicaoPresencaObraRegistro::query()
                ->whereDate('data', $ymd)
                ->where('colaborador_id', (int) $colaboradorId)
                ->first();

            if ($registro === null) {
                continue;
            }

            foreach ($arquivos as $arquivo) {
                if (! $arquivo instanceof UploadedFile || ! $arquivo->isValid()) {
                    continue;
                }

                $path = $arquivo->store('medicao/presenca-obra/'.$registro->id, 'public');

                MedicaoPresencaObraAnexo::query()->create([
                    'registro_id' => $registro->id,
                    'nome_original' => $arquivo->getClientOriginalName(),
                    'caminho' => $path,
                    'mime' => $arquivo->getClientMimeType(),
                    'tamanho' => $arquivo->getSize(),
                ]);

                $salvos++;
            }
        }

        return $salvos;
    }

    /**
     * @param  list<UploadedFile>  $anexos
     */
    public function salvarJustificativa(
        Colaborador $confirmador,
        CarbonInterface|string $data,
        int $colaboradorId,
        ?string $observacao,
        ?string $status,
        array $anexos = [],
    ): MedicaoPresencaObraRegistro {
        if (! $this->podeConfirmar($confirmador)) {
            throw ValidationException::withMessages([
                'acesso' => 'Seu acesso para confirmar presença na obra não está liberado.',
            ]);
        }

        $ymd = Carbon::parse($data)->toDateString();
        $colab = Colaborador::query()
            ->where('status', 'ativo')
            ->whereKey($colaboradorId)
            ->first(['id', 'centro_custo']);

        if ($colab === null) {
            throw ValidationException::withMessages([
                'colaborador_id' => 'Colaborador não encontrado ou inativo.',
            ]);
        }

        $registro = MedicaoPresencaObraRegistro::query()
            ->whereDate('data', $ymd)
            ->where('colaborador_id', $colaboradorId)
            ->first();

        $statusFinal = $status ?? $registro?->status;
        if (! in_array($statusFinal, [
            MedicaoPresencaObraRegistro::STATUS_PRESENTE,
            MedicaoPresencaObraRegistro::STATUS_AUSENTE,
        ], true)) {
            throw ValidationException::withMessages([
                'status' => 'Marque o colaborador como presente ou ausente antes de salvar a justificativa.',
            ]);
        }

        $observacaoFinal = $observacao !== null && trim($observacao) !== ''
            ? mb_substr(trim($observacao), 0, 500)
            : null;

        $registro = MedicaoPresencaObraRegistro::query()->updateOrCreate(
            [
                'data' => $ymd,
                'colaborador_id' => $colaboradorId,
            ],
            [
                'status' => $statusFinal,
                'confirmado_por_id' => $confirmador->id,
                'centro_custo' => $colab->centro_custo,
                'observacao' => $observacaoFinal,
                'confirmado_em' => now(),
            ]
        );

        if ($anexos !== []) {
            $this->salvarAnexos($data, [$colaboradorId => $anexos]);
        }

        return $registro->fresh(['anexos:id,registro_id,nome_original,caminho,mime,tamanho'])
            ->loadCount('anexos');
    }

    /**
     * @return array<string, mixed>
     */
    public function dadosDashboardPainel(
        CarbonInterface|string $dataInicio,
        CarbonInterface|string $dataFim,
        ?string $centroCusto = null,
    ): array {
        $inicio = Carbon::parse($dataInicio)->startOfDay();
        $fim = Carbon::parse($dataFim)->startOfDay();

        if ($fim->lt($inicio)) {
            throw ValidationException::withMessages([
                'data_fim' => 'A data final deve ser igual ou posterior à data inicial.',
            ]);
        }

        $diasPeriodo = $inicio->diffInDays($fim) + 1;
        if ($diasPeriodo > 62) {
            throw ValidationException::withMessages([
                'data_fim' => 'O período máximo do painel é de 62 dias.',
            ]);
        }

        $inicioYmd = $inicio->toDateString();
        $fimYmd = $fim->toDateString();

        $baseQuery = MedicaoPresencaObraRegistro::query()
            ->whereBetween('data', [$inicioYmd, $fimYmd]);

        if ($centroCusto !== null && trim($centroCusto) !== '') {
            $baseQuery->where('centro_custo', $centroCusto);
        }

        $totalPresentes = (clone $baseQuery)
            ->where('status', MedicaoPresencaObraRegistro::STATUS_PRESENTE)
            ->count();

        $totalAusentes = (clone $baseQuery)
            ->where('status', MedicaoPresencaObraRegistro::STATUS_AUSENTE)
            ->count();

        $totalRegistros = $totalPresentes + $totalAusentes;
        $taxaPresenca = $totalRegistros > 0 ? round(($totalPresentes / $totalRegistros) * 100, 1) : 0.0;
        $taxaAbsenteismo = $totalRegistros > 0 ? round(($totalAusentes / $totalRegistros) * 100, 1) : 0.0;

        $diasComRegistro = (clone $baseQuery)
            ->distinct()
            ->count('data');

        $colaboradoresComFalta = (clone $baseQuery)
            ->where('status', MedicaoPresencaObraRegistro::STATUS_AUSENTE)
            ->distinct()
            ->count('colaborador_id');

        $colaboradoresRegistrados = (clone $baseQuery)
            ->distinct()
            ->count('colaborador_id');

        $faltasComJustificativa = (clone $baseQuery)
            ->where('status', MedicaoPresencaObraRegistro::STATUS_AUSENTE)
            ->where(function ($q) {
                $q->whereNotNull('observacao')->where('observacao', '!=', '');
            })
            ->count();

        $faltasComAnexo = (clone $baseQuery)
            ->where('status', MedicaoPresencaObraRegistro::STATUS_AUSENTE)
            ->whereHas('anexos')
            ->count();

        $faltasSemJustificativa = (clone $baseQuery)
            ->where('status', MedicaoPresencaObraRegistro::STATUS_AUSENTE)
            ->where(function ($q) {
                $q->where(function ($inner) {
                    $inner->whereNull('observacao')->orWhere('observacao', '');
                })->whereDoesntHave('anexos');
            })
            ->count();

        $rankingFaltas = (clone $baseQuery)
            ->where('status', MedicaoPresencaObraRegistro::STATUS_AUSENTE)
            ->selectRaw('colaborador_id, COUNT(*) as faltas')
            ->groupBy('colaborador_id')
            ->orderByDesc('faltas')
            ->limit(5)
            ->get();

        $rankingAtestados = (clone $baseQuery)
            ->where('status', MedicaoPresencaObraRegistro::STATUS_AUSENTE)
            ->whereHas('anexos')
            ->selectRaw('colaborador_id, COUNT(*) as atestados')
            ->groupBy('colaborador_id')
            ->orderByDesc('atestados')
            ->limit(5)
            ->get();

        $idsRanking = $rankingFaltas->pluck('colaborador_id')
            ->merge($rankingAtestados->pluck('colaborador_id'))
            ->filter()
            ->unique()
            ->values()
            ->all();
        $presentesPorColab = $idsRanking === [] ? collect() : (clone $baseQuery)
            ->where('status', MedicaoPresencaObraRegistro::STATUS_PRESENTE)
            ->whereIn('colaborador_id', $idsRanking)
            ->selectRaw('colaborador_id, COUNT(*) as presentes')
            ->groupBy('colaborador_id')
            ->pluck('presentes', 'colaborador_id');

        $colaboradoresRanking = $idsRanking === []
            ? collect()
            : Colaborador::query()
                ->whereIn('id', $idsRanking)
                ->get(['id', 'nome', 'matricula', 'cargo', 'centro_custo', 'foto_path'])
                ->keyBy('id');

        $rankingMaisFaltas = $this->montarRankingDashboard(
            $rankingFaltas,
            $colaboradoresRanking,
            $presentesPorColab,
            'faltas',
        );

        $rankingMaisAtestados = $this->montarRankingDashboard(
            $rankingAtestados,
            $colaboradoresRanking,
            collect(),
            'atestados',
        );

        $serieRaw = (clone $baseQuery)
            ->selectRaw('DATE(data) as dia, status, COUNT(*) as total')
            ->groupBy('dia', 'status')
            ->orderBy('dia')
            ->get();

        $seriePorDia = [];
        foreach ($serieRaw as $row) {
            $dia = Carbon::parse($row->dia)->toDateString();
            $seriePorDia[$dia][$row->status] = (int) $row->total;
        }

        $evolucaoPeriodo = [];
        for ($cursor = $inicio->copy(); $cursor->lte($fim); $cursor->addDay()) {
            $dia = $cursor->toDateString();
            $evolucaoPeriodo[] = [
                'label' => $cursor->format('d/m'),
                'presentes' => (int) ($seriePorDia[$dia][MedicaoPresencaObraRegistro::STATUS_PRESENTE] ?? 0),
                'ausentes' => (int) ($seriePorDia[$dia][MedicaoPresencaObraRegistro::STATUS_AUSENTE] ?? 0),
            ];
        }

        $porCentroCusto = (clone $baseQuery)
            ->selectRaw("COALESCE(NULLIF(centro_custo, ''), 'Sem CC') as cc, status, COUNT(*) as total")
            ->groupBy('cc', 'status')
            ->get()
            ->groupBy('cc')
            ->map(function ($rows, $cc) {
                $presentes = (int) ($rows->firstWhere('status', MedicaoPresencaObraRegistro::STATUS_PRESENTE)?->total ?? 0);
                $ausentes = (int) ($rows->firstWhere('status', MedicaoPresencaObraRegistro::STATUS_AUSENTE)?->total ?? 0);

                return [
                    'centro_custo' => $cc,
                    'presentes' => $presentes,
                    'ausentes' => $ausentes,
                    'total' => $presentes + $ausentes,
                    'taxa_falta' => ($presentes + $ausentes) > 0
                        ? round(($ausentes / ($presentes + $ausentes)) * 100, 1)
                        : 0.0,
                ];
            })
            ->sortByDesc('ausentes')
            ->values()
            ->take(10)
            ->all();

        $confirmadores = (clone $baseQuery)
            ->selectRaw('confirmado_por_id, COUNT(*) as total')
            ->groupBy('confirmado_por_id')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $confirmadorIds = $confirmadores->pluck('confirmado_por_id')->filter()->all();
        $confirmadoresMap = $confirmadorIds === []
            ? collect()
            : Colaborador::query()->whereIn('id', $confirmadorIds)->get(['id', 'nome', 'matricula'])->keyBy('id');

        $rankingSupervisores = $confirmadores->map(function ($row) use ($confirmadoresMap) {
            $sup = $confirmadoresMap->get((int) $row->confirmado_por_id);

            return [
                'nome' => $sup?->nome ?? '—',
                'matricula' => $sup?->matricula,
                'total' => (int) $row->total,
            ];
        })->values()->all();

        $totalAtestados = (clone $baseQuery)
            ->where('status', MedicaoPresencaObraRegistro::STATUS_AUSENTE)
            ->whereHas('anexos')
            ->count();

        $efetivoAtivo = $this->colaboradoresParaConfirmacao(null, $centroCusto)->count();

        return [
            'dataInicio' => $inicioYmd,
            'dataFim' => $fimYmd,
            'centroCusto' => $centroCusto ?? '',
            'centrosCusto' => $this->centrosCustoAtivos(),
            'indicadores' => [
                'dias_periodo' => $diasPeriodo,
                'dias_com_registro' => $diasComRegistro,
                'efetivo_ativo' => $efetivoAtivo,
                'colaboradores_registrados' => $colaboradoresRegistrados,
                'colaboradores_com_falta' => $colaboradoresComFalta,
                'total_registros' => $totalRegistros,
                'total_presentes' => $totalPresentes,
                'total_ausentes' => $totalAusentes,
                'taxa_presenca' => $taxaPresenca,
                'taxa_absenteismo' => $taxaAbsenteismo,
                'faltas_com_justificativa' => $faltasComJustificativa,
                'faltas_com_anexo' => $faltasComAnexo,
                'faltas_sem_justificativa' => $faltasSemJustificativa,
                'total_atestados' => $totalAtestados,
            ],
            'rankingMaisFaltas' => $rankingMaisFaltas,
            'rankingMaisAtestados' => $rankingMaisAtestados,
            'porCentroCusto' => $porCentroCusto,
            'evolucaoPeriodo' => $evolucaoPeriodo,
            'rankingSupervisores' => $rankingSupervisores,
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, object>  $rankingRows
     * @param  \Illuminate\Support\Collection<int, Colaborador>  $colaboradoresRanking
     * @param  \Illuminate\Support\Collection<int, int>  $presentesPorColab
     * @return list<array<string, mixed>>
     */
    private function montarRankingDashboard(
        \Illuminate\Support\Collection $rankingRows,
        \Illuminate\Support\Collection $colaboradoresRanking,
        \Illuminate\Support\Collection $presentesPorColab,
        string $campoValor,
    ): array {
        return $rankingRows->map(function ($row) use ($colaboradoresRanking, $presentesPorColab, $campoValor) {
            $colab = $colaboradoresRanking->get((int) $row->colaborador_id);
            $valor = (int) $row->{$campoValor};
            $presentes = (int) ($presentesPorColab[(int) $row->colaborador_id] ?? 0);
            $marcacoes = $campoValor === 'faltas' ? $valor + $presentes : $valor;

            return [
                'nome' => $colab?->nome ?? '—',
                'matricula' => $colab?->matricula,
                'cargo' => $colab?->cargo,
                'centro_custo' => $colab?->centro_custo,
                'foto_url' => $colab?->urlFotoPerfil(),
                'iniciais' => $colab ? mb_strtoupper(mb_substr($colab->nome, 0, 1)) : '?',
                'faltas' => $campoValor === 'faltas' ? $valor : 0,
                'atestados' => $campoValor === 'atestados' ? $valor : 0,
                'presentes' => $presentes,
                'valor' => $valor,
                'taxa_falta' => $campoValor === 'faltas' && $marcacoes > 0
                    ? round(($valor / $marcacoes) * 100, 1)
                    : 0.0,
            ];
        })->values()->all();
    }

    /**
     * @return list<string>
     */
    public function centrosCustoAtivos(): array
    {
        return Colaborador::query()
            ->where('status', 'ativo')
            ->whereNotNull('centro_custo')
            ->where('centro_custo', '!=', '')
            ->distinct()
            ->orderBy('centro_custo')
            ->pluck('centro_custo')
            ->map(fn ($v) => (string) $v)
            ->values()
            ->all();
    }

    /**
     * @return array{
     *     colaboradores: Collection<int, Colaborador>,
     *     dias: list<Carbon>,
     *     marcacoes: array<int, array<string, string>>
     * }
     */
    public function dadosParaFolhaExport(
        CarbonInterface|string $dataInicio,
        CarbonInterface|string $dataFim,
        ?string $centroCusto = null
    ): array {
        $inicio = Carbon::parse($dataInicio)->startOfDay();
        $fim = Carbon::parse($dataFim)->startOfDay();

        if ($fim->lt($inicio)) {
            throw ValidationException::withMessages([
                'data_fim' => 'A data final deve ser igual ou posterior à data inicial.',
            ]);
        }

        $dias = [];
        for ($cursor = $inicio->copy(); $cursor->lte($fim); $cursor->addDay()) {
            $dias[] = $cursor->copy();
        }

        if (count($dias) > 62) {
            throw ValidationException::withMessages([
                'data_fim' => 'O período máximo para exportação é de 62 dias.',
            ]);
        }

        $query = Colaborador::query()
            ->where('status', 'ativo')
            ->orderBy('nome');

        if ($centroCusto !== null && trim($centroCusto) !== '') {
            $query->where('centro_custo', $centroCusto);
        }

        if (ContratoAccess::shouldRestrict()) {
            $valores = ContratoAccess::contratoValores();
            if ($valores === []) {
                $query->whereRaw('1 = 0');
            } else {
                $query->where(function ($q) use ($valores) {
                    $q->whereIn('centro_custo', $valores)
                        ->orWhereIn('tipo_contrato', $valores);
                });
            }
        }

        $colaboradores = $query->get(['id', 'nome', 'matricula', 'cargo', 'centro_custo']);
        $colaboradorIds = $colaboradores->pluck('id')->map(fn ($id) => (int) $id)->all();

        $marcacoes = [];
        if ($colaboradorIds !== []) {
            $registros = MedicaoPresencaObraRegistro::query()
                ->whereBetween('data', [$inicio->toDateString(), $fim->toDateString()])
                ->whereIn('colaborador_id', $colaboradorIds)
                ->get(['colaborador_id', 'data', 'status']);

            foreach ($registros as $registro) {
                $letra = match ($registro->status) {
                    MedicaoPresencaObraRegistro::STATUS_PRESENTE => 'P',
                    MedicaoPresencaObraRegistro::STATUS_AUSENTE => 'F',
                    default => '',
                };

                if ($letra === '') {
                    continue;
                }

                $ymd = Carbon::parse($registro->data)->toDateString();
                $marcacoes[(int) $registro->colaborador_id][$ymd] = $letra;
            }
        }

        return [
            'colaboradores' => $colaboradores,
            'dias' => $dias,
            'marcacoes' => $marcacoes,
        ];
    }
}

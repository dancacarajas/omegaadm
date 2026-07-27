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

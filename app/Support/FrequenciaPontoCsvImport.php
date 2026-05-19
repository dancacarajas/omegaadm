<?php

namespace App\Support;

use App\Models\Colaborador;
use App\Models\FrequenciaRegistro;
use App\Support\EscalaPontoRegras;
use App\Support\FeriadoPontoService;
use App\Support\Rh\ColaboradorVinculoPonto;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Importação de exportação CSV do sistema de ponto (OMEGARH / relógio).
 * Colunas: matrícula, CPF, Dia, Entrada 1–Saída 2, Justificativas.
 */
class FrequenciaPontoCsvImport
{
    public const ORIGEM = 'csv_ponto';

    /** @var array<string, Colaborador> */
    private array $indiceColaboradores = [];

    /** @var array<string, string> */
    private const MAPA_CABECALHO = [
        'numero de matricula' => 'matricula',
        'cpf do funcionario' => 'cpf',
        'dia' => 'dia',
        'entrada 1' => 'entrada_1',
        'saida 1' => 'saida_1',
        'entrada 2' => 'entrada_2',
        'saida 2' => 'saida_2',
        'justificativas' => 'justificativas',
    ];

    /**
     * @return array{
     *     importados: int,
     *     ignorados: int,
     *     fora_periodo: int,
     *     linhas: int,
     *     colaboradores_nao_encontrados: list<string>,
     *     datas: list<string>,
     *     periodo: array{inicio: string, fim: string},
     *     fora_escopo_colaborador: int
     * }
     */
    /**
     * @param  list<int>|null  $colaboradorIds  Se informado, importa só linhas desses colaboradores (IDs no efetivo).
     */
    public function importar(string $caminhoArquivo, string $dataInicio, string $dataFim, ?array $colaboradorIds = null): array
    {
        $inicio = Carbon::parse($dataInicio)->startOfDay();
        $fim = Carbon::parse($dataFim)->endOfDay();
        if ($fim->lt($inicio)) {
            throw new \InvalidArgumentException('A data fim deve ser igual ou posterior à data início.');
        }
        $conteudo = file_get_contents($caminhoArquivo);
        if ($conteudo === false || trim($conteudo) === '') {
            return $this->resultadoVazio($dataInicio, $dataFim);
        }

        if (! mb_check_encoding($conteudo, 'UTF-8')) {
            $conteudo = mb_convert_encoding($conteudo, 'UTF-8', 'Windows-1252');
        }

        $conteudo = preg_replace('/^\xEF\xBB\xBF/', '', $conteudo) ?? $conteudo;

        $handle = fopen('php://memory', 'r+');
        fwrite($handle, $conteudo);
        rewind($handle);

        $cabecalho = fgetcsv($handle, 0, ';');
        if ($cabecalho === false) {
            fclose($handle);

            return $this->resultadoVazio($dataInicio, $dataFim);
        }

        $indices = $this->mapearIndicesCabecalho($cabecalho);
        if (! isset($indices['matricula'], $indices['dia'], $indices['entrada_1'])) {
            fclose($handle);

            throw new \InvalidArgumentException(
                'CSV inválido: cabeçalho deve conter matrícula, Dia e colunas de marcação (Entrada 1, etc.).'
            );
        }

        $importados = 0;
        $ignorados = 0;
        $foraPeriodo = 0;
        $foraEscopoColaborador = 0;
        $linhas = 0;
        $naoEncontrados = [];
        $datas = [];
        $filtroIds = $colaboradorIds !== null
            ? array_fill_keys(array_map('intval', $colaboradorIds), true)
            : null;

        $this->montarIndiceColaboradores();

        DB::transaction(function () use ($handle, $indices, $inicio, $fim, $filtroIds, &$importados, &$ignorados, &$foraPeriodo, &$foraEscopoColaborador, &$linhas, &$naoEncontrados, &$datas) {
            while (($row = fgetcsv($handle, 0, ';')) !== false) {
                if ($this->linhaVazia($row)) {
                    continue;
                }

                $linhas++;
                $linha = $this->extrairLinha($row, $indices);

                if ($linha['data'] === null) {
                    $ignorados++;

                    continue;
                }

                $dataLinha = Carbon::parse($linha['data'])->startOfDay();
                if ($dataLinha->lt($inicio) || $dataLinha->gt($fim)) {
                    $foraPeriodo++;

                    continue;
                }

                $colaborador = $this->resolverColaborador($linha['matricula'], $linha['cpf']);
                if (! $colaborador) {
                    $ignorados++;
                    $chave = $linha['matricula'] ?: $linha['cpf'] ?: '?';
                    $naoEncontrados[$chave] = true;

                    continue;
                }

                if ($filtroIds !== null && ! isset($filtroIds[$colaborador->id])) {
                    $foraEscopoColaborador++;

                    continue;
                }

                if (! ColaboradorVinculoPonto::contaPontoNaData($colaborador, $linha['data'])) {
                    $ignorados++;

                    continue;
                }

                $attrs = $this->montarRegistro($linha, $colaborador, $linha['data']);
                FrequenciaRegistro::query()->updateOrCreate(
                    [
                        'colaborador_id' => $colaborador->id,
                        'data' => $linha['data'],
                    ],
                    array_merge($attrs, [
                        'importado_em' => now(),
                        'origem' => self::ORIGEM,
                    ])
                );

                $importados++;
                $datas[$linha['data']] = true;
            }
        });

        fclose($handle);

        return [
            'importados' => $importados,
            'ignorados' => $ignorados,
            'fora_periodo' => $foraPeriodo,
            'linhas' => $linhas,
            'colaboradores_nao_encontrados' => array_keys($naoEncontrados),
            'datas' => array_keys($datas),
            'periodo' => [
                'inicio' => $inicio->toDateString(),
                'fim' => $fim->toDateString(),
            ],
            'fora_escopo_colaborador' => $foraEscopoColaborador,
        ];
    }

    /**
     * @param  list<string|null>  $row
     * @param  array<string, int>  $indices
     * @return array{
     *     matricula: string,
     *     cpf: string,
     *     data: string|null,
     *     entrada_1: string|null,
     *     saida_1: string|null,
     *     entrada_2: string|null,
     *     saida_2: string|null,
     *     justificativas: string,
     *     celulas: list<string>
     * }
     */
    private function extrairLinha(array $row, array $indices): array
    {
        $valor = static fn (string $chave) => isset($indices[$chave])
            ? trim((string) ($row[$indices[$chave]] ?? ''))
            : '';

        $celulas = [
            $valor('entrada_1'),
            $valor('saida_1'),
            $valor('entrada_2'),
            $valor('saida_2'),
        ];

        return [
            'matricula' => $valor('matricula'),
            'cpf' => $valor('cpf'),
            'data' => $this->parseDataDia($valor('dia')),
            'entrada_1' => $this->parseHorarioMarcacao($celulas[0]),
            'saida_1' => $this->parseHorarioMarcacao($celulas[1]),
            'entrada_2' => $this->parseHorarioMarcacao($celulas[2]),
            'saida_2' => $this->parseHorarioMarcacao($celulas[3]),
            'justificativas' => $valor('justificativas'),
            'celulas' => $celulas,
        ];
    }

    /**
     * @param  array<string, int>  $indices
     * @param  list<string|null>  $row
     * @return array<string, mixed>
     */
    private function montarRegistro(array $linha, Colaborador $colaborador, string $dataYmd): array
    {
        $status = $this->resolverStatus($linha, $colaborador, $dataYmd);
        $textoJustificativa = $this->resolverTextoJustificativa($linha);

        $base = [
            'entrada_1' => null,
            'saida_1' => null,
            'entrada_2' => null,
            'saida_2' => null,
            'justificativa_tipo' => null,
            'justificativa_texto' => null,
            'status' => $status,
        ];

        if ($status === 'justificado') {
            $base['justificativa_tipo'] = $this->resolverTipoJustificativa($linha, $textoJustificativa);
            $base['justificativa_texto'] = $textoJustificativa;

            return $base;
        }

        if ($status === 'folga') {
            return $base;
        }

        $base['entrada_1'] = $linha['entrada_1'];
        $base['saida_1'] = $linha['saida_1'];
        $base['entrada_2'] = $linha['entrada_2'];
        $base['saida_2'] = $linha['saida_2'];

        return $base;
    }

    /**
     * @param  array{
     *     celulas: list<string>,
     *     justificativas: string,
     *     entrada_1: string|null,
     *     saida_1: string|null,
     *     entrada_2: string|null,
     *     saida_2: string|null
     * }  $linha
     */
    private function resolverStatus(array $linha, Colaborador $colaborador, string $dataYmd): string
    {
        foreach ($linha['celulas'] as $celula) {
            if ($this->celulaIndicaFolga($celula)) {
                return 'folga';
            }
        }

        foreach ($linha['celulas'] as $celula) {
            if ($this->celulaIndicaJustificado($celula)) {
                return 'justificado';
            }
        }

        if (trim($linha['justificativas']) !== '') {
            return 'justificado';
        }

        $horarios = array_filter([
            $linha['entrada_1'],
            $linha['saida_1'],
            $linha['entrada_2'],
            $linha['saida_2'],
        ]);

        $qtd = count($horarios);

        if ($qtd === 0) {
            if (app(EscalaPontoRegras::class)->diaAbonadoPorFolgaEscala($colaborador, $dataYmd)) {
                return 'folga';
            }

            if (app(FeriadoPontoService::class)->diaAbonadoPorFeriado($dataYmd)) {
                return 'justificado';
            }
        }

        return match (true) {
            $qtd >= 4 => 'presente',
            $qtd >= 2 => 'presente',
            $qtd === 1 => 'incompleto',
            default => 'falta',
        };
    }

    /**
     * @param  array{celulas: list<string>, justificativas: string}  $linha
     */
    private function resolverTextoJustificativa(array $linha): ?string
    {
        if (trim($linha['justificativas']) !== '') {
            return trim($linha['justificativas']);
        }

        foreach ($linha['celulas'] as $celula) {
            if ($this->celulaIndicaJustificado($celula)) {
                return trim($celula);
            }
        }

        return null;
    }

    private function resolverTipoJustificativa(array $linha, ?string $texto): string
    {
        $blob = mb_strtolower(implode(' ', array_merge($linha['celulas'], [$texto ?? '', $linha['justificativas'] ?? ''])));

        if (str_contains($blob, 'atestado')) {
            return 'atestado';
        }

        if (str_contains($blob, 'feriado') || str_contains($blob, 'abono')) {
            return 'abono';
        }

        return 'abono';
    }

    private function parseDataDia(string $dia): ?string
    {
        if (preg_match('/(\d{2})\/(\d{2})\/(\d{4})/', $dia, $m)) {
            return Carbon::createFromFormat('d/m/Y', $m[1].'/'.$m[2].'/'.$m[3])->toDateString();
        }

        return null;
    }

    private function parseHorarioMarcacao(string $raw): ?string
    {
        $s = trim($raw);
        if ($s === '' || $s === '-' || preg_match('/^\s*-\s*$/', $s)) {
            return null;
        }

        if ($this->celulaIndicaFolga($s) || $this->celulaIndicaJustificado($s)) {
            return null;
        }

        if (preg_match('/(\d{1,2}):(\d{2})/', $s, $m)) {
            return sprintf('%02d:%02d:00', (int) $m[1], (int) $m[2]);
        }

        return null;
    }

    private function celulaIndicaFolga(string $raw): bool
    {
        $s = mb_strtolower(trim($raw));

        return $s !== '' && str_contains($s, 'folga');
    }

    private function celulaIndicaJustificado(string $raw): bool
    {
        $s = mb_strtolower(trim($raw));

        if ($s === '' || $s === '-') {
            return false;
        }

        return str_contains($s, 'justificado')
            || str_contains($s, 'feriado')
            || str_contains($s, 'atestado');
    }

    private function resolverColaborador(string $matricula, string $cpf): ?Colaborador
    {
        if ($matricula !== '') {
            $colaborador = $this->buscarPorDocumento($matricula);
            if ($colaborador) {
                return $colaborador;
            }
        }

        if ($cpf !== '') {
            return $this->buscarPorDocumento($cpf);
        }

        return null;
    }

    private function montarIndiceColaboradores(): void
    {
        $this->indiceColaboradores = [];

        foreach (Colaborador::query()->get(['id', 'matricula', 'cpf', 'pis', 'data_admissao', 'data_demissao']) as $colaborador) {
            foreach (['matricula', 'cpf', 'pis'] as $campo) {
                $chave = $this->normalizarDocumento($colaborador->getAttribute($campo));
                if ($chave !== '') {
                    $this->indiceColaboradores[$chave] = $colaborador;
                }
            }
        }
    }

    private function buscarPorDocumento(string $documento): ?Colaborador
    {
        $chave = $this->normalizarDocumento($documento);

        return $chave !== '' ? ($this->indiceColaboradores[$chave] ?? null) : null;
    }

    private function normalizarDocumento(mixed $documento): string
    {
        return ltrim(preg_replace('/\D+/', '', (string) $documento) ?: '', '0');
    }

    /**
     * @param  list<string|null>  $cabecalho
     * @return array<string, int>
     */
    private function mapearIndicesCabecalho(array $cabecalho): array
    {
        $indices = [];
        foreach ($cabecalho as $i => $coluna) {
            $chave = self::MAPA_CABECALHO[$this->normalizarChave((string) $coluna)] ?? null;
            if ($chave !== null) {
                $indices[$chave] = $i;
            }
        }

        return $indices;
    }

    private function normalizarChave(string $header): string
    {
        $h = mb_strtolower(trim($header));
        $h = str_replace(
            ['á', 'à', 'â', 'ã', 'é', 'ê', 'í', 'ó', 'ô', 'õ', 'ú', 'ç', 'ñ'],
            ['a', 'a', 'a', 'a', 'e', 'e', 'i', 'o', 'o', 'o', 'u', 'c', 'n'],
            $h
        );

        return preg_replace('/\s+/', ' ', $h) ?? $h;
    }

    /**
     * @param  list<string|null>  $row
     */
    private function linhaVazia(array $row): bool
    {
        foreach ($row as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array{
     *     importados: int,
     *     ignorados: int,
     *     linhas: int,
     *     colaboradores_nao_encontrados: list<string>,
     *     datas: list<string>
     * }
     */
    /**
     * @return array{
     *     importados: int,
     *     ignorados: int,
     *     fora_periodo: int,
     *     linhas: int,
     *     colaboradores_nao_encontrados: list<string>,
     *     datas: list<string>,
     *     periodo: array{inicio: string, fim: string}
     * }
     */
    private function resultadoVazio(string $dataInicio = '', string $dataFim = ''): array
    {
        return [
            'importados' => 0,
            'ignorados' => 0,
            'fora_periodo' => 0,
            'linhas' => 0,
            'colaboradores_nao_encontrados' => [],
            'datas' => [],
            'periodo' => [
                'inicio' => $dataInicio,
                'fim' => $dataFim,
            ],
            'fora_escopo_colaborador' => 0,
        ];
    }
}

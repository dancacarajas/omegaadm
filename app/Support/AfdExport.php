<?php

namespace App\Support;

use App\Models\Colaborador;
use App\Models\FrequenciaRegistro;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AfdExport
{
    private int $nsr = 0;

    /**
     * @return array{
     *     conteudo: string,
     *     nome_arquivo: string,
     *     total_marcacoes: int,
     *     registros_com_horario: int,
     *     colaboradores_sem_identificador: int
     * }
     */
    public function gerar(string $dataInicio, string $dataFim, ?string $busca = null): array
    {
        $inicio = Carbon::parse($dataInicio)->startOfDay();
        $fim = Carbon::parse($dataFim)->startOfDay();

        if ($fim->lt($inicio)) {
            [$inicio, $fim] = [$fim, $inicio];
        }

        $registros = $this->buscarRegistros($inicio->toDateString(), $fim->toDateString(), $busca);

        $linhas = [];
        $linhas[] = $this->registroEmpresa($inicio);
        $totalMarcacoes = 0;
        $registrosComHorario = 0;
        $semIdentificador = 0;

        foreach ($registros as $registro) {
            $colaborador = $registro->colaborador;
            if (! $colaborador) {
                continue;
            }

            $horas = $this->horariosDoRegistro($registro);
            if ($horas === []) {
                continue;
            }

            $registrosComHorario++;
            $documento = $this->documentoColaborador($colaborador);
            if ($documento === null) {
                $semIdentificador++;

                continue;
            }

            foreach ($horas as $hora) {
                $linha = $this->registroMarcacao($registro->data, $hora, $documento);
                if ($linha !== null) {
                    $linhas[] = $linha;
                    $totalMarcacoes++;
                }
            }
        }

        $linhas[] = $this->registroTrailer($totalMarcacoes);

        $conteudo = implode("\r\n", $linhas)."\r\n";
        $nome = sprintf(
            'afd_ponto_%s_%s.txt',
            $inicio->format('Ymd'),
            $fim->format('Ymd')
        );

        return [
            'conteudo' => $conteudo,
            'nome_arquivo' => $nome,
            'total_marcacoes' => $totalMarcacoes,
            'registros_com_horario' => $registrosComHorario,
            'colaboradores_sem_identificador' => $semIdentificador,
        ];
    }

    /**
     * @return Collection<int, FrequenciaRegistro>
     */
    private function buscarRegistros(string $dataInicio, string $dataFim, ?string $busca): Collection
    {
        $query = FrequenciaRegistro::query()
            ->with('colaborador')
            ->join('colaboradores', 'colaboradores.id', '=', 'frequencia_registros.colaborador_id')
            ->whereDate('frequencia_registros.data', '>=', $dataInicio)
            ->whereDate('frequencia_registros.data', '<=', $dataFim)
            ->where(function (Builder $q) {
                foreach (['entrada_1', 'saida_1', 'entrada_2', 'saida_2'] as $campo) {
                    $q->orWhereNotNull("frequencia_registros.{$campo}");
                }
            })
            ->when(filled($busca), function (Builder $q) use ($busca) {
                $q->where(function (Builder $inner) use ($busca) {
                    $inner->where('colaboradores.nome', 'like', "%{$busca}%")
                        ->orWhere('colaboradores.matricula', 'like', "%{$busca}%")
                        ->orWhere('colaboradores.pis', 'like', "%{$busca}%")
                        ->orWhere('colaboradores.cpf', 'like', "%{$busca}%")
                        ->orWhere('colaboradores.cargo', 'like', "%{$busca}%");
                });
            })
            ->orderBy('frequencia_registros.data')
            ->orderBy('colaboradores.nome')
            ->select('frequencia_registros.*');

        return $query->get();
    }

    /**
     * @return list<string>
     */
    private function horariosDoRegistro(FrequenciaRegistro $registro): array
    {
        $horas = [];
        foreach (['entrada_1', 'saida_1', 'entrada_2', 'saida_2'] as $campo) {
            $valor = $registro->getAttribute($campo);
            if (FrequenciaCalculo::horarioArmazenadoVazio($valor)) {
                continue;
            }
            $hora = FrequenciaCalculo::normalizarHorarioBanco($valor);
            if ($hora !== null) {
                $horas[] = $hora;
            }
        }

        sort($horas);

        return $horas;
    }

    private function registroMarcacao(mixed $data, string $hora, string $documento): ?string
    {
        $carbonData = $data instanceof \DateTimeInterface
            ? Carbon::instance($data)
            : Carbon::parse((string) $data);

        $horaNorm = FrequenciaCalculo::normalizarHorarioBanco($hora);
        if ($horaNorm === null) {
            return null;
        }

        $this->nsr++;

        // Registro tipo 3 — Portaria 1510 (compatível com o importador deste sistema).
        return sprintf(
            '%09d3%s%s%s',
            $this->nsr,
            $carbonData->format('Ymd'),
            substr($horaNorm, 0, 2).substr($horaNorm, 3, 2),
            str_pad($documento, 12, '0', STR_PAD_LEFT)
        );
    }

    private function documentoColaborador(Colaborador $colaborador): ?string
    {
        $pis = preg_replace('/\D+/', '', (string) $colaborador->pis) ?: '';
        if ($pis !== '') {
            return substr(str_pad($pis, 12, '0', STR_PAD_LEFT), -12);
        }

        $cpf = preg_replace('/\D+/', '', (string) $colaborador->cpf) ?: '';
        if ($cpf !== '') {
            return substr(str_pad($cpf, 12, '0', STR_PAD_LEFT), -12);
        }

        $matricula = preg_replace('/\D+/', '', (string) $colaborador->matricula) ?: '';
        if ($matricula !== '') {
            return substr(str_pad($matricula, 12, '0', STR_PAD_LEFT), -12);
        }

        $matriculaTexto = preg_replace('/\s+/', '', (string) $colaborador->matricula) ?: '';
        if ($matriculaTexto !== '') {
            $numerico = (string) abs(crc32($matriculaTexto));

            return substr(str_pad($numerico, 12, '0', STR_PAD_LEFT), -12);
        }

        if ($colaborador->id) {
            return substr(str_pad((string) $colaborador->id, 12, '0', STR_PAD_LEFT), -12);
        }

        return null;
    }

    private function registroEmpresa(Carbon $referencia): string
    {
        $cnpj = preg_replace('/\D+/', '', (string) config('frequencia.afd.cnpj', '')) ?: '00000000000000';
        $cnpj = str_pad(substr($cnpj, 0, 14), 14, '0', STR_PAD_LEFT);
        $razao = $this->textoAscii(config('frequencia.afd.razao_social', config('app.name', 'Omega')));
        $razao = str_pad(substr($razao, 0, 150), 150, ' ', STR_PAD_RIGHT);

        $this->nsr++;

        return sprintf(
            '%09d1%s%s%s%s',
            $this->nsr,
            '1',
            $cnpj,
            $razao,
            '0'.'1'.$referencia->format('Ymd').$referencia->format('Hi')
        );
    }

    private function registroTrailer(int $totalMarcacoes): string
    {
        $this->nsr++;

        return sprintf('%09d9%09d', $this->nsr, max(0, $totalMarcacoes));
    }

    private function textoAscii(string $valor): string
    {
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $valor) ?: $valor;

        return preg_replace('/[^\x20-\x7E]/', '', $ascii) ?: 'EMPRESA';
    }
}

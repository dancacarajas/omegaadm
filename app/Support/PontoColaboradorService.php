<?php

namespace App\Support;

use App\Models\Colaborador;
use App\Models\FrequenciaRegistro;
use App\Models\HorarioEscalaDia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PontoColaboradorService
{
    public const ORIGEM = 'app_colaborador';

    /** Batidas de intervalo preenchidas com horário da escala (não relógio). */
    private const BATIDAS_HORARIO_ESCALA = ['saida_1', 'entrada_2'];

    /** @var array<string, string> */
    public const BATIDAS = [
        'entrada_1' => 'Entrada',
        'saida_1' => 'Saída (intervalo)',
        'entrada_2' => 'Retorno (intervalo)',
        'saida_2' => 'Saída final',
    ];

    public function encontrarColaboradorAtivo(string $matricula, string $cpf): ?Colaborador
    {
        $matriculaNorm = $this->normalizarMatricula($matricula);
        $cpfNorm = $this->normalizarCpf($cpf);

        if ($matriculaNorm === '' || $cpfNorm === '') {
            return null;
        }

        return Colaborador::query()
            ->where('status', 'ativo')
            ->get()
            ->first(fn (Colaborador $c) => $this->normalizarMatricula((string) $c->matricula) === $matriculaNorm
                && $this->normalizarCpf((string) $c->cpf) === $cpfNorm);
    }

    public function registroDoDia(Colaborador $colaborador, ?Carbon $data = null): FrequenciaRegistro
    {
        $dataYmd = ($data ?? now())->toDateString();

        $registro = FrequenciaRegistro::query()
            ->where('colaborador_id', $colaborador->id)
            ->whereDate('data', $dataYmd)
            ->first();

        if ($registro) {
            $this->sincronizarIntervaloPendenteDaEscala($registro, $colaborador);

            return $registro->fresh();
        }

        return FrequenciaRegistro::create([
            'colaborador_id' => $colaborador->id,
            'data' => $dataYmd,
            'status' => 'falta',
            'origem' => self::ORIGEM,
        ]);
    }

    /**
     * @return array{campo: string, label: string}|null
     */
    public function proximaBatida(FrequenciaRegistro $registro): ?array
    {
        foreach (self::BATIDAS as $campo => $label) {
            if (FrequenciaCalculo::horarioArmazenadoVazio($registro->getAttribute($campo))) {
                return ['campo' => $campo, 'label' => $label];
            }
        }

        return null;
    }

    /**
     * @return array{
     *     registro: FrequenciaRegistro,
     *     campo: string,
     *     label: string,
     *     hora: string,
     *     mensagem: string,
     *     extras: list<array{label: string, hora: string}>
     * }
     */
    public function registrarProximaBatida(Colaborador $colaborador, ?Carbon $momento = null): array
    {
        $momento = $momento ?? now();
        $colaborador->loadMissing('horarioEscala.excecoes', 'horarioEscala.dias');

        $avaliacao = app(EscalaPontoRegras::class)->avaliarMarcacao(
            $colaborador,
            $momento,
            true
        );

        if (! $avaliacao['permitido']) {
            throw ValidationException::withMessages([
                'ponto' => $avaliacao['motivo'] ?? 'Marcação não permitida nesta data.',
            ]);
        }

        return DB::transaction(function () use ($colaborador, $momento) {
            $registro = $this->registroDoDia($colaborador, $momento);
            $registro->refresh();
            $this->sincronizarIntervaloPendenteDaEscala($registro, $colaborador);
            $registro->refresh();

            $proxima = $this->proximaBatida($registro);
            if ($proxima === null) {
                throw ValidationException::withMessages([
                    'ponto' => 'Todas as batidas de hoje já foram registradas.',
                ]);
            }

            $diaEscala = $colaborador->horarioEscalaDiaNaData($momento);
            $hora = $this->horarioParaBatida($proxima['campo'], $momento, $diaEscala);
            $registro->setAttribute($proxima['campo'], $hora);

            $extras = [];
            if ($proxima['campo'] === 'entrada_1') {
                $extras = $this->preencherIntervaloAutomaticoDaEscala($registro, $diaEscala);
            }

            $this->recalcularStatus($registro);
            $registro->origem = self::ORIGEM;
            $registro->save();

            return [
                'registro' => $registro->fresh(),
                'campo' => $proxima['campo'],
                'label' => $proxima['label'],
                'hora' => substr($hora, 0, 5),
                'extras' => $extras,
                'mensagem' => $this->montarMensagemSucesso($proxima['label'], substr($hora, 0, 5), $extras),
            ];
        });
    }

    /**
     * Preenche saída/retorno do intervalo quando já existe entrada e a escala define esses horários.
     */
    public function sincronizarIntervaloPendenteDaEscala(FrequenciaRegistro $registro, Colaborador $colaborador): bool
    {
        if (FrequenciaCalculo::horarioArmazenadoVazio($registro->entrada_1)) {
            return false;
        }

        $colaborador->loadMissing('horarioEscala.dias');
        $diaEscala = $colaborador->horarioEscalaDiaNaData($registro->data);
        if (! $diaEscala) {
            return false;
        }

        $extras = $this->preencherIntervaloAutomaticoDaEscala($registro, $diaEscala);
        if ($extras === []) {
            return false;
        }

        $this->recalcularStatus($registro);
        $registro->save();

        return true;
    }

    private function recalcularStatus(FrequenciaRegistro $registro): void
    {
        $preenchidos = 0;
        foreach (array_keys(self::BATIDAS) as $campo) {
            if (! FrequenciaCalculo::horarioArmazenadoVazio($registro->getAttribute($campo))) {
                $preenchidos++;
            }
        }

        $registro->status = match (true) {
            $preenchidos >= 2 => 'presente',
            $preenchidos === 1 => 'incompleto',
            default => 'falta',
        };
    }

    /**
     * @return list<array{label: string, hora: string}>
     */
    private function preencherIntervaloAutomaticoDaEscala(FrequenciaRegistro $registro, ?HorarioEscalaDia $diaEscala): array
    {
        if (! $diaEscala) {
            return [];
        }

        $extras = [];
        foreach (['saida_1' => 'Saída (intervalo)', 'entrada_2' => 'Retorno (intervalo)'] as $campo => $label) {
            if (! FrequenciaCalculo::horarioArmazenadoVazio($registro->getAttribute($campo))) {
                continue;
            }

            $valorEscala = $diaEscala->getAttribute($campo);
            if (FrequenciaCalculo::horarioArmazenadoVazio($valorEscala)) {
                continue;
            }

            $hora = FrequenciaCalculo::normalizarHorarioBanco($valorEscala);
            if ($hora === null) {
                continue;
            }

            $registro->setAttribute($campo, $hora);
            $extras[] = [
                'label' => $label,
                'hora' => substr($hora, 0, 5),
            ];
        }

        return $extras;
    }

    private function horarioParaBatida(string $campo, Carbon $momento, ?HorarioEscalaDia $diaEscala): string
    {
        if ($diaEscala && in_array($campo, self::BATIDAS_HORARIO_ESCALA, true)) {
            $valorEscala = $diaEscala->getAttribute($campo);
            if (! FrequenciaCalculo::horarioArmazenadoVazio($valorEscala)) {
                $hora = FrequenciaCalculo::normalizarHorarioBanco($valorEscala);

                return $hora ?? $momento->format('H:i:s');
            }
        }

        return $momento->format('H:i:s');
    }

    /**
     * @param  list<array{label: string, hora: string}>  $extras
     */
    private function montarMensagemSucesso(string $labelPrincipal, string $horaPrincipal, array $extras): string
    {
        $partes = [$labelPrincipal.' registrada às '.$horaPrincipal.'.'];

        if ($extras !== []) {
            $intervalos = collect($extras)
                ->map(fn (array $e) => $e['label'].' às '.$e['hora'])
                ->implode('; ');
            $partes[] = 'Intervalo conforme escala: '.$intervalos.'.';
        }

        return implode(' ', $partes);
    }

    /**
     * @return list<array{campo: string, label: string, hora: string|null, registrada: bool, automatica_escala: bool}>
     */
    public function resumoBatidas(FrequenciaRegistro $registro, ?Colaborador $colaborador = null): array
    {
        $colaborador ??= $registro->colaborador;
        $diaEscala = $colaborador?->horarioEscalaDiaNaData($registro->data);

        $out = [];
        foreach (self::BATIDAS as $campo => $label) {
            $valor = $registro->getAttribute($campo);
            $previstoEscala = $diaEscala?->getAttribute($campo);
            $automatica = in_array($campo, self::BATIDAS_HORARIO_ESCALA, true)
                && ! FrequenciaCalculo::horarioArmazenadoVazio($previstoEscala);

            $out[] = [
                'campo' => $campo,
                'label' => $label,
                'hora' => FrequenciaCalculo::horarioArmazenadoVazio($valor)
                    ? null
                    : substr((string) $valor, 0, 5),
                'registrada' => ! FrequenciaCalculo::horarioArmazenadoVazio($valor),
                'automatica_escala' => $automatica,
            ];
        }

        return $out;
    }

    public function formatarHora(?string $valor): ?string
    {
        if ($valor === null || FrequenciaCalculo::horarioArmazenadoVazio($valor)) {
            return null;
        }

        return substr((string) $valor, 0, 5);
    }

    private function normalizarMatricula(string $valor): string
    {
        $digits = preg_replace('/\D+/', '', $valor) ?: '';

        return ltrim($digits, '0') ?: $digits;
    }

    private function normalizarCpf(string $valor): string
    {
        return preg_replace('/\D+/', '', $valor) ?: '';
    }
}

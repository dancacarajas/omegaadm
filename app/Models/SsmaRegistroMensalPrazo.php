<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class SsmaRegistroMensalPrazo extends Model
{
    protected $table = 'ssma_registro_mensal_prazos';

    protected $fillable = [
        'competencia',
        'recorrente',
        'data_limite',
        'exige_finalizado',
        'observacao',
    ];

    protected $casts = [
        'competencia' => 'date',
        'recorrente' => 'boolean',
        'data_limite' => 'datetime',
        'exige_finalizado' => 'boolean',
    ];

    /** Prazo explícito do mês ou regra recorrente vigente (prioriza o explícito). */
    public static function prazoEfetivoParaCompetencia(Carbon $mesReferencia): ?self
    {
        $mes = $mesReferencia->copy()->startOfMonth()->toDateString();

        $explicito = self::query()
            ->where('recorrente', false)
            ->whereDate('competencia', $mes)
            ->first();

        if ($explicito) {
            return $explicito;
        }

        return self::query()
            ->where('recorrente', true)
            ->whereDate('competencia', '<=', $mes)
            ->orderByDesc('competencia')
            ->first();
    }

    /** Data/hora limite aplicável ao mês informado (recorrente = mesmo dia/hora em cada mês). */
    public function dataLimiteEfetiva(Carbon $mesReferencia): Carbon
    {
        $mes = $mesReferencia->copy()->startOfMonth();

        if (! $this->recorrente) {
            return $this->data_limite->copy();
        }

        $diaModelo = (int) $this->data_limite->format('d');
        $ultimoDia = (int) $mes->copy()->endOfMonth()->format('d');
        $dia = min($diaModelo, $ultimoDia);

        return $mes->copy()->day($dia)->setTime(
            (int) $this->data_limite->format('H'),
            (int) $this->data_limite->format('i'),
            (int) $this->data_limite->format('s')
        );
    }

    /**
     * Avalia o SLA para o mês de referência (competência do registro mensal).
     *
     * @return array{cumprido: bool, atrasado: bool, pendente: bool, rotulo: string}
     */
    public function situacaoNoMes(Carbon $mesReferencia): array
    {
        $mes = $mesReferencia->copy()->startOfMonth();

        $q = SsmaRegistroMensal::query()->whereDate('competencia', $mes->toDateString());

        if ($this->exige_finalizado) {
            $cumprido = (clone $q)->where('status', 'finalizado')->exists();
        } else {
            $cumprido = $q->exists();
        }

        $now = now();
        $limite = $this->dataLimiteEfetiva($mes);

        $atrasado = ! $cumprido && $now->gt($limite);
        $pendente = ! $cumprido && $now->lte($limite);

        if ($cumprido) {
            $rotulo = 'Dentro do SLA';
        } elseif ($atrasado) {
            $rotulo = 'Fora do prazo';
        } else {
            $rotulo = 'Pendente';
        }

        return compact('cumprido', 'atrasado', 'pendente', 'rotulo');
    }

    /**
     * @return array{cumprido: bool, atrasado: bool, pendente: bool, rotulo: string}
     */
    public function situacao(): array
    {
        return $this->situacaoNoMes(Carbon::parse($this->competencia)->startOfMonth());
    }
}

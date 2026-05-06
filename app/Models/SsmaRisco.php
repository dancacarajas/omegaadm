<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SsmaRisco extends Model
{
    protected $table = 'ssma_riscos';

    public const CATEGORIAS = [
        'seguranca' => 'Segurança',
        'saude' => 'Saúde',
        'meio_ambiente' => 'Meio ambiente',
        'operacional' => 'Operacional',
    ];

    /** Escala 1–5 para matriz (quanto maior, maior o risco). */
    public const ESCALA_NIVEIS = [
        1 => '1 — Muito baixo',
        2 => '2 — Baixo',
        3 => '3 — Médio',
        4 => '4 — Alto',
        5 => '5 — Muito alto',
    ];

    public const CLASSIFICACOES = [
        'baixo' => 'Baixo',
        'medio' => 'Médio',
        'alto' => 'Alto',
        'critico' => 'Crítico',
    ];

    public const STATUS = [
        'identificado' => 'Identificado',
        'em_tratamento' => 'Em tratamento',
        'tratado' => 'Tratado',
        'cancelado' => 'Cancelado',
    ];

    protected $fillable = [
        'risco_identificado',
        'area_local',
        'atividade',
        'categoria',
        'probabilidade',
        'severidade',
        'classificacao_final',
        'medida_controle_existente',
        'medida_adicional_necessaria',
        'responsavel',
        'prazo',
        'status',
        'evidencia_path',
        'tratado_em',
    ];

    protected $casts = [
        'prazo' => 'date',
        'tratado_em' => 'date',
    ];

    public static function classificacaoFromScores(int $probabilidade, int $severidade): string
    {
        $p = max(1, min(5, $probabilidade));
        $s = max(1, min(5, $severidade));
        $produto = $p * $s;

        if ($produto <= 4) {
            return 'baixo';
        }
        if ($produto <= 9) {
            return 'medio';
        }
        if ($produto <= 15) {
            return 'alto';
        }

        return 'critico';
    }

    protected static function booted(): void
    {
        static::saving(function (SsmaRisco $risco) {
            $risco->classificacao_final = self::classificacaoFromScores(
                (int) $risco->probabilidade,
                (int) $risco->severidade
            );

            if ($risco->status === 'tratado' && $risco->tratado_em === null) {
                $risco->tratado_em = now()->toDateString();
            }
            if ($risco->status !== 'tratado') {
                $risco->tratado_em = null;
            }
        });
    }

    public function rotuloCategoria(): string
    {
        return self::CATEGORIAS[$this->categoria] ?? $this->categoria;
    }

    public function rotuloClassificacao(): string
    {
        return self::CLASSIFICACOES[$this->classificacao_final] ?? $this->classificacao_final;
    }

    public function rotuloStatus(): string
    {
        return self::STATUS[$this->status] ?? $this->status;
    }

    public function scopeFiltrar(Builder $q, ?string $busca = null, ?string $status = null, ?string $categoria = null, ?string $classificacao = null): Builder
    {
        if ($busca !== null && $busca !== '') {
            $b = "%{$busca}%";
            $q->where(function (Builder $w) use ($b) {
                $w->where('risco_identificado', 'like', $b)
                    ->orWhere('atividade', 'like', $b)
                    ->orWhere('area_local', 'like', $b)
                    ->orWhere('responsavel', 'like', $b)
                    ->orWhere('medida_adicional_necessaria', 'like', $b);
            });
        }
        if ($status !== null && $status !== '') {
            $q->where('status', $status);
        }
        if ($categoria !== null && $categoria !== '') {
            $q->where('categoria', $categoria);
        }
        if ($classificacao !== null && $classificacao !== '') {
            $q->where('classificacao_final', $classificacao);
        }

        return $q;
    }
}

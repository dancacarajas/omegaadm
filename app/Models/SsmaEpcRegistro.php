<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SsmaEpcRegistro extends Model
{
    protected $table = 'ssma_epc_registros';

    public const CONDICOES = [
        'conforme' => 'Conforme',
        'nao_conforme' => 'Não conforme',
        'em_observacao' => 'Em observação',
    ];

    protected $fillable = [
        'local',
        'tipo_epc',
        'condicao',
        'necessita_correcao',
        'risco_associado',
        'responsavel',
        'prazo',
        'evidencia_foto_path',
    ];

    protected $casts = [
        'necessita_correcao' => 'boolean',
        'prazo' => 'date',
    ];

    public function rotuloCondicao(): string
    {
        return self::CONDICOES[$this->condicao] ?? $this->condicao;
    }

    public function estaNaoConforme(): bool
    {
        return $this->condicao === 'nao_conforme' || $this->necessita_correcao;
    }

    public function scopeFiltrarEpc(Builder $q, ?string $busca = null, ?string $condicao = null, ?string $local = null): Builder
    {
        if ($busca !== null && $busca !== '') {
            $b = "%{$busca}%";
            $q->where(function (Builder $w) use ($b) {
                $w->where('local', 'like', $b)
                    ->orWhere('tipo_epc', 'like', $b)
                    ->orWhere('risco_associado', 'like', $b)
                    ->orWhere('responsavel', 'like', $b);
            });
        }
        if ($condicao !== null && $condicao !== '') {
            $q->where('condicao', $condicao);
        }
        if ($local !== null && $local !== '') {
            $q->where('local', 'like', "%{$local}%");
        }

        return $q;
    }
}

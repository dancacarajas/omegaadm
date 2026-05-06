<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SesmtTarefa extends Model
{
    public const TIPOS_PADRAO = [
        'ART',
        'OS',
        'ANUENCIAS',
        'PAEBM',
        'CHECKLIST',
        'CONTROLE_DE_EPIS',
        'CAROMETRO',
        'PASSAPORTE_COMPLEMENTAR',
        'CADERNO_LISTA_PRESENCA_DDS',
        'MINISTRAR_TREINAMENTO',
        'INSPECOES',
    ];

    /** Tipos considerados críticos para indicador (não concluídos ou fora do prazo). */
    public const TIPOS_CRITICOS = [
        'ART',
        'PAEBM',
        'INSPECOES',
        'MINISTRAR_TREINAMENTO',
    ];

    public const LABELS = [
        'ART' => 'ART',
        'OS' => 'OS',
        'ANUENCIAS' => 'Anuências',
        'PAEBM' => 'PAEBM',
        'CHECKLIST' => 'Checklist',
        'CONTROLE_DE_EPIS' => 'Controle de EPIs',
        'CAROMETRO' => 'Carômetro',
        'PASSAPORTE_COMPLEMENTAR' => 'Passaporte/Complementar',
        'CADERNO_LISTA_PRESENCA_DDS' => 'Caderno e Lista de Presença DDS',
        'MINISTRAR_TREINAMENTO' => 'Ministrar Treinamento',
        'INSPECOES' => 'Inspeções',
    ];

    protected $fillable = [
        'colaborador_id',
        'tipo',
        'status',
        'data_prevista',
        'data_conclusao',
        'responsavel',
        'observacoes',
    ];

    protected $casts = [
        'data_prevista' => 'date',
        'data_conclusao' => 'date',
    ];

    public function colaborador()
    {
        return $this->belongsTo(Colaborador::class);
    }

    public function getLabelAttribute(): string
    {
        return self::LABELS[$this->tipo] ?? $this->tipo;
    }

    public function estaVencida(): bool
    {
        if ($this->status === 'concluido' || $this->data_prevista === null) {
            return false;
        }

        return $this->data_prevista->copy()->startOfDay()->lt(now()->startOfDay());
    }

    public function pendenciaEhCritica(): bool
    {
        if ($this->status === 'concluido') {
            return false;
        }

        if ($this->estaVencida()) {
            return true;
        }

        return in_array($this->tipo, self::TIPOS_CRITICOS, true);
    }
}

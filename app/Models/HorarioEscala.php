<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HorarioEscala extends Model
{
    protected $table = 'horario_escalas';

    public const TIPOS = [
        'semanal' => 'Semanal (fixo por dia da semana)',
        'rotativa' => 'Rotativa (ciclo no calendario)',
        'rotativa_semanal' => 'Rotativa (alterna a cada semana - motoristas)',
        'rotativa_dias_uteis' => 'Rotativa por dias uteis',
        'rotativa_veiculos' => 'Rotativa veiculos (micro-onibus/caminhonete)',
    ];

    protected $fillable = [
        'nome',
        'tipo',
        'ciclo_dias',
        'data_inicio_ciclo',
        'status',
    ];

    protected $casts = [
        'data_inicio_ciclo' => 'date',
        'ciclo_dias' => 'integer',
    ];

    public function isRotativa(): bool
    {
        return $this->tipo === 'rotativa';
    }

    public function isRotativaSemanal(): bool
    {
        return $this->tipo === 'rotativa_semanal';
    }

    public function isRotativaDiasUteis(): bool
    {
        return $this->tipo === 'rotativa_dias_uteis';
    }

    public function isRotativaVeiculos(): bool
    {
        return $this->tipo === 'rotativa_veiculos';
    }

    public function usaRotatividade(): bool
    {
        return $this->isRotativa()
            || $this->isRotativaSemanal()
            || $this->isRotativaDiasUteis()
            || $this->isRotativaVeiculos();
    }

    public function isSemanal(): bool
    {
        return $this->tipo === 'semanal' || $this->tipo === null || $this->tipo === '';
    }

    public function rotuloTipo(): string
    {
        return self::TIPOS[$this->tipo] ?? (string) $this->tipo;
    }

    public function dias(): HasMany
    {
        return $this->hasMany(HorarioEscalaDia::class)->orderBy('dia_semana');
    }

    public function colaboradores(): HasMany
    {
        return $this->hasMany(Colaborador::class, 'horario_escala_id');
    }

    public function excecoes(): HasMany
    {
        return $this->hasMany(HorarioEscalaExcecao::class)->orderByDesc('data_inicio');
    }
}

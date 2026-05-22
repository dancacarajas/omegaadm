<?php

namespace App\Models;

use App\Support\Rh\AfastamentoAcidenteTrabalho;
use App\Support\Rh\ColaboradorMovimentacaoSituacao;
use App\Support\Rh\ColaboradorMovimentacaoTipos;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ColaboradorMovimentacao extends Model
{
    protected $table = 'colaborador_movimentacoes';

    protected $fillable = [
        'colaborador_id',
        'tipo',
        'situacao',
        'data_inicio',
        'data_fim',
        'status_anterior',
        'status_novo',
        'centro_custo_anterior',
        'centro_custo_novo',
        'tipo_contrato_anterior',
        'tipo_contrato_novo',
        'local_trabalho_anterior',
        'local_trabalho_novo',
        'departamento_anterior',
        'departamento_novo',
        'cargo_anterior',
        'cargo_novo',
        'salario_anterior',
        'salario_novo',
        'tipo_rescisao',
        'motivo_codigo',
        'motivo_texto',
        'especie_beneficio_inss',
        'cid',
        'dias_ferias',
        'abono_pecuniario',
        'registrado_por_user_id',
        'observacoes',
        'finalizada_em',
        'finalizada_por_user_id',
    ];

    protected $casts = [
        'colaborador_id' => 'integer',
        'registrado_por_user_id' => 'integer',
        'finalizada_por_user_id' => 'integer',
        'finalizada_em' => 'datetime',
        'data_inicio' => 'date',
        'data_fim' => 'date',
        'salario_anterior' => 'decimal:2',
        'salario_novo' => 'decimal:2',
        'abono_pecuniario' => 'boolean',
        'dias_ferias' => 'integer',
    ];

    public function colaborador(): BelongsTo
    {
        return $this->belongsTo(Colaborador::class);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por_user_id');
    }

    public function finalizadaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalizada_por_user_id');
    }

    public function isPendente(): bool
    {
        return $this->situacao === ColaboradorMovimentacaoSituacao::PENDENTE;
    }

    public function isFinalizada(): bool
    {
        return $this->situacao === ColaboradorMovimentacaoSituacao::FINALIZADA;
    }

    public function situacaoLabel(): string
    {
        return ColaboradorMovimentacaoSituacao::label((string) $this->situacao);
    }

    public function scopePendente(Builder $query): Builder
    {
        return $query->where('situacao', ColaboradorMovimentacaoSituacao::PENDENTE);
    }

    public function scopeFinalizada(Builder $query): Builder
    {
        return $query->where('situacao', ColaboradorMovimentacaoSituacao::FINALIZADA);
    }

    public function scopeEfetiva(Builder $query): Builder
    {
        return $query->where('situacao', ColaboradorMovimentacaoSituacao::FINALIZADA);
    }

    public function tipoLabel(): string
    {
        return ColaboradorMovimentacaoTipos::label((string) $this->tipo);
    }

    public function resumoAlteracao(): string
    {
        return match ($this->tipo) {
            ColaboradorMovimentacaoTipos::DESLIGAMENTO => $this->resumoDesligamento(),
            ColaboradorMovimentacaoTipos::TRANSFERENCIA_CONTRATO => $this->resumoPar($this->centro_custo_anterior, $this->centro_custo_novo, 'Contrato'),
            ColaboradorMovimentacaoTipos::PROMOCAO => $this->resumoPar($this->cargo_anterior, $this->cargo_novo, 'Cargo')
                .($this->salario_novo ? ' · Salário '.$this->fmtMoney($this->salario_novo) : ''),
            ColaboradorMovimentacaoTipos::MUDANCA_FUNCAO => $this->resumoPar($this->cargo_anterior, $this->cargo_novo, 'Função'),
            ColaboradorMovimentacaoTipos::FERIAS => $this->resumoPeriodo()
                .($this->dias_ferias ? " ({$this->dias_ferias} dias)" : ''),
            ColaboradorMovimentacaoTipos::AFASTAMENTO_INSS => $this->resumoAfastamentoInss(),
            default => '—',
        };
    }

    private function resumoDesligamento(): string
    {
        $tipo = ColaboradorMovimentacaoTipos::tiposRescisao()[$this->tipo_rescisao] ?? $this->tipo_rescisao;

        return trim(($tipo ?: 'Desligamento').($this->motivo_texto ? ' — '.$this->motivo_texto : ''));
    }

    private function resumoPar(?string $antes, ?string $depois, string $rotulo): string
    {
        if (blank($antes) && blank($depois)) {
            return '—';
        }

        return $rotulo.': '.($antes ?: '—').' → '.($depois ?: '—');
    }

    private function resumoPeriodo(): string
    {
        $ini = $this->data_inicio?->format('d/m/Y') ?? '—';
        $fim = $this->data_fim?->format('d/m/Y')
            ?? ($this->isPendente() ? 'pendente finalização' : '—');

        return "{$ini} até {$fim}";
    }

    private function resumoAfastamentoInss(): string
    {
        $periodo = $this->resumoPeriodo();
        $especie = ColaboradorMovimentacaoTipos::labelEspecieInss($this->especie_beneficio_inss);
        $partes = array_filter([$periodo, $especie !== '' ? $especie : null]);

        if (AfastamentoAcidenteTrabalho::especieElegivel($this->especie_beneficio_inss)) {
            $partes[] = 'Vale alimentação integral até 3º mês (regra benefícios)';
        }

        return implode(' · ', $partes);
    }

    private function fmtMoney(mixed $valor): string
    {
        return 'R$ '.number_format((float) $valor, 2, ',', '.');
    }
}

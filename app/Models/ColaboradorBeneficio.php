<?php

namespace App\Models;

use App\Support\Rh\BeneficioAdesaoStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ColaboradorBeneficio extends Model
{
    protected $fillable = [
        'colaborador_id',
        'beneficio_id',
        'tem_direito',
        'cartao_entregue',
        'beneficio_ativo',
        'status_adesao',
        'data_formulario_recebido',
        'formulario_adesao_assinado_path',
        'data_envio_matriz',
        'protocolo_matriz',
        'data_aviso_coleta_matriz',
        'data_retorno_matriz',
        'data_previsao_cartao',
        'adesao_atualizado_por_id',
        'data_direito',
        'data_entrega_cartao',
        'numero_cartao',
        'observacoes',
    ];

    protected $casts = [
        'colaborador_id' => 'integer',
        'beneficio_id' => 'integer',
        'tem_direito' => 'boolean',
        'cartao_entregue' => 'boolean',
        'beneficio_ativo' => 'boolean',
        'data_formulario_recebido' => 'date',
        'data_envio_matriz' => 'date',
        'data_aviso_coleta_matriz' => 'date',
        'data_retorno_matriz' => 'date',
        'data_previsao_cartao' => 'date',
        'data_direito' => 'date',
        'data_entrega_cartao' => 'date',
        'adesao_atualizado_por_id' => 'integer',
    ];

    public function colaborador(): BelongsTo
    {
        return $this->belongsTo(Colaborador::class);
    }

    public function beneficio(): BelongsTo
    {
        return $this->belongsTo(Beneficio::class);
    }

    public function adesaoAtualizadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'adesao_atualizado_por_id');
    }

    public function webcardSolicitacoes()
    {
        return $this->hasMany(ColaboradorBeneficioWebcardSolicitacao::class);
    }

    public function usaControleAdesao(): bool
    {
        return (bool) ($this->beneficio?->requer_controle_adesao ?? false);
    }

    public function rotuloStatusAdesao(): string
    {
        return BeneficioAdesaoStatus::rotulo((string) ($this->status_adesao ?? ''));
    }

    public function rotuloStatusAdesaoCurto(): string
    {
        return BeneficioAdesaoStatus::rotuloCurto((string) ($this->status_adesao ?? ''));
    }

    public function badgeStatusAdesao(): string
    {
        return BeneficioAdesaoStatus::corBadge((string) ($this->status_adesao ?? ''));
    }

    public function indicadorStatusAdesao(): string
    {
        return BeneficioAdesaoStatus::corIndicador((string) ($this->status_adesao ?? ''));
    }

    public function adesaoEmAndamento(): bool
    {
        return in_array($this->status_adesao, BeneficioAdesaoStatus::emAndamento(), true);
    }

    public function temFormularioAdesaoAssinado(): bool
    {
        return filled($this->formulario_adesao_assinado_path)
            && Storage::disk('public')->exists((string) $this->formulario_adesao_assinado_path);
    }

    public function urlFormularioAdesaoAssinado(): ?string
    {
        if (! $this->temFormularioAdesaoAssinado()) {
            return null;
        }

        return route('rh.beneficios.vinculos.formulario-adesao', [
            'beneficio' => $this->beneficio_id,
            'vinculo' => $this->id,
        ]);
    }

    protected static function booted(): void
    {
        static::deleting(function (ColaboradorBeneficio $vinculo): void {
            if (filled($vinculo->formulario_adesao_assinado_path)) {
                Storage::disk('public')->delete((string) $vinculo->formulario_adesao_assinado_path);
            }
        });
    }
}

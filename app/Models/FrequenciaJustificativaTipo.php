<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FrequenciaJustificativaTipo extends Model
{
    public const CATEGORIAS = [
        'atestado' => 'Atestado',
        'abono' => 'Abono',
        'compensacao' => 'Compensação',
        'justificativa' => 'Justificativa',
        'folga' => 'Folga',
        'outro' => 'Outro',
    ];

    protected $table = 'frequencia_justificativa_tipos';

    protected $fillable = [
        'nome',
        'categoria',
        'limpa_batidas',
        'ativo',
        'ordem',
    ];

    protected $casts = [
        'limpa_batidas' => 'boolean',
        'ativo' => 'boolean',
        'ordem' => 'integer',
    ];

    public function registros(): HasMany
    {
        return $this->hasMany(FrequenciaRegistro::class, 'justificativa_tipo_id');
    }

    public function categoriaLegado(): string
    {
        return match ($this->categoria) {
            'atestado' => 'atestado',
            'abono' => 'abono',
            'folga' => 'folga',
            'compensacao', 'justificativa', 'outro' => in_array($this->categoria, ['compensacao', 'justificativa'], true)
                ? 'justificativa'
                : 'outro',
            default => 'outro',
        };
    }

    public function rotuloCompleto(?string $observacao = null): string
    {
        $texto = trim($this->nome);
        $obs = trim((string) $observacao);

        return $obs !== '' && $obs !== $texto ? "{$texto} — {$obs}" : $texto;
    }
}

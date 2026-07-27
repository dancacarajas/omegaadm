<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class MedicaoPresencaObraAnexo extends Model
{
    protected $table = 'medicao_presenca_obra_anexos';

    protected $fillable = [
        'registro_id',
        'nome_original',
        'caminho',
        'mime',
        'tamanho',
    ];

    public function registro(): BelongsTo
    {
        return $this->belongsTo(MedicaoPresencaObraRegistro::class, 'registro_id');
    }

    public function urlPublica(): ?string
    {
        if ($this->caminho === '') {
            return null;
        }

        return Storage::disk('public')->url($this->caminho);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class SsmaTstRegistroFoto extends Model
{
    protected $table = 'ssma_tst_registro_fotos';

    protected $fillable = [
        'ssma_tst_registro_id',
        'arquivo_path',
        'arquivo_nome',
        'arquivo_mime',
        'ordem',
    ];

    protected $casts = [
        'ordem' => 'integer',
    ];

    public function registro(): BelongsTo
    {
        return $this->belongsTo(SsmaTstRegistro::class, 'ssma_tst_registro_id');
    }

    public function urlPublica(): ?string
    {
        if (! $this->arquivo_path) {
            return null;
        }

        return asset('storage/'.ltrim($this->arquivo_path, '/'));
    }

    public function removerArquivo(): void
    {
        if ($this->arquivo_path) {
            Storage::disk('public')->delete($this->arquivo_path);
        }
    }
}

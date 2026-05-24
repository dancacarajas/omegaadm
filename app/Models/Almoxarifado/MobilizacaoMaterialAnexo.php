<?php

namespace App\Models\Almoxarifado;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class MobilizacaoMaterialAnexo extends Model
{
    protected $table = 'mobilizacao_material_anexos';

    protected $fillable = [
        'mobilizacao_material_id',
        'tipo_anexo',
        'nome_arquivo',
        'caminho_arquivo',
        'observacao',
        'uploaded_by',
    ];

    public function material(): BelongsTo
    {
        return $this->belongsTo(MobilizacaoMaterial::class, 'mobilizacao_material_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function urlPublica(): string
    {
        return asset('storage/'.$this->caminho_arquivo);
    }
}

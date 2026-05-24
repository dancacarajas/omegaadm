<?php

namespace App\Models\Almoxarifado;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MobilizacaoMaterialCategoria extends Model
{
    protected $table = 'mobilizacao_material_categorias';

    protected $fillable = [
        'nome',
        'descricao',
        'cor',
        'ordem',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'ordem' => 'integer',
    ];

    public function materiais(): HasMany
    {
        return $this->hasMany(MobilizacaoMaterial::class, 'categoria_id');
    }
}

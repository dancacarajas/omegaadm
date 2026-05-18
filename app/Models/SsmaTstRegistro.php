<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SsmaTstRegistro extends Model
{
    protected $table = 'ssma_tst_registros';

    protected $fillable = [
        'ssma_tst_atividade_id',
        'data',
        'colaborador_id',
        'descricao',
        'arquivo_path',
        'arquivo_nome',
        'arquivo_mime',
        'user_id',
        'origem',
    ];

    protected $casts = [
        'data' => 'date',
    ];

    public function atividade(): BelongsTo
    {
        return $this->belongsTo(SsmaTstAtividade::class, 'ssma_tst_atividade_id');
    }

    public function colaborador(): BelongsTo
    {
        return $this->belongsTo(Colaborador::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function fotos(): HasMany
    {
        return $this->hasMany(SsmaTstRegistroFoto::class, 'ssma_tst_registro_id')->orderBy('ordem');
    }

    public function sincronizarCamposLegados(): void
    {
        $primeira = $this->fotos()->orderBy('ordem')->first();

        $this->forceFill([
            'arquivo_path' => $primeira?->arquivo_path,
            'arquivo_nome' => $primeira?->arquivo_nome,
            'arquivo_mime' => $primeira?->arquivo_mime,
        ])->saveQuietly();
    }

    public function removerTodosArquivos(): void
    {
        $this->loadMissing('fotos');

        foreach ($this->fotos as $foto) {
            $foto->removerArquivo();
            $foto->delete();
        }

        $this->forceFill([
            'arquivo_path' => null,
            'arquivo_nome' => null,
            'arquivo_mime' => null,
        ])->saveQuietly();
    }

    public function scopeFiltrar(
        $query,
        ?string $busca,
        ?string $dataDe,
        ?string $dataAte,
        ?int $atividadeId,
        ?int $colaboradorId,
    ) {
        return $query
            ->when($busca !== null && $busca !== '', function ($q) use ($busca) {
                $q->where(function ($inner) use ($busca) {
                    $inner->where('descricao', 'like', '%'.$busca.'%')
                        ->orWhereHas('colaborador', fn ($c) => $c->where('nome', 'like', '%'.$busca.'%'))
                        ->orWhereHas('atividade', fn ($a) => $a->where('nome', 'like', '%'.$busca.'%'));
                });
            })
            ->when($dataDe, fn ($q) => $q->whereDate('data', '>=', $dataDe))
            ->when($dataAte, fn ($q) => $q->whereDate('data', '<=', $dataAte))
            ->when($atividadeId, fn ($q) => $q->where('ssma_tst_atividade_id', $atividadeId))
            ->when($colaboradorId, fn ($q) => $q->where('colaborador_id', $colaboradorId));
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Perfil extends Model
{
    protected $table = 'perfis';

    protected $fillable = [
        'nome',
        'descricao',
        'permissoes',
        'ativo',
    ];

    protected $casts = [
        'permissoes' => 'array',
        'ativo' => 'boolean',
    ];

    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** Perfil mestre: acesso a todos os módulos/ações, inclusive os criados depois. */
    public function acessoTotalAoSistema(): bool
    {
        return strcasecmp(trim((string) $this->nome), 'Administrador') === 0;
    }

    /**
     * Se o JSON do perfil ainda não tem sesmt.secoes, considera todas as áreas liberadas (compatibilidade).
     */
    public function permiteSecaoSesmt(string $secao): bool
    {
        if (! array_key_exists($secao, User::sesmtSecoesDefinicao())) {
            return false;
        }

        $secoes = data_get($this->permissoes, 'sesmt.secoes');
        if (! is_array($secoes) || $secoes === []) {
            return true;
        }

        $known = array_keys(User::sesmtSecoesDefinicao());
        $temChaveConhecida = false;
        foreach ($known as $k) {
            if (array_key_exists($k, $secoes)) {
                $temChaveConhecida = true;

                break;
            }
        }

        if (! $temChaveConhecida) {
            return true;
        }

        return (bool) ($secoes[$secao] ?? false);
    }

    /**
     * Se o JSON do perfil ainda não tem rh.secoes, considera todas as áreas liberadas (compatibilidade).
     */
    public function permiteSecaoRh(string $secao): bool
    {
        if (! array_key_exists($secao, User::rhSecoesDefinicao())) {
            return false;
        }

        $secoes = data_get($this->permissoes, 'rh.secoes');
        if (! is_array($secoes) || $secoes === []) {
            return true;
        }

        $known = array_keys(User::rhSecoesDefinicao());
        $temChaveConhecida = false;
        foreach ($known as $k) {
            if (array_key_exists($k, $secoes)) {
                $temChaveConhecida = true;

                break;
            }
        }

        if (! $temChaveConhecida) {
            return true;
        }

        return (bool) ($secoes[$secao] ?? false);
    }
}

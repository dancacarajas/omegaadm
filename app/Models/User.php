<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'perfil_id',
        'todos_contratos',
        'name',
        'email',
        'telefone',
        'cargo',
        'password',
        'status',
        'ultimo_acesso_em',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'ultimo_acesso_em' => 'datetime',
            'todos_contratos' => 'boolean',
        ];
    }

    public function perfil(): BelongsTo
    {
        return $this->belongsTo(Perfil::class);
    }

    /**
     * Usuário sem perfil: mantém acesso total (compatibilidade). Com perfil ativo, aplica matriz de permissões.
     */
    public function temQualquerPermissaoNoModulo(string $modulo): bool
    {
        if (! $this->perfil_id) {
            return true;
        }

        $perfil = $this->relationLoaded('perfil') ? $this->perfil : $this->perfil()->first();

        if (! $perfil || ! $perfil->ativo) {
            return false;
        }

        $matriz = $perfil->permissoes[$modulo] ?? null;
        if (! is_array($matriz)) {
            return false;
        }

        foreach ($matriz as $chave => $permitido) {
            if ($chave === 'secoes') {
                continue;
            }
            if (is_array($permitido)) {
                continue;
            }
            if ((bool) $permitido) {
                return true;
            }
        }

        return false;
    }

    /**
     * Áreas do menu SSMA (chave persistida em permissoes.sesmt.secoes.*).
     *
     * @return array<string, string>
     */
    public static function sesmtSecoesDefinicao(): array
    {
        return [
            'conformidade' => 'Controle de Conformidade',
            'plano_acao' => 'Plano de Ação',
            'gestao_riscos' => 'Gestão de Riscos',
            'epi_epc' => 'Gestão de EPI/EPC',
            'meio_ambiente' => 'Meio Ambiente',
            'registro_mensal' => 'Registro Mensal',
            'prazos_sla' => 'Prazos (SLA)',
            'indicadores_mensais' => 'Indicadores mensais',
            'registros_tst' => 'Registros TST',
        ];
    }

    /** Nome da rota Laravel → chave de {@see sesmtSecoesDefinicao()} ou null. */
    public static function sesmtSecaoFromRouteName(?string $routeName): ?string
    {
        if ($routeName === null || $routeName === '' || ! str_starts_with($routeName, 'sesmt.')) {
            return null;
        }

        if ($routeName === 'sesmt.index' || $routeName === 'sesmt.sync' || str_starts_with($routeName, 'sesmt.tarefas.')) {
            return 'conformidade';
        }

        if (str_starts_with($routeName, 'sesmt.plano-acao')) {
            return 'plano_acao';
        }

        if (str_starts_with($routeName, 'sesmt.riscos')) {
            return 'gestao_riscos';
        }

        if (str_starts_with($routeName, 'sesmt.epi-epc')) {
            return 'epi_epc';
        }

        if (str_starts_with($routeName, 'sesmt.meio-ambiente')) {
            return 'meio_ambiente';
        }

        if (str_starts_with($routeName, 'sesmt.registros.prazos')) {
            return 'prazos_sla';
        }

        if (str_starts_with($routeName, 'sesmt.registros')) {
            return 'registro_mensal';
        }

        if (str_starts_with($routeName, 'sesmt.indicadores-mensais')) {
            return 'indicadores_mensais';
        }

        if (str_starts_with($routeName, 'sesmt.registros-tst')) {
            return 'registros_tst';
        }

        return null;
    }

    /**
     * Acesso à área específica do SSMA (exige permissão no módulo sesmt + área marcada no perfil).
     * Se permissoes.sesmt.secoes não existir (perfis antigos), todas as áreas ficam liberadas.
     */
    public function podeSecaoSesmt(string $secao): bool
    {
        if (! array_key_exists($secao, self::sesmtSecoesDefinicao())) {
            return false;
        }

        if (! $this->perfil_id) {
            return true;
        }

        if (! $this->temQualquerPermissaoNoModulo('sesmt')) {
            return false;
        }

        $perfil = $this->relationLoaded('perfil') ? $this->perfil : $this->perfil()->first();

        if (! $perfil || ! $perfil->ativo) {
            return false;
        }

        $secoes = data_get($perfil->permissoes, 'sesmt.secoes');
        if (! is_array($secoes) || $secoes === []) {
            return true;
        }

        $known = array_keys(self::sesmtSecoesDefinicao());
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

        if ($secao === 'indicadores_mensais' && ! array_key_exists('indicadores_mensais', $secoes)) {
            return true;
        }

        return (bool) ($secoes[$secao] ?? false);
    }

    /** Há pelo menos uma área do SSMA liberada (para exibir o grupo no menu). */
    public function temAlgumaSecaoSesmt(): bool
    {
        if (! $this->perfil_id) {
            return true;
        }

        if (! $this->temQualquerPermissaoNoModulo('sesmt')) {
            return false;
        }

        foreach (array_keys(self::sesmtSecoesDefinicao()) as $secao) {
            if ($this->podeSecaoSesmt($secao)) {
                return true;
            }
        }

        return false;
    }

    public function primeiraUrlSesmtPermitida(): ?string
    {
        $rotas = [
            'conformidade' => fn () => route('sesmt.index'),
            'plano_acao' => fn () => route('sesmt.plano-acao.index'),
            'gestao_riscos' => fn () => route('sesmt.riscos.index'),
            'epi_epc' => fn () => route('sesmt.epi-epc.index'),
            'meio_ambiente' => fn () => route('sesmt.meio-ambiente.index'),
            'registro_mensal' => fn () => route('sesmt.registros.index'),
            'prazos_sla' => fn () => route('sesmt.registros.prazos.index'),
            'indicadores_mensais' => fn () => route('sesmt.indicadores-mensais.painel-executivo'),
            'registros_tst' => fn () => route('sesmt.registros-tst.registros.index'),
        ];

        foreach ($rotas as $secao => $resolver) {
            if ($this->podeSecaoSesmt($secao)) {
                return $resolver();
            }
        }

        return null;
    }

    public function podeAcaoNoModulo(string $modulo, string $acao): bool
    {
        if (! $this->perfil_id) {
            return true;
        }

        $perfil = $this->relationLoaded('perfil') ? $this->perfil : $this->perfil()->first();

        if (! $perfil || ! $perfil->ativo) {
            return false;
        }

        return (bool) data_get($perfil->permissoes, "{$modulo}.{$acao}", false);
    }

    /** Primeira rota permitida após login ou quando o painel não é permitido. */
    public function urlInicialAposLogin(): ?string
    {
        $mapa = [
            'dashboard' => fn () => route('dashboard'),
            'rh' => fn () => route('rh.dashboard'),
            'veiculos' => fn () => route('veiculos.index'),
            'sesmt' => fn () => $this->primeiraUrlSesmtPermitida(),
            'contratos' => fn () => route('contratos.index'),
            'patrimonial' => fn () => route('patrimonial.index'),
            'medicao' => fn () => route('medicao.index'),
            'rdo' => fn () => route('rdo.index'),
            'acessos' => fn () => route('usuarios.index'),
        ];

        foreach ($mapa as $modulo => $resolver) {
            if (! $this->temQualquerPermissaoNoModulo($modulo)) {
                continue;
            }
            $url = $resolver();
            if ($url !== null && $url !== '') {
                return $url;
            }
        }

        return null;
    }

    public function contratos(): BelongsToMany
    {
        return $this->belongsToMany(Contrato::class)->withTimestamps();
    }
}

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
        'colaborador_id',
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

    public function colaborador(): BelongsTo
    {
        return $this->belongsTo(Colaborador::class);
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

    /**
     * Áreas do menu RH (chave persistida em permissoes.rh.secoes.*).
     *
     * @return array<string, string>
     */
    public static function rhSecoesDefinicao(): array
    {
        return [
            'dashboard' => 'Painel RH',
            'efetivo' => 'Efetivo',
            'chamados_movimentacao' => 'Movimentações',
            'beneficios' => 'Benefícios',
            'recrutamento' => 'Recrutamento',
            'frequencia_ponto' => 'Frequência — Ponto diário',
            'frequencia_apuracao' => 'Frequência — Apuração do Ponto',
            'frequencia_feriados' => 'Frequência — Feriados',
            'frequencia_justificativas' => 'Frequência — Tipos de justificativa',
            'horarios' => 'Cadastro de horários',
            'indicadores_mensais' => 'Indicadores mensais — Painel Executivo',
        ];
    }

    /** Nome da rota Laravel → chave de {@see rhSecoesDefinicao()} ou null. */
    /**
     * Ação CRUD exigida pela rota RH (visualizar, criar, editar, excluir).
     * Rotas com regras próprias no controller (ex.: chamados-movimentacao) retornam null.
     */
    public static function acaoRhRequeridaParaRota(?string $routeName): ?string
    {
        if ($routeName === null || $routeName === '' || ! str_starts_with($routeName, 'rh.')) {
            return null;
        }

        if (str_starts_with($routeName, 'rh.chamados-movimentacao')) {
            return null;
        }

        if (preg_match('/\.(create|store)$/', $routeName) === 1) {
            return 'criar';
        }

        if (str_contains($routeName, '.importar')
            || str_contains($routeName, 'modelo-importacao')
            || str_contains($routeName, 'atualizacao-massa')) {
            return 'criar';
        }

        if (preg_match('/\.(edit|update)$/', $routeName) === 1) {
            return 'editar';
        }

        if (str_contains($routeName, '.foto.update')
            || str_contains($routeName, '.marcacao')
            || str_contains($routeName, '.justificar')
            || str_contains($routeName, '.limpar')
            || str_contains($routeName, '.apuracao.')
            || str_contains($routeName, '.salvar')
            || str_contains($routeName, '.aplicar')
            || str_contains($routeName, '.manage')
            || str_contains($routeName, 'config.salvar')
            || str_contains($routeName, 'regras.salvar')) {
            return 'editar';
        }

        if (preg_match('/\.(destroy|excluir)/', $routeName) === 1) {
            return 'excluir';
        }

        if (str_contains($routeName, '.finalizar')
            || str_contains($routeName, '.cancelar')
            || str_contains($routeName, 'concluir')) {
            return 'editar';
        }

        return 'visualizar';
    }

    public function podeExecutarRotaRh(?string $routeName): bool
    {
        if (! $this->temQualquerPermissaoNoModulo('rh')) {
            return false;
        }

        $secao = self::rhSecaoFromRouteName($routeName);
        if ($secao !== null && ! $this->podeSecaoRh($secao)) {
            return false;
        }

        $acao = self::acaoRhRequeridaParaRota($routeName);
        if ($acao === null) {
            return true;
        }

        return $this->podeAcaoNoModulo('rh', $acao);
    }

    public static function rhSecaoFromRouteName(?string $routeName): ?string
    {
        if ($routeName === null || $routeName === '' || ! str_starts_with($routeName, 'rh.')) {
            return null;
        }

        if ($routeName === 'rh.dashboard') {
            return 'dashboard';
        }

        if (str_starts_with($routeName, 'rh.chamados-movimentacao')) {
            return 'chamados_movimentacao';
        }

        if (str_contains($routeName, 'movimentacoes') || str_contains($routeName, 'movimentacao')) {
            return 'chamados_movimentacao';
        }

        if (str_starts_with($routeName, 'rh.efetivo')) {
            return 'efetivo';
        }

        if (str_starts_with($routeName, 'rh.beneficios')) {
            return 'beneficios';
        }

        if (str_starts_with($routeName, 'rh.recrutamento')) {
            return 'recrutamento';
        }

        if (str_starts_with($routeName, 'rh.indicadores-mensais')) {
            return 'indicadores_mensais';
        }

        if (str_starts_with($routeName, 'rh.frequencia.apuracao')) {
            return 'frequencia_apuracao';
        }

        if (str_starts_with($routeName, 'rh.frequencia.feriados')) {
            return 'frequencia_feriados';
        }

        if (str_starts_with($routeName, 'rh.frequencia.justificativa-tipos')) {
            return 'frequencia_justificativas';
        }

        if (str_starts_with($routeName, 'rh.horarios')) {
            return 'horarios';
        }

        if (str_starts_with($routeName, 'rh.frequencia')) {
            return 'frequencia_ponto';
        }

        return null;
    }

    /**
     * Acesso à área específica do RH (exige permissão no módulo rh + área marcada no perfil).
     * Se permissoes.rh.secoes não existir (perfis antigos), todas as áreas ficam liberadas.
     */
    public function podeSecaoRh(string $secao): bool
    {
        if (! array_key_exists($secao, self::rhSecoesDefinicao())) {
            return false;
        }

        if (! $this->perfil_id) {
            return true;
        }

        if (! $this->temQualquerPermissaoNoModulo('rh')) {
            return false;
        }

        $perfil = $this->relationLoaded('perfil') ? $this->perfil : $this->perfil()->first();

        if (! $perfil || ! $perfil->ativo) {
            return false;
        }

        $secoes = data_get($perfil->permissoes, 'rh.secoes');
        if (! is_array($secoes) || $secoes === []) {
            return true;
        }

        $known = array_keys(self::rhSecoesDefinicao());
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

    /** Há pelo menos uma área do RH liberada (para exibir o grupo no menu). */
    public function temAlgumaSecaoRh(): bool
    {
        if (! $this->perfil_id) {
            return true;
        }

        if (! $this->temQualquerPermissaoNoModulo('rh')) {
            return false;
        }

        foreach (array_keys(self::rhSecoesDefinicao()) as $secao) {
            if ($this->podeSecaoRh($secao)) {
                return true;
            }
        }

        return false;
    }

    public function primeiraUrlRhPermitida(): ?string
    {
        $rotas = [
            'dashboard' => fn () => route('rh.dashboard'),
            'efetivo' => fn () => route('rh.efetivo.index'),
            'chamados_movimentacao' => fn () => route('rh.chamados-movimentacao.index'),
            'beneficios' => fn () => route('rh.beneficios.index'),
            'recrutamento' => fn () => route('rh.recrutamento.index'),
            'frequencia_ponto' => fn () => route('rh.frequencia.index'),
            'frequencia_apuracao' => fn () => route('rh.frequencia.apuracao.index'),
            'frequencia_feriados' => fn () => route('rh.frequencia.feriados.index'),
            'frequencia_justificativas' => fn () => route('rh.frequencia.justificativa-tipos.index'),
            'horarios' => fn () => route('rh.horarios.index'),
            'indicadores_mensais' => fn () => route('rh.indicadores-mensais.painel-executivo'),
        ];

        foreach ($rotas as $secao => $resolver) {
            if ($this->podeSecaoRh($secao)) {
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
            'rh' => fn () => $this->primeiraUrlRhPermitida() ?? route('rh.dashboard'),
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

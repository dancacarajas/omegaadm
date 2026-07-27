<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Colaborador extends Model
{
    protected $table = 'colaboradores';

    protected $fillable = [
        'matricula',
        'nome',
        'telefone',
        'email',
        'recrutamento_vaga_id',
        'recrutamento_posicao',
        'foto_path',
        'filiacao_pai',
        'filiacao_mae',
        'cpf',
        'rg',
        'carteira_profissional',
        'serie_ctps',
        'pis',
        'titulo_eleitor',
        'zona_eleitoral',
        'secao_eleitoral',
        'carteira_identidade',
        'emissao_identidade',
        'orgao_emissor',
        'data_ctps',
        'vencimento_ctps',
        'data_nascimento',
        'estado_civil',
        'conjuge',
        'local_nascimento',
        'sexo',
        'grau_instrucao',
        'uf_nascimento',
        'cor',
        'nacionalidade',
        'endereco',
        'numero',
        'bairro',
        'cidade',
        'estado',
        'cep',
        'tipo_contrato',
        'departamento',
        'cargo',
        'cbo',
        'centro_custo',
        'jornada_semanal',
        'horario',
        'horario_escala_id',
        'horario_escala_ciclo_offset',
        'data_admissao',
        'data_opcao_fgts',
        'data_demissao',
        'forma_pagamento',
        'salario_inicial',
        'local_trabalho',
        'almoco',
        'status',
        'presenca_obra_liberado',
        'mobilizacao_status',
        'sgc_data_postagem',
        'sgc_numero_solicitacao',
        'sgc_data_aprovacao',
        'sgc_data_entrega_cracha',
        'sgc_observacoes',
        'dependentes',
        'contato_emergencia_nome',
        'contato_emergencia_telefone',
        'contato_emergencia_parentesco',
        'observacoes',
    ];

    protected $casts = [
        'data_ctps' => 'date',
        'vencimento_ctps' => 'date',
        'emissao_identidade' => 'date',
        'data_nascimento' => 'date',
        'data_admissao' => 'date',
        'data_opcao_fgts' => 'date',
        'data_demissao' => 'date',
        'sgc_data_postagem' => 'date',
        'sgc_data_aprovacao' => 'date',
        'sgc_data_entrega_cracha' => 'date',
        'salario_inicial' => 'decimal:2',
        'presenca_obra_liberado' => 'boolean',
    ];

    public function scopeElegivelVinculoBeneficio($query)
    {
        return $query->where('status', '!=', 'desligado');
    }

    public function podeVincularBeneficio(): bool
    {
        return $this->status !== 'desligado';
    }

    public function beneficios()
    {
        return $this->hasMany(ColaboradorBeneficio::class);
    }

    public function sesmtTarefas()
    {
        return $this->hasMany(SesmtTarefa::class);
    }

    public function frequencias()
    {
        return $this->hasMany(FrequenciaRegistro::class);
    }

    public function movimentacoes()
    {
        return $this->hasMany(ColaboradorMovimentacao::class)->orderByDesc('data_inicio')->orderByDesc('id');
    }

    public function horarioEscala()
    {
        return $this->belongsTo(HorarioEscala::class, 'horario_escala_id');
    }

    public function recrutamentoVaga(): BelongsTo
    {
        return $this->belongsTo(RecrutamentoVaga::class, 'recrutamento_vaga_id');
    }

    public function usuarioSistema(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(User::class, 'colaborador_id');
    }

    /**
     * URL da foto via rota Laravel (funciona em produção sem depender de symlink /storage).
     */
    public function urlFotoPerfil(): ?string
    {
        if (blank($this->foto_path)) {
            return null;
        }

        if ($this->exists) {
            return route('rh.efetivo.foto.show', $this);
        }

        $path = str_replace('\\', '/', (string) $this->foto_path);

        return asset('storage/'.ltrim($path, '/'));
    }

    /**
     * Dia da escala na data: semanal (dia_semana 1=seg … 7=dom) ou rotativa (posição no ciclo).
     */
    public function horarioEscalaDiaNaData(DateTimeInterface|string|null $data): ?HorarioEscalaDia
    {
        if (! $this->horario_escala_id || $data === null) {
            return null;
        }

        $this->loadMissing('horarioEscala.dias');

        $escala = $this->horarioEscala;
        if (! $escala) {
            return null;
        }

        $carbon = $data instanceof DateTimeInterface
            ? Carbon::parse($data)
            : Carbon::parse((string) $data);

        if ($escala->isRotativaSemanal()) {
            return \App\Support\HorarioEscalaSemanalAlternada::diaNaData($this, $carbon);
        }

        if ($escala->isRotativaDiasUteis()) {
            return \App\Support\HorarioEscalaDiasUteis::diaNaData($this, $carbon);
        }

        if ($escala->isRotativa()) {
            $indice = \App\Support\HorarioEscalaRotativa::indiceDiaCiclo(
                $escala,
                $carbon,
                (int) ($this->horario_escala_ciclo_offset ?? 0)
            );
            if ($indice === null) {
                return null;
            }

            return $escala->dias->firstWhere('dia_semana', $indice);
        }

        $diaSemana = (int) $carbon->isoWeekday();

        return $escala->dias->firstWhere('dia_semana', $diaSemana);
    }
}

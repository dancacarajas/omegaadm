<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Colaborador extends Model
{
    protected $table = 'colaboradores';

    protected $fillable = [
        'matricula',
        'nome',
        'telefone',
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
        'data_admissao',
        'data_opcao_fgts',
        'data_demissao',
        'forma_pagamento',
        'salario_inicial',
        'local_trabalho',
        'almoco',
        'status',
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
    ];

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
}

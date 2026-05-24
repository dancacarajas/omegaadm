<?php

namespace App\Models\Almoxarifado;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SigoExtracao extends Model
{
    protected $table = 'sigo_extracoes';

    public const STATUS_PENDENTE = 'pendente';

    public const STATUS_EXECUTANDO = 'executando';

    public const STATUS_CONCLUIDO = 'concluido';

    public const STATUS_ERRO = 'erro';

    protected $fillable = [
        'uuid',
        'user_id',
        'sigo_usuario',
        'sigo_senha_criptografada',
        'status',
        'paginas_lidas',
        'registros_brutos',
        'registros_unicos',
        'diretorio_relativo',
        'erro_tecnico',
        'erro_usuario',
        'iniciado_em',
        'finalizado_em',
    ];

    protected function casts(): array
    {
        return [
            'iniciado_em' => 'datetime',
            'finalizado_em' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function limparSenha(): void
    {
        $this->forceFill(['sigo_senha_criptografada' => null])->save();
    }

    /** @return array<string, mixed> */
    public function paraPainel(): array
    {
        return [
            'uuid' => $this->uuid,
            'status' => $this->status,
            'data_extracao' => $this->finalizado_em?->format('Y-m-d H:i:s'),
            'paginas_lidas' => $this->paginas_lidas,
            'registros_brutos' => $this->registros_brutos,
            'registros_unicos' => $this->registros_unicos,
            'erro' => $this->erro_usuario,
            'token' => $this->uuid,
        ];
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SistemaConfiguracaoEmail extends Model
{
    protected $table = 'sistema_configuracao_email';

    protected $fillable = [
        'mail_mailer',
        'mail_encryption',
        'mail_host',
        'mail_port',
        'mail_username',
        'mail_password',
        'mail_from_name',
        'mail_from_address',
        'updated_by_id',
    ];

    protected $casts = [
        'mail_port' => 'integer',
        'mail_password' => 'encrypted',
    ];

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }

    public static function registro(): self
    {
        return static::query()->firstOrCreate(
            ['id' => 1],
            [
                'mail_mailer' => config('mail.default', 'log'),
                'mail_encryption' => 'tls',
                'mail_host' => config('mail.mailers.smtp.host'),
                'mail_port' => (int) config('mail.mailers.smtp.port', 587),
                'mail_username' => config('mail.mailers.smtp.username'),
                'mail_from_name' => config('mail.from.name'),
                'mail_from_address' => config('mail.from.address'),
            ]
        );
    }

    public function senhaConfigurada(): bool
    {
        return filled($this->mail_password);
    }
}

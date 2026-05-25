<?php

namespace App\Listeners;

use App\Services\EmailEnvioLogService;
use Illuminate\Mail\Events\MessageSent;

final class LogEmailEnviadoListener
{
    public function __construct(
        private readonly EmailEnvioLogService $logService,
    ) {}

    public function handle(MessageSent $event): void
    {
        $this->logService->registrarMessageSent($event);
    }
}

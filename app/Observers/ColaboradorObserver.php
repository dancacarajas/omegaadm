<?php

namespace App\Observers;

use App\Models\Colaborador;
use App\Services\Rh\ColaboradorBeneficioDesligamentoService;

class ColaboradorObserver
{
    public function __construct(
        private readonly ColaboradorBeneficioDesligamentoService $beneficioDesligamentoService,
    ) {}

    public function created(Colaborador $colaborador): void
    {
        if ($colaborador->status === 'desligado') {
            $this->beneficioDesligamentoService->desativarTodos($colaborador);
        }
    }

    public function updated(Colaborador $colaborador): void
    {
        if (! $colaborador->wasChanged('status') || $colaborador->status !== 'desligado') {
            return;
        }

        $this->beneficioDesligamentoService->desativarTodos($colaborador);
    }
}

<?php

namespace App\Http\Controllers\Rh;

use App\Http\Controllers\Controller;
use App\Models\Colaborador;
use App\Services\Rh\ContratoWebcardPdfService;
use Illuminate\Http\Request;

class ContratoWebcardController extends Controller
{
    public function pdf(Colaborador $colaborador, Request $request, ContratoWebcardPdfService $service)
    {
        $email = $request->query('email');
        if (! is_string($email) || trim($email) === '') {
            $email = $colaborador->email;
        }
        $conteudo = $service->render($colaborador, is_string($email) ? trim($email) : null);

        return response($conteudo, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$service->nomeArquivo($colaborador).'"',
        ]);
    }
}

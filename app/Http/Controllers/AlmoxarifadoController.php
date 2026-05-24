<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class AlmoxarifadoController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('almoxarifado.painel');
    }
}

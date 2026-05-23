<?php

namespace App\Http\Controllers;

use App\Services\BuscaSistemaService;
use Illuminate\Http\Request;

class BuscaSistemaController extends Controller
{
    public function index(Request $request, BuscaSistemaService $busca)
    {
        $termo = trim((string) $request->input('q', ''));
        $resultado = $busca->buscar($request->user(), $termo, limite: 12);

        return view('busca.index', [
            'termo' => $resultado['termo'],
            'grupos' => $resultado['grupos'],
        ]);
    }

    public function sugestoes(Request $request, BuscaSistemaService $busca)
    {
        $termo = trim((string) $request->input('q', ''));
        $resultado = $busca->buscar($request->user(), $termo, limite: 5);

        return response()->json($resultado);
    }
}

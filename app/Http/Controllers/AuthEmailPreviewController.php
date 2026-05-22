<?php

namespace App\Http\Controllers;

use App\Services\AuthEmailService;
use InvalidArgumentException;

class AuthEmailPreviewController extends Controller
{
    public function show(string $tipo, AuthEmailService $authEmail)
    {
        if (! array_key_exists($tipo, AuthEmailService::tiposPreview())) {
            abort(404);
        }

        try {
            $html = $authEmail->renderPreview($tipo);
        } catch (InvalidArgumentException) {
            abort(404);
        }

        return response($html)->header('Content-Type', 'text/html; charset=UTF-8');
    }
}

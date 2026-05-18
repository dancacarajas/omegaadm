<?php

namespace App\Support;

use App\Models\Colaborador;
use App\Models\SsmaTstRegistro;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\File;

class SsmaTstRegistroService
{
    public const ORIGEM_APP_COLABORADOR = 'app_colaborador';

    public const ORIGEM_SISTEMA = 'sistema';

    /**
     * @return array<string, mixed>
     */
    public function validar(Request $request, bool $arquivoObrigatorio, ?int $colaboradorIdFixo = null): array
    {
        $rules = [
            'ssma_tst_atividade_id' => ['nullable', 'exists:ssma_tst_atividades,id'],
            'data' => ['required', 'date'],
            'descricao' => ['required', 'string', 'max:20000'],
        ];

        if ($colaboradorIdFixo !== null) {
            $rules['colaborador_id'] = ['prohibited'];
        } else {
            $rules['colaborador_id'] = ['required', 'exists:colaboradores,id'];
        }

        $rules['arquivo'] = $arquivoObrigatorio
            ? ['required', File::types(['jpg', 'jpeg', 'png', 'gif', 'webp'])->max(10240)]
            : ['nullable', File::types(['jpg', 'jpeg', 'png', 'gif', 'webp'])->max(10240)];

        $data = $request->validate($rules, [], [
            'ssma_tst_atividade_id' => 'atividade',
            'colaborador_id' => 'colaborador',
            'descricao' => 'descrição da atividade',
            'arquivo' => 'registro fotográfico',
        ]);

        if ($colaboradorIdFixo !== null) {
            $data['colaborador_id'] = $colaboradorIdFixo;
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function criar(array $data, Request $request, ?int $userId = null, string $origem = self::ORIGEM_SISTEMA): SsmaTstRegistro
    {
        if (! $request->hasFile('arquivo')) {
            throw new \InvalidArgumentException('Registro fotográfico é obrigatório.');
        }

        $upload = $this->armazenarArquivo($request->file('arquivo'));

        return SsmaTstRegistro::create([
            'ssma_tst_atividade_id' => $data['ssma_tst_atividade_id'] ?? null,
            'data' => $data['data'],
            'colaborador_id' => $data['colaborador_id'],
            'descricao' => $data['descricao'],
            'arquivo_path' => $upload['path'],
            'arquivo_nome' => $upload['nome'],
            'arquivo_mime' => $upload['mime'],
            'user_id' => $userId,
            'origem' => $origem,
        ]);
    }

    /**
     * @return array{path: string, nome: string, mime: string}
     */
    public function armazenarArquivo(UploadedFile $file): array
    {
        $path = $file->store('ssma/tst/registros', 'public');

        return [
            'path' => $path,
            'nome' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType() ?? 'application/octet-stream',
        ];
    }
}

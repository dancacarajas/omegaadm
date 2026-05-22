<?php

namespace App\Support;

use App\Models\Colaborador;
use App\Models\SsmaTstRegistro;
use App\Models\SsmaTstRegistroFoto;
use App\Support\TstColaboradorAcesso;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\ValidationException;

class SsmaTstRegistroService
{
    public const ORIGEM_APP_COLABORADOR = 'app_colaborador';

    public const ORIGEM_SISTEMA = 'sistema';

    public const MIN_FOTOS = 1;

    public const MAX_FOTOS = 4;

    /**
     * @return array<string, mixed>
     */
    public function validar(Request $request, bool $fotosObrigatorias, ?int $colaboradorIdFixo = null, int $fotosExistentes = 0): array
    {
        $regraAtividade = ['nullable', 'integer'];

        if ($colaboradorIdFixo !== null) {
            $colaborador = Colaborador::query()->find($colaboradorIdFixo);
            $todasAtividades = $colaborador && TstColaboradorAcesso::veTodasAtividadesNoApp($colaborador);

            $regraAtividade[] = Rule::exists('ssma_tst_atividades', 'id')->where(
                function ($q) use ($todasAtividades) {
                    $q->where('ativo', true);
                    if (! $todasAtividades) {
                        $q->where('exibir_no_app', true);
                    }
                },
            );
        } else {
            $regraAtividade[] = Rule::exists('ssma_tst_atividades', 'id')->where('ativo', true);
        }

        $rules = [
            'ssma_tst_atividade_id' => $regraAtividade,
            'data' => ['required', 'date'],
            'descricao' => ['required', 'string', 'max:20000'],
        ];

        if ($colaboradorIdFixo !== null) {
            $rules['colaborador_id'] = ['prohibited'];
        } else {
            $rules['colaborador_id'] = ['required', 'exists:colaboradores,id'];
        }

        $maxNovas = max(0, self::MAX_FOTOS - $fotosExistentes);

        if ($fotosObrigatorias && $fotosExistentes === 0) {
            $rules['arquivos'] = ['required', 'array', 'min:'.self::MIN_FOTOS, 'max:'.self::MAX_FOTOS];
        } else {
            $rules['arquivos'] = ['nullable', 'array', 'max:'.$maxNovas];
        }

        $rules['arquivos.*'] = [
            File::types(['jpg', 'jpeg', 'png', 'gif', 'webp'])->max(10240),
        ];

        $data = $request->validate($rules, [
            'arquivos.required' => 'Adicione pelo menos uma foto (mínimo '.self::MIN_FOTOS.', máximo '.self::MAX_FOTOS.').',
            'arquivos.min' => 'Adicione pelo menos '.self::MIN_FOTOS.' foto.',
            'arquivos.max' => 'É permitido no máximo '.self::MAX_FOTOS.' fotos por registro.',
            'arquivos.*.max' => 'Cada imagem deve ter no máximo 10 MB.',
        ], [
            'ssma_tst_atividade_id' => 'atividade',
            'colaborador_id' => 'colaborador',
            'descricao' => 'descrição da atividade',
            'arquivos' => 'fotos',
        ]);

        if ($colaboradorIdFixo !== null) {
            $data['colaborador_id'] = $colaboradorIdFixo;
        }

        $novas = $this->extrairArquivos($request);
        $total = $fotosExistentes + count($novas);

        if ($fotosObrigatorias && $total < self::MIN_FOTOS) {
            throw ValidationException::withMessages([
                'arquivos' => 'Adicione pelo menos '.self::MIN_FOTOS.' foto.',
            ]);
        }

        if ($total > self::MAX_FOTOS) {
            throw ValidationException::withMessages([
                'arquivos' => 'É permitido no máximo '.self::MAX_FOTOS.' fotos por registro.',
            ]);
        }

        if ($total === 0 && ! $fotosObrigatorias && $fotosExistentes === 0) {
            throw ValidationException::withMessages([
                'arquivos' => 'O registro precisa de pelo menos uma foto.',
            ]);
        }

        return $data;
    }

    /**
     * @return list<UploadedFile>
     */
    public function extrairArquivos(Request $request): array
    {
        $arquivos = $request->file('arquivos', []);

        if (! is_array($arquivos)) {
            return [];
        }

        return array_values(array_filter($arquivos, fn ($f) => $f instanceof UploadedFile));
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<UploadedFile>  $arquivos
     */
    public function criar(
        array $data,
        array $arquivos,
        ?int $userId = null,
        string $origem = self::ORIGEM_SISTEMA,
    ): SsmaTstRegistro {
        if (count($arquivos) < self::MIN_FOTOS) {
            throw new \InvalidArgumentException('É necessário pelo menos '.self::MIN_FOTOS.' foto.');
        }

        $uploads = array_map(fn (UploadedFile $f) => $this->armazenarArquivo($f), $arquivos);
        $primeira = $uploads[0];

        $registro = SsmaTstRegistro::create([
            'ssma_tst_atividade_id' => $data['ssma_tst_atividade_id'] ?? null,
            'data' => $data['data'],
            'colaborador_id' => $data['colaborador_id'],
            'descricao' => $data['descricao'],
            'arquivo_path' => $primeira['path'],
            'arquivo_nome' => $primeira['nome'],
            'arquivo_mime' => $primeira['mime'],
            'user_id' => $userId,
            'origem' => $origem,
        ]);

        foreach ($uploads as $i => $upload) {
            $registro->fotos()->create([
                'arquivo_path' => $upload['path'],
                'arquivo_nome' => $upload['nome'],
                'arquivo_mime' => $upload['mime'],
                'ordem' => $i,
            ]);
        }

        $registro = $registro->fresh(['fotos', 'colaborador', 'atividade', 'usuario']);

        app(\App\Services\SsmaTstRegistroNotificacaoService::class)->notificarRegistroConcluido($registro);

        return $registro;
    }

    /**
     * @param  list<UploadedFile>  $arquivos
     */
    public function anexarFotos(SsmaTstRegistro $registro, array $arquivos): void
    {
        $ordemBase = (int) $registro->fotos()->max('ordem');

        foreach ($arquivos as $i => $file) {
            $upload = $this->armazenarArquivo($file);
            $registro->fotos()->create([
                'arquivo_path' => $upload['path'],
                'arquivo_nome' => $upload['nome'],
                'arquivo_mime' => $upload['mime'],
                'ordem' => $ordemBase + $i + 1,
            ]);
        }

        $registro->sincronizarCamposLegados();
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

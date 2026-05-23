<?php

namespace App\Http\Controllers\Rh;

use App\Http\Controllers\Controller;
use App\Models\Beneficio;
use App\Models\Colaborador;
use App\Models\ColaboradorBeneficio;
use App\Services\Rh\BeneficioAdesaoService;
use App\Support\Rh\BeneficioAdesaoStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BeneficioColaboradorController extends Controller
{
    public function __construct(
        private readonly BeneficioAdesaoService $adesaoService
    ) {}

    public function store(Request $request, Beneficio $beneficio)
    {
        if (config('app.debug')) {
            logger()->info('beneficio.colaborador.store', [
                'beneficio_id' => $beneficio->id,
                'payload' => $request->except(['_token']),
            ]);
        }

        if ($request->has('vinculo_id') && $request->input('vinculo_id') !== '' && $request->input('vinculo_id') !== null) {
            $vinculo = $this->findVinculoDoBeneficio($request, $beneficio);

            return $this->manage($request, $beneficio, $vinculo);
        }

        $data = $this->validatedData($request, $beneficio);
        $colaborador = Colaborador::query()->find($data['colaborador_id']);
        if ($colaborador === null) {
            throw ValidationException::withMessages([
                'colaborador_id' => 'Colaborador não encontrado.',
            ]);
        }

        ColaboradorBeneficio::create([
            ...$data,
            'beneficio_id' => $beneficio->id,
            'data_direito' => $colaborador->data_admissao,
            'tem_direito' => $request->boolean('tem_direito'),
            'cartao_entregue' => $request->boolean('cartao_entregue'),
            'beneficio_ativo' => $request->boolean('beneficio_ativo'),
            'status_adesao' => $this->adesaoService->statusInicialParaBeneficio($beneficio),
            'adesao_atualizado_por_id' => $request->user()?->id,
        ]);

        return $this->redirectAposAcao($beneficio)
            ->with('success', 'Colaborador vinculado ao beneficio com sucesso.');
    }

    public function manage(Request $request, Beneficio $beneficio, ColaboradorBeneficio $vinculo)
    {
        if ((int) $vinculo->beneficio_id !== (int) $beneficio->id) {
            throw ValidationException::withMessages([
                'vinculo_id' => 'Este vínculo não pertence a este benefício.',
            ]);
        }

        if (! $request->filled('acao') && str_contains($request->path(), '/excluir')) {
            $request->merge(['acao' => 'excluir']);
        }

        if ($request->input('acao') === 'excluir') {
            $vinculo->delete();

            return $this->redirectAposAcao($beneficio)
                ->with('success', 'Vinculo removido do beneficio.');
        }

        $payload = $this->validatedData($request, $beneficio, $vinculo);

        if (filled($payload['data_entrega_cartao'] ?? null)) {
            $request->merge(['cartao_entregue' => '1']);
        }

        $payload = array_merge($payload, $this->processarFormularioAdesaoAssinado($request, $vinculo));
        $payload = $this->adesaoService->normalizarDadosAdesao($vinculo, $payload, $request);

        $vinculo->update([
            ...$payload,
            'tem_direito' => $request->boolean('tem_direito'),
            'cartao_entregue' => $request->boolean('cartao_entregue'),
            'beneficio_ativo' => $request->boolean('beneficio_ativo'),
        ]);

        return $this->redirectAposAcao($beneficio, $vinculo)
            ->with('success', 'Situacao do beneficio atualizada.');
    }

    private function redirectAposAcao(Beneficio $beneficio, ?ColaboradorBeneficio $vinculo = null): \Illuminate\Http\RedirectResponse
    {
        $anterior = url()->previous();
        $destino = route('rh.beneficios.show', $beneficio);

        if (
            $anterior !== ''
            && $anterior !== url()->current()
            && (str_contains($anterior, '/rh/beneficios/') || str_contains($anterior, '/public/rh/beneficios/'))
            && ! str_contains($anterior, '/colaboradores/')
            && ! str_contains($anterior, '/vinculos')
        ) {
            return redirect()->to($this->urlComVinculoDestaque($anterior, $vinculo));
        }

        return redirect()->to($this->urlComVinculoDestaque($destino, $vinculo));
    }

    private function urlComVinculoDestaque(string $url, ?ColaboradorBeneficio $vinculo): string
    {
        if ($vinculo === null) {
            return $url;
        }

        $base = str_contains($url, '#') ? strstr($url, '#', true) : $url;
        $base = preg_replace('/([?&])vinculo=\d+(&)?/', '$1', (string) $base) ?? $base;
        $base = rtrim((string) $base, '?&');
        $sep = str_contains($base, '?') ? '&' : '?';

        return $base.$sep.'vinculo='.$vinculo->id.'#vinculo-'.$vinculo->id;
    }

    private function findVinculoDoBeneficio(Request $request, Beneficio $beneficio): ColaboradorBeneficio
    {
        $vinculoId = $request->integer('vinculo_id');

        if ($vinculoId < 1) {
            throw ValidationException::withMessages([
                'vinculo_id' => 'Vínculo inválido. Recarregue a página e tente novamente.',
            ]);
        }

        $vinculo = ColaboradorBeneficio::query()
            ->where('beneficio_id', $beneficio->id)
            ->whereKey($vinculoId)
            ->first();

        if ($vinculo === null) {
            throw ValidationException::withMessages([
                'vinculo_id' => 'Vínculo não encontrado para este benefício. Recarregue a página.',
            ]);
        }

        return $vinculo;
    }

    public function downloadFormularioAdesao(Beneficio $beneficio, ColaboradorBeneficio $vinculo)
    {
        abort_if((int) $vinculo->beneficio_id !== (int) $beneficio->id, 404);
        abort_unless($vinculo->temFormularioAdesaoAssinado(), 404);

        $path = str_replace('\\', '/', (string) $vinculo->formulario_adesao_assinado_path);

        return Storage::disk('public')->response(
            $path,
            basename($path),
            ['Cache-Control' => 'private, max-age=3600'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function processarFormularioAdesaoAssinado(Request $request, ColaboradorBeneficio $vinculo): array
    {
        if ($request->boolean('remover_formulario_adesao')) {
            if (filled($vinculo->formulario_adesao_assinado_path)) {
                Storage::disk('public')->delete((string) $vinculo->formulario_adesao_assinado_path);
            }

            return ['formulario_adesao_assinado_path' => null];
        }

        if (! $request->hasFile('formulario_adesao_assinado')) {
            return [];
        }

        if (filled($vinculo->formulario_adesao_assinado_path)) {
            Storage::disk('public')->delete((string) $vinculo->formulario_adesao_assinado_path);
        }

        $path = $request->file('formulario_adesao_assinado')->store(
            'rh/beneficios/formularios-adesao',
            'public',
        );

        return ['formulario_adesao_assinado_path' => $path];
    }

    private function validatedData(Request $request, Beneficio $beneficio, ?ColaboradorBeneficio $vinculo = null): array
    {
        return $request->validate([
            'colaborador_id' => [
                $vinculo ? 'sometimes' : 'required',
                'integer',
                Rule::exists('colaboradores', 'id'),
                Rule::unique('colaborador_beneficios', 'colaborador_id')
                    ->where('beneficio_id', $beneficio->id)
                    ->ignore($vinculo),
            ],
            'tem_direito' => ['sometimes', 'boolean'],
            'cartao_entregue' => ['sometimes', 'boolean'],
            'beneficio_ativo' => ['sometimes', 'boolean'],
            'data_entrega_cartao' => ['nullable', 'date'],
            'numero_cartao' => ['nullable', 'string', 'max:255'],
            'observacoes' => ['nullable', 'string'],
            'status_adesao' => ['nullable', 'string', Rule::in(BeneficioAdesaoStatus::valores())],
            'data_formulario_recebido' => ['nullable', 'date'],
            'data_envio_matriz' => ['nullable', 'date'],
            'protocolo_matriz' => ['nullable', 'string', 'max:120'],
            'data_aviso_coleta_matriz' => ['nullable', 'date'],
            'data_retorno_matriz' => ['nullable', 'date'],
            'data_previsao_cartao' => ['nullable', 'date'],
            'formulario_adesao_assinado' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'remover_formulario_adesao' => ['sometimes', 'boolean'],
        ]);
    }
}

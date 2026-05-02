<?php

namespace App\Http\Controllers;

use App\Models\Colaborador;
use App\Models\RdoRelatorio;
use App\Support\ContratoAccess;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RdoController extends Controller
{
    public function index(Request $request)
    {
        $indicadores = [
            'total' => ContratoAccess::applyContratoString(RdoRelatorio::query())->count(),
            'hoje' => ContratoAccess::applyContratoString(RdoRelatorio::query())->whereDate('data', today())->count(),
            'mes' => ContratoAccess::applyContratoString(RdoRelatorio::query())->whereBetween('data', [today()->startOfMonth(), today()->endOfMonth()])->count(),
            'com_evidencia' => ContratoAccess::applyContratoString(RdoRelatorio::query())->whereNotNull('evidencia_path')->count(),
        ];

        $relatorios = $this->rdoQuery($request)->latest('data')->latest()->paginate(10)->withQueryString();

        return view('rdo.index', compact('relatorios', 'indicadores'));
    }

    public function create()
    {
        $colaboradores = Colaborador::query()
            ->where('status', 'ativo')
            ->orderBy('nome')
            ->get(['id', 'matricula', 'nome', 'cargo']);

        return view('rdo.create', [
            'rdo' => new RdoRelatorio(['data' => today()]),
            'colaboradores' => $colaboradores,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);

        if ($data['offline_uuid'] ?? null) {
            $existing = RdoRelatorio::where('offline_uuid', $data['offline_uuid'])->first();

            if ($existing) {
                return $request->expectsJson()
                    ? response()->json(['ok' => true, 'id' => $existing->id, 'duplicado' => true])
                    : redirect()->route('rdo.show', $existing);
            }
        }

        if ($request->hasFile('evidencia')) {
            $data['evidencia_path'] = $request->file('evidencia')->store('rdo/evidencias', 'public');
        }

        if ($request->input('evidencia_base64')) {
            $data['evidencia_path'] = $this->storeBase64Evidence($request->input('evidencia_base64'));
        }

        unset($data['evidencia'], $data['evidencia_base64']);

        $data['status'] = 'transmitido';
        $data['transmitido_em'] = now();

        $rdo = RdoRelatorio::create($data);

        return $request->expectsJson()
            ? response()->json(['ok' => true, 'id' => $rdo->id])
            : redirect()->route('rdo.show', $rdo)->with('success', 'RDO transmitido com sucesso.');
    }

    public function show(RdoRelatorio $rdo)
    {
        $this->authorizeContratoString($rdo->contrato);

        return view('rdo.show', compact('rdo'));
    }

    public function exportExcel(Request $request)
    {
        $relatorios = $this->rdoQuery($request)->latest('data')->latest()->get();
        $filename = 'relatorio-rdo-'.now()->format('Ymd-His').'.xls';

        return response()->streamDownload(function () use ($relatorios) {
            echo "\xEF\xBB\xBF";
            echo view('rdo.exports.excel', compact('relatorios'))->render();
        }, $filename, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
        ]);
    }

    public function exportPdf(Request $request)
    {
        $relatorios = $this->rdoQuery($request)->latest('data')->latest()->get();
        $periodo = $this->periodoTexto($request);

        return $this->downloadPdf('rdo.pdf.index', [
            'relatorios' => $relatorios,
            'periodo' => $periodo,
            'logo' => $this->logoBase64(),
            'geradoEm' => now(),
        ], 'relatorio-rdo-'.now()->format('Ymd-His').'.pdf', 'landscape');
    }

    public function pdf(RdoRelatorio $rdo)
    {
        $this->authorizeContratoString($rdo->contrato);

        return $this->downloadPdf('rdo.pdf.show', [
            'rdo' => $rdo,
            'logo' => $this->logoBase64(),
            'evidencia' => $this->evidenceBase64($rdo->evidencia_path),
            'geradoEm' => now(),
        ], 'rdo-'.$rdo->id.'-'.$rdo->data?->format('Ymd').'.pdf');
    }

    private function rdoQuery(Request $request)
    {
        return ContratoAccess::applyContratoString(RdoRelatorio::query())
            ->when($request->filled('busca'), function ($query) use ($request) {
                $busca = $request->string('busca')->toString();

                $query->where(function ($query) use ($busca) {
                    $query->where('titulo', 'like', "%{$busca}%")
                        ->orWhere('contrato', 'like', "%{$busca}%")
                        ->orWhere('frente', 'like', "%{$busca}%")
                        ->orWhere('area', 'like', "%{$busca}%")
                        ->orWhere('disciplina', 'like', "%{$busca}%")
                        ->orWhere('supervisor_nome', 'like', "%{$busca}%")
                        ->orWhere('encarregado_nome', 'like', "%{$busca}%");
                });
            })
            ->when($request->filled('data_inicio'), fn ($query) => $query->whereDate('data', '>=', $request->date('data_inicio')))
            ->when($request->filled('data_fim'), fn ($query) => $query->whereDate('data', '<=', $request->date('data_fim')));
    }

    private function authorizeContratoString(?string $contrato): void
    {
        if (! ContratoAccess::shouldRestrict()) {
            return;
        }

        abort_unless($contrato && in_array($contrato, ContratoAccess::contratoValores(), true), 404);
    }

    private function downloadPdf(string $view, array $data, string $filename, string $orientation = 'portrait')
    {
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);

        $pdf = new Dompdf($options);
        $pdf->loadHtml(view($view, $data)->render(), 'UTF-8');
        $pdf->setPaper('A4', $orientation);
        $pdf->render();

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    private function logoBase64(): ?string
    {
        $path = public_path('logo.png');

        if (! is_file($path)) {
            return null;
        }

        return 'data:image/png;base64,'.base64_encode(file_get_contents($path));
    }

    private function evidenceBase64(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        $fullPath = storage_path('app/public/'.$path);

        if (! is_file($fullPath)) {
            return null;
        }

        $mime = mime_content_type($fullPath) ?: '';

        if (! str_starts_with($mime, 'image/')) {
            return null;
        }

        return 'data:'.$mime.';base64,'.base64_encode(file_get_contents($fullPath));
    }

    private function periodoTexto(Request $request): string
    {
        $inicio = $request->date('data_inicio')?->format('d/m/Y');
        $fim = $request->date('data_fim')?->format('d/m/Y');

        return match (true) {
            filled($inicio) && filled($fim) => "{$inicio} a {$fim}",
            filled($inicio) => "A partir de {$inicio}",
            filled($fim) => "Até {$fim}",
            default => 'Todos os registros',
        };
    }

    private function validatedData(Request $request): array
    {
        $data = $request->validate([
            'offline_uuid' => ['nullable', 'uuid'],
            'data' => ['required', 'date'],
            'titulo' => ['nullable', 'string', 'max:255'],
            'contrato' => ['nullable', 'string', 'max:255'],
            'frente' => ['nullable', 'string', 'max:255'],
            'area' => ['nullable', 'string', 'max:255'],
            'disciplina' => ['nullable', 'string', 'max:255'],
            'supervisor_nome' => ['nullable', 'string', 'max:255'],
            'supervisor_matricula' => ['nullable', 'string', 'max:80'],
            'encarregado_nome' => ['nullable', 'string', 'max:255'],
            'encarregado_matricula' => ['nullable', 'string', 'max:80'],
            'condicao_climatica' => ['nullable', 'string', 'max:255'],
            'atividades' => ['nullable', 'array'],
            'atividades.*.inicio' => ['nullable', 'string', 'max:20'],
            'atividades.*.fim' => ['nullable', 'string', 'max:20'],
            'atividades.*.descricao' => ['nullable', 'string'],
            'equipe' => ['nullable', 'array'],
            'equipe.*.funcao' => ['nullable', 'string', 'max:120'],
            'equipe.*.nome' => ['nullable', 'string', 'max:255'],
            'equipe.*.matricula' => ['nullable', 'string', 'max:80'],
            'observacoes' => ['nullable', 'string'],
            'ocorrencias' => ['nullable', 'string'],
            'evidencia' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
            'evidencia_base64' => ['nullable', 'string'],
        ]);

        $data['atividades'] = collect($data['atividades'] ?? [])
            ->filter(fn ($item) => filled($item['descricao'] ?? null) || filled($item['inicio'] ?? null) || filled($item['fim'] ?? null))
            ->values()
            ->all();

        $data['equipe'] = collect($data['equipe'] ?? [])
            ->filter(fn ($item) => filled($item['nome'] ?? null) || filled($item['funcao'] ?? null) || filled($item['matricula'] ?? null))
            ->values()
            ->all();

        return $data;
    }

    private function storeBase64Evidence(string $payload): ?string
    {
        if (! str_contains($payload, ';base64,')) {
            return null;
        }

        [$meta, $content] = explode(';base64,', $payload, 2);
        $extension = str_contains($meta, 'png') ? 'png' : 'jpg';
        $path = 'rdo/evidencias/'.Str::uuid().'.'.$extension;

        Storage::disk('public')->put($path, base64_decode($content));

        return $path;
    }
}

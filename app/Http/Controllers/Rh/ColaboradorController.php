<?php

namespace App\Http\Controllers\Rh;

use App\Http\Controllers\Controller;
use App\Models\Colaborador;
use App\Models\HorarioEscala;
use App\Support\SimpleSpreadsheet;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ColaboradorController extends Controller
{
    public function index()
    {
        $colaboradores = Colaborador::query()
            ->with('horarioEscala')
            ->when(request('busca'), function ($query, string $busca) {
                $query->where(function ($query) use ($busca) {
                        $query->where('nome', 'like', "%{$busca}%")
                        ->orWhere('telefone', 'like', "%{$busca}%")
                        ->orWhere('cpf', 'like', "%{$busca}%")
                        ->orWhere('matricula', 'like', "%{$busca}%")
                        ->orWhere('cargo', 'like', "%{$busca}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('rh.colaboradores.index', compact('colaboradores'));
    }

    public function create()
    {
        return view('rh.colaboradores.create', [
            'colaborador' => new Colaborador([
                'status' => 'ativo',
                'mobilizacao_status' => 'pendente',
            ]),
            'horarioEscalas' => $this->horarioEscalasParaSelect(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        unset($data['foto_perfil']);
        $data = $this->withMobilizacaoStatus($data);
        $foto = $this->storeFotoPerfil($request, null);
        if ($foto !== null) {
            $data['foto_path'] = $foto;
        }
        Colaborador::create($data);

        return redirect()
            ->route('rh.efetivo.index')
            ->with('success', 'Colaborador cadastrado com sucesso.');
    }

    public function show(Colaborador $colaborador)
    {
        $colaborador->load('horarioEscala');

        return view('rh.colaboradores.show', compact('colaborador'));
    }

    public function edit(Colaborador $colaborador)
    {
        return view('rh.colaboradores.edit', [
            'colaborador' => $colaborador,
            'horarioEscalas' => $this->horarioEscalasParaSelect(),
        ]);
    }

    public function update(Request $request, Colaborador $colaborador)
    {
        $data = $this->validatedData($request, $colaborador);
        unset($data['foto_perfil']);
        $data = $this->withMobilizacaoStatus($data);
        $foto = $this->storeFotoPerfil($request, $colaborador);
        if ($foto !== null) {
            $data['foto_path'] = $foto;
        }
        $colaborador->update($data);

        return redirect()
            ->route('rh.efetivo.show', $colaborador)
            ->with('success', 'Cadastro atualizado com sucesso.');
    }

    public function destroy(Colaborador $colaborador)
    {
        $colaborador->delete();

        return redirect()
            ->route('rh.efetivo.index')
            ->with('success', 'Colaborador removido do efetivo.');
    }

    public function modeloImportacao()
    {
        $columns = $this->importColumns();

        return SimpleSpreadsheet::xlsx(
            'modelo-importacao-efetivo.xlsx',
            array_keys($columns),
            array_pad([
                '22214',
                'JOAO DA SILVA',
                '(94) 99999-9999',
                '000.000.000-00',
                'Motorista',
                '286',
                'SALOB0',
                'ativo',
                '2026-05-01',
            ], count($columns), '')
        );
    }

    public function importar(Request $request)
    {
        $request->validate([
            'arquivo' => ['required', 'file', 'mimes:xlsx,xlsm,csv', 'max:10240'],
        ]);

        $rows = SimpleSpreadsheet::read($request->file('arquivo'));

        if (count($rows) < 2) {
            return back()->with('error', 'A planilha precisa ter cabeçalho e ao menos uma linha de colaborador.');
        }

        $columns = $this->importColumns();
        $headers = array_map(fn ($header) => $this->normalizeHeader((string) $header), array_shift($rows));
        $mapped = [];

        foreach ($headers as $index => $header) {
            foreach ($columns as $label => $field) {
                if ($header === $this->normalizeHeader($label) || $header === $this->normalizeHeader($field)) {
                    $mapped[$index] = $field;
                    break;
                }
            }
        }

        if (! in_array('nome', $mapped, true)) {
            return back()->with('error', 'A planilha precisa ter a coluna Nome.');
        }

        $created = 0;
        $updated = 0;
        $errors = [];

        foreach ($rows as $rowIndex => $row) {
            $line = $rowIndex + 2;
            $data = [];

            foreach ($mapped as $index => $field) {
                $data[$field] = $row[$index] ?? null;
            }

            $data = $this->normalizeImportData($data);

            if (collect($data)->filter(fn ($value) => filled($value))->isEmpty()) {
                continue;
            }

            $colaborador = $this->findImportColaborador($data);
            $validator = Validator::make($data, $this->importRules($colaborador));

            if ($validator->fails()) {
                $errors[] = "Linha {$line}: ".$validator->errors()->first();
                continue;
            }

            $payload = $this->withMobilizacaoStatus($validator->validated());

            if ($colaborador) {
                $colaborador->update($payload);
                $updated++;
            } else {
                Colaborador::create($payload);
                $created++;
            }
        }

        $message = "Importação concluída: {$created} criado(s), {$updated} atualizado(s).";

        if ($errors !== []) {
            return back()
                ->with('success', $message)
                ->with('import_errors', array_slice($errors, 0, 10));
        }

        return back()->with('success', $message);
    }

    private function validatedData(Request $request, ?Colaborador $colaborador = null): array
    {
        return $request->validate($this->validatedDataRules($colaborador));
    }

    private function storeFotoPerfil(Request $request, ?Colaborador $colaborador = null): ?string
    {
        if (! $request->hasFile('foto_perfil')) {
            return null;
        }

        $path = $request->file('foto_perfil')->store('rh/colaboradores/fotos', 'public');
        if ($colaborador?->foto_path) {
            Storage::disk('public')->delete($colaborador->foto_path);
        }

        return $path;
    }

    private function withMobilizacaoStatus(array $data): array
    {
        if (! empty($data['sgc_data_entrega_cracha'])) {
            $data['mobilizacao_status'] = 'mobilizacao_concluida';
        } elseif (! empty($data['sgc_data_aprovacao'])) {
            $data['mobilizacao_status'] = 'aprovado';
        } elseif (! empty($data['sgc_data_postagem']) || ! empty($data['sgc_numero_solicitacao'])) {
            $data['mobilizacao_status'] = 'postado_sgc';
        } else {
            $data['mobilizacao_status'] = $data['mobilizacao_status'] ?? 'pendente';
        }

        return $data;
    }

    private function importColumns(): array
    {
        return [
            'Matrícula' => 'matricula',
            'Nome' => 'nome',
            'Telefone' => 'telefone',
            'CPF' => 'cpf',
            'Cargo' => 'cargo',
            'Centro de custo' => 'centro_custo',
            'Local de trabalho' => 'local_trabalho',
            'Status' => 'status',
            'Data de admissão' => 'data_admissao',
            'Pai' => 'filiacao_pai',
            'Mãe' => 'filiacao_mae',
            'RG' => 'rg',
            'Carteira profissional' => 'carteira_profissional',
            'Serie CTPS' => 'serie_ctps',
            'PIS' => 'pis',
            'Titulo eleitor' => 'titulo_eleitor',
            'Zona eleitoral' => 'zona_eleitoral',
            'Secao eleitoral' => 'secao_eleitoral',
            'Carteira identidade' => 'carteira_identidade',
            'Emissao identidade' => 'emissao_identidade',
            'Orgao emissor' => 'orgao_emissor',
            'Data CTPS' => 'data_ctps',
            'Vencimento CTPS' => 'vencimento_ctps',
            'Data nascimento' => 'data_nascimento',
            'Estado civil' => 'estado_civil',
            'Conjuge' => 'conjuge',
            'Local nascimento' => 'local_nascimento',
            'Sexo' => 'sexo',
            'Grau instrucao' => 'grau_instrucao',
            'UF nascimento' => 'uf_nascimento',
            'Cor' => 'cor',
            'Nacionalidade' => 'nacionalidade',
            'Endereco' => 'endereco',
            'Número' => 'numero',
            'Bairro' => 'bairro',
            'Cidade' => 'cidade',
            'Estado' => 'estado',
            'CEP' => 'cep',
            'Tipo contrato' => 'tipo_contrato',
            'Departamento' => 'departamento',
            'CBO' => 'cbo',
            'Jornada semanal' => 'jornada_semanal',
            'Horario' => 'horario',
            'Escala de horários (ID)' => 'horario_escala_id',
            'Data opcao FGTS' => 'data_opcao_fgts',
            'Data demissao' => 'data_demissao',
            'Forma pagamento' => 'forma_pagamento',
            'Salário inicial' => 'salario_inicial',
            'Almoço' => 'almoco',
            'Status da mobilização' => 'mobilizacao_status',
            'SGC data postagem' => 'sgc_data_postagem',
            'SGC número da solicitação' => 'sgc_numero_solicitacao',
            'SGC data de aprovação' => 'sgc_data_aprovacao',
            'SGC data de entrega do crachá' => 'sgc_data_entrega_cracha',
            'SGC observacoes' => 'sgc_observacoes',
            'Dependentes' => 'dependentes',
            'Contato emergencia nome' => 'contato_emergencia_nome',
            'Contato emergencia telefone' => 'contato_emergencia_telefone',
            'Contato emergencia parentesco' => 'contato_emergencia_parentesco',
            'Observações' => 'observacoes',
        ];
    }

    private function normalizeImportData(array $data): array
    {
        $dateFields = [
            'emissao_identidade',
            'data_ctps',
            'vencimento_ctps',
            'data_nascimento',
            'data_admissao',
            'data_opcao_fgts',
            'data_demissao',
            'sgc_data_postagem',
            'sgc_data_aprovacao',
            'sgc_data_entrega_cracha',
        ];

        foreach ($data as $field => $value) {
            $data[$field] = is_string($value) ? trim($value) : $value;
            $data[$field] = $data[$field] === '' ? null : $data[$field];
        }

        foreach ($dateFields as $field) {
            $data[$field] = $this->normalizeDate($data[$field] ?? null);
        }

        foreach (['uf_nascimento', 'estado'] as $field) {
            if (filled($data[$field] ?? null)) {
                $data[$field] = Str::upper(Str::substr((string) $data[$field], 0, 2));
            }
        }

        if (filled($data['salario_inicial'] ?? null)) {
            $data['salario_inicial'] = str_replace(['.', ','], ['', '.'], (string) $data['salario_inicial']);
        }

        if (array_key_exists('horario_escala_id', $data)) {
            $raw = $data['horario_escala_id'];
            if ($raw === null || $raw === '') {
                $data['horario_escala_id'] = null;
            } elseif (is_numeric($raw)) {
                $data['horario_escala_id'] = (int) $raw;
            } else {
                $data['horario_escala_id'] = null;
            }
        }

        if (array_key_exists('horario_escala_ciclo_offset', $data)) {
            $off = $data['horario_escala_ciclo_offset'];
            $data['horario_escala_ciclo_offset'] = is_numeric($off)
                ? max(0, min(13, (int) $off))
                : 0;
        }

        $data['status'] = $this->normalizeOption($data['status'] ?? 'ativo', [
            'ativo' => 'ativo',
            'afastado' => 'afastado',
            'desligado' => 'desligado',
        ], 'ativo');

        $data['mobilizacao_status'] = $this->normalizeOption($data['mobilizacao_status'] ?? null, [
            'pendente' => 'pendente',
            'postado_sgc' => 'postado_sgc',
            'postado no sgc' => 'postado_sgc',
            'aprovado' => 'aprovado',
            'mobilizacao concluida' => 'mobilizacao_concluida',
            'mobilizacao_concluida' => 'mobilizacao_concluida',
        ], null);

        return $data;
    }

    private function normalizeDate(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        if (is_numeric($value) && (float) $value > 20000) {
            return Carbon::create(1899, 12, 30)->addDays((int) $value)->toDateString();
        }

        $value = trim((string) $value);

        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y'] as $format) {
            try {
                return Carbon::createFromFormat($format, $value)->toDateString();
            } catch (\Throwable $e) {
                //
            }
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function normalizeOption(mixed $value, array $options, ?string $default): ?string
    {
        if (blank($value)) {
            return $default;
        }

        $normalized = $this->normalizeHeader((string) $value);

        return $options[$normalized] ?? $default;
    }

    private function normalizeHeader(string $value): string
    {
        return Str::of($value)->ascii()->lower()->replace(['_', '-', '.', ':'], ' ')->squish()->toString();
    }

    private function findImportColaborador(array $data): ?Colaborador
    {
        if (filled($data['matricula'] ?? null)) {
            return Colaborador::where('matricula', $data['matricula'])->first();
        }

        if (filled($data['cpf'] ?? null)) {
            return Colaborador::where('cpf', $data['cpf'])->first();
        }

        return null;
    }

    private function importRules(?Colaborador $colaborador): array
    {
        $rules = $this->validatedDataRules($colaborador);
        $rules['nome'] = ['required', 'string', 'max:255'];

        return $rules;
    }

    private function validatedDataRules(?Colaborador $colaborador = null): array
    {
        return [
            'matricula' => ['nullable', 'string', 'max:40', Rule::unique('colaboradores', 'matricula')->ignore($colaborador)],
            'nome' => ['required', 'string', 'max:255'],
            'foto_perfil' => ['nullable', 'file', 'max:5120', 'mimes:jpeg,jpg,png,gif,webp'],
            'telefone' => ['nullable', 'string', 'max:40'],
            'filiacao_pai' => ['nullable', 'string', 'max:255'],
            'filiacao_mae' => ['nullable', 'string', 'max:255'],
            'cpf' => ['nullable', 'string', 'max:20'],
            'rg' => ['nullable', 'string', 'max:30'],
            'carteira_profissional' => ['nullable', 'string', 'max:40'],
            'serie_ctps' => ['nullable', 'string', 'max:20'],
            'pis' => ['nullable', 'string', 'max:30'],
            'titulo_eleitor' => ['nullable', 'string', 'max:40'],
            'zona_eleitoral' => ['nullable', 'string', 'max:20'],
            'secao_eleitoral' => ['nullable', 'string', 'max:20'],
            'carteira_identidade' => ['nullable', 'string', 'max:40'],
            'emissao_identidade' => ['nullable', 'date'],
            'orgao_emissor' => ['nullable', 'string', 'max:40'],
            'data_ctps' => ['nullable', 'date'],
            'vencimento_ctps' => ['nullable', 'date'],
            'data_nascimento' => ['nullable', 'date'],
            'estado_civil' => ['nullable', 'string', 'max:40'],
            'conjuge' => ['nullable', 'string', 'max:255'],
            'local_nascimento' => ['nullable', 'string', 'max:255'],
            'sexo' => ['nullable', 'string', 'max:30'],
            'grau_instrucao' => ['nullable', 'string', 'max:255'],
            'uf_nascimento' => ['nullable', 'string', 'size:2'],
            'cor' => ['nullable', 'string', 'max:40'],
            'nacionalidade' => ['nullable', 'string', 'max:80'],
            'endereco' => ['nullable', 'string', 'max:255'],
            'numero' => ['nullable', 'string', 'max:20'],
            'bairro' => ['nullable', 'string', 'max:255'],
            'cidade' => ['nullable', 'string', 'max:255'],
            'estado' => ['nullable', 'string', 'size:2'],
            'cep' => ['nullable', 'string', 'max:20'],
            'tipo_contrato' => ['nullable', 'string', 'max:80'],
            'departamento' => ['nullable', 'string', 'max:255'],
            'cargo' => ['nullable', 'string', 'max:255'],
            'cbo' => ['nullable', 'string', 'max:30'],
            'centro_custo' => ['nullable', 'string', 'max:80'],
            'jornada_semanal' => ['nullable', 'string', 'max:40'],
            'horario' => ['nullable', 'string', 'max:255'],
            'horario_escala_id' => ['nullable', 'integer', Rule::exists('horario_escalas', 'id')],
            'horario_escala_ciclo_offset' => ['nullable', 'integer', 'min:0', 'max:13'],
            'data_admissao' => ['nullable', 'date'],
            'data_opcao_fgts' => ['nullable', 'date'],
            'data_demissao' => ['nullable', 'date'],
            'forma_pagamento' => ['nullable', 'string', 'max:80'],
            'salario_inicial' => ['nullable', 'numeric', 'min:0'],
            'local_trabalho' => ['nullable', 'string', 'max:255'],
            'almoco' => ['nullable', 'string', 'max:80'],
            'status' => ['required', 'string', Rule::in(['ativo', 'afastado', 'desligado'])],
            'mobilizacao_status' => ['nullable', 'string', Rule::in(['pendente', 'postado_sgc', 'aprovado', 'mobilizacao_concluida'])],
            'sgc_data_postagem' => ['nullable', 'date'],
            'sgc_numero_solicitacao' => ['nullable', 'string', 'max:80'],
            'sgc_data_aprovacao' => ['nullable', 'date'],
            'sgc_data_entrega_cracha' => ['nullable', 'date'],
            'sgc_observacoes' => ['nullable', 'string'],
            'dependentes' => ['nullable', 'string'],
            'contato_emergencia_nome' => ['nullable', 'string', 'max:255'],
            'contato_emergencia_telefone' => ['nullable', 'string', 'max:40'],
            'contato_emergencia_parentesco' => ['nullable', 'string', 'max:80'],
            'observacoes' => ['nullable', 'string'],
        ];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, HorarioEscala>
     */
    private function horarioEscalasParaSelect()
    {
        return HorarioEscala::query()
            ->orderByRaw("CASE WHEN status = 'ativo' THEN 0 ELSE 1 END")
            ->orderBy('nome')
            ->get(['id', 'nome', 'tipo', 'status']);
    }
}

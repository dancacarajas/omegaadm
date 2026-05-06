from pathlib import Path

p = Path("_assemble_extract.txt")
t = p.read_text(encoding="utf-8")
t = t.replace(
    "        $etapas = [];\n        foreach",
    "        $etapas = [];\n        $prev = $previousEtapas ?? [];\n        foreach",
)

repls = [
    (
        """                $path1 = null;
                $path2 = null;
                if ($request->hasFile('etapas.auditoria_mensal.evidencia_1')) {
                    $path1 = $request->file('etapas.auditoria_mensal.evidencia_1')->store('ssma/registros/evidencias', 'public');
                }
                if ($request->hasFile('etapas.auditoria_mensal.evidencia_2')) {
                    $path2 = $request->file('etapas.auditoria_mensal.evidencia_2')->store('ssma/registros/evidencias', 'public');
                }""",
        """                $path1 = $request->hasFile('etapas.auditoria_mensal.evidencia_1')
                    ? $request->file('etapas.auditoria_mensal.evidencia_1')->store('ssma/registros/evidencias', 'public')
                    : data_get($prev, 'auditoria_mensal.evidencia_1_path');
                $path2 = $request->hasFile('etapas.auditoria_mensal.evidencia_2')
                    ? $request->file('etapas.auditoria_mensal.evidencia_2')->store('ssma/registros/evidencias', 'public')
                    : data_get($prev, 'auditoria_mensal.evidencia_2_path');""",
    ),
    (
        """                $path1 = null;
                $path2 = null;
                if ($request->hasFile('etapas.inspecao_mensal_canteiro.evidencia_1')) {
                    $path1 = $request->file('etapas.inspecao_mensal_canteiro.evidencia_1')->store('ssma/registros/evidencias', 'public');
                }
                if ($request->hasFile('etapas.inspecao_mensal_canteiro.evidencia_2')) {
                    $path2 = $request->file('etapas.inspecao_mensal_canteiro.evidencia_2')->store('ssma/registros/evidencias', 'public');
                }""",
        """                $path1 = $request->hasFile('etapas.inspecao_mensal_canteiro.evidencia_1')
                    ? $request->file('etapas.inspecao_mensal_canteiro.evidencia_1')->store('ssma/registros/evidencias', 'public')
                    : data_get($prev, 'inspecao_mensal_canteiro.evidencia_1_path');
                $path2 = $request->hasFile('etapas.inspecao_mensal_canteiro.evidencia_2')
                    ? $request->file('etapas.inspecao_mensal_canteiro.evidencia_2')->store('ssma/registros/evidencias', 'public')
                    : data_get($prev, 'inspecao_mensal_canteiro.evidencia_2_path');""",
    ),
    (
        """                $fotoAntesPath = null;
                $fotoDepoisPath = null;
                if ($request->hasFile('etapas.boas_praticas_kaizen.foto_antes')) {
                    $fotoAntesPath = $request->file('etapas.boas_praticas_kaizen.foto_antes')->store('ssma/registros/kaizen', 'public');
                }
                if ($request->hasFile('etapas.boas_praticas_kaizen.foto_depois')) {
                    $fotoDepoisPath = $request->file('etapas.boas_praticas_kaizen.foto_depois')->store('ssma/registros/kaizen', 'public');
                }""",
        """                $fotoAntesPath = $request->hasFile('etapas.boas_praticas_kaizen.foto_antes')
                    ? $request->file('etapas.boas_praticas_kaizen.foto_antes')->store('ssma/registros/kaizen', 'public')
                    : data_get($prev, 'boas_praticas_kaizen.foto_antes_path');
                $fotoDepoisPath = $request->hasFile('etapas.boas_praticas_kaizen.foto_depois')
                    ? $request->file('etapas.boas_praticas_kaizen.foto_depois')->store('ssma/registros/kaizen', 'public')
                    : data_get($prev, 'boas_praticas_kaizen.foto_depois_path');""",
    ),
    (
        """                    $path1 = null;
                    $path2 = null;
                    $key1 = "etapas.campanha_seguranca.itens.$idx.evidencia_1";
                    $key2 = "etapas.campanha_seguranca.itens.$idx.evidencia_2";
                    if ($request->hasFile($key1)) {
                        $path1 = $request->file($key1)->store('ssma/registros/campanha_seguranca', 'public');
                    }
                    if ($request->hasFile($key2)) {
                        $path2 = $request->file($key2)->store('ssma/registros/campanha_seguranca', 'public');
                    }

                    $linhaPreenchida = $titulo !== ''
                        || $local !== ''
                        || $resp !== ''
                        || $gerencia !== ''
                        || $desc !== ''
                        || ! empty($dataReuniao)
                        || $path1
                        || $path2;""",
        """                    $prevCampanhas = data_get($prev, 'campanha_seguranca.campanhas', []);
                    $prevRow = $prevCampanhas[$idx] ?? [];
                    $key1 = "etapas.campanha_seguranca.itens.$idx.evidencia_1";
                    $key2 = "etapas.campanha_seguranca.itens.$idx.evidencia_2";
                    $path1 = $request->hasFile($key1)
                        ? $request->file($key1)->store('ssma/registros/campanha_seguranca', 'public')
                        : ($prevRow['evidencia_1_path'] ?? null);
                    $path2 = $request->hasFile($key2)
                        ? $request->file($key2)->store('ssma/registros/campanha_seguranca', 'public')
                        : ($prevRow['evidencia_2_path'] ?? null);

                    $linhaPreenchida = $titulo !== ''
                        || $local !== ''
                        || $resp !== ''
                        || $gerencia !== ''
                        || $desc !== ''
                        || ! empty($dataReuniao)
                        || $path1
                        || $path2;""",
    ),
    (
        """                $path1 = null;
                $path2 = null;
                if ($request->hasFile('etapas.registro_acidente.evidencia_1')) {
                    $path1 = $request->file('etapas.registro_acidente.evidencia_1')->store('ssma/registros/acidentes', 'public');
                }
                if ($request->hasFile('etapas.registro_acidente.evidencia_2')) {
                    $path2 = $request->file('etapas.registro_acidente.evidencia_2')->store('ssma/registros/acidentes', 'public');
                }""",
        """                $path1 = $request->hasFile('etapas.registro_acidente.evidencia_1')
                    ? $request->file('etapas.registro_acidente.evidencia_1')->store('ssma/registros/acidentes', 'public')
                    : data_get($prev, 'registro_acidente.evidencia_1_path');
                $path2 = $request->hasFile('etapas.registro_acidente.evidencia_2')
                    ? $request->file('etapas.registro_acidente.evidencia_2')->store('ssma/registros/acidentes', 'public')
                    : data_get($prev, 'registro_acidente.evidencia_2_path');""",
    ),
]

for old, new in repls:
    if old not in t:
        raise SystemExit(f"block not found:\n{old[:80]}")
    t = t.replace(old, new, 1)

header = """<?php

namespace App\\Services;

use App\\Models\\Colaborador;
use App\\Models\\SsmaRegistroMensal;
use Illuminate\\Http\\Request;

class SsmaRegistroMensalEtapasAssembler
{
    public static function assemble(Request $request, array $data, ?array $previousEtapas = null): array
    {
"""

footer = """
        return $etapas;
    }
}
"""

# Dedent extract: remove 8 leading spaces from each line
body_lines = []
for line in t.splitlines():
    if line.startswith("        "):
        body_lines.append("        " + line[8:])
    else:
        body_lines.append(line)

body = "\n".join(body_lines)

Path("app/Services/SsmaRegistroMensalEtapasAssembler.php").write_text(
    header + body + footer, encoding="utf-8"
)
print("ok")

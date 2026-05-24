#!/usr/bin/env python3
"""Gera SQL de importação mobilizacao_materiais — contrato 312 / PGU SALOBO."""
from __future__ import annotations

import math
import re
from datetime import datetime
from pathlib import Path

import openpyxl

XLSX = Path(
    r"c:\Users\Administrator\Downloads\AKNIXARIFADO AMOR"
    r"\CRUZAMENTO_CT312_CATEGORIZADO_ELETRICA_MECANICA (1).xlsx"
)
OUT = Path(__file__).resolve().parent.parent / "database" / "sql" / "import_mobilizacao_materiais_ct312.sql"


def norm_disciplina(v: str) -> str:
    v = (v or "").strip().upper()
    v = v.replace("MECNICA", "MECÂNICA").replace("MECÂNICA", "MECÂNICA")
    if "COMUM" in v or "APOIO" in v or "SEGURAN" in v:
        return "COMUM / APOIO / SEGURANÇA"
    if "EL" in v and "TRICA" in v:
        return "ELÉTRICA"
    if "MEC" in v:
        return "MECÂNICA"
    return v


def fnum(x) -> float:
    if x is None or x == "":
        return 0.0
    if isinstance(x, (int, float)):
        if isinstance(x, float) and (math.isnan(x) or math.isinf(x)):
            return 0.0
        return float(x)
    try:
        return float(str(x).replace(",", "."))
    except ValueError:
        return 0.0


def infer_necessaria(row) -> float:
    nec = fnum(row[5])
    if nec > 0:
        return nec
    ped = fnum(row[6])
    em = fnum(row[7])
    rec = fnum(row[8])
    falta = fnum(row[9])
    if falta > 0:
        return round(falta + em + rec, 2)
    if ped > 0:
        return ped
    return 0.0


def infer_status(sit: str, sit_sigo: str, nec: float, ped: float, em: float, rec: float) -> str:
    if nec > 0 and rec >= nec:
        return "RECEBIDO_TOTAL"
    if rec > 0:
        return "RECEBIDO_PARCIAL"
    if em > 0 and nec > 0 and em < nec:
        return "COMPRA_PARCIAL"
    if nec > 0 and em >= nec:
        return "EM_COMPRAS"
    sit_sigo = (sit_sigo or "").strip().upper()
    sit = (sit or "").strip().upper()
    if sit_sigo == "COMPRA PARCIAL":
        return "COMPRA_PARCIAL"
    if sit_sigo == "EM COMPRA" or sit == "EM ATENDIMENTO EM COMPRAS":
        return "EM_COMPRAS" if em >= nec and nec > 0 else "COMPRA_PARCIAL" if em > 0 else "EM_COMPRAS"
    if sit_sigo == "AGUARDANDO COMPRAS" or ped > 0 or sit == "CADASTRADO NO SIGO SEM ATENDIMENTO":
        return "PEDIDO_NO_SIGO"
    return "SEM_TRATATIVA"


def acao_do_dia(status: str, previsao=None) -> str:
    m = {
        "SEM_TRATATIVA": "Validar se precisa pedir.",
        "PEDIDO_NO_SIGO": "Cobrar Compras pela PM.",
        "EM_COMPRAS": "Acompanhar OC e previsão de entrega.",
        "COMPRA_PARCIAL": "Cobrar complemento da compra.",
        "RECEBIDO_PARCIAL": "Acompanhar saldo pendente.",
        "RECEBIDO_TOTAL": "Finalizado.",
    }
    return m.get(status, "Acompanhar item.")


def esc_sql(s: str | None) -> str:
    if s is None:
        return "NULL"
    s = str(s).replace("\\", "\\\\").replace("'", "''")
    return f"'{s}'"


def main() -> None:
    wb = openpyxl.load_workbook(XLSX, read_only=True, data_only=True)
    ws = wb["CONTROLE_GERAL"]
    rows = list(ws.iter_rows(values_only=True))[1:]
    wb.close()

    lines: list[str] = [
        "-- Importação: Controle de Materiais da Mobilização — Contrato 312 PGU SALOBO",
        f"-- Gerado em {datetime.now().isoformat(timespec='seconds')}",
        f"-- Origem: {XLSX.name}",
        f"-- Registros: {len(rows)}",
        "--",
        "SET NAMES utf8mb4;",
        "SET @contrato_id := (",
        "  SELECT id FROM contratos",
        "  WHERE numero = '312'",
        "     OR numero LIKE '%312%'",
        "     OR nome LIKE '%312%'",
        "     OR nome LIKE '%SALOBO%'",
        "     OR nome LIKE '%PGU%SALOBO%'",
        "  ORDER BY (numero = '312') DESC, id ASC",
        "  LIMIT 1",
        ");",
        "",
        "SELECT @contrato_id AS contrato_id_resolvido;",
        "",
        "-- Opcional: limpar importação anterior do mesmo contrato",
        "-- DELETE FROM mobilizacao_materiais WHERE contrato_id = @contrato_id AND origem_cadastro = 'IMPORT_CT312';",
        "",
    ]

    for i, row in enumerate(rows, start=2):
        disciplina = norm_disciplina(str(row[0] or ""))
        categoria = (str(row[1] or "")).strip()
        situacao = (str(row[2] or "")).strip()
        material = (str(row[3] or "")).strip()
        if not material:
            continue
        unid = (str(row[4] or "")).strip() or "UND"
        nec = infer_necessaria(row)
        ped = fnum(row[6])
        em = fnum(row[7])
        rec = fnum(row[8])
        saldo_comprar = max(0.0, round(nec - em - rec, 2))
        saldo_receber = max(0.0, round(em - rec, 2))
        sit_sigo = (str(row[11] or "")).strip() if len(row) > 11 else ""
        status = infer_status(situacao, sit_sigo, nec, ped, em, rec)
        acao = acao_do_dia(status)

        lines.append(
            "INSERT INTO mobilizacao_materiais ("
            "contrato_id, disciplina, categoria_descricao, situacao_tratativa, "
            "descricao_material, unidade_medida, "
            "quantidade_necessaria, quantidade_pedida_sigo, quantidade_em_compra, quantidade_recebida, "
            "saldo_a_comprar, saldo_a_receber, status, situacao_sigo_descricao, acao_do_dia, "
            "origem_cadastro, ativo, created_at, updated_at"
            ") VALUES ("
            f"@contrato_id, {esc_sql(disciplina)}, {esc_sql(categoria)}, {esc_sql(situacao)}, "
            f"{esc_sql(material)}, {esc_sql(unid)}, "
            f"{nec:.2f}, {ped:.2f}, {em:.2f}, {rec:.2f}, "
            f"{saldo_comprar:.2f}, {saldo_receber:.2f}, {esc_sql(status)}, {esc_sql(sit_sigo or None)}, {esc_sql(acao)}, "
            "'IMPORT_CT312', 1, NOW(), NOW()"
            f"); -- linha planilha {i}"
        )

    lines.extend([
        "",
        "SELECT COUNT(*) AS total_importado FROM mobilizacao_materiais",
        "WHERE contrato_id = @contrato_id AND origem_cadastro = 'IMPORT_CT312';",
    ])

    OUT.parent.mkdir(parents=True, exist_ok=True)
    OUT.write_text("\n".join(lines) + "\n", encoding="utf-8")
    print(f"OK: {len(rows)} linhas -> {OUT}")


if __name__ == "__main__":
    main()

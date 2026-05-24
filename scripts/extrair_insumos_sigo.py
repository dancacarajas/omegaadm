#!/usr/bin/env python3
"""
Extrator de insumos/produtos da tela Novo Pedido do SIGO.

URL alvo: http://sigo.omegaservice.com.br/SIGO/PM/NovoPM

Uso manual:
  pip install -r scripts/requirements-sigo-extractor.txt
  playwright install chromium

  python scripts/extrair_insumos_sigo.py --usuario X --senha Y --output-dir tmp/sigo

Integração Laravel: o sistema chama este script com os parâmetros acima.
"""
from __future__ import annotations

import argparse
import csv
import json
import logging
import os
import re
import string
import sys
from dataclasses import dataclass
from datetime import datetime
from pathlib import Path

try:
    from playwright.sync_api import Page, sync_playwright
except ImportError:
    print("Instale: pip install playwright && playwright install chromium", file=sys.stderr)
    raise

try:
    import openpyxl
    from openpyxl.styles import Font
except ImportError:
    print("Instale: pip install openpyxl", file=sys.stderr)
    raise

BASE_URL = os.environ.get("SIGO_BASE_URL", "http://sigo.omegaservice.com.br").rstrip("/")
TARGET_PATH = os.environ.get("SIGO_PM_PATH", "/SIGO/PM/NovoPM")
LOGIN_PATH = os.environ.get("SIGO_LOGIN_PATH", "/SIGO/Login")  # ajustar após F12
OUTPUT_DIR = Path(os.environ.get("SIGO_OUTPUT_DIR", Path(__file__).resolve().parent.parent / "tmp"))
HEADLESS = os.environ.get("SIGO_HEADLESS", "1") not in ("0", "false", "False")
TIMEOUT_MS = int(os.environ.get("SIGO_TIMEOUT_MS", "60000"))

# Ajuste estes seletores após inspecionar o HTML no navegador (F12).
SELECTORS = {
    "login_user": 'input[name*="Usuario"], input[name*="usuario"], input[type="text"]',
    "login_pass": 'input[name*="Senha"], input[name*="senha"], input[type="password"]',
    "login_submit": 'input[type="submit"], button[type="submit"]',
    "search_input": (
        'input[placeholder*="Descrição"], input[placeholder*="Detalhe"], '
        'input[placeholder*="Código"], input[name*="Insumo"], input[name*="insumo"]'
    ),
    "search_button": 'input[type="submit"], button[type="submit"], input[value*="Pesquis"], button:has-text("Pesquis")',
    "results_table": "table",
    "pagination_links": 'a:has-text("2"), .pagination a, a[href*="Page"]',
}

CAMPOS = ("cod", "insumo", "detalhe", "und", "grupo", "familia")
HEADER_ALIASES = {
    "cod": ("cod", "código", "codigo", "cod."),
    "insumo": ("insumo", "descrição", "descricao", "material"),
    "detalhe": ("detalhe", "detalhes"),
    "und": ("und", "unid", "unidade", "unid."),
    "grupo": ("grupo",),
    "familia": ("família", "familia", "fam."),
}


@dataclass(frozen=True)
class Insumo:
    cod: str
    insumo: str
    detalhe: str
    und: str
    grupo: str
    familia: str

    def chave_unica(self) -> str:
        partes = (self.cod, self.insumo, self.detalhe, self.und, self.grupo, self.familia)
        return "|".join(normalizar(p) for p in partes)


def normalizar(valor: str) -> str:
    return re.sub(r"\s+", " ", (valor or "").strip())


def configurar_log(output_dir: Path) -> logging.Logger:
    output_dir.mkdir(parents=True, exist_ok=True)
    log_path = output_dir / f"extracao_sigo_{datetime.now():%Y%m%d_%H%M%S}.log"
    logging.basicConfig(
        level=logging.INFO,
        format="%(asctime)s [%(levelname)s] %(message)s",
        handlers=[
            logging.StreamHandler(sys.stdout),
            logging.FileHandler(log_path, encoding="utf-8"),
        ],
    )
    logger = logging.getLogger("sigo_extractor")
    logger.info("Log em %s", log_path)
    return logger


def fazer_login(page: Page, user: str, password: str, logger: logging.Logger) -> None:
    login_url = f"{BASE_URL}{LOGIN_PATH}"
    logger.info("Acessando login: %s", login_url)
    page.goto(login_url, wait_until="domcontentloaded", timeout=TIMEOUT_MS)

    user_input = page.locator(SELECTORS["login_user"]).first
    pass_input = page.locator(SELECTORS["login_pass"]).first
    if user_input.count() == 0 or pass_input.count() == 0:
        logger.warning("Campos de login não encontrados; tentando continuar (sessão já autenticada?)")
        return

    user_input.fill(user)
    pass_input.fill(password)
    page.locator(SELECTORS["login_submit"]).first.click()
    page.wait_for_load_state("networkidle", timeout=TIMEOUT_MS)
    logger.info("Login enviado")


def ir_para_novo_pm(page: Page, logger: logging.Logger) -> None:
    url = f"{BASE_URL}{TARGET_PATH}"
    logger.info("Abrindo tela Novo PM: %s", url)
    page.goto(url, wait_until="domcontentloaded", timeout=TIMEOUT_MS)
    page.wait_for_load_state("networkidle", timeout=TIMEOUT_MS)


def mapear_colunas(cabecalhos: list[str]) -> dict[str, int]:
    normalizados = [normalizar(h).lower() for h in cabecalhos]
    mapa: dict[str, int] = {}
    for campo, aliases in HEADER_ALIASES.items():
        for idx, cab in enumerate(normalizados):
            if any(alias in cab for alias in aliases):
                mapa[campo] = idx
                break
    return mapa


def tabela_e_resultados(page: Page) -> tuple | None:
    """Retorna (locator da tabela, mapa colunas) ou None."""
    tabelas = page.locator(SELECTORS["results_table"])
    for i in range(tabelas.count()):
        tabela = tabelas.nth(i)
        texto = normalizar(tabela.inner_text(timeout=5000))
        if not texto:
            continue
        if "relação de itens" in texto.lower() or "relacao de itens" in texto.lower():
            continue
        if "itens a serem solicitados" in texto.lower():
            continue

        ths = tabela.locator("thead tr th, tr:first-child th, tr:first-child td")
        if ths.count() == 0:
            continue
        cabecalhos = [normalizar(ths.nth(j).inner_text()) for j in range(ths.count())]
        mapa = mapear_colunas(cabecalhos)
        if "cod" in mapa and "insumo" in mapa:
            return tabela, mapa
    return None


def extrair_linhas(page: Page, logger: logging.Logger) -> list[Insumo]:
    encontrado = tabela_e_resultados(page)
    if not encontrado:
        logger.warning("Tabela de resultados não encontrada nesta página")
        return []

    tabela, mapa = encontrado
    linhas = tabela.locator("tbody tr, tr")
    registros: list[Insumo] = []

    for i in range(linhas.count()):
        cells = linhas.nth(i).locator("td")
        if cells.count() < 3:
            continue
        valores = [normalizar(cells.nth(j).inner_text()) for j in range(cells.count())]
        if not valores or valores[0].lower() in ("nº", "no", "cod", "código"):
            continue

        def col(campo: str) -> str:
            idx = mapa.get(campo)
            return valores[idx] if idx is not None and idx < len(valores) else ""

        item = Insumo(
            cod=col("cod"),
            insumo=col("insumo"),
            detalhe=col("detalhe"),
            und=col("und"),
            grupo=col("grupo"),
            familia=col("familia"),
        )
        if item.cod or item.insumo:
            registros.append(item)

    return registros


def pesquisar(page: Page, termo: str, logger: logging.Logger) -> None:
    campo = page.locator(SELECTORS["search_input"]).first
    campo.wait_for(state="visible", timeout=TIMEOUT_MS)
    campo.fill("")
    if termo:
        campo.fill(termo)

    botao = page.locator(SELECTORS["search_button"]).first
    if botao.count() > 0:
        botao.click()
    else:
        campo.press("Enter")

    page.wait_for_load_state("networkidle", timeout=TIMEOUT_MS)


def numeros_paginacao(page: Page) -> list[int]:
    nums: set[int] = set()
    for link in page.locator("a").all():
        texto = normalizar(link.inner_text())
        if texto.isdigit():
            nums.add(int(texto))
    return sorted(nums)


def ir_para_pagina(page: Page, numero: int, logger: logging.Logger) -> bool:
    link = page.locator(f'a:has-text("{numero}")').first
    if link.count() == 0:
        return False
    logger.info("Indo para página %s", numero)
    link.click()
    page.wait_for_load_state("networkidle", timeout=TIMEOUT_MS)
    return True


def extrair_pagina_atual_e_restantes(page: Page, logger: logging.Logger) -> tuple[list[Insumo], int]:
    brutos: list[Insumo] = []
    paginas_lidas = 0
    visitadas: set[int] = set()

    while True:
        paginas_lidas += 1
        pagina_atual = paginas_lidas
        visitadas.add(pagina_atual)
        linhas = extrair_linhas(page, logger)
        logger.info("Página %s: %s registros brutos", pagina_atual, len(linhas))
        brutos.extend(linhas)

        proximas = [n for n in numeros_paginacao(page) if n not in visitadas]
        if not proximas:
            break
        if not ir_para_pagina(page, proximas[0], logger):
            break

    return brutos, paginas_lidas


def extrair_termo(page: Page, termo: str, logger: logging.Logger) -> tuple[list[Insumo], int]:
    logger.info("Pesquisando termo: %r", termo)
    pesquisar(page, termo, logger)
    return extrair_pagina_atual_e_restantes(page, logger)


def deduplicar(registros: list[Insumo]) -> list[Insumo]:
    vistos: dict[str, Insumo] = {}
    for item in registros:
        vistos[item.chave_unica()] = item
    return list(vistos.values())


def exportar(registros: list[Insumo], output_dir: Path, data_extracao: str) -> tuple[Path, Path]:
    output_dir.mkdir(parents=True, exist_ok=True)
    xlsx_path = output_dir / "insumos_sigo_extraidos.xlsx"
    csv_path = output_dir / "insumos_sigo_extraidos.csv"

    colunas = ["COD", "INSUMO", "DETALHE", "UND", "GRUPO", "FAMILIA", "CHAVE_UNICA", "DATA_EXTRACAO"]

    wb = openpyxl.Workbook()
    ws = wb.active
    ws.title = "Insumos"
    ws.append(colunas)
    for cell in ws[1]:
        cell.font = Font(bold=True)

    for item in registros:
        ws.append([
            item.cod,
            item.insumo,
            item.detalhe,
            item.und,
            item.grupo,
            item.familia,
            item.chave_unica(),
            data_extracao,
        ])
    wb.save(xlsx_path)

    with csv_path.open("w", newline="", encoding="utf-8-sig") as f:
        writer = csv.writer(f, delimiter=";")
        writer.writerow(colunas)
        for item in registros:
            writer.writerow([
                item.cod,
                item.insumo,
                item.detalhe,
                item.und,
                item.grupo,
                item.familia,
                item.chave_unica(),
                data_extracao,
            ])

    return xlsx_path, csv_path


def termos_varredura() -> list[str]:
    return [""] + list(string.ascii_uppercase) + list(string.digits)


def executar_extracao(
    user: str,
    password: str,
    output_dir: Path,
    *,
    base_url: str | None = None,
    login_path: str | None = None,
    target_path: str | None = None,
    headless: bool | None = None,
    timeout_ms: int | None = None,
) -> dict:
    global BASE_URL, LOGIN_PATH, TARGET_PATH, HEADLESS, TIMEOUT_MS, OUTPUT_DIR

    BASE_URL = (base_url or os.environ.get("SIGO_BASE_URL", BASE_URL)).rstrip("/")
    LOGIN_PATH = login_path or os.environ.get("SIGO_LOGIN_PATH", LOGIN_PATH)
    TARGET_PATH = target_path or os.environ.get("SIGO_PM_PATH", TARGET_PATH)
    if headless is not None:
        HEADLESS = headless
    else:
        HEADLESS = os.environ.get("SIGO_HEADLESS", "1") not in ("0", "false", "False")
    TIMEOUT_MS = timeout_ms or int(os.environ.get("SIGO_TIMEOUT_MS", str(TIMEOUT_MS)))
    OUTPUT_DIR = output_dir

    logger = configurar_log(output_dir)
    data_extracao = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    todos_brutos: list[Insumo] = []
    total_paginas = 0
    erro: str | None = None

    try:
        with sync_playwright() as p:
            browser = p.chromium.launch(headless=HEADLESS)
            context = browser.new_context(ignore_https_errors=True)
            page = context.new_page()
            page.set_default_timeout(TIMEOUT_MS)

            try:
                fazer_login(page, user, password, logger)
                ir_para_novo_pm(page, logger)

                brutos_vazio, paginas_vazio = extrair_termo(page, "", logger)
                todos_brutos.extend(brutos_vazio)
                total_paginas += paginas_vazio

                busca_vazia_suficiente = len(brutos_vazio) >= 20 and paginas_vazio <= 1
                msg_mais = page.locator('text=/\\d+\\s*Resultados ou mais/i')
                if msg_mais.count() > 0:
                    busca_vazia_suficiente = False
                    logger.info("Mensagem 'Resultados ou mais' detectada — varredura por termos será usada")

                if not busca_vazia_suficiente or len(brutos_vazio) == 0:
                    logger.info("Iniciando varredura por termos (A–Z, 0–9)")
                    for termo in termos_varredura():
                        if termo == "" and brutos_vazio:
                            continue
                        brutos, paginas = extrair_termo(page, termo, logger)
                        todos_brutos.extend(brutos)
                        total_paginas += paginas
                        ir_para_novo_pm(page, logger)
            finally:
                browser.close()
    except Exception as exc:  # noqa: BLE001
        erro = str(exc)
        logger.exception("Falha na extração SIGO")

    unicos = deduplicar(todos_brutos)
    xlsx_path = csv_path = resumo_path = None
    if unicos:
        xlsx_path, csv_path = exportar(unicos, output_dir, data_extracao)

    resumo = {
        "ok": erro is None and len(unicos) > 0,
        "erro": erro,
        "data_extracao": data_extracao,
        "paginas_lidas": total_paginas,
        "registros_brutos": len(todos_brutos),
        "registros_unicos": len(unicos),
        "xlsx": str(xlsx_path) if xlsx_path else None,
        "csv": str(csv_path) if csv_path else None,
    }
    resumo_path = output_dir / "extracao_sigo_resumo.json"
    resumo_path.write_text(json.dumps(resumo, indent=2, ensure_ascii=False), encoding="utf-8")
    resumo["resumo_json"] = str(resumo_path)

    logger.info("Páginas lidas: %s", total_paginas)
    logger.info("Registros brutos: %s", len(todos_brutos))
    logger.info("Registros únicos: %s", len(unicos))
    if xlsx_path:
        logger.info("XLSX: %s", xlsx_path)
    if csv_path:
        logger.info("CSV: %s", csv_path)
    logger.info("Resumo: %s", resumo_path)

    return resumo


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="Extrator de insumos SIGO (Novo PM)")
    parser.add_argument("--usuario", default=os.environ.get("SIGO_USER", ""))
    parser.add_argument("--senha", default=os.environ.get("SIGO_PASS", ""))
    parser.add_argument("--output-dir", default=os.environ.get("SIGO_OUTPUT_DIR", ""))
    parser.add_argument("--base-url", default=os.environ.get("SIGO_BASE_URL", ""))
    parser.add_argument("--login-path", default=os.environ.get("SIGO_LOGIN_PATH", ""))
    parser.add_argument("--target-path", default=os.environ.get("SIGO_PM_PATH", ""))
    parser.add_argument("--headless", choices=("0", "1"), default=os.environ.get("SIGO_HEADLESS", "1"))
    return parser.parse_args()


def main() -> int:
    args = parse_args()
    user = (args.usuario or "").strip()
    password = (args.senha or "").strip()
    if not user or not password:
        print("Informe --usuario e --senha (ou SIGO_USER / SIGO_PASS).", file=sys.stderr)
        return 1

    output_dir = Path(args.output_dir or (Path(__file__).resolve().parent.parent / "tmp" / "sigo-extracao"))
    output_dir.mkdir(parents=True, exist_ok=True)

    resumo = executar_extracao(
        user,
        password,
        output_dir,
        base_url=args.base_url or None,
        login_path=args.login_path or None,
        target_path=args.target_path or None,
        headless=args.headless != "0",
    )

    print("SIGO_RESULT:" + json.dumps(resumo, ensure_ascii=False))
    if not resumo.get("ok"):
        return 1
    return 0


if __name__ == "__main__":
    raise SystemExit(main())

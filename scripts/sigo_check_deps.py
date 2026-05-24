#!/usr/bin/env python3
"""Verifica dependências Playwright/openpyxl para extração SIGO (chamado pelo Laravel)."""
from __future__ import annotations

import sys


def main() -> int:
    try:
        from playwright.sync_api import Page, sync_playwright  # noqa: F401
        import openpyxl  # noqa: F401
    except Exception as exc:  # noqa: BLE001
        print(str(exc), file=sys.stderr)
        return 1

    print("ok")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())

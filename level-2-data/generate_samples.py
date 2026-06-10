#!/usr/bin/env python3
"""Generate sample Import and Price Excel files for Level 2 demo."""

from __future__ import annotations

from pathlib import Path

from openpyxl import Workbook

DATA_DIR = Path(__file__).resolve().parent / "data"


def create_samples() -> None:
    DATA_DIR.mkdir(parents=True, exist_ok=True)

    price_wb = Workbook()
    price_ws = price_wb.active
    price_ws.title = "Прайс"
    price_ws.append(["Артикул", "Ціна"])
    price_rows = [
        ("SKU-001", 150.0),
        ("SKU-002", 320.0),
        ("SKU-003", 89.5),
        ("SKU-004", 1200.0),
        ("SKU-005", 45.0),
        ("SKU-006", 780.0),
        ("SKU-007", 199.99),
        ("SKU-008", 550.0),
        ("SKU-009", 25.0),
        ("SKU-010", 999.0),
    ]
    for sku, price in price_rows:
        price_ws.append([sku, price])
    price_wb.save(DATA_DIR / "Прайс.xlsx")

    import_wb = Workbook()
    import_ws = import_wb.active
    import_ws.title = "Імпорт"
    import_ws.append(["Артикул", "Назва", "Ціна", "old_price", "Категорія"])
    import_rows = [
        ("SKU-001", "Товар 1", 140.0, "", "Електроніка"),
        ("SKU-002", "Товар 2", 300.0, "", "Електроніка"),
        ("SKU-003", "Товар 3", 89.5, "", "Аксесуари"),
        ("SKU-004", "Товар 4", 1100.0, "", "Побутова техніка"),
        ("SKU-005", "Товар 5", 45.0, "", "Аксесуари"),
        ("SKU-006", "Товар 6", 800.0, "", "Побутова техніка"),
        ("SKU-007", "Товар 7", 180.0, "", "Електроніка"),
        ("SKU-008", "Товар 8", 550.0, "", "Електроніка"),
        ("SKU-009", "Товар 9", 30.0, "", "Аксесуари"),
        ("SKU-010", "Товар 10", 950.0, "", "Побутова техніка"),
        ("SKU-011", "Товар без прайсу", 75.0, "", "Інше"),
    ]
    for row in import_rows:
        import_ws.append(list(row))
    import_wb.save(DATA_DIR / "Імпорт.xlsx")

    print(f"Created: {DATA_DIR / 'Імпорт.xlsx'}")
    print(f"Created: {DATA_DIR / 'Прайс.xlsx'}")


if __name__ == "__main__":
    create_samples()

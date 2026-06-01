#!/usr/bin/env python3
"""Audit imported BOB-LIFT products against their old-site source pages.

This does not guess or invent product data. It re-parses the saved source URL,
compares the current Payload specs with source-page specs, and writes a review
report that can be used before marking parameters as verified.
"""
from __future__ import annotations

import argparse
import csv
import importlib.util
import re
import sys
import time
from pathlib import Path

SCRIPT_DIR = Path(__file__).resolve().parent
IMPORT_SCRIPT = SCRIPT_DIR / "import-legacy-products.py"
REPORT_DIR = SCRIPT_DIR.parents[2] / "output" / "product-source-audits"


def load_importer():
    spec = importlib.util.spec_from_file_location("legacy_product_importer", IMPORT_SCRIPT)
    if not spec or not spec.loader:
        raise RuntimeError(f"Cannot load importer: {IMPORT_SCRIPT}")
    module = importlib.util.module_from_spec(spec)
    sys.modules[spec.name] = module
    spec.loader.exec_module(module)
    return module


legacy = load_importer()


def query_products(limit: int | None, only_unverified: bool):
    where = "WHERE p.source_url IS NOT NULL AND p.source_url <> ''"
    if only_unverified:
        where += " AND COALESCE(p.parameter_review_status::text, '') <> 'verified-from-source'"
    sql = f"""
      SELECT p.id, p.slug, p.source_url, COALESCE(p.parameter_review_status::text, ''), COALESCE(p.frontend_content_status, ''), pl.title
      FROM products p
      JOIN products_locales pl ON pl._parent_id=p.id AND pl._locale='en'
      {where}
      ORDER BY p.id
      {f'LIMIT {int(limit)}' if limit else ''}
    """
    rows = legacy.run_psql(sql)
    output = []
    for line in rows.splitlines():
        parts = line.split("|", 5)
        if len(parts) == 6:
            output.append({
                "id": int(parts[0]),
                "slug": parts[1],
                "sourceUrl": parts[2],
                "reviewStatus": parts[3],
                "frontendStatus": parts[4],
                "title": parts[5],
            })
    return output


def query_specs(product_id: int):
    rows = legacy.run_psql(f"""
      SELECT sl.label, sl.value
      FROM products_specs s
      JOIN products_specs_locales sl ON sl._parent_id=s.id AND sl._locale='en'
      WHERE s._parent_id={int(product_id)}
      ORDER BY s._order
    """)
    specs = []
    for line in rows.splitlines():
        parts = line.split("|", 1)
        if len(parts) == 2:
            specs.append((parts[0], parts[1]))
    return specs


def normalize(value: str) -> str:
    text = re.sub(r"\s+", " ", value or "").strip().lower()
    text = text.replace("°", " degree")
    text = re.sub(r"\s*/\s*", "/", text)
    text = re.sub(r"\s+", " ", text)
    return text


def spec_map(specs: list[tuple[str, str]]) -> dict[str, str]:
    return {normalize(label): normalize(value) for label, value in specs if label and value}


def compare_specs(db_specs: list[tuple[str, str]], source_specs: list[tuple[str, str]]):
    db = spec_map(db_specs)
    source = spec_map(source_specs)
    missing = [label for label in source if label not in db]
    extra = [label for label in db if label not in source]
    mismatched = [label for label in source if label in db and db[label] != source[label]]
    return {
        "matches": not missing and not mismatched and len(source) > 0,
        "missing": missing,
        "extra": extra,
        "mismatched": mismatched,
    }


def mark_verified(product_id: int):
    legacy.run_psql(f"""
      UPDATE products
      SET parameter_review_status='verified-from-source',
          parameter_reviewer='source-audit-script',
          parameter_reviewed_at=now(),
          updated_at=now()
      WHERE id={int(product_id)};
    """)
    legacy.recalculate_frontend_status(product_id)


def audit_product(record: dict, mark_exact: bool, retries: int, retry_delay: float):
    parsed = None
    error = ""
    for attempt in range(1, retries + 1):
        try:
            parsed = legacy.parse_product(record["sourceUrl"])
            error = ""
            break
        except Exception as exc:  # noqa: BLE001
            error = str(exc)
            if attempt < retries:
                time.sleep(retry_delay * attempt)

    db_specs = query_specs(record["id"])
    source_specs = parsed.specs if parsed else []
    comparison = compare_specs(db_specs, source_specs)
    title_match = bool(parsed and normalize(parsed.title) == normalize(record["title"]))
    exact = bool(parsed and title_match and comparison["matches"])
    recommendation = "mark-verified" if exact else "manual-review"

    if exact and mark_exact:
        mark_verified(record["id"])

    return {
        "id": record["id"],
        "slug": record["slug"],
        "title": record["title"],
        "sourceUrl": record["sourceUrl"],
        "reviewStatus": record["reviewStatus"],
        "frontendStatus": record["frontendStatus"],
        "sourceParsed": bool(parsed),
        "sourceTitle": parsed.title if parsed else "",
        "titleMatch": title_match,
        "dbSpecCount": len(db_specs),
        "sourceSpecCount": len(source_specs),
        "specsMatch": comparison["matches"],
        "missingSourceLabelsInDb": ", ".join(comparison["missing"][:12]),
        "extraDbLabels": ", ".join(comparison["extra"][:12]),
        "mismatchedLabels": ", ".join(comparison["mismatched"][:12]),
        "sourceImageCount": len(parsed.images) if parsed else 0,
        "recommendation": recommendation,
        "error": error,
    }


def write_report(rows: list[dict], output_path: Path):
    output_path.parent.mkdir(parents=True, exist_ok=True)
    fieldnames = [
        "id",
        "slug",
        "title",
        "sourceUrl",
        "reviewStatus",
        "frontendStatus",
        "sourceParsed",
        "sourceTitle",
        "titleMatch",
        "dbSpecCount",
        "sourceSpecCount",
        "specsMatch",
        "missingSourceLabelsInDb",
        "extraDbLabels",
        "mismatchedLabels",
        "sourceImageCount",
        "recommendation",
        "error",
    ]
    with output_path.open("w", newline="", encoding="utf-8") as file:
        writer = csv.DictWriter(file, fieldnames=fieldnames)
        writer.writeheader()
        writer.writerows(rows)


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--limit", type=int, default=None)
    parser.add_argument("--only-unverified", action="store_true")
    parser.add_argument("--mark-exact", action="store_true", help="Mark products verified only when title and specs match the source page exactly.")
    parser.add_argument("--output", default="")
    parser.add_argument("--sleep", type=float, default=0.12)
    parser.add_argument("--timeout", type=float, default=8, help="Seconds to wait for each old-site HTTP request before marking the product for manual review.")
    parser.add_argument("--retries", type=int, default=3, help="Retry old-site HTTP parsing before marking a product for manual review.")
    parser.add_argument("--retry-delay", type=float, default=0.8, help="Base seconds to wait between old-site retry attempts.")
    args = parser.parse_args()

    legacy.REQUEST_TIMEOUT = args.timeout

    products = query_products(args.limit, args.only_unverified)
    rows = []
    for index, product in enumerate(products, start=1):
        result = audit_product(product, args.mark_exact, max(1, args.retries), max(0, args.retry_delay))
        rows.append(result)
        print(
            f"[{index}/{len(products)}] {product['slug']} parsed={result['sourceParsed']} "
            f"title={result['titleMatch']} specs={result['specsMatch']} -> {result['recommendation']}",
            flush=True,
        )
        time.sleep(args.sleep)

    output_path = Path(args.output) if args.output else REPORT_DIR / f"product-source-audit-{time.strftime('%Y%m%d-%H%M%S')}.csv"
    write_report(rows, output_path)
    exact_count = sum(1 for row in rows if row["recommendation"] == "mark-verified")
    print(f"Audit done. products={len(rows)} exact={exact_count} manualReview={len(rows) - exact_count} report={output_path}")


if __name__ == "__main__":
    main()

#!/usr/bin/env python3
"""Import BOB-LIFT legacy product pages into the local Payload PostgreSQL DB.

This script is intentionally conservative: it creates missing legacy products,
imports source images into apps/web/media, and keeps every imported product in
needs-review until a human checks the old source page text. Drawing images are
stored as supporting material only and do not block front-end readiness. Existing products
are preserved by default; pass --overwrite-unverified only when you intentionally
want to refresh unverified legacy-import rows from the old site.
"""
from __future__ import annotations

import argparse
import hashlib
import html
import mimetypes
import os
import re
import shlex
import subprocess
import sys
import time
import uuid
from dataclasses import dataclass
from pathlib import Path
from urllib.parse import unquote, urljoin, urlparse

import requests
from PIL import Image, ImageOps

ROOT = Path(__file__).resolve().parents[1]
MEDIA_DIR = ROOT / "media"
ENV_FILE = ROOT / ".env.local"
BASE_URL = "https://www.bobliftcrane.com"
REQUEST_TIMEOUT = float(os.environ.get("BOBLIFT_LEGACY_REQUEST_TIMEOUT", "30"))
DEFAULT_DB = {
    "host": "127.0.0.1",
    "port": "5438",
    "user": "bobliftcrane",
    "dbname": "bobliftcrane_content",
    "password": "bobliftcrane_dev_2026",
}

CATEGORY_SEEDS = [
    ("telescopic-boom-truck-mounted-cranes", "Telescopic Boom Truck Mounted Cranes", "Telescopic Truck Cranes", "truck-mounted-crane", "Truck Mounted Crane", "truck-crane", 10),
    ("knuckle-boom-truck-mounted-cranes", "Knuckle Boom Truck Mounted Cranes", "Knuckle Truck Cranes", "truck-mounted-crane", "Truck Mounted Crane", "knuckle-truck", 20),
    ("knuckle-boom-marine-cranes", "Knuckle Boom Marine Cranes", "Knuckle Marine Cranes", "marine-crane", "Marine Crane", "knuckle-marine", 30),
    ("telescopic-boom-marine-cranes", "Telescopic Boom Marine Cranes", "Telescopic Marine Cranes", "marine-crane", "Marine Crane", "telescopic-marine", 40),
    ("pickup-cranes", "Pickup Cranes", "Pickup Cranes", "compact-attachments", "Pickup Truck Crane", "pickup-crane", 50),
    ("forklift-fly-jib-cranes", "Forklift Fly Jib Cranes", "Forklift Fly Jib", "compact-attachments", "Forklift Fly Jib Crane", "forklift-jib", 60),
    ("mini-dumpers", "Mini Dumpers", "Mini Dumpers", "compact-attachments", "Mini Dumper", "mini-dumper", 70),
    ("aerial-work-platform", "Aerial Work Platform", "Aerial Work Platform", "truck-mounted-crane", "Aerial Work Platform", "aerial-platform", 80),
    ("tail-lift", "Tail Lift", "Tail Lift", "compact-attachments", "Crane Accessories", "other-crane", 90),
    ("spider-cranes", "Spider Cranes", "Spider Cranes", "compact-attachments", "Crane Accessories", "other-crane", 100),
    ("timber-cranes", "Timber Cranes", "Timber Cranes", "compact-attachments", "Crane Accessories", "other-crane", 110),
    ("other-cranes", "Other Cranes", "Other Cranes", "compact-attachments", "Crane Accessories", "other-crane", 120),
]

SPEC_LABELS = [
    "Model No.",
    "Rated Load (kg)",
    "Bucket Volume (m3)",
    "Discharge Height (mm)",
    "Maximum Gradient(%)",
    "Buck Width",
    "Traveling Speed",
    "Minimum Turning Radius (mm)",
    "Overall Dimension (mm)",
    "Wheel Base (mm)",
    "Thread (mm)",
    "Mini Ground Clearance (mm)",
    "Net Weight (kg)",
    "Engine Model",
    "Rated Power(kw)",
    "Rated Power(r/min)",
    "Tipping Model",
    "Max Lifting Capacity",
    "Max Lifting Moment",
    "Rated Working Pressure",
    "Rated Flow",
    "Max Working Distance",
    "Min Working Distance",
    "Max Lifting Height(from ground)",
    "Match Forklift Weight",
    "Recommend Power",
    "Hydraulic System Flow",
    "Hydraulic System Pressure",
    "Oil Tank Capacity",
    "Installation Space",
    "Self Weight",
    "Rotation angle",
    "Working Height",
    "Platform Capacity",
    "Max Working Radius",
    "Max Boom Length",
]

APP_BY_CATEGORY = {
    "telescopic-boom-truck-mounted-cranes": ["construction", "logistics", "municipal-engineering"],
    "knuckle-boom-truck-mounted-cranes": ["construction", "logistics", "municipal-engineering"],
    "knuckle-boom-marine-cranes": ["marine", "logistics"],
    "telescopic-boom-marine-cranes": ["marine", "logistics"],
    "pickup-cranes": ["logistics", "agriculture", "construction"],
    "forklift-fly-jib-cranes": ["logistics", "construction"],
    "mini-dumpers": ["construction", "agriculture"],
    "aerial-work-platform": ["municipal-engineering", "construction"],
    "tail-lift": ["logistics"],
    "spider-cranes": ["construction", "municipal-engineering"],
    "timber-cranes": ["agriculture", "logistics"],
    "other-cranes": ["construction", "logistics"],
}

@dataclass
class ProductPage:
    url: str
    slug: str
    title: str
    model: str | None
    category_slug: str
    capacity: str | None
    capacity_value: float | None
    boom_type: str
    intro: str
    specs: list[tuple[str, str]]
    features: list[tuple[str, str]]
    images: list[str]


@dataclass
class ImportedMedia:
    id: int
    url: str
    role: str


def sql_literal(value):
    if value is None:
        return "NULL"
    if isinstance(value, bool):
        return "true" if value else "false"
    if isinstance(value, (int, float)):
        return str(value)
    return "'" + str(value).replace("'", "''") + "'"


def load_env_file(path: Path = ENV_FILE) -> None:
    if not path.exists():
        return

    for line in path.read_text().splitlines():
        line = line.strip()
        if not line or line.startswith("#") or "=" not in line:
            continue
        key, raw_value = line.split("=", 1)
        key = key.strip()
        if not key or key in os.environ:
            continue
        value = raw_value.strip()
        try:
            parts = shlex.split(value, comments=False, posix=True)
            if len(parts) == 1:
                value = parts[0]
        except ValueError:
            value = value.strip("'\"")
        os.environ[key] = value


def database_config() -> dict[str, str]:
    load_env_file()
    database_url = os.environ.get("DATABASE_URL")
    if database_url:
        parsed = urlparse(database_url)
        return {
            "host": parsed.hostname or DEFAULT_DB["host"],
            "port": str(parsed.port or DEFAULT_DB["port"]),
            "user": unquote(parsed.username or DEFAULT_DB["user"]),
            "dbname": unquote(parsed.path.lstrip("/") or DEFAULT_DB["dbname"]),
            "password": unquote(parsed.password or DEFAULT_DB["password"]),
        }

    return {
        "host": os.environ.get("PGHOST", DEFAULT_DB["host"]),
        "port": os.environ.get("PGPORT", DEFAULT_DB["port"]),
        "user": os.environ.get("PGUSER", DEFAULT_DB["user"]),
        "dbname": os.environ.get("PGDATABASE", DEFAULT_DB["dbname"]),
        "password": os.environ.get("PGPASSWORD", DEFAULT_DB["password"]),
    }


def run_psql(sql: str, *, tuples_only: bool = True) -> str:
    env = os.environ.copy()
    db = database_config()
    env.setdefault("PGPASSWORD", db["password"])
    args = [
        "psql",
        "-h", db["host"],
        "-p", db["port"],
        "-U", db["user"],
        "-d", db["dbname"],
        "-v", "ON_ERROR_STOP=1",
    ]
    if tuples_only:
        args.append("-At")
    args += ["-c", sql]
    result = subprocess.run(args, env=env, text=True, capture_output=True)
    if result.returncode != 0:
        print(result.stderr, file=sys.stderr)
        raise RuntimeError(f"psql failed: {sql[:240]}")
    lines = [line.strip() for line in result.stdout.splitlines() if line.strip() and not line.startswith("INSERT ") and not line.startswith("UPDATE ") and not line.startswith("DELETE ")]
    return "\n".join(lines)


def recalculate_frontend_status(product_id: int | None = None) -> None:
    """Keep direct-SQL imports aligned with the Payload product visibility hook."""
    where = f"WHERE p.id={int(product_id)}" if product_id is not None else ""
    run_psql(f"""
    WITH checklist AS (
      SELECT
        p.id,
        array_remove(ARRAY[
          CASE WHEN p.category_ref_id IS NULL THEN '产品分类' END,
          CASE WHEN p.cover_image_id IS NULL THEN '封面图' END,
          CASE WHEN NOT EXISTS (SELECT 1 FROM products_locales pl WHERE pl._parent_id = p.id AND coalesce(trim(pl.intro), '') <> '') THEN '简短介绍' END,
          CASE WHEN coalesce(trim(p.capacity), '') = '' AND p.capacity_value IS NULL THEN '起重能力' END,
          CASE WHEN coalesce(trim(p.boom_type), '') = '' THEN '臂架类型' END,
          CASE WHEN NOT EXISTS (SELECT 1 FROM products_applications pa WHERE pa.parent_id = p.id AND coalesce(trim(pa.value::text), '') <> '') THEN '应用场景' END,
          CASE WHEN NOT EXISTS (SELECT 1 FROM products_specs ps WHERE ps._parent_id = p.id) THEN '规格参数' END,
          CASE WHEN NOT EXISTS (SELECT 1 FROM products_gallery pg WHERE pg._parent_id = p.id) AND NOT EXISTS (SELECT 1 FROM products_drawing_images pdi WHERE pdi._parent_id = p.id) THEN '产品图库或图纸' END,
          CASE WHEN p.parameter_review_status::text IS DISTINCT FROM 'verified-from-source' THEN '参数未按来源核对' END,
        ], NULL) AS missing
      FROM products p
      {where}
    ),
    drawing_stats AS (
      SELECT
        p.id,
        count(pdi.id) AS drawing_total_count,
        0 AS drawing_review_needed_count,
        count(pdi.id) AS drawing_reviewed_count
      FROM products p
      LEFT JOIN products_drawing_images pdi ON pdi._parent_id = p.id
      {where}
      GROUP BY p.id
    )
    UPDATE products p
    SET
      frontend_content_status = CASE
        WHEN cardinality(checklist.missing) = 0 THEN 'ready'
        WHEN cardinality(checklist.missing) = 1 AND checklist.missing[1] = '参数未按来源核对' THEN 'needs-parameter-review'
        ELSE 'needs-content'
      END,
      frontend_missing_fields_note = CASE
        WHEN cardinality(checklist.missing) = 0 THEN '已具备前台测试需要的基础内容'
        ELSE array_to_string(checklist.missing, '、')
      END,
      drawing_review_status = 'not-required',
      drawing_review_summary = CASE
        WHEN coalesce(drawing_stats.drawing_total_count, 0) = 0 THEN '未上传图纸/参数图；当前图纸资料不参与发布阻塞'
        ELSE drawing_stats.drawing_total_count::text || ' 张图纸/参数图已记录；当前图纸资料不参与发布阻塞'
      END,
      drawing_review_needed_count = coalesce(drawing_stats.drawing_review_needed_count, 0),
      drawing_reviewed_count = coalesce(drawing_stats.drawing_reviewed_count, 0),
      updated_at = now()
    FROM checklist
    LEFT JOIN drawing_stats ON drawing_stats.id = checklist.id
    WHERE checklist.id = p.id;
    """, tuples_only=False)


def recalculate_category_frontend_status() -> None:
    run_psql("""
    WITH checklist AS (
      SELECT
        pc.id,
        array_remove(ARRAY[
          CASE WHEN pc.status::text IS DISTINCT FROM 'published' THEN '分类未发布' END,
          CASE WHEN pc.page_type::text = 'redirect-only' THEN '栏目类型为不在前台展示' END,
          CASE WHEN NOT EXISTS (SELECT 1 FROM product_categories_locales pcl WHERE pcl._parent_id = pc.id AND coalesce(trim(pcl.title), '') <> '') THEN '分类名称' END,
          CASE WHEN coalesce(trim(pc.slug), '') = '' THEN 'slug' END
        ], NULL) AS blocking,
        array_remove(ARRAY[
          CASE WHEN coalesce(trim(pc.legacy_path), '') = '' THEN '未填写旧站公开 URL，将使用 /products/category/{slug}' END,
          CASE WHEN pc.show_in_mega_menu IS NOT TRUE THEN '不进入顶部 Products 菜单' END,
          CASE WHEN pc.show_on_home IS NOT TRUE THEN '不进入首页产品分类' END
        ], NULL) AS notes
      FROM product_categories pc
    )
    UPDATE product_categories pc
    SET
      frontend_surface_status = CASE
        WHEN cardinality(checklist.blocking) > 0 THEN 'hidden'
        WHEN EXISTS (SELECT 1 FROM unnest(checklist.notes) AS n(note) WHERE n.note LIKE '%公开 URL%') THEN 'needs-url-review'
        ELSE 'ready'
      END,
      frontend_surface_note = CASE
        WHEN cardinality(checklist.blocking) > 0 THEN array_to_string(checklist.blocking || checklist.notes, '、')
        WHEN cardinality(checklist.notes) > 0 THEN array_to_string(checklist.notes, '、')
        ELSE '已具备产品列表筛选、分类页、顶部菜单和首页分类测试条件'
      END,
      updated_at = now()
    FROM checklist
    WHERE checklist.id = pc.id;
    """, tuples_only=False)


def collapse_text(markup: str) -> str:
    text = re.sub(r"<script.*?</script>", " ", markup, flags=re.I | re.S)
    text = re.sub(r"<style.*?</style>", " ", text, flags=re.I | re.S)
    text = re.sub(r"<[^>]+>", " ", text)
    text = html.unescape(text).replace("\xa0", " ")
    return re.sub(r"\s+", " ", text).strip()


def find_first(pattern: str, text: str, flags=re.I | re.S) -> str | None:
    match = re.search(pattern, text, flags)
    if not match:
        return None
    return collapse_text(match.group(1)).strip()


def normalize_url(value: str) -> str:
    value = html.unescape(value).strip()
    if value.startswith("//"):
        return "https:" + value
    return urljoin(BASE_URL, value)


def candidate_product_urls(limit: int | None = None) -> list[str]:
    xml = requests.get(f"{BASE_URL}/sitemap.xml", timeout=REQUEST_TIMEOUT).text
    urls = re.findall(r"<loc>(.*?)</loc>", xml)
    product_pattern = re.compile(r"(\d+(?:-\d+)?-(?:ton|kg|meter)-|zm\d+|tail-lift|spider-crane|timber-crane)", re.I)
    excluded = ["news", "company-news", "industry-news", "products-", "/products/", "productcategory", "keywords", "gallery", "video", "privacy", "contact", "about", "download", "faqlist"]
    selected = []
    for url in urls:
        path = urlparse(url).path.strip("/")
        if not path or any(item.lower() in url.lower() for item in excluded):
            continue
        lower = path.lower()
        if not (lower.endswith(".html") or "pd" in lower):
            continue
        if re.search(r"-(?:id|pl)\d+\.html$", lower):
            continue
        if lower in {"timber-crane.html", "timber-crane-1.html"}:
            continue
        if product_pattern.search(lower):
            selected.append(url)
    # Keep stable order, de-duplicate variants.
    deduped = list(dict.fromkeys(selected))
    return deduped[:limit] if limit else deduped


def category_for_url(url: str) -> str:
    lower = url.lower()
    if "telescopic-boom-truck" in lower:
        return "telescopic-boom-truck-mounted-cranes"
    if "knuckle-boom-truck" in lower:
        return "knuckle-boom-truck-mounted-cranes"
    if "knuckle-boom-marine" in lower:
        return "knuckle-boom-marine-cranes"
    if "telescopic-boom-marine" in lower:
        return "telescopic-boom-marine-cranes"
    if "pickup-crane" in lower:
        return "pickup-cranes"
    if "forklift-fly-jib" in lower:
        return "forklift-fly-jib-cranes"
    if "mini-dumper" in lower:
        return "mini-dumpers"
    if "aerial-work-platform" in lower:
        return "aerial-work-platform"
    if "tail-lift" in lower:
        return "tail-lift"
    if "spider-crane" in lower:
        return "spider-cranes"
    if "timber-crane" in lower:
        return "timber-cranes"
    return "other-cranes"


def boom_for_category(category_slug: str, title: str) -> str:
    text = f"{category_slug} {title}".lower()
    if "telescopic" in text:
        return "telescopic-boom"
    if "knuckle" in text:
        return "knuckle-boom"
    if "forklift" in text:
        return "fly-jib"
    return "custom-solution"


def legacy_category_for(category_slug: str) -> str:
    mapping = {
        "telescopic-boom-truck-mounted-cranes": "telescopic-boom-truck-mounted-cranes",
        "knuckle-boom-truck-mounted-cranes": "knuckle-boom-truck-mounted-cranes",
        "knuckle-boom-marine-cranes": "knuckle-boom-marine-cranes",
        "telescopic-boom-marine-cranes": "telescopic-boom-marine-cranes",
        "pickup-cranes": "pickup-cranes",
        "forklift-fly-jib-cranes": "forklift-fly-jib-cranes",
        "mini-dumpers": "mini-dumpers",
        "aerial-work-platform": "aerial-work-platform",
    }
    return mapping.get(category_slug, "other-cranes")


def parse_capacity(title: str, specs: list[tuple[str, str]], intro: str = "") -> tuple[str | None, float | None]:
    title_ton_match = re.search(r"([0-9]+(?:\.[0-9]+)?)\s*ton\b", title, flags=re.I)
    title_tons = float(title_ton_match.group(1)) if title_ton_match else None

    for label, value in specs:
        if label in {"Max Lifting Capacity", "Platform Capacity"}:
            match = re.search(r"([0-9]+(?:\.[0-9]+)?)", value)
            if match:
                number = float(match.group(1))
                if re.search(r"\bkg\b|kilogram", value, flags=re.I):
                    tons = round(number / 1000, 3)
                    if re.search(r"\bkg\b", title, flags=re.I) or tons < 1:
                        return (f"{number:g} kg", tons)
                    return (f"{tons:g} Ton", tons)
                if title_tons and number >= 1000 and abs(number / 1000 - title_tons) < 0.05:
                    return (f"{title_tons:g} Ton", title_tons)
                return (f"{number:g} Ton", number)
        if label == "Working Height" and "aerial work platform" in title.lower():
            match = re.search(r"([0-9]+(?:\.[0-9]+)?)\s*m", value, flags=re.I)
            if match:
                number = float(match.group(1))
                return (f"{number:g} m", number)
    tail_lift_match = re.search(r"handles\s+([0-9]+(?:\.[0-9]+)?(?:\s*-\s*[0-9]+(?:\.[0-9]+)?)?)\s*(tons?|kg)\b", intro, flags=re.I)
    if tail_lift_match:
        raw = re.sub(r"\s+", "", tail_lift_match.group(1))
        unit = tail_lift_match.group(2).lower()
        numbers = [float(item) for item in re.findall(r"[0-9]+(?:\.[0-9]+)?", raw)]
        if numbers:
            display_unit = "kg" if unit == "kg" else "tons"
            value = max(numbers)
            capacity_value = round(value / 1000, 3) if unit == "kg" else value
            return (f"{raw} {display_unit}", capacity_value)
    match = re.search(r"([0-9]+(?:-[0-9]+)?(?:\.[0-9]+)?)\s*-?\s*(ton|kg|meter)", title, re.I)
    if not match:
        return None, None
    raw = match.group(1).replace("-", ".")
    unit = match.group(2).lower()
    number = float(raw)
    if unit == "kg":
        return f"{number:g} kg", round(number / 1000, 3)
    if unit == "meter":
        return f"{number:g} m", number
    return f"{number:g} Ton", number


def parse_specs(text: str) -> list[tuple[str, str]]:
    start_match = re.search(r"\bSpecifications\b", text, flags=re.I)
    source = text[start_match.start():] if start_match else text
    stop_match = re.search(r"\b(Why|Details|Send Inquiry|Related Products|Recommended Products)\b", source, flags=re.I)
    if stop_match:
        source = source[:stop_match.start()]

    positions = []
    for label in SPEC_LABELS:
        pattern = re.escape(label).replace(r"\ ", r"\s+")
        for match in re.finditer(pattern, source, flags=re.I):
            positions.append((match.start(), match.end(), label))
    positions.sort(key=lambda item: item[0])

    specs = []
    seen = set()
    for index, (start, end, label) in enumerate(positions):
        if start in seen:
            continue
        seen.add(start)
        next_start = positions[index + 1][0] if index + 1 < len(positions) else len(source)
        raw = re.sub(r"\s+", " ", source[end:next_start]).strip(" :-")
        raw = re.sub(r"^(Specifications|specification)\s+", "", raw, flags=re.I).strip(" :-")
        if not raw or len(raw) > 120:
            continue
        specs.append((label, normalize_spec_value(raw)))
    return specs


def normalize_spec_value(raw: str) -> str:
    parts = raw.split()
    if len(parts) < 2 or not re.match(r"^[A-Za-z°./%]+$", parts[0]):
        return raw

    tail = " ".join(parts[1:])
    if not re.search(r"\d", tail):
        return raw
    if re.search(r"[A-Za-z°./%]", tail):
        return tail
    return f"{tail} {parts[0]}"


def inferred_specs(title: str, model: str | None, category_slug: str, intro: str = "") -> list[tuple[str, str]]:
    specs = []
    if model:
        specs.append(("Model", model))
    if category_slug == "aerial-work-platform":
        match = re.search(r"([0-9]+(?:\.[0-9]+)?)\s*-?\s*meter", title, flags=re.I)
        if match:
            specs.append(("Working Height", f"{match.group(1)} m"))
        specs.append(("Boom Type", boom_for_category(category_slug, title).replace("-", " ").title()))
    if category_slug == "spider-cranes":
        specs.append(("Product Type", "Spider Crane"))
        specs.append(("Boom Type", "Knuckle Boom" if "knuckle" in title.lower() else "Telescopic Boom"))
        if re.search(r"self-balancing|legs can be adjusted independently|multi-leg", intro, flags=re.I):
            specs.append(("Stabilization", "Adjustable spider legs"))
        if re.search(r"narrow|complex terrains|restricted spaces|indoors|outdoors", intro, flags=re.I):
            specs.append(("Working Scenario", "Narrow or complex working spaces"))
    return specs


def parse_images(markup: str) -> list[str]:
    values = re.findall(r"(?:data-original|data-src|src)=['\"]([^'\"]+)['\"]", markup, flags=re.I)
    result = []
    for value in values:
        url = normalize_url(value)
        lower = url.lower()
        if any(bad in lower for bad in ["facebook", "sharethis", "langbar", "1211.png", "logo", "100-100", "-300-300", "wangyanweixin", "weixin", "qrcode", "qr-code", ".svg", "noscript"]):
            continue
        if not any(ext in lower for ext in [".jpg", ".jpeg", ".png", ".webp"]):
            continue
        if url not in result:
            result.append(url)
    return result[:8]


def feature_title_from_text(text: str, index: int) -> str:
    lowered = text.lower()
    if any(token in lowered for token in ["steel plate", "reinforced", "interface"]):
        return "Reinforced boom interface"
    if any(token in lowered for token in ["telescopic", "cylinder", "boom extension"]):
        return "Telescopic boom design"
    if any(token in lowered for token in ["hexagon", "pentagon", "anti-bending", "bending"]):
        return "Stable boom structure"
    if any(token in lowered for token in ["truck", "mount"]):
        return "Truck-mounted installation"
    if any(token in lowered for token in ["leg", "outrigger", "support"]):
        return "Stable support system"
    if any(token in lowered for token in ["safe", "operator", "load"]):
        return "Safer lifting operation"
    if any(token in lowered for token in ["cargo", "construction", "forestry", "job"]):
        return "Practical jobsite handling"
    if any(token in lowered for token in ["lifting", "power", "capacity"]):
        return "Reliable lifting performance"

    fallback_titles = [
        "Practical product configuration",
        "Reliable operating structure",
        "Project-ready performance",
        "Export-oriented build quality",
    ]
    return fallback_titles[min(index - 1, len(fallback_titles) - 1)]


def clean_feature_text(text: str) -> str:
    return text.replace("does not enhance its lifting power", "helps improve lifting performance")


def truncate_feature_text(text: str, max_length: int = 260) -> str:
    cleaned = clean_feature_text(text).strip()
    if len(cleaned) <= max_length:
        return cleaned

    punctuation_cut = max(cleaned.rfind(".", 0, max_length), cleaned.rfind("!", 0, max_length), cleaned.rfind("?", 0, max_length))
    if punctuation_cut >= 80:
        return cleaned[: punctuation_cut + 1].strip()

    word_cut = cleaned.rfind(" ", 0, max_length)
    if word_cut >= 80:
        return cleaned[:word_cut].rstrip(" ,;:") + "."

    return cleaned[:max_length].rstrip(" ,;:") + "."


def parse_product(url: str) -> ProductPage | None:
    markup = requests.get(url, timeout=REQUEST_TIMEOUT).text
    title = find_first(r"<h1[^>]*>(.*?)</h1>", markup) or find_first(r"<title[^>]*>(.*?)</title>", markup)
    if not title or len(title) < 4:
        return None
    if title.endswith(" - BOB-LIFT"):
        return None
    plain = collapse_text(markup)
    if "Product Description" not in plain and "Specifications" not in plain and "Model:" not in plain:
        return None
    slug = Path(urlparse(url).path).name.replace(".html", "").lower()
    category_slug = category_for_url(url)
    model_match = re.search(r"Model:\s*([A-Z0-9.-]+)", plain)
    model = model_match.group(1) if model_match else None
    specs = parse_specs(plain)
    intro = ""
    summary_match = re.search(r"(?:loading\s+)?Share to:\s*" + re.escape(title) + r"\s+(.+?)\s+Availability:", plain, flags=re.I)
    if summary_match:
        intro = summary_match.group(1).strip()
    if not intro:
        desc_match = re.search(r"Product Description\s+(.+?)(?:" + re.escape(title) + r" Specifications|Why |Details|Send Inquiry)", plain, flags=re.I)
        if desc_match:
            intro = desc_match.group(1).strip()
    if not intro:
        intro = f"{title} for BOB-LIFT lifting and export projects."
    intro = intro[:320]
    if not specs:
        specs = inferred_specs(title, model, category_slug, intro)
    features = []
    why_match = re.search(r"Why .*?\?\s+(.+?)(?:" + re.escape(title) + r" Details|Send Inquiry|Related Products)", plain, flags=re.I)
    if why_match:
        paragraphs = re.split(r"(?<=[.!?])\s+(?=[A-Z])", why_match.group(1).strip())
        for idx, paragraph in enumerate([p for p in paragraphs if len(p) > 40][:4], start=1):
            cleaned_paragraph = truncate_feature_text(paragraph)
            features.append((feature_title_from_text(cleaned_paragraph, idx), cleaned_paragraph))
    capacity, capacity_value = parse_capacity(title, specs, intro)
    if not capacity and category_slug == "spider-cranes":
        capacity = "Project-specific configuration"
    if not specs and capacity:
        specs = [("Max Lifting Capacity", capacity)]
    images = parse_images(markup)
    return ProductPage(url, slug, title, model, category_slug, capacity, capacity_value, boom_for_category(category_slug, title), intro, specs, features, images)


def ensure_categories() -> dict[str, int]:
    ids = {}
    for slug, title, short, menu, listing, icon, sort in CATEGORY_SEEDS:
        category_id = run_psql(f"""
        WITH upsert AS (
          INSERT INTO product_categories (slug, legacy_path, icon_name, menu_group, listing_group, show_in_mega_menu, show_on_home, sort_order, status, published_at, updated_at, created_at)
          VALUES ({sql_literal(slug)}, {sql_literal('/' + slug)}, {sql_literal(icon)}, {sql_literal(menu)}, {sql_literal(listing)}, true, true, {sort}, 'published', now(), now(), now())
          ON CONFLICT (slug) DO UPDATE SET icon_name=EXCLUDED.icon_name, menu_group=EXCLUDED.menu_group, listing_group=EXCLUDED.listing_group, sort_order=EXCLUDED.sort_order, status='published', updated_at=now()
          RETURNING id
        ) SELECT id FROM upsert;
        """)
        ids[slug] = int(category_id)
        run_psql(f"""
        INSERT INTO product_categories_locales (_parent_id, _locale, title, short_title, description, banner_label, banner_title)
        VALUES ({ids[slug]}, 'en', {sql_literal(title)}, {sql_literal(short)}, {sql_literal(title + ' from BOB-LIFT legacy catalog.')}, 'BOB-LIFT Product Range', {sql_literal(title)})
        ON CONFLICT (_locale, _parent_id) DO UPDATE SET title=EXCLUDED.title, short_title=EXCLUDED.short_title, description=EXCLUDED.description, banner_label=EXCLUDED.banner_label, banner_title=EXCLUDED.banner_title;
        """)
    recalculate_category_frontend_status()
    return ids


def make_sizes(src: Path) -> dict[str, tuple[Path, int, int, int]]:
    sizes = {"thumbnail": (480, 320), "card": (900, 700), "hero": (1920, 1080)}
    out = {}
    with Image.open(src) as image:
        image = image.convert("RGB") if image.mode not in ("RGB", "RGBA") else image
        for name, size in sizes.items():
            resized = ImageOps.contain(image, size)
            canvas = Image.new("RGB", size, "white")
            canvas.paste(resized.convert("RGB"), ((size[0] - resized.width) // 2, (size[1] - resized.height) // 2))
            out_path = src.with_name(f"{src.stem}-{size[0]}x{size[1]}{src.suffix}")
            canvas.save(out_path, quality=88)
            out[name] = (out_path, size[0], size[1], out_path.stat().st_size)
    return out


def import_media(image_url: str, alt: str, prefix: str) -> ImportedMedia | None:
    MEDIA_DIR.mkdir(parents=True, exist_ok=True)
    suffix = Path(urlparse(image_url).path).suffix.lower() or ".jpg"
    if suffix == ".jpeg":
        suffix = ".jpg"
    digest = hashlib.sha1(image_url.encode()).hexdigest()[:10]
    filename = re.sub(r"[^a-z0-9-]+", "-", prefix.lower()).strip("-")[:80] + f"-{digest}{suffix}"
    existing = run_psql(f"SELECT id FROM media WHERE filename={sql_literal(filename)} LIMIT 1")
    if existing:
        path = MEDIA_DIR / filename
        role = classify_image(path, image_url) if path.exists() else "product"
        if role == "skip":
            return None
        return ImportedMedia(int(existing), image_url, role)
    try:
        response = requests.get(image_url, timeout=REQUEST_TIMEOUT)
        response.raise_for_status()
        path = MEDIA_DIR / filename
        path.write_bytes(response.content)
        with Image.open(path) as image:
            width, height = image.size
        role = classify_image(path, image_url)
        if role == "skip":
            path.unlink(missing_ok=True)
            return None
        sizes = make_sizes(path)
    except Exception as exc:
        print(f"skip image {image_url}: {exc}", file=sys.stderr)
        return None
    mime = mimetypes.guess_type(filename)[0] or "image/jpeg"
    def url_for(file: Path): return f"/api/payload/media/file/{file.name}"
    thumbnail, card, hero = sizes["thumbnail"], sizes["card"], sizes["hero"]
    media_id = run_psql(f"""
    INSERT INTO media (alt, caption, url, filename, mime_type, filesize, width, height, focal_x, focal_y,
      sizes_thumbnail_url, sizes_thumbnail_width, sizes_thumbnail_height, sizes_thumbnail_mime_type, sizes_thumbnail_filesize, sizes_thumbnail_filename,
      sizes_card_url, sizes_card_width, sizes_card_height, sizes_card_mime_type, sizes_card_filesize, sizes_card_filename,
      sizes_hero_url, sizes_hero_width, sizes_hero_height, sizes_hero_mime_type, sizes_hero_filesize, sizes_hero_filename,
      updated_at, created_at)
    VALUES ({sql_literal(alt)}, NULL, {sql_literal(url_for(path))}, {sql_literal(filename)}, {sql_literal(mime)}, {path.stat().st_size}, {width}, {height}, 50, 50,
      {sql_literal(url_for(thumbnail[0]))}, {thumbnail[1]}, {thumbnail[2]}, {sql_literal(mime)}, {thumbnail[3]}, {sql_literal(thumbnail[0].name)},
      {sql_literal(url_for(card[0]))}, {card[1]}, {card[2]}, {sql_literal(mime)}, {card[3]}, {sql_literal(card[0].name)},
      {sql_literal(url_for(hero[0]))}, {hero[1]}, {hero[2]}, {sql_literal(mime)}, {hero[3]}, {sql_literal(hero[0].name)},
      now(), now()) RETURNING id;
    """)
    return ImportedMedia(int(media_id), image_url, role)


def classify_image(path: Path, image_url: str) -> str:
    lower = image_url.lower()
    if any(token in lower for token in ["weixin", "qrcode", "qr-code", "whatsapp"]):
        return "skip"
    if any(token in lower for token in ["parameter", "spec", "drawing", "chart"]):
        return "parameter-chart"

    try:
        with Image.open(path) as image:
            sample = ImageOps.contain(image.convert("RGB"), (120, 120))
            pixels = list(sample.getdata())
    except Exception:
        return "product"

    if not pixels:
        return "skip"

    white_ratio = sum(1 for red, green, blue in pixels if red > 232 and green > 232 and blue > 232) / len(pixels)
    dark_ratio = sum(1 for red, green, blue in pixels if red < 92 and green < 92 and blue < 92) / len(pixels)
    chroma_avg = sum(max(pixel) - min(pixel) for pixel in pixels) / len(pixels)

    if white_ratio > 0.94 and dark_ratio < 0.015:
        return "skip"
    if white_ratio > 0.58 and chroma_avg < 38 and dark_ratio > 0.025:
        return "parameter-chart"
    return "product"


def import_product(product: ProductPage, category_ids: dict[str, int], import_batch_id: str, dry_run: bool = False, overwrite_unverified: bool = False, refresh_verified_media: bool = False) -> int | None:
    if dry_run:
        print(f"DRY {product.slug}: {product.title} specs={len(product.specs)} images={len(product.images)}")
        return None
    existing = run_psql(f"SELECT id || '|' || COALESCE(parameter_review_status::text, '') || '|' || COALESCE(import_source::text, '') FROM products WHERE slug={sql_literal(product.slug)} LIMIT 1")
    if existing:
        existing_id, existing_status, existing_source = existing.split("|", 2)
        if existing_status == "verified-from-source" and not refresh_verified_media:
            run_psql(f"""
            UPDATE products
            SET source_url={sql_literal(product.url)}, import_source='legacy-site', import_batch_id={sql_literal(import_batch_id)}, updated_at=now()
            WHERE id={int(existing_id)};
            """)
            recalculate_frontend_status(int(existing_id))
            print(f"preserved verified product {product.slug} id={existing_id}")
            return int(existing_id)
        if existing_source != "legacy-site":
            print(f"preserved manual product {product.slug} id={existing_id}")
            return int(existing_id)
        if not overwrite_unverified and not (refresh_verified_media and existing_status == "verified-from-source"):
            run_psql(f"""
            UPDATE products
            SET source_url={sql_literal(product.url)}, import_batch_id={sql_literal(import_batch_id)}, updated_at=now()
            WHERE id={int(existing_id)};
            """)
            recalculate_frontend_status(int(existing_id))
            print(f"preserved existing legacy product {product.slug} id={existing_id}; pass --overwrite-unverified to refresh parsed rows")
            return int(existing_id)
    imported_media = [item for item in (import_media(url, product.title, product.slug) for url in product.images) if item]
    product_media = [item for item in imported_media if item.role == "product"]
    parameter_media = [item for item in imported_media if item.role == "parameter-chart"]
    cover_id = product_media[0].id if product_media else (imported_media[0].id if imported_media else None)
    review = "needs-review"
    category_id = category_ids[product.category_slug]
    legacy_path = urlparse(product.url).path
    product_id = run_psql(f"""
    INSERT INTO products (slug, legacy_path, model, category, capacity, cover_image_id, seo_meta_title, seo_meta_description, seo_canonical_path,
      status, published_at, updated_at, created_at, capacity_value, boom_type, availability, is_featured, popularity, sort_order, category_ref_id,
      source_url, import_source, parameter_review_status, import_batch_id)
    VALUES ({sql_literal(product.slug)}, {sql_literal(urlparse(product.url).path)}, {sql_literal(product.model)}, {sql_literal(legacy_category_for(product.category_slug))},
      {sql_literal(product.capacity)}, {sql_literal(cover_id)}, {sql_literal(product.title)}, {sql_literal(product.intro)}, {sql_literal(legacy_path)},
      'published', now(), now(), now(), {sql_literal(product.capacity_value)}, {sql_literal(product.boom_type)}, 'customizable', false, 60, 100, {category_id},
      {sql_literal(product.url)}, 'legacy-site', {sql_literal(review)}, {sql_literal(import_batch_id)})
    ON CONFLICT (slug) DO UPDATE SET legacy_path=EXCLUDED.legacy_path, model=EXCLUDED.model, category=EXCLUDED.category, capacity=EXCLUDED.capacity,
      cover_image_id=COALESCE(EXCLUDED.cover_image_id, products.cover_image_id), seo_meta_title=EXCLUDED.seo_meta_title, seo_meta_description=EXCLUDED.seo_meta_description,
      seo_canonical_path=EXCLUDED.seo_canonical_path, status='published', published_at=COALESCE(products.published_at, now()), updated_at=now(), capacity_value=EXCLUDED.capacity_value, boom_type=EXCLUDED.boom_type,
      category_ref_id=EXCLUDED.category_ref_id, source_url=EXCLUDED.source_url, import_source='legacy-site',
      parameter_review_status=CASE WHEN products.parameter_review_status='verified-from-source' THEN products.parameter_review_status ELSE EXCLUDED.parameter_review_status END,
      import_batch_id=EXCLUDED.import_batch_id
    RETURNING id;
    """)
    pid = int(product_id)
    notes = f"Imported from old site batch {import_batch_id}. Parameters are parsed from original source text and must be checked against the old-site source page before marking as verified. Drawing images are stored as supporting material only."
    run_psql(f"""
    INSERT INTO products_locales (_parent_id, _locale, title, product_type, intro, parameter_review_notes)
    VALUES ({pid}, 'en', {sql_literal(product.title)}, {sql_literal(product.title)}, {sql_literal(product.intro)}, {sql_literal(notes)})
    ON CONFLICT (_locale, _parent_id) DO UPDATE SET title=EXCLUDED.title, product_type=EXCLUDED.product_type, intro=EXCLUDED.intro, parameter_review_notes=EXCLUDED.parameter_review_notes;
    DELETE FROM products_specs WHERE _parent_id={pid};
    DELETE FROM products_key_features WHERE _parent_id={pid};
    DELETE FROM products_gallery WHERE _parent_id={pid};
    DELETE FROM products_drawing_images WHERE _parent_id={pid};
    DELETE FROM products_applications WHERE parent_id={pid};
    DELETE FROM products_texts WHERE parent_id={pid} AND path='applications';
    """)
    for order, app in enumerate(APP_BY_CATEGORY.get(product.category_slug, ["construction"])):
        run_psql(f'INSERT INTO products_applications ("order", parent_id, value) VALUES ({order}, {pid}, {sql_literal(app)});')
        run_psql(f'INSERT INTO products_texts ("order", parent_id, path, text) VALUES ({order}, {pid}, {sql_literal("applications")}, {sql_literal(app)});')
    spec_source_image_id = parameter_media[0].id if parameter_media else None
    spec_source_note = f"Parsed from old-site specification text: {product.url}"
    for order, (label, value) in enumerate(product.specs):
        row_id = str(uuid.uuid4())
        run_psql(f"""
        INSERT INTO products_specs (_order, _parent_id, id, source_image_id) VALUES ({order}, {pid}, {sql_literal(row_id)}, {sql_literal(spec_source_image_id)});
        INSERT INTO products_specs_locales (_parent_id, _locale, label, value, source_note) VALUES ({sql_literal(row_id)}, 'en', {sql_literal(label)}, {sql_literal(value)}, {sql_literal(spec_source_note)});
        """)
    for order, (title, text) in enumerate(product.features):
        row_id = str(uuid.uuid4())
        run_psql(f"""
        INSERT INTO products_key_features (_order, _parent_id, id) VALUES ({order}, {pid}, {sql_literal(row_id)});
        INSERT INTO products_key_features_locales (_parent_id, _locale, title, text) VALUES ({sql_literal(row_id)}, 'en', {sql_literal(title)}, {sql_literal(text)});
        """)
    gallery_media = [item for item in product_media if item.id != cover_id][:4]
    for order, media in enumerate(gallery_media):
        row_id = str(uuid.uuid4())
        run_psql(f"""
        INSERT INTO products_gallery (_order, _parent_id, id, image_id) VALUES ({order}, {pid}, {sql_literal(row_id)}, {media.id});
        INSERT INTO products_gallery_locales (_parent_id, _locale, label) VALUES ({sql_literal(row_id)}, 'en', {sql_literal(product.title + ' image ' + str(order + 1))});
        """)
    for order, media in enumerate(parameter_media[:4]):
        row_id = str(uuid.uuid4())
        run_psql(f"""
        INSERT INTO products_drawing_images (_order, _parent_id, id, image_id, source_type, source_url, review_status)
        VALUES ({order}, {pid}, {sql_literal(row_id)}, {media.id}, 'parameter-chart', {sql_literal(media.url)}, 'needs-review');
        INSERT INTO products_drawing_images_locales (_parent_id, _locale, label) VALUES ({sql_literal(row_id)}, 'en', {sql_literal(product.title + ' parameter chart ' + str(order + 1))});
        """)
    recalculate_frontend_status(pid)
    return pid


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--limit", type=int, default=None)
    parser.add_argument("--dry-run", action="store_true")
    parser.add_argument("--sleep", type=float, default=0.15)
    parser.add_argument("--batch-id", default=time.strftime("legacy-%Y%m%d-%H%M%S"), help="Import batch id written to products.import_batch_id")
    parser.add_argument("--overwrite-unverified", action="store_true", help="Refresh existing legacy-site products that are not verified-from-source")
    parser.add_argument("--refresh-verified-media", action="store_true", help="Refresh media roles for verified legacy products while preserving their verified status.")
    args = parser.parse_args()

    urls = candidate_product_urls(args.limit)
    print(f"Found {len(urls)} candidate product URLs")
    category_ids = ensure_categories() if not args.dry_run else {slug: i + 1 for i, (slug, *_rest) in enumerate(CATEGORY_SEEDS)}
    imported = 0
    skipped = 0
    for index, url in enumerate(urls, start=1):
        try:
            product = parse_product(url)
            if not product:
                skipped += 1
                continue
            pid = import_product(product, category_ids, args.batch_id, dry_run=args.dry_run, overwrite_unverified=args.overwrite_unverified, refresh_verified_media=args.refresh_verified_media)
            imported += 1
            print(f"[{index}/{len(urls)}] imported {product.slug} id={pid} specs={len(product.specs)} images={len(product.images)}")
            time.sleep(args.sleep)
        except Exception as exc:
            skipped += 1
            print(f"[{index}/{len(urls)}] failed {url}: {exc}", file=sys.stderr)
    print(f"Done. imported={imported} skipped={skipped}")


if __name__ == "__main__":
    main()

"""Build plan.json for the truck-mounted cranes from the technical department's drawings (2026-10-09).

Source: ~/Desktop/工作/随车吊/{折叠臂,伸缩臂}/*样本PDF — per model an outline drawing with the technical parameter
table (外形尺寸及参数图 / 外形尺寸图) and a load chart (起升曲线图). drawings.json holds the parameter tables, read
by OCR from those PDFs and checked by eye where OCR missed a cell.

- Existing products: the spec table is rebuilt from the drawing (adds oil tank capacity and installation space).
  Aliases that turned out to be separate models are dropped.
- New models get a product with specs, the series features of a sibling, the sibling's photo as a labelled
  reference until own photos are added (tools/product-photos), and the outline drawing and load chart as drawings.

    python3 tools/truck-drawings/build.py      # renders missing drawing images to media/
Never writes to the source folder.
"""
import json, os, re, subprocess, sys

DIR = os.path.dirname(os.path.abspath(__file__))
sys.path.insert(0, os.path.join(DIR, '..', 'brochure-specs'))
import specs  # noqa: E402
from specs import ADV  # noqa: E402

SRC = os.path.expanduser('~/Desktop/工作/随车吊')
KNUCKLE = ('Knuckle Boom Truck Mounted Crane', 'knuckle-boom-truck-mounted-cranes', 'knuckle-boom', 'truck-knuckle')
TELESCOPIC = ('Telescopic Boom Truck Mounted Crane', 'telescopic-boom-truck-mounted-cranes', 'telescopic-boom', 'truck-telescopic')
APPS = 'construction,logistics,municipal-engineering'

# Products already on the site, by model.
EXISTING = {
    'BLZ129K4': '6-3-ton-knuckle-boom-truck-mounted-crane-blz129k4', 'BLZ90K5': '4-6-ton-knuckle-boom-truck-mounted-crane',
    'SQ10ZA6': '10-ton-knuckle-boom-truck-mounted-crane', 'SQ12ZA6': '12-ton-knuckle-boom-truck-mounted-crane',
    'SQ1ZA3+1': '1-ton-knuckle-boom-truck-mounted-crane', 'SQ2ZA4': '2-ton-knuckle-boom-truck-mounted-crane',
    'SQ3.2ZA3': '3-2-ton-knuckle-boom-truck-mounted-crane', 'SQ3.2ZA4': '3-2-ton-knuckle-boom-truck-mounted-crane-sq3-2za4',
    'SQ4ZA3': '4-ton-knuckle-boom-truck-mounted-crane-sq4za3', 'SQ4ZA4': '4-ton-knuckle-boom-truck-mounted-crane',
    'SQ5ZA3': '5-ton-knuckle-boom-truck-mounted-crane-sq5za3', 'SQ5ZA4': '5-ton-knuckle-boom-truck-mounted-crane',
    'SQ6.3ZA5': '6-3-ton-knuckle-boom-truck-mounted-crane', 'SQ8ZA6': '8-ton-knuckle-boom-truck-mounted-crane',
    'SQZ330': '16-ton-knuckle-boom-truck-mounted-crane', 'SQZ430': '20-ton-knuckle-boom-truck-mounted-crane',
    'SQZ500': '25-ton-knuckle-boom-truck-mounted-crane', 'SQZ560': '28-ton-knuckle-boom-truck-mounted-crane',
    'SQZ600': '30-ton-knuckle-boom-truck-mounted-crane', 'SQZ700': '34-ton-knuckle-boom-truck-mounted-crane',
    'SQZ800': '40-ton-knuckle-boom-truck-mounted-crane', 'SQ08ZA3+1': '800-kg-pickup-crane-with-outriggers',
    'SQ10SA4': '10-ton-telescopic-boom-truck-mounted-crane', 'SQ12SA4': '12-ton-telescopic-boom-truck-mounted-crane',
    'SQ14SA5': '14-ton-telescopic-boom-truck-mounted-crane', 'SQ16SA5': '16-ton-telescopic-boom-truck-mounted-crane',
    'SQ20SA5': '20-ton-telescopic-boom-truck-mounted-crane', 'SQ2SA3': '2-ton-telescopic-boom-truck-mounted-crane',
    'SQ3.2SA4': '3-2-ton-telescopic-boom-truck-mounted-crane', 'SQ4SA4': '4-ton-telescopic-boom-truck-mounted-crane',
    'SQ5SA3': '5-ton-telescopic-boom-truck-mounted-crane-sq5sa3', 'SQ5SA4': '5-ton-telescopic-boom-truck-mounted-crane',
    'SQ6.3SA4': '6-3-ton-telescopic-boom-truck-mounted-crane', 'SQ8SA5': '8-ton-telescopic-boom-truck-mounted-crane',
}
# Aliases that are now products of their own.
DROP_ALIASES = {'SQ6.3ZA5': 'SQ6.3ZA3', 'SQ12ZA6': 'SQ12ZA5', 'SQ8ZA6': 'SQ8ZA5', 'SQ10SA4': 'SQ10SA5', 'SQ3.2SA4': 'SQ3.2SA3'}
# New model -> sibling product whose photo and features stand in.
NEW = {
    'BLZ90K4': 'BLZ90K5', 'SQ1ZA4': 'SQ1ZA3+1', 'SQ2ZA2': 'SQ2ZA4', 'SQ2ZA3': 'SQ2ZA4', 'SQ6.3ZA3': 'SQ6.3ZA5',
    'SQ6.3ZA4': 'SQ6.3ZA5', 'SQ8ZA4': 'SQ8ZA6', 'SQ8ZA5': 'SQ8ZA6', 'SQ10ZA4': 'SQ10ZA6', 'SQ10ZA5': 'SQ10ZA6',
    'SQ12ZA4': 'SQ12ZA6', 'SQ12ZA5': 'SQ12ZA6', 'SQZ350': 'SQZ330', 'SQZ1000': 'SQZ800',
    'SQ3.2SA3': 'SQ3.2SA4', 'SQ6.3SA3': 'SQ6.3SA4', 'SQ8SA4': 'SQ8SA5', 'SQ10SA5': 'SQ10SA4', 'SQ12SA5': 'SQ12SA4',
    'SQ14SA4': 'SQ14SA5',
}
# SQ8ZA5F is held back: the F suffix needs the user's word on what kind of crane it is.
HOLD = {'SQ8ZA5F'}
DATA, PLAN = 'drawings.json', 'plan.json'
# Marine cranes (2026-10-09): same build from ~/Desktop/工作/船吊.   python3 build.py marine
if sys.argv[1:] == ['marine']:
    SRC = os.path.expanduser('~/Desktop/工作/船吊')
    KNUCKLE = ('Knuckle Boom Marine Crane', 'knuckle-boom-marine-cranes', 'knuckle-boom', 'marine')
    TELESCOPIC = ('Telescopic Boom Marine Crane', 'telescopic-boom-marine-cranes', 'telescopic-boom', 'marine-telescopic')
    APPS = 'marine,logistics'
    DATA, PLAN = 'marine-drawings.json', 'marine-plan.json'
    EXISTING = {
        'BLZ90K5TJ': '4-6-ton-knuckle-boom-marine-crane', 'SQ08ZA3TJ+1': '0-8-ton-knuckle-boom-marine-crane',
        'SQ10ZA6TJ': '10-ton-knuckle-boom-marine-crane', 'SQ12ZA6T': '12-ton-knuckle-boom-marine-crane',
        'SQ1ZA3TJ+1': '1-ton-knuckle-boom-marine-crane', 'SQ2ZA2TJ': '2-ton-knuckle-boom-marine-crane',
        'SQ3.2ZA3TJ': '3-2-ton-knuckle-boom-marine-crane', 'SQ4ZA3TJ': '4-ton-knuckle-boom-marine-crane',
        'SQ5ZA3TJ': '5-ton-knuckle-boom-marine-crane', 'SQ6.3ZA4TJ': '6-3-ton-knuckle-boom-marine-crane',
        'SQ8ZA6TJ': '8-ton-knuckle-boom-marine-crane', 'SQZ330T': '16-ton-knuckle-boom-marine-crane',
        'SQZ430T': '20-ton-knuckle-boom-marine-crane', 'SQZ500T': '25-ton-knuckle-boom-marine-crane',
        'SQ10SA4T': '10-ton-telescopic-boom-marine-crane', 'SQ12SA4T': '12-ton-telescopic-boom-marine-crane',
        'SQ16SA5T': '16-ton-telescopic-boom-marine-crane', 'SQ20SA5T': '20-ton-telescopic-boom-marine-crane',
        'SQ2SA3T': '2-ton-telescopic-boom-marine-crane', 'SQ3.2SA4T': '3-2-ton-telescopic-boom-marine-crane',
        'SQ4SA3T': '4-ton-telescopic-boom-marine-crane', 'SQ5SA4T': '5-ton-telescopic-boom-marine-crane',
        'SQ6.3SA4T': '6-3-ton-telescopic-boom-marine-crane', 'SQ8SA5T': '8-ton-telescopic-boom-marine-crane',
    }
    # Aliases that are now products of their own. Renamed models keep their old name (SQZ330T <- SQ16ZA5T...).
    DROP_ALIASES = {'SQ8SA5T': 'SQ8SA4T', 'SQ10ZA6TJ': 'SQ10ZA5TJ', 'SQ8ZA6TJ': 'SQ8ZA5TJ'}
    NEW = {
        'BLZ90K4TJ': 'BLZ90K5TJ', 'SQ08ZA3T+1': 'SQ08ZA3TJ+1', 'SQ1ZA3T+1': 'SQ1ZA3TJ+1', 'SQ2ZA4T': 'SQ2ZA2TJ',
        'SQ3.2ZA3T': 'SQ3.2ZA3TJ', 'SQ4ZA3T': 'SQ4ZA3TJ', 'SQ4ZA4T': 'SQ4ZA3TJ', 'SQ5ZA3T': 'SQ5ZA3TJ', 'SQ5ZA4T': 'SQ5ZA3TJ',
        'SQ6.3ZA4T': 'SQ6.3ZA4TJ', 'SQ6.3ZA5T': 'SQ6.3ZA4TJ', 'SQ8ZA4T': 'SQ8ZA6TJ', 'SQ8ZA4TJ': 'SQ8ZA6TJ', 'SQ8ZA5TJ': 'SQ8ZA6TJ',
        'SQ10ZA5T': 'SQ10ZA6TJ', 'SQ10ZA5TJ': 'SQ10ZA6TJ', 'SQ12ZA4T': 'SQ12ZA6T', 'SQ12ZA4TJ': 'SQ12ZA6T', 'SQ12ZA5T': 'SQ12ZA6T',
        'SQ12ZA5TJ': 'SQ12ZA6T', 'SQZ350T': 'SQZ330T', 'SQ3.2SA3T': 'SQ3.2SA4T', 'SQ5SA3T': 'SQ5SA4T', 'SQ6.3SA3T': 'SQ6.3SA4T',
        'SQ8SA4T': 'SQ8SA5T', 'SQ10SA5T': 'SQ10SA4T', 'SQ12SA5T': 'SQ12SA4T',
    }
    HOLD = set()
# Wording kept from the brochure where the drawing only says 360°.
FOOTER = json.load(open(os.path.join(DIR, 'footer.json')))
BROCHURE = {r['model']: r for r in json.load(open(os.path.join(DIR, '..', 'brochure-specs', 'brochure.json')))}


def spec_rows(row):
    rows = specs.specs(dict(row, kind='main'))
    out = []
    for label, value in rows:
        out.append({'label': label, 'value': value})
        if label == 'Hydraulic System Pressure' and row.get('tank'):
            out.append({'label': 'Oil Tank Capacity', 'value': f"{row['tank']} L"})
    if row.get('space'):
        out.insert(len(out) - 1, {'label': 'Installation Space', 'value': f"{row['space']} mm"})
    # T marks the marine version of a truck crane model; J adds a winch (user, 2026-10-09).
    if re.search(r'TJ(\+1)?$', row['model']):
        out.append({'label': 'Winch', 'value': 'Included'})
    return out


def render(model, kind, pdf):
    """Crop the drawing above its footer (address, phone and e-mail) and save it as webp.

    footer.json holds where the footer starts on each page (share of the page height, from OCR). A footer in the
    top half means the drawing lies sideways and runs off its page (SQ12ZA5 load chart), so it is left out.
    """
    top = FOOTER.get(os.path.splitext(os.path.basename(pdf))[0], 0.88)
    if top < 0.5:
        return None
    dest = os.path.join(DIR, 'media', f'{model}-{kind}.webp'.replace('+', 'plus'))
    if not os.path.exists(dest):
        png = dest + '.png'
        subprocess.run(['sips', '-s', 'format', 'png', '-Z', '2400', pdf, '--out', png], check=True, capture_output=True)
        subprocess.run(['magick', png, '-background', 'white', '-flatten', '-gravity', 'north', '-crop', f'100%x{(top - 0.004) * 100:.1f}%+0+0', '+repage',
                        '-trim', '-bordercolor', 'white', '-border', '24', '-resize', '1600x', '-quality', '82', dest], check=True, capture_output=True)
        os.remove(png)
    return os.path.basename(dest)


def pdfs(model):
    found = {}
    for root, _, files in os.walk(SRC):
        for f in files:
            m = re.fullmatch(re.escape(model) + r'(起升曲线图|外形尺寸及技术参数图|外形尺寸及参数图|外形尺寸图)\.pdf', f)
            if m:
                found['chart' if m.group(1) == '起升曲线图' else 'outline'] = os.path.join(root, f)
    return found


def main():
    os.makedirs(os.path.join(DIR, 'media'), exist_ok=True)
    rows = {r['model']: r for r in json.load(open(os.path.join(DIR, DATA)))}
    titles = {}
    plan = {'existing': [], 'new': []}
    for model, slug in EXISTING.items():
        row = dict(rows[model])
        brochure_rotation = BROCHURE.get(model, {}).get('rotation', '')
        if brochure_rotation.startswith(row['rotation']):
            row['rotation'] = brochure_rotation
        plan['existing'].append({'model': model, 'slug': slug, 'specs': spec_rows(row), 'drop_alias': DROP_ALIASES.get(model, '')})
        titles.setdefault((specs.capacity_tons(row), row['boom']), []).append(model)
    for model, sibling in NEW.items():
        row = rows[model]
        noun, category, boom, adv = TELESCOPIC if row['boom'] == 'telescopic' else KNUCKLE
        tons = specs.capacity_tons(row)
        same_tonnage = bool(titles.get((tons, row['boom']))) or sum(1 for m in NEW if specs.capacity_tons(rows[m]) == tons and rows[m]['boom'] == row['boom']) > 1
        title = f'{tons:g} Ton {noun}' + (f' {model}' if same_tonnage else '')
        files = pdfs(model)
        drawings = [{'file': f, 'kind': kind} for kind in ('outline', 'chart') if kind in files and (f := render(model, kind, files[kind]))]
        plan['new'].append({
            'model': model, 'title': title, 'category': category, 'boom_type': boom, 'applications': APPS,
            'capacity': specs.capacity_label(row), 'capacity_value': tons, 'specs': spec_rows(row),
            'features': [{'title': t, 'text': x} for t, x in ADV[adv]],
            'short_description': specs.short_description(dict(row, kind='main'), noun.lower()),
            'seo_title': f'China {title} - BOB-LIFT', 'sibling': EXISTING[sibling], 'drawings': drawings,
            'source': f'BOB-LIFT technical drawing {model} (technical department, 2026-10-09)',
        })
    json.dump(plan, open(os.path.join(DIR, PLAN), 'w'), ensure_ascii=False, indent=1)
    for p in plan['new']:
        print(f"{p['model']:<10} {p['title']:<60} {len(p['drawings'])} drawings")
    print('existing', len(plan['existing']), 'new', len(plan['new']), 'held', sorted(HOLD))


if __name__ == '__main__':
    main()

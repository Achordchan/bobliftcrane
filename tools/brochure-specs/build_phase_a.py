"""Build phase-a.json: update existing English products to the brochure and list products to remove.

Inputs: brochure.json (from the un-outlined brochures) and a site export with id, slug, model, title.
Products are keyed by slug so the same file works on production, where post IDs may differ.
"""
import json, re, sys
import specs

B = {r['model']: r for r in json.load(open('brochure.json'))}
site = json.load(open(sys.argv[1]))
slugs = {r['ID']: r['post_name'] for r in json.load(open(sys.argv[2]))}

# Site model -> brochure model where the names differ (same capacity and boom type).
RENAME = {
    'SQ20ZA4': 'SQZ430', 'SQ25ZA8T': 'SQZ500T', 'SQ20ZA7TJ+1': 'SQZ430T', 'SQ16ZA5T': 'SQZ330T',
    'SQ25ZA6': 'SQZ500', 'SQ16ZA4': 'SQZ330', 'SQ12ZA5': 'SQ12ZA6', 'SQ10Z': 'SQ10ZA6',
    'SQ6.3ZA3': 'SQ6.3ZA5', 'SQ08ZA3': 'SQ08ZA3+1', 'SQ10ZA5TJ': 'SQ10ZA6TJ', 'SQ8ZA5TJ': 'SQ8ZA6TJ',
    'SQ8ZA5': 'SQ8ZA6', 'SQ3.2Z': 'SQ3.2ZA3', 'SQ12SU4': 'SQ12SA4', 'SQ10SA5': 'SQ10SA4',
    'SQ3.2SA3': 'SQ3.2SA4', 'SQ8SA4T': 'SQ8SA5T',
}
# Products without a model on the site that the brochure covers.
BY_SLUG = {'knuckle-spider-crane': 'SQ4ZG3T', 'telescopic-spider-crane': 'BLS3.0T'}
# Products the brochure does not have.
REMOVE = {'25-ton-truck-crane', 'truck-tail-lift', '800-kg-pickup-crane-without-outriggers'}
REMOVE_MODELS = {'CC165'}

updates, remove, untouched = [], [], []
for p in site:
    slug = slugs[p['id']]
    model = BY_SLUG.get(slug) or (p['model'] if p['model'] in B else RENAME.get(p['model']))
    if slug in REMOVE or p['model'] in REMOVE_MODELS:
        remove.append({'slug': slug, 'title': p['title']})
        continue
    if not model:
        untouched.append(p['title'])
        continue
    row = B[model]
    title = p['title']
    m = re.match(r'^([\d.]+)\s*Ton\b', title)
    if m and float(m.group(1)) != specs.capacity_tons(row):
        title = f"{specs.capacity_tons(row):g} Ton" + title[m.end():]
    updates.append({
        'slug': slug, 'old_title': p['title'], 'title': title, 'old_model': p['model'], 'model': model,
        'capacity': specs.capacity_label(row), 'capacity_value': specs.capacity_tons(row),
        'specs': [{'label': l, 'value': v} for l, v in specs.specs(row)],
        'source': f"{row['book']} brochure, PDF page {row['pdf_page']}",
    })
json.dump({'updates': updates, 'remove': remove}, open('phase-a.json', 'w'), ensure_ascii=False, indent=1)
print(f"updates {len(updates)}, remove {len(remove)}, untouched {len(untouched)}: {untouched}")
for u in updates:
    if u['title'] != u['old_title'] or u['model'] != u['old_model']:
        print(f"  {u['old_model'] or '-':<13} -> {u['model']:<11} {u['old_title']}{'  =>  ' + u['title'] if u['title'] != u['old_title'] else ''}")
print('remove:', [r['title'] for r in remove])

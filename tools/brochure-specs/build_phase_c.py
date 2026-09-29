"""Build phase-c.json: brochure drawings, photos, features and source for every English product
that has a brochure model (phase A updates + phase B new products).

- drawing: drawings/<model>.webp (dimension drawing and load chart, cropped from the brochure)
- photos: photos/<name>.webp, the brochure's photos of that model (or of its series)
- features: the brochure's advantages list for the product's series
- borrow_photo_from: for new models the brochure has no photo of, a sibling model's product (by slug)
"""
import json, os
import specs
from specs import ADV

B = {r['model']: r for r in json.load(open('brochure.json'))}
a = json.load(open('phase-a.json'))['updates']
b = json.load(open('phase-b.json'))['products']

def slug(title):
    import re
    return re.sub(r'-+', '-', re.sub(r'[^a-z0-9]+', '-', title.lower())).strip('-')

products = [{'slug': u['slug'], 'model': u['model'], 'category': u.get('category', ''), 'aliases': [u['old_model']] if u['old_model'] and u['old_model'] != u['model'] else []} for u in a] + [{'slug': slug(p['title']), 'model': p['model'], 'new': True, 'has_image': bool(p['image']), 'category': p['category']} for p in b]
by_model = {p['model']: p['slug'] for p in products}

# Factory photos (photographer's archive) for models the brochure has no photo of; they lead the gallery.
FACTORY = {m: sorted(f'factory-photos/{m}/{f}' for f in os.listdir(f'factory-photos/{m}')) for m in os.listdir('factory-photos') if not m.startswith('.')}
# Workshop photos of existing products: appended to the gallery, the cut-out main image stays.
GALLERY = {m: sorted(f'factory-gallery/{m}/{f}' for f in os.listdir(f'factory-gallery/{m}')) for m in os.listdir('factory-gallery') if not m.startswith('.')}
PHOTOS = {m: [f'photos/{m}.webp'] for m in ['SQ4ZA3TJ', 'SQ6.3ZA4TJ', 'SQ8ZA6TJ', 'SQ10ZA6TJ', 'SQ12ZA6T', 'SQZ330T', 'SQ2SA3T', 'SQ3.2SA4T', 'SQ16SA5T', 'SQ4ZG3T', 'BLS3.0T']}
for m in ('CC85', 'CC105', 'CC125', 'CC145'):
    PHOTOS[m] = ['photos/flyjib-1.webp', 'photos/flyjib-2.webp']
# Main image replaced by a brochure photo (CC85's old "transparent" PNG has an opaque background).
MAIN = {'CC85': 'photos/flyjib-2.webp'}
# New model <- sibling whose existing product photo stands in until real photos arrive.
BORROW = {
    'SQ3.2ZA4': 'SQ3.2ZA3', 'SQ4ZA3': 'SQ4ZA4', 'BLZ90K5': 'SQ4ZA4', 'SQ5ZA3': 'SQ5ZA4', 'BLZ129K4': 'SQ6.3ZA5',
    'SQZ560': 'SQZ500', 'SQZ600': 'SQZ500', 'SQZ700': 'SQZ500', 'SQZ800': 'SQZ500',
    'SQ5SA3': 'SQ5SA4', 'SQ14SA5': 'SQ16SA5', 'SL70': 'ZM65', 'SL150-2A': 'ZM65', 'ZT120Z-2A': 'ZM75', 'ZT160Z-2A': 'ZM75',
    'SQ08ZA3TJ+1': 'SQ1ZA3TJ+1', 'BLZ90K5TJ': 'SQ4ZA3TJ', 'SQ4SA3T': 'SQ5SA4T',
}
SERIES = {  # model family -> advantages list
    'marine-knuckle': lambda m, r: r['book'] == 'marine' and 'SA' not in m,
    'marine-telescopic': lambda m, r: r['book'] == 'marine' and 'SA' in m,
    'truck-telescopic': lambda m, r: r['book'] == 'truck' and r['kind'] == 'main' and ('SA' in m) and r['pdf_page'] in (12, 13, 14),
    'truck-knuckle': lambda m, r: r['book'] == 'truck' and r['kind'] == 'main' and r['pdf_page'] in (6, 7, 8, 9, 10, 11),
}
ADV_KEY = {'marine-knuckle': 'marine', 'marine-telescopic': 'marine-telescopic', 'truck-telescopic': 'truck-telescopic', 'truck-knuckle': 'truck-knuckle'}

out = []
for p in products:
    m = p['model']; r = B[m]
    noun = specs.NOUNS.get(p['category']) or {'SQ5SA2F': 'tow truck crane', 'F100Z-2E': 'garbage crane'}.get(m, 'crane')
    item = {'slug': p['slug'], 'model': m, 'drawing': f'drawings/{m}.webp',
            'photos': FACTORY.get(m, []) + GALLERY.get(m, []) + PHOTOS.get(m, []), 'own_photos_first': m in FACTORY,
            'short_description': specs.short_description(r, noun) + (f" Previously listed as {', '.join(p['aliases'])}." if p.get('aliases') else ''),
            'model_aliases': p.get('aliases', []),
            'main_photo': MAIN.get(m), 'source': f"BOB-LIFT {'Truck Mounted Crane' if r['book'] == 'truck' else 'Marine Crane'} Product Manual, PDF page {r['pdf_page']}"}
    assert os.path.exists(item['drawing']), item['drawing']
    for key, test in SERIES.items():
        if test(m, r):
            item['features'] = [{'title': t, 'text': x} for t, x in ADV[ADV_KEY[key]]]
            break
    if p.get('new') and not p.get('has_image') and not item['photos']:
        item['borrow_photo_from'] = by_model[BORROW[m]]
    out.append(item)
json.dump({'products': out}, open('phase-c.json', 'w'), ensure_ascii=False, indent=1)
print(len(out), 'products;', sum('features' in o for o in out), 'with brochure features;', sum(bool(o['photos']) for o in out), 'with brochure photos;', sum('borrow_photo_from' in o for o in out), 'borrowing a photo')
for o in out:
    if 'borrow_photo_from' in o: print('  ', o['model'], '<-', o['borrow_photo_from'])

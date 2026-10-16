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
# Main images that were photos saved as "transparent" PNGs: CC85 gets the brochure photo; CC105 and CC125
# get their own photo with the transparent padding trimmed off.
MAIN = {'CC85': 'photos/flyjib-2.webp', 'CC105': 'photos/CC105-main.webp', 'CC125': 'photos/CC125-main.webp'}
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
# Short card copy and a detail photo per advantage. Photos are only set where the picture plainly shows that part;
# the WIKA limiter screen was shot on a telescopic crane, so it is not reused for knuckle or marine cranes.
KNUCKLE_STRUCTURE = ('Welded high-strength plate with a hexagonal boom section.', 'features/knuckle-structure.webp')
FEATURE_CARDS = {
    'truck-knuckle': {
        'High-strength welded structure': KNUCKLE_STRUCTURE,
        'Dedicated torque limiter': ('Load moment limiter on every crane, safety certified.', None),
        'Box-type outriggers': ('Box outriggers above 6.3 t protect the cylinders.', 'features/knuckle-box-outriggers.webp'),
        'Optional attachments': ('Winch, auger, basket or grapple on request.', 'features/winch.webp'),
        'Operation modes': ('Manual levers, aerial seat or wireless remote.', 'features/knuckle-levers.webp'),
        'Dual four-bar linkage': ('Reverse-angle reach for tight jobs.', 'features/knuckle-four-bar.webp'),
        'Large-span outriggers': ('Wide fixed outriggers, manual flip optional.', None),
    },
    'truck-telescopic': {
        'High-strength steel structure': ('High-strength plate: stronger and lighter.', None),
        'Octagonal boom': ('Octagonal boom above 8 t: light and strong.', 'features/telescopic-octagonal-boom.webp'),
        'Dedicated torque limiter': ('Load moment limiter with live display, safety certified.', 'features/telescopic-torque-limiter.webp'),
        'Overwinding protection': ('Anti-overwind and anti-overlower cut-outs.', None),
        'Optional attachments': ('Winch, auger, basket or grapple on request.', 'features/winch.webp'),
        'Operation modes': ('Control panel, top seat or wireless remote.', 'features/telescopic-control-panel.webp'),
    },
    'marine': {
        'High-strength steel structure': ('Compact, light and strong steel structure.', 'features/marine-structure.webp'),
        'Dedicated torque limiter': ('Load moment limiter, safety certified.', None),
        'Custom installation': ('Pedestal mounting tailored to your deck.', 'features/marine-installation.webp'),
        'Optional attachments': ('Winch, drill pipe, basket or grapple.', 'features/marine-winch.webp'),
        'Operation modes': ('Manual levers, overhead seat or wireless remote.', 'features/marine-controls.webp'),
        'Dual four-bar linkage': ('Reverse-angle reach for flexible work.', 'features/marine-four-bar.webp'),
        'Marine anti-rust treatment': ('Multi-layer anti-rust process for sea duty.', None),
    },
}
FEATURE_CARDS['marine-telescopic'] = FEATURE_CARDS['marine']

# The brochure opens each series with one photo beside its advantages list; the product page pairs them the same way.
SERIES_NAME = {'marine-knuckle': 'Knuckle Boom Marine Crane', 'marine-telescopic': 'Telescopic Boom Marine Crane',
               'truck-telescopic': 'Telescopic Boom Truck Mounted Crane', 'truck-knuckle': 'Knuckle Boom Truck Mounted Crane'}
SERIES_PHOTO = {'marine-knuckle': 'series/marine.webp', 'marine-telescopic': 'series/marine-telescopic.webp',
                'truck-telescopic': 'series/truck-telescopic.webp', 'truck-knuckle': 'series/truck-knuckle.webp'}

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
            extra = FEATURE_CARDS[ADV_KEY[key]]
            # text: one short line for the page; detail: the brochure wording, kept for reference.
            item['features'] = [{'title': t, 'text': extra[t][0], 'detail': x, **({'photo': extra[t][1]} if extra[t][1] else {})}
                                for t, x in ADV[ADV_KEY[key]]]
            for f in item['features']:
                assert 'photo' not in f or os.path.exists(f['photo']), f['photo']
            item['features_series'] = SERIES_NAME[key]
            item['features_photo'] = SERIES_PHOTO[key]
            assert os.path.exists(item['features_photo']), item['features_photo']
            break
    if p.get('new') and not p.get('has_image') and not item['photos']:
        item['borrow_photo_from'] = by_model[BORROW[m]]
    out.append(item)
json.dump({'products': out}, open('phase-c.json', 'w'), ensure_ascii=False, indent=1)
print(len(out), 'products;', sum('features' in o for o in out), 'with brochure features;', sum(bool(o['photos']) for o in out), 'with brochure photos;', sum('borrow_photo_from' in o for o in out), 'borrowing a photo')
for o in out:
    if 'borrow_photo_from' in o: print('  ', o['model'], '<-', o['borrow_photo_from'])

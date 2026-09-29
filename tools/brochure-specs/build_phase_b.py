"""Build phase-b.json: new English products for brochure models the site does not have yet.

Features are the brochure's own "Product technical advantages" text for each series.
Images are in images/: the model's dimension drawing, or the brochure photo where one exists.
"""
import json
import specs

B = {r['model']: r for r in json.load(open('brochure.json'))}

ADV = {
    'truck-knuckle': [
        ('High-strength welded structure', 'All the key structural components are welded with high-strength plates. The hexagonal boom section ensures excellent alignment, more sturdy quality and greater durability.'),
        ('Dedicated torque limiter', 'Our products are equipped with a dedicated torque limiter, offering a high level of safety. The cranes are certified for various safety standards, making lifting operations more precise, safe, and reliable.'),
        ('Box-type outriggers', 'Cranes with a capacity above 6.3 tons feature box-type outriggers, offering better protection for the hydraulic cylinders.'),
        ('Optional attachments', 'Multiple optional configurations, such as winches, augers, baskets, and grapples, allow for personalized customization.'),
        ('Operation modes', 'Various operation modes are available, including aerial seats and wireless remote control systems, making work more convenient.'),
        ('Dual four-bar linkage', 'The dual four-bar linkage mechanism allows for reverse angles, offering wide application versatility and flexible operation.'),
        ('Large-span outriggers', 'Standard large-span fixed outriggers with optional manual flip provide stability and ease during operations.'),
    ],
    'truck-telescopic': [
        ('High-strength steel structure', 'The key structural components are welded with high-strength steel plates, offering high strength and lighter self-weight.'),
        ('Octagonal boom', 'Cranes with a capacity above 8 tons use octagonal booms, which possess light self-weight and a strong load-bearing capacity.'),
        ('Dedicated torque limiter', 'Our products are equipped with a dedicated torque limiter, ensuring a high safety level. The cranes are certified for various safety standards, ensuring lifting operations are precise, safe, and reliable.'),
        ('Overwinding protection', 'The features of anti-overwinding and anti-overlowering protection systems significantly enhance lifting safety and extend crane service life.'),
        ('Optional attachments', 'Multiple optional configurations, such as winches, augers, baskets, and grapples, can satisfy personalized customization needs.'),
        ('Operation modes', 'Various optional operation modes, such as top seats and wireless remote control systems, make operations more convenient.'),
    ],
    'marine': [
        ('High-strength steel structure', 'The main structure utilizes high-strength steel plates, resulting in a compact, lighter, stronger, and more durable design.'),
        ('Dedicated torque limiter', 'Equipped with a dedicated torque limiter for elevated safety standards; certified by multiple safety authorities to ensure precise, secure, and reliable lifting operations.'),
        ('Custom installation', 'Customizable installation methods tailored to specific operational scenarios.'),
        ('Optional attachments', 'Multiple optional configurations, including winches, drill pipes, aerial baskets, and grapples, accommodate personalized customization needs.'),
        ('Operation modes', 'Multiple operation modes available, including overhead seat and wireless remote control systems, for enhanced operational convenience.'),
        ('Dual four-bar linkage', 'Dual four-bar linkage boom mechanism enables reverse angle operation, offering broad applicability and flexible performance.'),
        ('Marine anti-rust treatment', 'Multiple anti-rust processes specifically designed for marine environments ensure excellent corrosion resistance under harsh marine conditions.'),
    ],
}
ADV['marine-telescopic'] = [a for a in ADV['marine'] if a[0] != 'Dual four-bar linkage']

KB_TRUCK = ('Knuckle Boom Truck Mounted Crane', 'knuckle-boom-truck-mounted-cranes', 'knuckle-boom', 'construction,logistics,municipal-engineering', 'truck-knuckle')
TB_TRUCK = ('Telescopic Boom Truck Mounted Crane', 'telescopic-boom-truck-mounted-cranes', 'telescopic-boom', 'construction,logistics,municipal-engineering', 'truck-telescopic')
KB_MARINE = ('Knuckle Boom Marine Crane', 'knuckle-boom-marine-cranes', 'knuckle-boom', 'marine,logistics', 'marine')
TB_MARINE = ('Telescopic Boom Marine Crane', 'telescopic-boom-marine-cranes', 'telescopic-boom', 'marine,logistics', 'marine-telescopic')
TIMBER = ('Timber Crane', 'timber-cranes', 'custom-solution', 'agriculture,logistics', None)
FLY = ('Forklift Fly Jib Crane', 'forklift-fly-jib-cranes', 'fly-jib', 'logistics,construction', None)

# model: (series, add model to title because the tonnage title already exists, image)
NEW = {
    'SQ3.2ZA4': (KB_TRUCK, True), 'SQ4ZA3': (KB_TRUCK, True), 'BLZ90K5': (KB_TRUCK, False), 'SQ5ZA3': (KB_TRUCK, True),
    'BLZ129K4': (KB_TRUCK, True), 'SQZ560': (KB_TRUCK, False), 'SQZ600': (KB_TRUCK, False), 'SQZ700': (KB_TRUCK, False),
    'SQZ800': (KB_TRUCK, False), 'SQ5SA3': (TB_TRUCK, True), 'SQ14SA5': (TB_TRUCK, False),
    'SL70': (TIMBER, None), 'SL150-2A': (TIMBER, None), 'ZT120Z-2A': (TIMBER, None), 'ZT160Z-2A': (TIMBER, None),
    'CC145': (FLY, False),
    'SQ5SA2F': (('Tow Truck Crane', 'other-cranes', 'telescopic-boom', 'logistics', None), False),
    'F100Z-2E': (('Garbage Crane', 'other-cranes', 'knuckle-boom', 'municipal-engineering', None), False),
    'SQ08ZA3TJ+1': (KB_MARINE, False), 'BLZ90K5TJ': (KB_MARINE, False), 'SQ4SA3T': (TB_MARINE, False),
}
PHOTOS = {'SQ5SA2F', 'F100Z-2E'}

products = []
for model, ((noun, category, boom, apps, adv), with_model) in NEW.items():
    row = B[model]
    tons = specs.capacity_tons(row)
    if with_model is None:
        title = f'{model} {noun}'
    else:
        title = f'{tons:g} Ton {noun}' + (f' {model}' if with_model else '')
    s = dict(specs.specs(row))
    parts = [f"a maximum lifting capacity of {s['Max Lifting Capacity']}"]
    if 'Max Lifting Moment' in s:
        parts.append(f"a maximum lifting moment of {s['Max Lifting Moment']}")
    if 'Self Weight' in s:
        parts.append(f"a self weight of {s['Self Weight']}")
    short = f"The BOB-LIFT {model} {noun.lower()} has " + ', '.join(parts[:-1]) + (' and ' if len(parts) > 1 else '') + parts[-1] + '.'
    image = f"{model}-photo.webp" if model in PHOTOS else f"{model}-drawing.webp"
    products.append({
        'model': model, 'title': title, 'category': category, 'boom_type': boom, 'applications': apps,
        'capacity': specs.capacity_label(row), 'capacity_value': tons,
        'specs': [{'label': l, 'value': v} for l, v in specs.specs(row)],
        'features': [{'title': t, 'text': x} for t, x in ADV.get(adv, [])] if adv else [],
        'short_description': short, 'seo_title': f'China {title} - BOB-LIFT', 'image': image,
        'source': f"{row['book']} brochure, PDF page {row['pdf_page']}",
    })
json.dump({'products': products}, open('phase-b.json', 'w'), ensure_ascii=False, indent=1)
for p in products:
    print(f"{p['model']:<12} {p['title']:<58} {p['category']:<36} {p['image']}")
    print('   ', p['short_description'])

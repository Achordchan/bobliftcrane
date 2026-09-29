"""Shared formatting: turn a brochure row (brochure.json) into the product's spec list and capacity."""
import re


def fmt_num(value):
    return f"{float(value):g}" if re.fullmatch(r'[\d.]+', value) else value


def rotation(value):
    return value.replace('°Continuous', '° continuous')


def capacity_tons(row):
    return float(row['cap']) / 1000


def capacity_label(row):
    return f"{capacity_tons(row):g} ton"


def specs(row):
    kg = lambda v: f"{int(v)} kg"
    if row['kind'] == 'main':
        return [
            ('Max Lifting Capacity', kg(row['cap'])),
            ('Max Lifting Moment', f"{fmt_num(row['moment'])} t·m"),
            ('Recommended Power', f"{fmt_num(row['power'])} kW"),
            ('Hydraulic System Flow', f"{row['flow']} L/min"),
            ('Hydraulic System Pressure', f"{fmt_num(row['pressure'])} MPa"),
            ('Self Weight', kg(row['weight'])),
            ('Rotation Angle', rotation(row['rotation'])),
        ]
    if row['kind'] == 'main-space':
        return [
            ('Max Lifting Capacity', kg(row['cap'])),
            ('Max Lifting Moment', f"{fmt_num(row['moment'])} t·m"),
            ('Recommended Power', f"{fmt_num(row['power'])} kW"),
            ('Hydraulic System Flow', f"{row['flow']} L/min"),
            ('Hydraulic System Pressure', f"{fmt_num(row['pressure'])} MPa"),
            ('Installation Space', f"{row['space']} mm"),
            ('Rotation Angle', rotation(row['rotation'])),
        ]
    if row['kind'] == 'spider':
        dims = ' × '.join(row['dims'].split('X'))
        return [
            ('Max Lifting Capacity', kg(row['cap'])),
            ('Max Working Radius', f"{fmt_num(row['radius'])} m"),
            ('Max Lifting Height (from ground)', f"{fmt_num(row['height'])} m"),
            ('Lifting Angle', row['angle'].replace('-', '–')),
            ('Dimensions (L × W × H)', f"{dims} mm"),
            ('Vehicle Weight', kg(row['weight'])),
            ('Rotation Angle', rotation(row['rotation'])),
        ]
    if row['kind'] == 'flyjib':
        height = re.sub(r'(\d+) \(', r'\1 mm (', row['lift_height_mm'])
        return [
            ('Max Lifting Capacity', kg(row['cap'])),
            ('Max Lifting Moment', f"{row['moment_knm']} kN·m"),
            ('Max Working Range', f"{row['working_range_mm']} mm"),
            ('Max Lifting Height', height),
            ('Rated Working Pressure', f"{row['pressure']} MPa"),
            ('Self Weight', kg(row['weight'])),
            ('Matching Forklifts', row['forklift']),
        ]
    raise ValueError(row['kind'])


# The brochure's "Product technical advantages" for each series (English text as printed).
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


NOUNS = {
    'knuckle-boom-truck-mounted-cranes': 'knuckle boom truck mounted crane',
    'telescopic-boom-truck-mounted-cranes': 'telescopic boom truck mounted crane',
    'pickup-cranes': 'pickup crane',
    'knuckle-boom-marine-cranes': 'knuckle boom marine crane',
    'telescopic-boom-marine-cranes': 'telescopic boom marine crane',
    'forklift-fly-jib-cranes': 'forklift fly jib crane',
    'timber-cranes': 'timber crane',
    'spider-cranes': 'spider crane',
}


def short_description(row, noun):
    """One factual sentence built only from the brochure values."""
    s = dict(specs(row))
    parts = [f"a maximum lifting capacity of {s['Max Lifting Capacity']}"]
    for label, text in (('Max Lifting Moment', 'a maximum lifting moment of {}'), ('Max Working Radius', 'a maximum working radius of {}'),
                        ('Max Working Range', 'a maximum working range of {}'), ('Self Weight', 'a self weight of {}')):
        if label in s:
            parts.append(text.format(s[label]))
    return f"The BOB-LIFT {row['model']} {noun} has " + ', '.join(parts[:-1]) + ' and ' + parts[-1] + '.'

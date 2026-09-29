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

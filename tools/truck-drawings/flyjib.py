"""Forklift fly jib cranes from the technical department's load charts (~/Desktop/工作/叉车飞臂吊, 2026-10-09).

The charts give the maximum capacity, the working radius and the boom; brochure.json now carries those
capacities and radii (CC85 2.3 t, CC105 2.5 t / 6.6 m, CC145 4.9 t), the other brochure values stay.
Writes flyjib-plan.json for apply-flyjib.php and renders each load chart to media/.

    python3 tools/truck-drawings/flyjib.py
"""
import json, os, sys

import build  # render() and the drawings' footer positions
sys.path.insert(0, os.path.join(build.DIR, '..', 'brochure-specs'))
import specs  # noqa: E402

SRC = os.path.expanduser('~/Desktop/工作/叉车飞臂吊')
SLUGS = {'CC85': '2-3-ton-forklift-fly-jib-crane', 'CC105': '3-ton-forklift-fly-jib-crane',
         'CC125': '4-ton-forklift-fly-jib-crane', 'CC145': '5-ton-forklift-fly-jib-crane'}
# Boom as printed on each chart: main boom sections × length, hydraulic and manual extensions.
BOOM = {'CC85': (4, 2), 'CC105': (4, 2), 'CC125': (5, 3), 'CC145': (6, 4)}

rows = {r['model']: r for r in json.load(open(os.path.join(build.DIR, '..', 'brochure-specs', 'brochure.json')))}
plan = []
for model, slug in SLUGS.items():
    row = rows[model]
    sections, hydraulic = BOOM[model]
    spec_rows = [{'label': l, 'value': v} for l, v in specs.specs(row)]
    spec_rows.insert(3, {'label': 'Boom Sections', 'value': f'{sections} × 1.9 m ({hydraulic} hydraulic + 1 manual extension)'})
    tons = specs.capacity_tons(row)
    noun = 'Forklift Fly Jib Crane'
    plan.append({
        'model': model, 'slug': slug, 'title': f'{tons:g} Ton {noun}', 'capacity': specs.capacity_label(row),
        'capacity_value': tons, 'specs': spec_rows, 'short_description': specs.short_description(row, noun.lower()),
        'chart': build.render(model, 'chart', os.path.join(SRC, model + '.pdf')),
    })
json.dump(plan, open(os.path.join(build.DIR, 'flyjib-plan.json'), 'w'), ensure_ascii=False, indent=1)
for p in plan:
    print(p['model'], p['title'], p['chart'])

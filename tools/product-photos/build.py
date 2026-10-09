"""Pick factory photos from the read-only NAS for products with thin galleries and write them to media/ as webp
(1800 px long edge, like tools/brochure-specs/factory-photos), plus plan.json for apply.php.

    python3 tools/product-photos/build.py      # converts missing files only
Never writes to /Volumes. Picks are by position in the folder's sorted file list; photos with people close up
were left out (2026-10-09).
"""
import json, os, re, subprocess

DIR = os.path.dirname(os.path.abspath(__file__))
ZHAO = '/Volumes/团队文件-运营部/赵振生拍摄素材/按照产品分类/随车吊/'
NIKI = '/Volumes/团队文件-通用空间/Niki/产品图片/'

# slug, model, source folder, picks, own_photos_first (replace a borrowed reference photo and become the main image)
PRODUCTS = [
    ('4-ton-knuckle-boom-truck-mounted-crane-sq4za3', 'SQ4ZA3', ZHAO + 'SQ4ZA3', [1, 6, 7, 13, 20, 25], True),
    ('0-8-ton-knuckle-boom-marine-crane', 'SQ08ZA3TJ+1', ZHAO + 'SQ08ZA3T', [4, 5, 9, 21, 26, 28], True),
    # Orange and blue sets are printed SQ5ZA3; the red sets have the same boom tip (1 main + 2 hydraulic booms).
    ('5-ton-knuckle-boom-truck-mounted-crane-sq5za3', 'SQ5ZA3', [NIKI + '折叠臂随车吊/SQ5ZA3_5Z红色展品', NIKI + '折叠臂随车吊/SQ5ZA3_5Z橙色', NIKI + '折叠臂随车吊/SQ5ZA3_5Z蓝色', NIKI + '折叠臂随车吊/SQ5ZA3_5Z红色1'], [(0, 1), (0, 5), (1, 2), (1, 7), (2, 1), (2, 4), (3, 1)], True),
    # New products from the technical drawings (tools/truck-drawings), 2026-10-09.
    ('8-ton-knuckle-boom-truck-mounted-crane-sq8za4', 'SQ8ZA4', ZHAO + 'SQ8ZA4', [2, 3, 9, 10, 11, 15], True),
    ('8-ton-knuckle-boom-truck-mounted-crane-sq8za5', 'SQ8ZA5', ZHAO + 'SQ8ZA5(黑色）', [25, 26, 13, 15, 27], True),
    ('10-ton-knuckle-boom-truck-mounted-crane-sq10za4', 'SQ10ZA4', ZHAO + 'SQ10ZA4', [9, 12, 18, 10, 22], True),
    ('10-ton-knuckle-boom-truck-mounted-crane-sq10za5', 'SQ10ZA5', ZHAO + 'SQ10ZA5', [1, 2, 7, 8, 11], True),
    ('4-6-ton-knuckle-boom-truck-mounted-crane-blz90k4', 'BLZ90K4', ZHAO + 'BLZ90K4', [5, 6, 7, 10, 11, 14], True),
    ('16-ton-knuckle-boom-truck-mounted-crane-sqz350', 'SQZ350', ZHAO + 'SQZ350', [8, 17, 18, 20, 26, 30], True),
    ('6-3-ton-knuckle-boom-truck-mounted-crane-sq6-3za3', 'SQ6.3ZA3', ZHAO + 'SQ6.3ZA3', [3, 11, 24, 26, 29], True),
    ('3-2-ton-telescopic-boom-truck-mounted-crane-sq3-2sa3', 'SQ3.2SA3', ZHAO + 'SQ3.2SA3_SQ3. 2SA3', [8, 9, 10, 4, 6], True),
    ('1-ton-knuckle-boom-marine-crane', 'SQ1ZA3TJ+1', NIKI + '折叠臂船吊/1Z船吊', [1, 3, 5, 6, 8], False),
    ('4-ton-knuckle-boom-marine-crane', 'SQ4ZA3TJ', NIKI + '折叠臂船吊/4Z船吊', [1, 5, 6, 9, 14], False),
    ('8-ton-knuckle-boom-marine-crane', 'SQ8ZA6TJ', NIKI + '折叠臂船吊/8Z船吊 红色', [2, 3, 9, 10, 1], False),
    ('20-ton-telescopic-boom-marine-crane', 'SQ20SA5T', NIKI + '伸缩臂船吊/SQ20SA5T_20S船吊红色', [1, 3, 4, 6, 10], False),
    ('telescopic-spider-crane', 'BLS3.0T', NIKI + '蜘蛛吊/3吨红色蜘蛛吊', [1, 2, 6, 8, 12], False),
    ('16-ton-knuckle-boom-truck-mounted-crane', 'SQZ330', NIKI + '折叠臂随车吊/16Z随车吊', [2, 3, 4, 7, 9, 13, 18], False),
]


def main():
    os.makedirs(os.path.join(DIR, 'media'), exist_ok=True)
    # Products already planned keep their picks: the photographer renames and adds files, which would shift positions.
    done = {p['slug']: p for p in json.load(open(os.path.join(DIR, 'plan.json')))['products']} if os.path.exists(os.path.join(DIR, 'plan.json')) else {}
    plan = []
    for slug, model, folder, picks, own in PRODUCTS:
        if slug in done:
            plan.append(done[slug])
            continue
        # One folder with picks [n, ...], or several folders with picks [(folder index, n), ...].
        folders = folder if isinstance(folder, list) else [folder]
        listing = [sorted(f for f in os.listdir(d) if f.lower().endswith(('.jpg', '.jpeg', '.png')) and not f.startswith('.')) for d in folders]
        photos = []
        for n, pick in enumerate(picks, 1):
            k, pick = pick if isinstance(pick, tuple) else (0, pick)
            src = os.path.join(folders[k], listing[k][pick - 1])
            # NAS files carry a '<model>_' prefix since 2026-10-09; drop it so output names stay stable.
            stem = re.sub(r'^(SQ|BLZ|SL|FY|FJZ|BL-)[^_]*_', '', os.path.splitext(listing[k][pick - 1])[0])
            name = f'{model}-{n:02d}-{stem}.webp'.replace('+', 'plus').replace('-已增强-降噪', '').replace(model.replace('+', 'plus') + '-' + f'{n:02d}-' + model + '_', model.replace('+', 'plus') + '-' + f'{n:02d}-')
            dest = os.path.join(DIR, 'media', name)
            if not os.path.exists(dest):
                subprocess.run(['magick', src, '-auto-orient', '-resize', '1800x1800>', '-strip', '-quality', '80', dest], check=True)
            photos.append({'file': name, 'source': src})
        plan.append({'slug': slug, 'model': model, 'own_photos_first': own, 'photos': photos})
        print('ok', model, len(photos))
    with open(os.path.join(DIR, 'plan.json'), 'w') as f:
        json.dump({'products': plan}, f, ensure_ascii=False, indent=1)


if __name__ == '__main__':
    main()

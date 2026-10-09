"""Pick factory photos from the read-only NAS for products with thin galleries and write them to media/ as webp
(1800 px long edge, like tools/brochure-specs/factory-photos), plus plan.json for apply.php.

    python3 tools/product-photos/build.py      # converts missing files only
Never writes to /Volumes. Picks are by position in the folder's sorted file list; photos with people close up
were left out (2026-10-09).
"""
import json, os, subprocess

DIR = os.path.dirname(os.path.abspath(__file__))
ZHAO = '/Volumes/团队文件-运营部/赵振生拍摄素材/按照产品分类/随车吊/'
NIKI = '/Volumes/团队文件-通用空间/Niki/产品图片/'

# slug, model, source folder, picks, own_photos_first (replace a borrowed reference photo and become the main image)
PRODUCTS = [
    ('4-ton-knuckle-boom-truck-mounted-crane-sq4za3', 'SQ4ZA3', ZHAO + 'SQ4ZA3', [1, 6, 7, 13, 20, 25], True),
    ('0-8-ton-knuckle-boom-marine-crane', 'SQ08ZA3TJ+1', ZHAO + 'SQ08ZA3T', [4, 5, 9, 21, 26, 28], True),
    # Orange and blue sets are printed SQ5ZA3; the red sets have the same boom tip (1 main + 2 hydraulic booms).
    ('5-ton-knuckle-boom-truck-mounted-crane-sq5za3', 'SQ5ZA3', [NIKI + '折叠臂随车吊/5Z红色展品', NIKI + '折叠臂随车吊/5Z橙色', NIKI + '折叠臂随车吊/5Z蓝色', NIKI + '折叠臂随车吊/5Z红色1'], [(0, 1), (0, 5), (1, 2), (1, 7), (2, 1), (2, 4), (3, 1)], True),
    ('1-ton-knuckle-boom-marine-crane', 'SQ1ZA3TJ+1', NIKI + '折叠臂船吊/1Z船吊', [1, 3, 5, 6, 8], False),
    ('4-ton-knuckle-boom-marine-crane', 'SQ4ZA3TJ', NIKI + '折叠臂船吊/4Z船吊', [1, 5, 6, 9, 14], False),
    ('8-ton-knuckle-boom-marine-crane', 'SQ8ZA6TJ', NIKI + '折叠臂船吊/8Z船吊 红色', [2, 3, 9, 10, 1], False),
    ('20-ton-telescopic-boom-marine-crane', 'SQ20SA5T', NIKI + '伸缩臂船吊/20S船吊红色', [1, 3, 4, 6, 10], False),
    ('telescopic-spider-crane', 'BLS3.0T', NIKI + '蜘蛛吊/3吨红色蜘蛛吊', [1, 2, 6, 8, 12], False),
    ('16-ton-knuckle-boom-truck-mounted-crane', 'SQZ330', NIKI + '折叠臂随车吊/16Z随车吊', [2, 3, 4, 7, 9, 13, 18], False),
]


def main():
    os.makedirs(os.path.join(DIR, 'media'), exist_ok=True)
    plan = []
    for slug, model, folder, picks, own in PRODUCTS:
        # One folder with picks [n, ...], or several folders with picks [(folder index, n), ...].
        folders = folder if isinstance(folder, list) else [folder]
        listing = [sorted(f for f in os.listdir(d) if f.lower().endswith(('.jpg', '.jpeg', '.png')) and not f.startswith('.')) for d in folders]
        photos = []
        for n, pick in enumerate(picks, 1):
            k, pick = pick if isinstance(pick, tuple) else (0, pick)
            src = os.path.join(folders[k], listing[k][pick - 1])
            name = f'{model}-{n:02d}-{os.path.splitext(listing[k][pick - 1])[0]}.webp'.replace('+', 'plus').replace('-已增强-降噪', '')
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

"""Build the customer feedback import from the shared folder (country / model / photos and videos).

Usage: python build.py <copy of 客户反馈图> [--dry]
Reads the source read-only and writes web-ready media to tools/feedback/media plus plan.json.

Privacy rules (the site shows only country and model, never who the customer is):
- EXCLUDE: files showing a customer's face up close, phone numbers, a distributor's own brand on the crane,
  or a military vessel. Chosen by looking at every file.
- CROP_BOTTOM: phone camera stamps (date, place, sometimes the photographer's name) are cut off.
- Images are re-encoded without EXIF, so GPS and camera data never reach the site.
- Videos lose their audio track: voices can carry names, and the site plays them muted anyway.
"""
import hashlib
import json
import os
import re
import subprocess
import sys

from PIL import Image, ImageOps

HERE = os.path.dirname(os.path.abspath(__file__))
SRC = sys.argv[1]
DRY = '--dry' in sys.argv
OUT = os.path.join(HERE, 'media')

EXCLUDE = {
    '乌兹别克斯坦/SQ10ZA4/SQ10ZA4 (2).jpg',          # group photo with customers
    '乌兹别克斯坦/SQ2ZA2+贝壳斗/SQ2ZA2 (1).jpg',       # phone number on the truck door
    '乌兹别克斯坦/SQ2ZA2+贝壳斗/SQ2ZA2 (2).jpg',       # phone number on the truck door
    '墨西哥/SQ2ZA2/SQ2ZA2 (2).jpg',                  # selfie
    '墨西哥/SQ6.3ZA4/SQ6.3ZA3-VIDEO (2).mp4',        # face close-up
    '希腊/SQ2ZA2T/SQ2ZA2T.jpg',                      # naval vessel
}
# Distributor's own brand (PROLINE / GORILLA) on these cranes.
EXCLUDE |= {f'澳大利亚/SQ6.3ZA4/SQ6.3ZA3 ({i}).{e}' for i in range(1, 16) for e in ('jpg', 'JPG')}
# Malaysia handover: crowds of site staff, faces recognisable. The crane-only shots stay.
EXCLUDE |= {f'马来西亚/SQ20ZA7/SQ20ZA7 ({i}).jpg' for i in (5, 7, 8, 9, 11, 12)}

CROP_BOTTOM = 0.09
CROP = {'俄罗斯/SQ6.3SA3/', '俄罗斯/SQ6.3ZA4T/', '印度/SQ1ZA3T/', '智利/SQ3.2ZA3/SQ3.2ZA2 (6).jpg',
        '秘鲁/SQ2SA3T/2S船吊.png', '西班牙/SQ08ZA3+1T/SQ08ZA3+1T.jpg'}

COUNTRIES = {  # folder -> (en, ja, zh)
    '乌克兰': ('Ukraine', 'ウクライナ', '乌克兰'), '乌兹别克斯坦': ('Uzbekistan', 'ウズベキスタン', '乌兹别克斯坦'),
    '乌拉圭': ('Uruguay', 'ウルグアイ', '乌拉圭'), '俄罗斯': ('Russia', 'ロシア', '俄罗斯'), '刚果': ('Congo', 'コンゴ', '刚果'),
    '加拿大': ('Canada', 'カナダ', '加拿大'), '卡塔尔': ('Qatar', 'カタール', '卡塔尔'), '印尼': ('Indonesia', 'インドネシア', '印度尼西亚'),
    '印度': ('India', 'インド', '印度'), '厄瓜多尔': ('Ecuador', 'エクアドル', '厄瓜多尔'),
    '台湾': ('Taiwan, China', '中国台湾', '中国台湾'), '哈萨克斯坦': ('Kazakhstan', 'カザフスタン', '哈萨克斯坦'),
    '哥伦比亚': ('Colombia', 'コロンビア', '哥伦比亚'), '国内': ('China', '中国', '中国'), '土耳其': ('Türkiye', 'トルコ', '土耳其'),
    '埃及': ('Egypt', 'エジプト', '埃及'), '塔吉克斯坦': ('Tajikistan', 'タジキスタン', '塔吉克斯坦'),
    '塞浦路斯': ('Cyprus', 'キプロス', '塞浦路斯'), '墨西哥': ('Mexico', 'メキシコ', '墨西哥'), '孟加拉': ('Bangladesh', 'バングラデシュ', '孟加拉国'),
    '巴林': ('Bahrain', 'バーレーン', '巴林'), '巴西': ('Brazil', 'ブラジル', '巴西'), '希腊': ('Greece', 'ギリシャ', '希腊'),
    '德国': ('Germany', 'ドイツ', '德国'), '摩尔多瓦': ('Moldova', 'モルドバ', '摩尔多瓦'), '摩洛哥': ('Morocco', 'モロッコ', '摩洛哥'),
    '斐济': ('Fiji', 'フィジー', '斐济'), '新西兰': ('New Zealand', 'ニュージーランド', '新西兰'), '智利': ('Chile', 'チリ', '智利'),
    '沙特': ('Saudi Arabia', 'サウジアラビア', '沙特阿拉伯'), '泰国': ('Thailand', 'タイ', '泰国'),
    '洪都拉斯': ('Honduras', 'ホンジュラス', '洪都拉斯'), '澳大利亚': ('Australia', 'オーストラリア', '澳大利亚'),
    '特立尼达和多巴哥': ('Trinidad and Tobago', 'トリニダード・トバゴ', '特立尼达和多巴哥'),
    '白俄罗斯': ('Belarus', 'ベラルーシ', '白俄罗斯'), '科威特': ('Kuwait', 'クウェート', '科威特'), '秘鲁': ('Peru', 'ペルー', '秘鲁'),
    '突尼斯': ('Tunisia', 'チュニジア', '突尼斯'), '缅甸': ('Myanmar', 'ミャンマー', '缅甸'), '美国': ('United States', 'アメリカ', '美国'),
    '肯尼亚': ('Kenya', 'ケニア', '肯尼亚'), '苏丹': ('Sudan', 'スーダン', '苏丹'), '荷兰': ('Netherlands', 'オランダ', '荷兰'),
    '菲律宾': ('Philippines', 'フィリピン', '菲律宾'), '萨尔瓦多': ('El Salvador', 'エルサルバドル', '萨尔瓦多'),
    '西班牙': ('Spain', 'スペイン', '西班牙'), '越南': ('Vietnam', 'ベトナム', '越南'), '阿根廷': ('Argentina', 'アルゼンチン', '阿根廷'),
    '马尔代夫': ('Maldives', 'モルディブ', '马尔代夫'), '马来西亚': ('Malaysia', 'マレーシア', '马来西亚'),
    '黎巴嫩': ('Lebanon', 'レバノン', '黎巴嫩'),
}
ATTACHMENTS = {'贝壳斗': ('with clamshell bucket', 'クラムシェルバケット付き', '配贝壳斗'),
               '抓砖机': ('with brick clamp', 'レンガクランプ付き', '配抓砖机')}
IMAGE_EXT = {'jpg', 'jpeg', 'png'}
VIDEO_EXT = {'mp4', 'mov'}


def slugify(text):
    return re.sub(r'[^a-z0-9]+', '-', text.lower()).strip('-')


def process_image(src, dest, crop):
    im = ImageOps.exif_transpose(Image.open(src)).convert('RGB')
    if crop:
        im = im.crop((0, 0, im.width, int(im.height * (1 - CROP_BOTTOM))))
    im.thumbnail((1600, 1600))
    im.save(dest, 'WEBP', quality=80, method=6)  # no exif passed: metadata is dropped
    return im.width, im.height


def process_video(src, dest, poster):
    # Short side 720, H.264, no audio, first 60 s, fast start for streaming.
    scale = "scale='if(gt(iw,ih),-2,720)':'if(gt(iw,ih),720,-2)'"
    if not os.path.exists(dest):  # transcoding is slow; a rerun keeps finished videos
        subprocess.run(['ffmpeg', '-v', 'error', '-y', '-i', src, '-t', '60', '-an', '-vf', scale, '-c:v', 'libx264',
                        '-preset', 'slow', '-crf', '28', '-pix_fmt', 'yuv420p', '-movflags', '+faststart',
                        '-map_metadata', '-1', dest + '.part.mp4'], check=True)
        os.replace(dest + '.part.mp4', dest)
    frame = poster + '.jpg'
    subprocess.run(['ffmpeg', '-v', 'error', '-y', '-ss', '1', '-i', dest, '-frames:v', '1', '-q:v', '3', frame], check=True)
    Image.open(frame).save(poster, 'WEBP', quality=78, method=6)
    os.remove(frame)
    out = subprocess.run(['ffprobe', '-v', 'error', '-select_streams', 'v:0', '-show_entries', 'stream=width,height',
                          '-of', 'csv=p=0', dest], capture_output=True, text=True).stdout.strip().split(',')
    return int(out[0]), int(out[1])


groups = {}
for dirpath, _, files in os.walk(SRC):
    for name in files:
        ext = name.rsplit('.', 1)[-1].lower()
        if ext not in IMAGE_EXT | VIDEO_EXT:
            continue
        rel = os.path.relpath(os.path.join(dirpath, name), SRC)
        parts = rel.split('/')
        country = parts[0]
        folder = parts[1] if len(parts) > 2 else os.path.splitext(parts[1])[0]  # Kenya: model is the file name
        groups.setdefault((country, folder), []).append(rel)

plan = []
excluded = []
for (country, folder), files in sorted(groups.items()):
    if country not in COUNTRIES:
        raise SystemExit(f'unknown country folder {country}')
    model, *extras = folder.split('+')
    notes = [ATTACHMENTS[x] for x in extras if x in ATTACHMENTS]
    if len(extras) != len(notes):
        # "+1" is part of the model name (e.g. SQ08ZA3+1), not an attachment.
        model = folder if not notes else model
    slug = slugify(f'{COUNTRIES[country][0]}-{model}')
    item = {'source': f'{country}/{folder}', 'slug': slug, 'model': model,
            'country': dict(zip(('en', 'ja', 'zh'), COUNTRIES[country])),
            'note': dict(zip(('en', 'ja', 'zh'), notes[0])) if notes else None, 'media': []}
    os.makedirs(os.path.join(OUT, slug), exist_ok=True)
    for rel in sorted(files, key=lambda r: (r.rsplit('.', 1)[-1].lower() in VIDEO_EXT, r)):
        if rel in EXCLUDE:
            excluded.append(rel)
            continue
        ext = rel.rsplit('.', 1)[-1].lower()
        src = os.path.join(SRC, rel)
        identity = hashlib.sha256(rel.encode('utf-8')).hexdigest()[:16]
        if ext in VIDEO_EXT:
            dest, poster = f'{slug}/{slug}-{identity}.mp4', f'{slug}/{slug}-{identity}-poster.webp'
            size = (0, 0) if DRY else process_video(src, os.path.join(OUT, dest), os.path.join(OUT, poster))
            item['media'].append({'type': 'video', 'file': dest, 'poster': poster, 'width': size[0], 'height': size[1]})
        else:
            dest = f'{slug}/{slug}-{identity}.webp'
            size = (0, 0) if DRY else process_image(src, os.path.join(OUT, dest), any(rel == c or (c.endswith('/') and rel.startswith(c)) for c in CROP))
            item['media'].append({'type': 'image', 'file': dest, 'width': size[0], 'height': size[1]})
    if item['media']:
        plan.append(item)

json.dump({'note': __doc__.strip().splitlines()[0], 'items': plan}, open(os.path.join(HERE, 'plan.json'), 'w'),
          ensure_ascii=False, indent=1)
print(len(plan), 'feedback groups;', sum(len(p['media']) for p in plan), 'media;',
      sum(m['type'] == 'video' for p in plan for m in p['media']), 'videos;', len(excluded), 'excluded')

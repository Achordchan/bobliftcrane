"""Encode the site video library from the read-only NAS into assets/videos/library/ (git-ignored)
and write posters to assets/images/video-posters/ (committed) plus manifest.json for inc/video-library.php.

    python3 tools/video-library/build.py            # encode missing files only
Never writes to /Volumes.
"""
import json, os, subprocess, sys

ROOT = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
OUT = os.path.join(ROOT, 'assets/videos/library')
POSTERS = os.path.join(ROOT, 'assets/images/video-posters')
NIKI = '/Volumes/团队文件-通用空间/Niki/产品视频/'
SHORTS = '/Volumes/团队文件-运营部/赵振生拍摄素材/视频类/短视频/'
AERIAL = '/Volumes/团队文件-运营部/赵振生拍摄素材/工厂航拍/26-8月新航拍/航拍横屏/'
T, M = NIKI + '随车吊/', NIKI + '船吊/'

# key, group, {lang: source}; 'any' serves every language. Aerial clips are muted loops.
VIDEOS = [
    ('truck-knuckle-1', 'truck', {'any': T + '折叠臂/1Z/1Z随车吊.mp4'}),
    ('truck-knuckle-2', 'truck', {'en': T + '折叠臂/2Z/2Z英文.mp4', 'zh': T + '折叠臂/2Z/2Z随车吊.mp4'}),
    ('truck-knuckle-3.2', 'truck', {'en': T + '折叠臂/3.2Z/3.2Z随车吊英文.mp4', 'zh': T + '折叠臂/3.2Z/白色3.2Z中文.mp4'}),
    ('truck-knuckle-4', 'truck', {'en': T + '折叠臂/4Z/4Z随车吊英语.mp4', 'zh': T + '折叠臂/4Z/4Z中文.mp4'}),
    ('truck-knuckle-5', 'truck', {'en': T + '折叠臂/5z/5Z英语.mp4', 'zh': T + '折叠臂/5z/5Z中文.mp4'}),
    ('truck-knuckle-6.3', 'truck', {'en': T + '折叠臂/6.3Z/6.3Z英语.mp4', 'zh': T + '折叠臂/6.3Z/6.3Z中文.mp4'}),
    ('truck-knuckle-8', 'truck', {'en': T + '折叠臂/橙色8Z/英语.mp4', 'zh': T + '折叠臂/橙色8Z/中文.mp4'}),
    ('truck-knuckle-10', 'truck', {'en': T + '折叠臂/10Z随车吊/10Z测试英语.mp4', 'zh': T + '折叠臂/10Z随车吊/10Z测试中文.mp4'}),
    ('truck-knuckle-12', 'truck', {'en': T + '折叠臂/12Z/12Z英语.mp4', 'zh': T + '折叠臂/12Z/12Z中文.mp4'}),
    ('truck-knuckle-20', 'truck', {'en': T + '折叠臂/20Z/20Z随车吊英语.mp4', 'zh': T + '折叠臂/20Z/SQ20Z.mp4'}),
    ('truck-knuckle-25', 'truck', {'any': T + '折叠臂/25Z/25Z随车吊.mp4'}),
    ('truck-telescopic-3.2', 'truck', {'any': T + '伸缩臂/3.2S/3.2S英语.mp4'}),
    ('truck-telescopic-4', 'truck', {'any': T + '伸缩臂/4S/随车吊4S.mp4'}),
    ('truck-telescopic-5', 'truck', {'en': T + '伸缩臂/5S随车吊/5S测试 英语.mp4', 'zh': T + '伸缩臂/5S随车吊/无配音.mp4'}),
    ('truck-telescopic-10', 'truck', {'en': T + '伸缩臂/10S随车吊/10S英语.mp4', 'zh': T + '伸缩臂/10S随车吊/10S中文.mp4'}),
    ('truck-telescopic-12', 'truck', {'en': T + '伸缩臂/12S/12S英语.mp4', 'zh': T + '伸缩臂/12S/12S中文.mp4'}),
    ('marine-knuckle-2', 'marine', {'en': M + '折叠臂船吊/2Z/2Z英语.mp4', 'zh': M + '折叠臂船吊/2Z/2Z船吊.mp4'}),
    ('marine-knuckle-3.2', 'marine', {'en': M + '折叠臂船吊/3.2z船吊测试/3.2Z船吊英语.mp4', 'zh': M + '折叠臂船吊/3.2z船吊测试/3.2Z船吊中文.mp4'}),
    ('marine-knuckle-5', 'marine', {'en': M + '折叠臂船吊/5Z船吊/5Z船吊测试英语.mp4', 'zh': M + '折叠臂船吊/5Z船吊/5Z船吊测试中文.mp4'}),
    ('marine-knuckle-10', 'marine', {'any': M + '折叠臂船吊/10Z船吊/10ZA4船吊.mp4'}),
    ('marine-knuckle-12', 'marine', {'en': M + '折叠臂船吊/12Z船吊/英语.mp4', 'zh': M + '折叠臂船吊/12Z船吊/12Z船吊中文.mp4'}),
    ('marine-knuckle-16', 'marine', {'en': M + '折叠臂船吊/16Z船吊/16Z船吊 英文.mp4', 'zh': M + '折叠臂船吊/16Z船吊/16Z船吊.mp4'}),
    ('marine-knuckle-20', 'marine', {'en': M + '折叠臂船吊/20Z船吊/20Z船吊英语.mp4', 'zh': M + '折叠臂船吊/20Z船吊/20Z船吊中文.mp4'}),
    ('marine-telescopic-12', 'marine', {'en': M + '伸缩臂船吊/12S/12S船吊英文.mp4', 'zh': M + '伸缩臂船吊/12S/12S船吊中文.mp4'}),
    ('marine-telescopic-20', 'marine', {'en': M + '伸缩臂船吊/20S/20S船吊测试 英语.mp4', 'zh': M + '伸缩臂船吊/20S/20S船吊测试 中文.mp4'}),
    ('test-load-5z', 'test', {'any': NIKI + '2026/1月/5Z/吊重测试.mp4'}),
    ('test-load-10z', 'test', {'any': NIKI + '2026/1月/10Z/吊重测试.mp4'}),
    ('test-load-6.3z', 'test', {'any': NIKI + '2026/2月/6.3Z/吊重测试.mp4'}),
    ('test-basket-6.3z', 'test', {'any': NIKI + '2026/2月/6.3Z/吊篮测试.mp4'}),
    ('test-load-marine-6.3s', 'test', {'any': NIKI + '2026/1月/6.3S船吊/6.3S船吊吊重测试.mp4'}),
    ('test-load-marine-2z', 'test', {'any': NIKI + '2026/1月/2Z船吊测试.mp4'}),
    ('test-load-sq8za6', 'test', {'any': SHORTS + '20260723测试/SQ8ZA6.mp4'}),
    ('test-batch-3.2z', 'test', {'any': NIKI + '2026/3月/20台3.2Z.mp4'}),
    ('test-explained', 'test', {'en': SHORTS + '260508拍摄 设备测试口播/设备测试（英文）.mp4', 'zh': SHORTS + '260508拍摄 设备测试口播/设备测试（中文）.mp4'}),
    ('quality-control', 'test', {'en': SHORTS + '20260630拍摄/质量把控/英文.mp4', 'zh': SHORTS + '20260630拍摄/质量把控/中文.mp4'}),
    ('factory-intro', 'factory', {'en': SHORTS + '20260818视频/源头厂-英文.mp4', 'zh': SHORTS + '20260818视频/源头厂-中文.mp4'}),
    ('factory-aerial', 'aerial', {'any': AERIAL + 'DJI_20260824090737_0003_D.MP4'}),
    ('factory-hall-door', 'aerial', {'any': AERIAL + 'DJI_20260824143923_0047_D.MP4'}),
    ('factory-hall-cranes', 'aerial', {'any': AERIAL + 'DJI_20260824163923_0097_D.MP4'}),
]


def probe(path):
    out = subprocess.run(['ffprobe', '-v', 'error', '-select_streams', 'v:0', '-show_entries', 'stream=width,height:stream_side_data=rotation:format=duration', '-of', 'json', path], capture_output=True, text=True, stdin=subprocess.DEVNULL).stdout
    data = json.loads(out or '{}')
    stream = (data.get('streams') or [{}])[0]
    w, h = int(stream.get('width', 0)), int(stream.get('height', 0))
    rot = 0
    for side in stream.get('side_data_list', []) or []:
        rot = abs(int(side.get('rotation', 0) or 0))
    if rot in (90, 270):
        w, h = h, w
    return w, h, float((data.get('format') or {}).get('duration', 0) or 0)


def encode(src, dest, muted, width, height):
    portrait = height > width
    scale = 'scale=540:-2' if portrait else ('scale=1280:-2' if muted else 'scale=-2:720')
    cmd = ['ffmpeg', '-nostdin', '-loglevel', 'error', '-y', '-i', src, '-vf', scale, '-c:v', 'libx264', '-preset', 'medium', '-crf', '30' if muted else '28', '-pix_fmt', 'yuv420p', '-movflags', '+faststart']
    cmd += ['-an'] if muted else ['-c:a', 'aac', '-b:a', '96k', '-ac', '2']
    subprocess.run(cmd + [dest], check=True, stdin=subprocess.DEVNULL)


def main():
    os.makedirs(OUT, exist_ok=True)
    os.makedirs(POSTERS, exist_ok=True)
    manifest = []
    for key, group, sources in VIDEOS:
        entry = {'key': key, 'group': group, 'sources': {}}
        muted = group == 'aerial'
        for lang, src in sources.items():
            if not os.path.exists(src):
                print('MISSING', key, lang, src, flush=True)
                continue
            w, h, duration = probe(src)
            name = f'{key}-{lang}.mp4'
            dest = os.path.join(OUT, name)
            if not os.path.exists(dest):
                encode(src, dest, muted, w, h)
            entry['sources'][lang] = {'file': 'videos/library/' + name}
            entry.update({'width': w, 'height': h, 'duration': round(duration)})
            print('ok', key, lang, round(os.path.getsize(dest) / 1e6, 1), 'MB', flush=True)
        if muted and entry['sources']:
            mobile = os.path.join(OUT, f'{key}-mobile.mp4')
            if not os.path.exists(mobile):
                subprocess.run(['ffmpeg', '-nostdin', '-loglevel', 'error', '-y', '-i', os.path.join(OUT, f'{key}-any.mp4'), '-vf', 'scale=854:-2', '-c:v', 'libx264', '-preset', 'medium', '-crf', '31', '-pix_fmt', 'yuv420p', '-movflags', '+faststart', '-an', mobile], check=True, stdin=subprocess.DEVNULL)
            entry['mobile'] = 'videos/library/' + f'{key}-mobile.mp4'
        if entry['sources']:
            first = os.path.join(ROOT, 'assets', next(iter(entry['sources'].values()))['file'])
            poster = os.path.join(POSTERS, key + '.webp')
            if not os.path.exists(poster):
                png = poster + '.png'
                subprocess.run(['ffmpeg', '-nostdin', '-loglevel', 'error', '-y', '-ss', '3', '-i', first, '-frames:v', '1', '-vf', 'scale=960:-2', png], check=True, stdin=subprocess.DEVNULL)
                subprocess.run(['magick', png, '-quality', '76', poster], check=True, stdin=subprocess.DEVNULL)
                os.remove(png)
            entry['poster'] = 'images/video-posters/' + key + '.webp'
            manifest.append(entry)
    meta = [{k: e[k] for k in ('key', 'width', 'height', 'duration', 'mobile') if k in e} for e in manifest]
    with open(os.path.join(ROOT, 'inc/video-library.json'), 'w') as f:
        json.dump(meta, f, ensure_ascii=False, indent=1)
    print('DONE', len(manifest), flush=True)


if __name__ == '__main__':
    main()

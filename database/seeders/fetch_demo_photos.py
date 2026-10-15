#!/usr/bin/env python3
"""Unduh foto dummy berlisensi bebas dari Wikimedia Commons ke storage/app/public/dummy/photos/<topik>/"""
import json, os, sys, urllib.parse, urllib.request, time

ROOT = '/home/ngemis/cms-tsm/storage/app/public/dummy/photos'
UA = {'User-Agent': 'TSM-CMS-demo/1.0 (admin@tsm-blitar.sch.id)'}
TOPICS = {
    'bengkel':   ('motorcycle mechanic repair workshop', 16),
    'mesin':     ('motorcycle engine disassembled', 10),
    'motor':     ('Honda motorcycle Indonesia', 10),
    'siswa':     ('vocational school students Indonesia', 12),
    'kelas':     ('classroom students Indonesia school', 8),
    'sekolah':   ('school building Indonesia SMK', 8),
    'piala':     ('trophy award ceremony students', 8),
    'masjid':    ('students praying mosque Indonesia', 6),
    'orang':     ('portrait Indonesian man smiling', 12),
    'wanita':    ('portrait Indonesian woman hijab', 10),
    'pabrik':    ('motorcycle factory assembly line', 6),
    'alat':      ('mechanic tools wrench set', 6),
    'logo':      ('motorcycle manufacturer logo', 10),
}

def get(url, timeout=40):
    req = urllib.request.Request(url, headers=UA)
    with urllib.request.urlopen(req, timeout=timeout) as r:
        return r.read()

credits = {}
for key, (q, n) in TOPICS.items():
    d = os.path.join(ROOT, key); os.makedirs(d, exist_ok=True)
    have = len([f for f in os.listdir(d) if f.endswith('.jpg') or f.endswith('.png')])
    if have >= n: print(key, 'sudah', have); continue
    width = 400 if key == 'logo' else 1280
    api = ('https://commons.wikimedia.org/w/api.php?action=query&generator=search&gsrnamespace=6'
           f'&gsrsearch={urllib.parse.quote(q + " filetype:bitmap" if key != "logo" else q)}&gsrlimit={n * 2}'
           f'&prop=imageinfo&iiprop=url|mime|extmetadata&iiurlwidth={width}&format=json')
    try:
        pages = json.loads(get(api))['query']['pages'].values()
    except Exception as e:
        print(key, 'gagal API', e); continue
    i = 0
    for p in sorted(pages, key=lambda x: x.get('index', 0)):
        ii = p.get('imageinfo', [{}])[0]
        mime = ii.get('mime', '')
        if key != 'logo' and mime != 'image/jpeg': continue
        if key == 'logo' and mime not in ('image/png', 'image/svg+xml', 'image/jpeg'): continue
        url = ii.get('thumburl') or ii.get('url')
        ext = 'png' if key == 'logo' else 'jpg'
        fn = os.path.join(d, f'{i:02d}.{ext}')
        try:
            data = get(url)
            if len(data) < 5000 and key != 'logo': continue
            open(fn, 'wb').write(data)
            meta = ii.get('extmetadata', {})
            credits[f'{key}/{i:02d}.{ext}'] = {'title': p['title'], 'license': meta.get('LicenseShortName', {}).get('value'), 'page': ii.get('descriptionurl')}
            i += 1
            time.sleep(0.3)
        except Exception as e:
            print('  skip', url[:80], e)
        if i >= n: break
    print(key, i)

cf = os.path.join(ROOT, 'CREDITS.json')
old = json.load(open(cf)) if os.path.exists(cf) else {}
old.update(credits)
json.dump(old, open(cf, 'w'), indent=1, ensure_ascii=False)

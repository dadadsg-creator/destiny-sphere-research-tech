"""
Картинки построек для карты колонии (соты) из перерисованных картинок.

Зачем: public/assets/buildings/<race>/<NAME>.png заменены перерисованным
артом ~512px — он нужен крупно (BuildingPanel, BuildMenuWindow), но на
карте рендерер рисовал его в родном размере, и здание вылезало далеко за
ячейку. Оригинальные FFDec-спрайты Flash-клиента были ~20-160px.

Что делает: для каждой картинки постройки
  1. обрезает прозрачные поля (альфа-bbox);
  2. уменьшает до ширины оригинального спрайта (scripts/building-sprite-sizes.json,
     снят с git-версии до замены), с запасом разрешения SCALE — карта
     масштабируется fitToViewport() в 2-3 раза, иначе было бы мыло;
  3. кладёт в public/assets/buildings-map/<race>/<NAME>.png;
  4. пишет src/map/buildingMapSizes.json — размер отображения на карте
     (в пикселях мира карты, как у оригинала), его читает renderer.ts.

Если картинка не перерисована (совпадает по размеру с оригиналом) —
копируется как есть. Варианты <NAME>_.png и подкаталоги redraw/ не
трогаются. Запуск после каждой замены арта:
    python scripts/make-map-sprites.py      (из каталога client/)
"""
import json
from pathlib import Path
from PIL import Image

SCALE = 3          # запас разрешения относительно размера на карте
ALPHA_THRESHOLD = 8

root = Path(__file__).resolve().parent.parent
src_dir = root / 'public' / 'assets' / 'buildings'
out_dir = root / 'public' / 'assets' / 'buildings-map'
orig_sizes = json.loads((root / 'scripts' / 'building-sprite-sizes.json').read_text())

display = {}
stats = {'resized': 0, 'copied': 0, 'no_reference': []}
for race in ('tech', 'bio', 'psi'):
    (out_dir / race).mkdir(parents=True, exist_ok=True)
    for src in sorted((src_dir / race).glob('*.png')):
        name = src.stem
        if name.endswith('_'):
            continue
        key = f'{race}/{name}'
        img = Image.open(src).convert('RGBA')
        ref = orig_sizes.get(key)
        if ref is None:
            # Новой картинке не с чем сравнить — оставляем как есть.
            stats['no_reference'].append(key)
            continue
        ow, oh = ref
        if img.size == (ow, oh):
            img.save(out_dir / race / f'{name}.png', optimize=True)
            display[key] = [ow, oh]
            stats['copied'] += 1
            continue
        bbox = img.getchannel('A').point(lambda a: 255 if a > ALPHA_THRESHOLD else 0).getbbox()
        if bbox:
            img = img.crop(bbox)
        tw, th = img.size
        dw = ow
        dh = round(ow * th / tw, 2)
        pw = min(tw, dw * SCALE)
        ph = max(1, round(pw * th / tw))
        img.resize((pw, ph), Image.LANCZOS).save(out_dir / race / f'{name}.png', optimize=True)
        display[key] = [dw, dh]
        stats['resized'] += 1

(root / 'src' / 'map' / 'buildingMapSizes.json').write_text(
    json.dumps(dict(sorted(display.items())), ensure_ascii=False, indent=0) + '\n', encoding='utf-8')
print(f"resized={stats['resized']} copied={stats['copied']} no_reference={stats['no_reference']}")

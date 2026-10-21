from pathlib import Path
import sys
from PIL import Image, ImageDraw, ImageFont

ROOT = Path(__file__).resolve().parents[1]
LAYOUT = (ROOT / "concept-art/terrain-prototypes/home-ground-layout-reference.txt").read_text(encoding="utf-8").strip()
OUT_DIR = ROOT / "concept-art/terrain-prototypes/home-hive-large-v09"
OUT_DIR.mkdir(parents=True, exist_ok=True)

W, H = 1536, 1024
MAP_W, MAP_H = 816.0, 338.0
PLAYFIELD_SCALE = 0.72
GROUND_W = MAP_W * 1.242352941
GROUND_H = MAP_H * 1.706666667
GROUND_X = -MAP_W * 0.121176471
GROUND_Y = -MAP_H / 3.0
SX = W / GROUND_W
SY = H / GROUND_H

CELL_H = 24.0
CELL_V = 13.0
COLORS = {
    "D": (219, 155, 72, 238),
    "S": (137, 96, 69, 238),
    "G": (73, 139, 76, 238),
    "W": (31, 145, 190, 238),
    "X": (120, 88, 210, 255),
}


def source_point(world_x: float, world_y: float) -> tuple[float, float]:
    return ((world_x - GROUND_X) * SX, (world_y - GROUND_Y) * SY)


def playfield_point(world_x: float, world_y: float) -> tuple[float, float]:
    offset_x = MAP_W * (1 - PLAYFIELD_SCALE) / 2
    offset_y = MAP_H * (1 - PLAYFIELD_SCALE) / 2
    return (offset_x + world_x * PLAYFIELD_SCALE, offset_y + world_y * PLAYFIELD_SCALE)


def build_cells():
    cells = []
    num = 0
    nn = 0
    for row in range(17):
        row_len = 9 + nn
        for col in range(row_len):
            x = (col * 2 - nn + 8) * CELL_H
            y = row * 1.5 * CELL_V
            cells.append((num, x, y))
            num += 1
        nn += 1 if row < 8 else -1
    return cells


def hex_points(x: float, y: float):
    local = [
        (CELL_H, 0), (2 * CELL_H, 0.5 * CELL_V),
        (2 * CELL_H, 1.5 * CELL_V), (CELL_H, 2 * CELL_V),
        (0, 1.5 * CELL_V), (0, 0.5 * CELL_V),
    ]
    return [source_point(*playfield_point(x + px, y + py)) for px, py in local]


guide = Image.new("RGBA", (W, H), (5, 14, 25, 255))
draw = ImageDraw.Draw(guide, "RGBA")
font = ImageFont.load_default()
for num, x, y in build_cells():
    code = LAYOUT[num]
    points = hex_points(x, y)
    draw.polygon(points, fill=COLORS[code], outline=(225, 246, 255, 210), width=2)
    if code == "X":
        cx = sum(p[0] for p in points) / 6
        cy = sum(p[1] for p in points) / 6
        draw.text((cx, cy), "X", fill=(255, 255, 255, 255), font=font, anchor="mm", stroke_width=1, stroke_fill=(20, 10, 50, 255))

legend = Image.new("RGBA", (W, 70), (3, 10, 18, 225))
legend_draw = ImageDraw.Draw(legend, "RGBA")
labels = [("D", "DESERT 35"), ("S", "SOIL 92"), ("G", "GRASS 45"), ("W", "WATER 44"), ("X", "SYSTEM 1")]
x = 36
for code, label in labels:
    legend_draw.rounded_rectangle((x, 19, x + 34, 51), radius=5, fill=COLORS[code], outline=(235, 250, 255, 230), width=2)
    legend_draw.text((x + 45, 35), label, fill=(235, 246, 255, 255), font=font, anchor="lm")
    x += 265
guide.save(OUT_DIR / "home-hive-large-v09-terrain-mask.png", optimize=True)

legend_guide = guide.copy()
legend_guide.alpha_composite(legend, (0, H - 70))
legend_guide.save(OUT_DIR / "home-hive-large-v09-terrain-mask-labeled.png", optimize=True)

candidate = Path(sys.argv[1]) if len(sys.argv) > 1 else ROOT / "concept-art/terrain-prototypes/home-hive-large-v07/home-hive-large-v07-source.png"
overlay = Image.open(candidate).convert("RGBA")
mask_only = guide.copy()
mask_only.putalpha(112)
overlay.alpha_composite(mask_only)
overlay_name = f"home-hive-large-v09-{candidate.stem}-overlay.png"
overlay.save(OUT_DIR / overlay_name, optimize=True)

print(OUT_DIR / "home-hive-large-v09-terrain-mask.png")
print(OUT_DIR / overlay_name)

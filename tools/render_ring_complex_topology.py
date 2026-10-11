from __future__ import annotations

import math
from pathlib import Path

from PIL import Image, ImageDraw, ImageFont


ROOT = Path(__file__).resolve().parents[1]
OUT_DIR = ROOT / "concept-art" / "world-structure"
OUT = OUT_DIR / "ring-complex-topology-v01.png"

W, H = 2400, 1400
BG = "#071824"
PANEL = "#0c2230"
GRID = "#557180"
TEXT = "#eaf5f7"
MUTED = "#a8c0c9"
CENTRAL = "#3f9cba"
HOME = "#4e7f68"
HOME_ALT = "#557c58"
NEUTRAL = "#c99755"
RING = "#1b3443"
SELECT = "#f3c75d"
FOREIGN = "#465766"


def font(size: int, bold: bool = False) -> ImageFont.FreeTypeFont:
    name = "segoeuib.ttf" if bold else "segoeui.ttf"
    return ImageFont.truetype(str(Path("C:/Windows/Fonts") / name), size)


F_TITLE = font(54, True)
F_SUB = font(29)
F_HEAD = font(34, True)
F_LABEL = font(26, True)
F_SMALL = font(22)
F_TINY = font(19)


def hex_points(cx: float, cy: float, size: float) -> list[tuple[float, float]]:
    # Flat-top hexagon: one neighbour per complete side.
    return [
        (cx + size * math.cos(math.radians(60 * i)),
         cy + size * math.sin(math.radians(60 * i)))
        for i in range(6)
    ]


def axial_to_px(q: int, r: int, size: float, ox: float, oy: float) -> tuple[float, float]:
    return (
        ox + 1.5 * size * q,
        oy + math.sqrt(3) * size * (r + q / 2),
    )


def hex_distance(a: tuple[int, int], b: tuple[int, int]) -> int:
    dq = a[0] - b[0]
    dr = a[1] - b[1]
    return max(abs(dq), abs(dr), abs(dq + dr))


def draw_hex(
    draw: ImageDraw.ImageDraw,
    cx: float,
    cy: float,
    size: float,
    fill: str,
    outline: str = GRID,
    width: int = 4,
) -> None:
    pts = hex_points(cx, cy, size)
    draw.polygon(pts, fill=fill)
    draw.line(pts + [pts[0]], fill=outline, width=width, joint="curve")


def centered(draw: ImageDraw.ImageDraw, xy: tuple[float, float], text: str,
             fnt: ImageFont.FreeTypeFont, fill: str = TEXT) -> None:
    box = draw.textbbox((0, 0), text, font=fnt)
    x = xy[0] - (box[2] - box[0]) / 2
    y = xy[1] - (box[3] - box[1]) / 2 - box[1]
    draw.text((x, y), text, font=fnt, fill=fill)


def rounded_panel(draw: ImageDraw.ImageDraw, box: tuple[int, int, int, int]) -> None:
    draw.rounded_rectangle(box, radius=32, fill=PANEL, outline="#254657", width=3)


def main() -> None:
    OUT_DIR.mkdir(parents=True, exist_ok=True)
    im = Image.new("RGB", (W, H), BG)
    d = ImageDraw.Draw(im)

    d.text((80, 48), "Кусочек кольца: строгая топология сот", font=F_TITLE, fill=TEXT)
    d.text(
        (82, 118),
        "Каждая грань принадлежит ровно одной паре соседей. Вершина не является дорогой или развязкой.",
        font=F_SUB,
        fill=MUTED,
    )

    # Left panel: exact complex and one home-neutral relation.
    rounded_panel(d, (70, 185, 965, 1260))
    d.text((120, 220), "Локальная геометрия комплекса", font=F_HEAD, fill=TEXT)
    d.text((120, 268), "Подтверждённая часть", font=F_SMALL, fill="#7ed2e8")

    size = 78
    ox, oy = 470, 610
    dirs = [(1, 0), (1, -1), (0, -1), (-1, 0), (-1, 1), (0, 1)]

    # Radius-2 context makes edge contacts readable.
    cells: list[tuple[int, int]] = []
    for q in range(-2, 3):
        for r in range(-2, 3):
            if hex_distance((q, r), (0, 0)) <= 2:
                cells.append((q, r))

    selected_home = (1, 0)
    inferred_neutrals = {(2 * q, 2 * r) for q, r in dirs}
    for q, r in cells:
        x, y = axial_to_px(q, r, size, ox, oy)
        dist = hex_distance((q, r), (0, 0))
        if (q, r) == (0, 0):
            fill, outline = CENTRAL, "#9fe9fb"
        elif dist == 1:
            fill = HOME if (q - r) % 2 else HOME_ALT
            outline = SELECT if (q, r) == selected_home else "#81a892"
        elif (q, r) in inferred_neutrals:
            fill = NEUTRAL
            outline = SELECT if (q, r) == (2, 0) else "#e7bb72"
        else:
            fill, outline = FOREIGN, GRID
        draw_hex(d, x, y, size - 2, fill, outline, 5)

    cx, cy = axial_to_px(0, 0, size, ox, oy)
    centered(d, (cx, cy - 12), "Ц", F_HEAD)
    centered(d, (cx, cy + 34), "общая", F_TINY, "#d8f5fa")
    for i, (q, r) in enumerate(dirs, start=1):
        x, y = axial_to_px(q, r, size, ox, oy)
        centered(d, (x, y - 10), f"Д{i}", F_LABEL)
        centered(d, (x, y + 27), "дом", F_TINY, "#dbece2")

    for i, (q, r) in enumerate(dirs, start=1):
        nx, ny = axial_to_px(2 * q, 2 * r, size, ox, oy)
        centered(d, (nx, ny - 8), f"Н{i}", F_LABEL, "#fff5d9")
        centered(d, (nx, ny + 25), "пустыня", F_TINY, "#fff0cd")

    d.text((120, 986), "Ц + Д1…Д6 — один комплекс", font=F_LABEL, fill=TEXT)
    d.text((120, 1030), "Центральная сота разделена на 6 секторов.", font=F_SMALL, fill=MUTED)
    d.text((120, 1082), "Рабочая гипотеза", font=F_SMALL, fill=SELECT)
    d.text((120, 1121), "Н1…Н6 образуют наружный пустынный пояс:", font=F_SMALL, fill=TEXT)
    d.text((120, 1156), "каждая примыкает к своей домашней полной гранью.", font=F_SMALL, fill=TEXT)
    d.text((120, 1202), "Серые промежутки заполняют соты соседних комплексов.", font=F_TINY, fill=MUTED)

    # Right panel: a continuous strip. Centres use a 7-colour perfect code,
    # so their radius-1 flowers partition the hex grid without overlaps.
    rounded_panel(d, (1000, 185, 2330, 1260))
    d.text((1050, 220), "Непрерывная полоса кольца", font=F_HEAD, fill=TEXT)
    d.text((1050, 268), "Комплексы сцепляются полными гранями, без мостов и «пальцев»", font=F_SMALL, fill=MUTED)

    s2 = 72
    ox2, oy2 = 1610, 690
    patch: list[tuple[int, int]] = []
    for q in range(-7, 8):
        for r in range(-6, 7):
            x, y = axial_to_px(q, r, s2, ox2, oy2)
            # A clipped ring strip. The gentle bend is only a frame cue;
            # topology remains an exact axial grid.
            if 1080 < x < 2250 and 340 < y < 1115:
                patch.append((q, r))

    centres = [(q, r) for q, r in patch if (q + 3 * r) % 7 == 0]

    def owner(cell: tuple[int, int]) -> tuple[int, int] | None:
        near = [c for c in centres if hex_distance(cell, c) <= 1]
        return near[0] if len(near) == 1 else None

    palette = ["#456e61", "#506d4e", "#3f696f", "#6a6650"]
    for q, r in patch:
        x, y = axial_to_px(q, r, s2, ox2, oy2)
        own = owner((q, r))
        if own is None:
            fill = RING
        elif (q, r) == own:
            fill = CENTRAL
        else:
            idx = centres.index(own) % len(palette)
            fill = palette[idx]
        draw_hex(d, x, y, s2 - 1, fill, GRID, 3)

    # Add a desert edge band as the explicit working hypothesis for neutrals.
    edge_cells = sorted(patch, key=lambda c: axial_to_px(*c, s2, ox2, oy2)[1], reverse=True)
    desert = []
    used_q: set[int] = set()
    for cell in edge_cells:
        x, y = axial_to_px(*cell, s2, ox2, oy2)
        if y < 935 or cell[0] in used_q:
            continue
        desert.append(cell)
        used_q.add(cell[0])
    for q, r in desert:
        x, y = axial_to_px(q, r, s2, ox2, oy2)
        draw_hex(d, x, y, s2 - 1, NEUTRAL, "#e7bb72", 4)

    for q, r in centres:
        x, y = axial_to_px(q, r, s2, ox2, oy2)
        if 1120 < x < 2200 and 390 < y < 990:
            centered(d, (x, y), "Ц", F_LABEL)

    d.rounded_rectangle((1070, 1135, 2260, 1218), radius=18, fill="#102c3b")
    legends = [
        (CENTRAL, "общая центральная"),
        (HOME, "домашняя"),
        (NEUTRAL, "нейтральная пустыня (гипотеза внешнего края)"),
    ]
    lx = 1100
    for colour, label in legends:
        d.rounded_rectangle((lx, 1157, lx + 32, 1189), radius=6, fill=colour)
        d.text((lx + 44, 1153), label, font=F_TINY, fill=TEXT)
        lx += 285 if label != legends[-1][1] else 0

    d.text((82, 1305), "V01 · топологический стенд, не финальный арт", font=F_TINY, fill="#668894")
    d.text((1530, 1305), "Правило: 6 граней → максимум 6 непосредственных соседей", font=F_SMALL, fill=SELECT)

    im.save(OUT, optimize=True)
    print(OUT)


if __name__ == "__main__":
    main()

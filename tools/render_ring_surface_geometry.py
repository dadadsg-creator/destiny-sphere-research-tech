from __future__ import annotations

import math
from pathlib import Path

from PIL import Image, ImageDraw, ImageFont


ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / "concept-art" / "world-structure" / "ring-surface-geometry-v01.png"

W, H = 2400, 1400
BG = "#071824"
PANEL = "#0c2230"
TEXT = "#edf7f8"
MUTED = "#a8c1ca"
CYAN = "#53bedc"
GOLD = "#efc35c"
BAD = "#de765f"
GREEN = "#5b8b71"
SAND = "#c99a58"


def font(size: int, bold: bool = False) -> ImageFont.FreeTypeFont:
    filename = "segoeuib.ttf" if bold else "segoeui.ttf"
    return ImageFont.truetype(str(Path("C:/Windows/Fonts") / filename), size)


F_TITLE = font(52, True)
F_HEAD = font(34, True)
F_TEXT = font(25)
F_SMALL = font(21)
F_FORMULA = font(29, True)


def arrow(draw: ImageDraw.ImageDraw, start: tuple[float, float], end: tuple[float, float],
          color: str, width: int = 5) -> None:
    draw.line((start, end), fill=color, width=width)
    angle = math.atan2(end[1] - start[1], end[0] - start[0])
    a = 15
    for delta in (2.55, -2.55):
        p = (end[0] + a * math.cos(angle + delta), end[1] + a * math.sin(angle + delta))
        draw.line((end, p), fill=color, width=width)


def rounded_panel(draw: ImageDraw.ImageDraw, box: tuple[int, int, int, int]) -> None:
    draw.rounded_rectangle(box, radius=34, fill=PANEL, outline="#284a5b", width=3)


def hexagon(cx: float, cy: float, size: float) -> list[tuple[float, float]]:
    return [
        (cx + size * math.cos(math.radians(60 * i)),
         cy + size * math.sin(math.radians(60 * i)))
        for i in range(6)
    ]


def main() -> None:
    OUT.parent.mkdir(parents=True, exist_ok=True)
    im = Image.new("RGB", (W, H), BG)
    d = ImageDraw.Draw(im)

    d.text((75, 48), "Физика кольца: одинаковый шаг сот без растяжения", font=F_TITLE, fill=TEXT)
    d.text((78, 118), "Игровая сетка лежит на внутренней цилиндрической стенке; вогнутый профиль создают неигровые плечи.", font=F_TEXT, fill=MUTED)

    rounded_panel(d, (65, 185, 1160, 1260))
    rounded_panel(d, (1200, 185, 2335, 1260))

    d.text((110, 225), "Плоский аннулярный пояс", font=F_HEAD, fill=BAD)
    d.text((110, 274), "Проблема: радиальные ряды имеют разную длину", font=F_TEXT, fill=MUTED)

    cx, cy = 610, 690
    outer, inner = 360, 205
    d.ellipse((cx - outer, cy - outer, cx + outer, cy + outer), fill="#233b48", outline="#5b7480", width=5)
    d.ellipse((cx - inner, cy - inner, cx + inner, cy + inner), fill=BG, outline="#5b7480", width=5)
    d.ellipse((cx - 52, cy - 52, cx + 52, cy + 52), fill="#fff0b0", outline=GOLD, width=7)
    d.text((cx - 63, cy + 76), "Силус", font=F_SMALL, fill=GOLD)

    # Radial rows: same angular pitch creates visibly wider outer cells.
    for radius, color in ((245, SAND), (300, GREEN), (345, CYAN)):
        for i in range(18):
            a = math.radians(i * 20 - 100)
            x = cx + radius * math.cos(a)
            y = cy + radius * math.sin(a)
            pts = hexagon(x, y, 27 if radius == 245 else (31 if radius == 300 else 36))
            d.polygon(pts, fill=color, outline="#91abb5")

    d.text((190, 1080), "Cвнутр = 2π(R − W/2)", font=F_FORMULA, fill=TEXT)
    d.text((190, 1132), "Cнаруж = 2π(R + W/2)", font=F_FORMULA, fill=TEXT)
    d.text((190, 1185), "ΔC = 2πW  →  нужны растяжения или дефекты", font=F_TEXT, fill=BAD)

    d.text((1245, 225), "Внутренняя цилиндрическая лента", font=F_HEAD, fill=CYAN)
    d.text((1245, 274), "Решение: игровые ряды лежат на одном радиусе R", font=F_TEXT, fill=MUTED)

    # Perspective construction: a cylindrical strip viewed from inside.
    left, right = 1320, 2210
    top, bottom = 405, 970
    # Raised non-playable shoulders make a shallow half-pipe silhouette.
    d.polygon([(left, top + 120), (left + 90, top), (left + 130, bottom - 25), (left + 35, bottom + 45)],
              fill="#273f4c", outline="#78909a")
    d.polygon([(right, top + 120), (right - 90, top), (right - 130, bottom - 25), (right - 35, bottom + 45)],
              fill="#273f4c", outline="#78909a")
    d.rounded_rectangle((left + 100, top + 45, right - 100, bottom), radius=65,
                        fill="#294553", outline="#80a4b2", width=5)

    # Uniform honeycomb on the playable band.
    s = 47
    for row in range(8):
        for col in range(9):
            x = left + 165 + col * 1.5 * s
            y = top + 125 + math.sqrt(3) * s * (row + col / 2)
            if y > bottom - 35:
                continue
            fill = SAND if row >= 6 else (GREEN if (row + col) % 4 else "#4d7a79")
            d.polygon(hexagon(x, y, s - 2), fill=fill, outline="#9ab5be")

    # Constant-radius guides and star direction.
    arrow(d, (1765, 1055), (1765, 1190), GOLD, 6)
    d.text((1794, 1091), "к Силусу", font=F_TEXT, fill=GOLD)
    arrow(d, (1410, 1028), (2100, 1028), CYAN, 5)
    d.text((1520, 1050), "направление окружности кольца", font=F_SMALL, fill=CYAN)
    d.text((1300, 1130), "Для всех игровых рядов: C = 2πR", font=F_FORMULA, fill=TEXT)
    d.text((1300, 1182), "Боковые плечи и купол не входят в сетку клеток.", font=F_TEXT, fill=MUTED)

    d.text((83, 1312), "Рабочая физическая модель V01", font=F_SMALL, fill="#668894")
    d.text((1425, 1312), "Кривизна видна миру, но не ломает локальную гексагональную метрику", font=F_SMALL, fill=GOLD)

    im.save(OUT, optimize=True)
    print(OUT)


if __name__ == "__main__":
    main()

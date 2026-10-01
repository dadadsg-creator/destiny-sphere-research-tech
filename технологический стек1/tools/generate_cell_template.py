"""Генерирует контрольный PNG-шаблон игровой ячейки."""

from pathlib import Path

from PIL import Image, ImageDraw, ImageFont


ROOT = Path(__file__).resolve().parents[1]
OUTPUT = ROOT / "assets" / "tech" / "residential" / "source" / "cell-template.png"

WIDTH, HEIGHT = 1200, 900
SCALE = 6
ORIGIN_X, ORIGIN_Y = 360, 650

HEX = [(24, 0), (48, 6.5), (48, 19.5), (24, 26), (0, 19.5), (0, 6.5)]
PORTS = {
    "NE": (36, 3.25),
    "E": (48, 13),
    "SE": (36, 22.75),
    "SW": (12, 22.75),
    "W": (0, 13),
    "NW": (12, 3.25),
}
ANCHOR = (24, 26)
ENVELOPE = (-24, -86, 72, 28)


def point(x: float, y: float) -> tuple[int, int]:
    return round(ORIGIN_X + x * SCALE), round(ORIGIN_Y + y * SCALE)


def font(size: int, bold: bool = False) -> ImageFont.FreeTypeFont | ImageFont.ImageFont:
    names = [
        "C:/Windows/Fonts/seguisb.ttf" if bold else "C:/Windows/Fonts/segoeui.ttf",
        "C:/Windows/Fonts/arialbd.ttf" if bold else "C:/Windows/Fonts/arial.ttf",
    ]
    for name in names:
        try:
            return ImageFont.truetype(name, size)
        except OSError:
            pass
    return ImageFont.load_default()


def main() -> None:
    image = Image.new("RGB", (WIDTH, HEIGHT), "#08111f")
    draw = ImageDraw.Draw(image)
    title = font(34, bold=True)
    body = font(21)
    small = font(17)

    draw.text((50, 40), "DestinySphere — шаблон изометрической ячейки", fill="#f2f7ff", font=title)
    draw.text(
        (52, 92),
        "Нулевая точка спрайта совпадает с нижней вершиной ячейки: anchor (24, 26)",
        fill="#9fb4cc",
        font=body,
    )

    left, top, right, bottom = ENVELOPE
    draw.rectangle([point(left, top), point(right, bottom)], fill="#0d1b2d", outline="#5c7390", width=3)
    draw.text(point(left + 2, top + 2), "допустимый габарит спрайта 96 × 114", fill="#8da6c2", font=small)
    draw.line([point(24, -90), point(24, 32)], fill="#253b54", width=2)
    draw.line([point(-28, 13), point(76, 13)], fill="#253b54", width=2)

    draw.polygon([point(x, y) for x, y in HEX], fill="#132e45", outline="#46d5ff", width=5)
    base = [(24, 2.7), (44, 8), (44, 18), (24, 23.4), (4, 18), (4, 8)]
    draw.polygon([point(x, y) for x, y in base], fill="#164b5c", outline="#63f0bf", width=3)
    draw.text(point(18, 11), "ОСНОВАНИЕ", fill="#d6fff2", font=small)

    vectors = {
        "NE": (6, -9.75), "E": (12, 0), "SE": (6, 9.75),
        "SW": (-6, 9.75), "W": (-12, 0), "NW": (-6, -9.75),
    }
    for name, (x, y) in PORTS.items():
        vx, vy = vectors[name]
        draw.line([point(x, y), point(x + vx, y + vy)], fill="#ffcc66", width=4)
        px, py = point(x, y)
        draw.ellipse((px - 7, py - 7, px + 7, py + 7), fill="#ffcc66", outline="#fff3cd", width=2)
        tx, ty = point(x + vx * 1.25, y + vy * 1.25)
        draw.text((tx - 12, ty - 10), name, fill="#ffdc8a", font=small)

    ax, ay = point(*ANCHOR)
    draw.line((ax - 14, ay, ax + 14, ay), fill="#ff5d73", width=4)
    draw.line((ax, ay - 14, ax, ay + 14), fill="#ff5d73", width=4)
    draw.ellipse((ax - 6, ay - 6, ax + 6, ay + 6), fill="#ff5d73")
    draw.text((ax + 20, ay - 14), "ANCHOR (24, 26)", fill="#ff9baa", font=body)

    legend_x = 790
    draw.text((legend_x, 190), "Контрольные размеры", fill="#f2f7ff", font=font(25, bold=True))
    lines = [
        "Клетка: 48 × 26 px",
        "Шаг по горизонтали: 48 px",
        "Диагональный шаг: 24 × 19,5 px",
        "Основание здания: до 46 × 25 px",
        "Рекомендуемая ширина: 72–88 px",
        "Абсолютная ширина: 96 px",
        "Рекомендуемая высота: до 104 px",
        "Абсолютная высота: 112 px",
        "Ниже якоря: не более 2 px",
    ]
    for index, line in enumerate(lines):
        draw.text((legend_x, 240 + index * 43), line, fill="#b7c8da", font=body)

    draw.rounded_rectangle((775, 660, 1140, 820), radius=18, fill="#10243a", outline="#2e5577", width=2)
    draw.text((800, 688), "Пилотная камера", fill="#f2f7ff", font=font(23, bold=True))
    draw.text((800, 733), "Ортографическая", fill="#b7c8da", font=body)
    draw.text((800, 770), "Азимут 45° · высота 30°", fill="#b7c8da", font=body)

    OUTPUT.parent.mkdir(parents=True, exist_ok=True)
    image.save(OUTPUT, optimize=True)
    print("cell-template.png generated")


if __name__ == "__main__":
    main()

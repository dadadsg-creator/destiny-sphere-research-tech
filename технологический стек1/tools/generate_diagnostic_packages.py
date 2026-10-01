"""Создаёт три заменяемых технических пакета здания с прозрачными слоями."""

from pathlib import Path

from PIL import Image, ImageDraw


ROOT = Path(__file__).resolve().parents[1]
EXPORTS = ROOT / "assets" / "tech" / "residential" / "exports"
SIZE = (512, 640)


def polygon(draw: ImageDraw.ImageDraw, points, fill, outline=None, width=1):
    draw.polygon(points, fill=fill)
    if outline:
        draw.line([*points, points[0]], fill=outline, width=width, joint="curve")


def make_package(name: str, primary: str, secondary: str, glow: str, style: int) -> None:
    EXPORTS.mkdir(parents=True, exist_ok=True)

    shadow = Image.new("RGBA", SIZE, (0, 0, 0, 0))
    sd = ImageDraw.Draw(shadow)
    sd.ellipse((104, 542, 424, 616), fill=(0, 0, 0, 92))
    shadow.save(EXPORTS / f"{name}-shadow.png", optimize=True)

    color = Image.new("RGBA", SIZE, (0, 0, 0, 0))
    d = ImageDraw.Draw(color)
    polygon(d, [(90, 560), (256, 492), (422, 560), (358, 612), (154, 612)], (22, 58, 82, 255), (129, 225, 255, 255), 6)
    profiles = [
        ([(142, 552), (172, 226), (256, 70), (340, 226), (370, 552), (320, 590), (192, 590)], [(172, 226), (256, 70), (256, 558), (192, 590), (142, 552)], [(210, 25), (290, 31), (374, 38)]),
        ([(126, 552), (155, 270), (210, 120), (256, 82), (302, 120), (357, 270), (386, 552), (326, 590), (186, 590)], [(155, 270), (210, 120), (256, 82), (256, 558), (186, 590), (126, 552)], [(238, 31), (330, 37), (410, 42)]),
        ([(112, 552), (140, 340), (192, 210), (256, 150), (320, 210), (372, 340), (400, 552), (330, 590), (182, 590)], [(140, 340), (192, 210), (256, 150), (256, 558), (182, 590), (112, 552)], [(272, 42), (362, 47), (440, 51)]),
    ]
    body, shade, rings = profiles[style]
    polygon(d, body, primary, (221, 249, 255, 255), 7)
    polygon(d, shade, secondary)
    d.ellipse((210, 460, 302, 552), fill=(8, 30, 52, 255), outline=(220, 250, 255, 255), width=6)
    d.ellipse((233, 483, 279, 529), fill=glow)
    for y, radius in rings:
        d.ellipse((256-radius, y-radius//3, 256+radius, y+radius//3), outline=(235, 252, 255, 230), width=5)
    color.save(EXPORTS / f"{name}-color.png", optimize=True)

    emissive = Image.new("RGBA", SIZE, (0, 0, 0, 0))
    ed = ImageDraw.Draw(emissive)
    ed.ellipse((221, 471, 291, 541), fill=glow)
    ed.ellipse((239, 489, 273, 523), fill=(255, 255, 255, 235))
    for y, radius in rings:
        radius -= 4
        ed.ellipse((256-radius, y-radius//3, 256+radius, y+radius//3), outline=glow, width=7)
    emissive.save(EXPORTS / f"{name}-emissive.png", optimize=True)


def main() -> None:
    make_package("diagnostic-blue", (43, 151, 213, 255), (22, 91, 148, 255), (71, 220, 255, 205), 0)
    make_package("diagnostic-gold", (198, 137, 54, 255), (102, 55, 41, 255), (255, 190, 74, 215), 1)
    make_package("diagnostic-teal", (45, 157, 148, 255), (20, 86, 96, 255), (91, 255, 214, 215), 2)
    print("diagnostic building packages generated")


if __name__ == "__main__":
    main()

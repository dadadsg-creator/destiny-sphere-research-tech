"""Экспортирует цвет, контактную тень и свечение жилья Воранеров уровня 1."""

import math
from pathlib import Path

from PIL import Image, ImageDraw, ImageFilter


ROOT = Path(__file__).resolve().parents[1]
SOURCE = ROOT / "assets" / "tech" / "residential" / "source"
EXPORTS = ROOT / "assets" / "tech" / "residential" / "exports"

ASSETS = {
    "voraner-residential-v02": "voraner-residential-v02-simple.png",
    "voraner-residential-v03": "voraner-residential-v03-dome.png",
    "voraner-residential-v05": "voraner-residential-v05-oval.png",
    "voraner-residential-complex-3": "voraner-residential-v04-terrace.png",
    "voraner-residential-complex-7": "voraner-residential-complex-7.png",
}


def make_color(source: Image.Image, stem: str) -> Image.Image:
    rgba = source.convert("RGBA")
    output = Image.new("RGBA", rgba.size, (0, 0, 0, 0))
    source_pixels = rgba.load()
    output_pixels = output.load()
    complex_asset = "complex" in stem

    for y in range(rgba.height):
        vertical = y / max(1, rgba.height - 1)
        for x in range(rgba.width):
            red, green, blue, alpha = source_pixels[x, y]
            if alpha == 0:
                continue
            horizontal = x / max(1, rgba.width - 1)
            # Warm key light from the upper right and a cooler, darker left
            # side make neighbouring white-gold volumes readable at game size.
            light = 0.935 + horizontal * 0.17 - vertical * 0.035
            if complex_asset:
                light *= 1 + math.sin(horizontal * math.pi * 4) * 0.025
            warmth = horizontal * 0.035
            cool = (1 - horizontal) * 0.035
            output_pixels[x, y] = (
                min(255, round(red * light * (1 + warmth))),
                min(255, round(green * light)),
                min(255, round(blue * light * (1 + cool))),
                alpha,
            )
    return output


def make_emissive(source: Image.Image) -> Image.Image:
    rgba = source.convert("RGBA")
    output = Image.new("RGBA", rgba.size, (0, 0, 0, 0))
    source_pixels = rgba.load()
    output_pixels = output.load()

    for y in range(rgba.height):
        for x in range(rgba.width):
            red, green, blue, alpha = source_pixels[x, y]
            # Keep only the bright cyan energy cores and windows.  The stricter
            # green channel requirement prevents the blue glass roofs from
            # glowing as one large flat surface.
            if (
                alpha == 0
                or blue < 190
                or green < 135
                or blue < red + 50
                or green < red + 20
            ):
                continue
            energy = max(0, min(255, (blue - 175) * 3 + (green - 125) * 2))
            layer_alpha = min(alpha, energy)
            if layer_alpha < 18:
                continue
            output_pixels[x, y] = (
                min(255, max(45, red)),
                min(255, max(150, green)),
                255,
                layer_alpha,
            )

    return output.filter(ImageFilter.GaussianBlur(radius=max(1.0, rgba.width / 900)))


def find_stable_base(alpha: Image.Image) -> int:
    """Ignore isolated alpha debris below the visually solid building base."""
    threshold = max(6, alpha.width // 100)
    pixels = alpha.load()
    stable_rows = [
        y for y in range(alpha.height)
        if sum(1 for x in range(alpha.width) if pixels[x, y] > 16) >= threshold
    ]
    return stable_rows[-1] if stable_rows else alpha.height - 1


def make_shadow(source: Image.Image, stem: str) -> Image.Image:
    rgba = source.convert("RGBA")
    alpha = rgba.getchannel("A")
    bbox = alpha.getbbox()
    output = Image.new("RGBA", rgba.size, (0, 0, 0, 0))
    if bbox is None:
        return output

    left, top, right, _ = bbox
    width = right - left
    base = find_stable_base(alpha)
    height = max(1, base - top)
    complex_asset = "complex" in stem
    shadow_width = width * (0.78 if complex_asset else 0.62)
    shadow_height = height * (0.06 if complex_asset else 0.05)
    center_x = (left + right) / 2 - width * 0.018
    shadow_box = (
        max(0, int(center_x - shadow_width / 2)),
        max(0, int(base - shadow_height * 0.58)),
        min(rgba.width, int(center_x + shadow_width / 2)),
        min(rgba.height, int(base + shadow_height * 0.42)),
    )
    mask = Image.new("L", rgba.size, 0)
    draw = ImageDraw.Draw(mask)
    draw.ellipse(shadow_box, fill=108)
    mask = mask.filter(ImageFilter.GaussianBlur(radius=max(4, shadow_width * 0.018)))
    output.putalpha(mask)
    return output


def export_asset(stem: str, source_name: str) -> None:
    source_path = SOURCE / source_name
    with Image.open(source_path) as image:
        rgba = image.convert("RGBA")
        make_color(rgba, stem).save(EXPORTS / f"{stem}-color.png", optimize=True)
        make_shadow(rgba, stem).save(EXPORTS / f"{stem}-shadow.png", optimize=True)
        make_emissive(rgba).save(EXPORTS / f"{stem}-emissive.png", optimize=True)
        print(f"{stem}: {rgba.width}x{rgba.height}")


def main() -> None:
    EXPORTS.mkdir(parents=True, exist_ok=True)
    for stem, source_name in ASSETS.items():
        export_asset(stem, source_name)


if __name__ == "__main__":
    main()

"""Build the sharp, deterministic V17 hive terrain master and web tiles.

The AI-painted V14 image supplies the large-scale composition.  This pass
adds material-scale detail at the final 4608x3072 resolution instead of
asking an image model to invent the whole map at once.  The seed is fixed so
the output is reproducible.
"""

from __future__ import annotations

from pathlib import Path

import numpy as np
from PIL import Image, ImageEnhance, ImageFilter


ROOT = Path(__file__).resolve().parents[1]
ASSETS = ROOT / "public" / "assets" / "hive-lab"
MATERIALS = ROOT / "art-source" / "hive-lab" / "materials-v17"
MASTERS = ROOT / "art-source" / "hive-lab" / "masters"
SOURCE = ASSETS / "home-hive-large-v14-max-detail-4608.png"
MASTER = MASTERS / "home-hive-large-v17-material-master-4608.png"
PREVIEW = ASSETS / "home-hive-large-v17-preview.png"
TILE_DIR = ASSETS / "home-hive-large-v17-tiles"

WIDTH = 4608
HEIGHT = 3072
TILE_COLUMNS = 6
TILE_ROWS = 4
SEED = 217_017


def smooth_mask(mask: np.ndarray, radius: float = 8.0) -> np.ndarray:
    image = Image.fromarray(np.uint8(np.clip(mask, 0, 1) * 255), "L")
    return np.asarray(image.filter(ImageFilter.GaussianBlur(radius)), dtype=np.float32) / 255.0


def octave_noise(rng: np.random.Generator, width: int, height: int, scales: list[tuple[int, float]]) -> np.ndarray:
    result = np.zeros((height, width), dtype=np.float32)
    total = 0.0
    for cell_size, weight in scales:
        small_w = max(2, width // cell_size + 2)
        small_h = max(2, height // cell_size + 2)
        small = np.uint8(rng.random((small_h, small_w)) * 255)
        layer = Image.fromarray(small, "L").resize((width, height), Image.Resampling.BICUBIC)
        result += (np.asarray(layer, dtype=np.float32) / 127.5 - 1.0) * weight
        total += weight
    return result / max(total, 1e-6)


def add_luminance_detail(rgb: np.ndarray, mask: np.ndarray, detail: np.ndarray, amount: float) -> None:
    delta = detail * mask * amount
    rgb += delta[..., None]


def material_detail(path: Path, tile_size: int = 768) -> np.ndarray:
    """Extract real high-frequency material information and mirror-tile it.

    Mirrored edges meet exactly.  Only the high-pass signal is returned, so
    the source painting still controls biome colour, shoreline and lighting.
    """
    material = Image.open(path).convert("L").resize((tile_size, tile_size), Image.Resampling.LANCZOS)
    local = np.asarray(material, dtype=np.float32)
    blur_small = np.asarray(material.filter(ImageFilter.GaussianBlur(5.5)), dtype=np.float32)
    blur_large = np.asarray(material.filter(ImageFilter.GaussianBlur(24.0)), dtype=np.float32)
    detail = (local - blur_small) + (local - blur_large) * 0.32
    detail -= float(detail.mean())
    detail /= max(float(detail.std()), 1e-6)

    tiled = np.empty((HEIGHT, WIDTH), dtype=np.float32)
    for row in range(TILE_ROWS):
        for column in range(TILE_COLUMNS):
            current = detail
            if column % 2:
                current = np.fliplr(current)
            if row % 2:
                current = np.flipud(current)
            top = row * tile_size
            left = column * tile_size
            tiled[top:top + tile_size, left:left + tile_size] = current
    return tiled


def build() -> None:
    rng = np.random.default_rng(SEED)
    source = Image.open(SOURCE).convert("RGB")
    if source.size != (WIDTH, HEIGHT):
        raise RuntimeError(f"Expected {WIDTH}x{HEIGHT}, got {source.size[0]}x{source.size[1]}")

    # Recover edge definition without exaggerating the old broad painterly forms.
    base = source.filter(ImageFilter.UnsharpMask(radius=1.35, percent=112, threshold=3))
    base = ImageEnhance.Contrast(base).enhance(1.035)
    base = ImageEnhance.Color(base).enhance(0.93)

    rgb_u8 = np.asarray(base, dtype=np.uint8)
    hsv = np.asarray(base.convert("HSV"), dtype=np.uint8)
    hue = hsv[..., 0]
    sat = hsv[..., 1]
    val = hsv[..., 2]

    yy = np.linspace(0.0, 1.0, HEIGHT, dtype=np.float32)[:, None]
    xx = np.linspace(0.0, 1.0, WIDTH, dtype=np.float32)[None, :]

    water = smooth_mask(((hue >= 92) & (hue <= 165) & (sat >= 58) & (val >= 55)).astype(np.float32), 5.0)
    grass_zone = np.clip(1.0 - np.abs(yy - 0.31) / 0.24, 0.0, 1.0)
    grass = smooth_mask((((hue >= 31) & (hue <= 92) & (sat >= 48)).astype(np.float32) * grass_zone), 7.0)
    warm = ((hue <= 52) & (sat >= 35) & (water < 0.18)).astype(np.float32)
    sand_zone = np.clip((0.45 - yy) / 0.28, 0.0, 1.0)
    sand = smooth_mask(warm * sand_zone * np.clip((val.astype(np.float32) - 80.0) / 100.0, 0.0, 1.0), 8.0)
    soil_zone = np.clip((yy - 0.37) / 0.30, 0.0, 1.0)
    soil = smooth_mask(warm * soil_zone, 8.0)

    # Keep luminous metal borders, gate hardware and high-contrast joins intact.
    blue_metal = ((hue >= 130) & (hue <= 180) & (sat >= 150) & (val >= 155)).astype(np.float32)
    protected = smooth_mask(blue_metal, 3.0)
    sand *= 1.0 - protected
    soil *= 1.0 - protected
    grass *= 1.0 - protected
    water *= 1.0 - protected * 0.92

    rgb = rgb_u8.astype(np.float32)

    # Real material scans supply new information that is absent from the
    # original AI painting.  Their colour is discarded; masks and the source
    # image remain responsible for biome boundaries and global illumination.
    sand_material = material_detail(MATERIALS / "sand.png")
    soil_material = material_detail(MATERIALS / "soil.png")
    grass_material = material_detail(MATERIALS / "grass.png")
    water_material = material_detail(MATERIALS / "water.png")

    add_luminance_detail(rgb, sand, sand_material, 7.2)
    add_luminance_detail(rgb, soil, soil_material, 7.8)
    add_luminance_detail(rgb, grass, grass_material, 6.2)
    add_luminance_detail(rgb, water, water_material, 5.4)

    # Fine sand lamination: irregular, low relief and directional, never dune-sized.
    warp = octave_noise(rng, WIDTH, HEIGHT, [(220, 0.65), (78, 0.35)])
    sand_ripples = (
        np.sin(xx * 690.0 + yy * 115.0 + warp * 4.2)
        + 0.42 * np.sin(xx * 1170.0 - yy * 82.0 + warp * 7.0)
    ) / 1.42
    sand_grain = rng.normal(0.0, 1.0, (HEIGHT, WIDTH)).astype(np.float32)
    add_luminance_detail(rgb, sand, sand_ripples, 3.4)
    add_luminance_detail(rgb, sand, sand_grain, 1.25)

    # Earth receives granular aggregate and sparse sub-cell pebbles.
    soil_noise = octave_noise(rng, WIDTH, HEIGHT, [(34, 0.55), (11, 0.30), (4, 0.15)])
    soil_grain = rng.normal(0.0, 1.0, (HEIGHT, WIDTH)).astype(np.float32)
    add_luminance_detail(rgb, soil, soil_noise, 4.7)
    add_luminance_detail(rgb, soil, soil_grain, 1.45)

    # Grass is a fine, calm ground cover with tiny dark/light blades, not trees.
    grass_noise = octave_noise(rng, WIDTH, HEIGHT, [(26, 0.52), (9, 0.33), (3, 0.15)])
    grass_speck = rng.random((HEIGHT, WIDTH), dtype=np.float32)
    grass_speck = np.where(grass_speck > 0.986, (grass_speck - 0.986) * 86.0, 0.0)
    add_luminance_detail(rgb, grass, grass_noise, 4.0)
    rgb[..., 0] -= grass * grass_speck * 2.4
    rgb[..., 1] += grass * grass_speck * 1.2
    rgb[..., 2] -= grass * grass_speck * 2.7

    # Water gets very small crossing wavelets; no large painted caustic blobs.
    water_warp = octave_noise(rng, WIDTH, HEIGHT, [(150, 0.7), (52, 0.3)])
    wave_a = np.sin(xx * 530.0 + yy * 86.0 + water_warp * 3.0)
    wave_b = np.sin(xx * 310.0 - yy * 235.0 - water_warp * 2.4)
    water_detail = (wave_a + 0.55 * wave_b) / 1.55
    add_luminance_detail(rgb, water, water_detail, 2.9)
    rgb[..., 2] += water * np.maximum(water_detail, 0.0) * 1.5

    # Subtle sensor-like grain prevents flat enlarged colour fields at close zoom.
    global_grain = rng.normal(0.0, 0.62, (HEIGHT, WIDTH)).astype(np.float32)
    rgb += global_grain[..., None]
    result = Image.fromarray(np.uint8(np.clip(rgb, 0, 255)), "RGB")
    result = result.filter(ImageFilter.UnsharpMask(radius=0.65, percent=58, threshold=2))

    MASTER.parent.mkdir(parents=True, exist_ok=True)
    result.save(MASTER, format="PNG", optimize=False, compress_level=4)
    preview = result.resize((1536, 1024), Image.Resampling.LANCZOS)
    preview = preview.filter(ImageFilter.UnsharpMask(radius=0.55, percent=44, threshold=2))
    preview.save(PREVIEW, format="PNG", optimize=True, compress_level=8)

    TILE_DIR.mkdir(parents=True, exist_ok=True)
    tile_w = WIDTH // TILE_COLUMNS
    tile_h = HEIGHT // TILE_ROWS
    for row in range(TILE_ROWS):
        for column in range(TILE_COLUMNS):
            left = column * tile_w
            top = row * tile_h
            tile = result.crop((left, top, left + tile_w, top + tile_h))
            tile.save(TILE_DIR / f"r{row}-c{column}.png", format="PNG", optimize=True, compress_level=8)

    print(f"master={MASTER} ({MASTER.stat().st_size} bytes)")
    print(f"preview={PREVIEW} ({PREVIEW.stat().st_size} bytes)")
    print(f"tiles={TILE_DIR} ({sum(path.stat().st_size for path in TILE_DIR.glob('*.png'))} bytes)")


if __name__ == "__main__":
    build()

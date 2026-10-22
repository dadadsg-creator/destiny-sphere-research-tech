"""Build V19: cohesive native landscape art plus true 4608px material detail."""

from __future__ import annotations

from pathlib import Path

import numpy as np
from PIL import Image, ImageEnhance, ImageFilter


ROOT = Path(__file__).resolve().parents[1]
ASSETS = ROOT / "public" / "assets" / "hive-lab"
ART = ROOT / "art-source" / "hive-lab"
NATIVE = ART / "concepts" / "home-hive-v19-cohesive-native-1536.png"
MATERIALS = ART / "materials-v17"
MASTER = ART / "masters" / "home-hive-large-v19-cohesive-master-4608.png"
PREVIEW = ASSETS / "home-hive-large-v19-preview.png"
TILE_DIR = ASSETS / "home-hive-large-v19-tiles"

WIDTH = 4608
HEIGHT = 3072
TILE_SIZE = 768
TILE_COLUMNS = WIDTH // TILE_SIZE
TILE_ROWS = HEIGHT // TILE_SIZE
SEED = 217_019


def soft_mask(mask: np.ndarray, radius: float) -> np.ndarray:
    image = Image.fromarray(np.uint8(np.clip(mask, 0.0, 1.0) * 255), "L")
    return np.asarray(image.filter(ImageFilter.GaussianBlur(radius)), dtype=np.float32) / 255.0


def material_detail(path: Path) -> np.ndarray:
    """Extract seam-free high-frequency material information at final size."""
    material = Image.open(path).convert("L").resize((TILE_SIZE, TILE_SIZE), Image.Resampling.LANCZOS)
    local = np.asarray(material, dtype=np.float32)
    fine = np.asarray(material.filter(ImageFilter.GaussianBlur(4.2)), dtype=np.float32)
    medium = np.asarray(material.filter(ImageFilter.GaussianBlur(18.0)), dtype=np.float32)
    detail = (local - fine) + (local - medium) * 0.22
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
            top = row * TILE_SIZE
            left = column * TILE_SIZE
            tiled[top:top + TILE_SIZE, left:left + TILE_SIZE] = current
    return tiled


def build() -> None:
    rng = np.random.default_rng(SEED)
    native = Image.open(NATIVE).convert("RGB")
    if native.size != (1536, 1024):
        raise RuntimeError(f"Expected 1536x1024 native art, got {native.size[0]}x{native.size[1]}")

    source = native.resize((WIDTH, HEIGHT), Image.Resampling.LANCZOS)
    source = source.filter(ImageFilter.UnsharpMask(radius=0.85, percent=76, threshold=2))
    source = ImageEnhance.Contrast(source).enhance(1.025)
    hsv = np.asarray(source.convert("HSV"), dtype=np.uint8)
    hue, sat, val = hsv[..., 0], hsv[..., 1], hsv[..., 2]
    yy = np.linspace(0.0, 1.0, HEIGHT, dtype=np.float32)[:, None]

    water = soft_mask(((hue >= 92) & (hue <= 165) & (sat >= 62) & (val >= 52)).astype(np.float32), 5.0)
    grass_zone = np.clip(1.0 - np.abs(yy - 0.32) / 0.28, 0.0, 1.0)
    grass = soft_mask(((hue >= 31) & (hue <= 94) & (sat >= 35)).astype(np.float32) * grass_zone, 7.0)
    warm = ((hue <= 58) & (sat >= 25) & (water < 0.18)).astype(np.float32)
    sand = soft_mask(warm * np.clip((0.50 - yy) / 0.34, 0.0, 1.0), 8.0)
    soil = soft_mask(warm * np.clip((yy - 0.30) / 0.42, 0.0, 1.0), 8.0)

    blue_metal = ((hue >= 125) & (hue <= 180) & (sat >= 115) & (val >= 110)).astype(np.float32)
    protected = soft_mask(blue_metal, 3.0)
    sand *= 1.0 - protected
    soil *= 1.0 - protected
    grass *= 1.0 - protected
    water *= 1.0 - protected

    rgb = np.asarray(source, dtype=np.uint8).astype(np.float32)
    for filename, mask, amount in (
        ("sand.png", sand, 5.4),
        ("soil.png", soil, 5.8),
        ("grass.png", grass, 4.8),
        ("water.png", water, 4.2),
    ):
        detail = material_detail(MATERIALS / filename)
        rgb += detail[..., None] * mask[..., None] * amount
        del detail

    # Fine final-size grain keeps calm areas alive without changing the art.
    grain = rng.normal(0.0, 0.42, (HEIGHT, WIDTH)).astype(np.float32)
    rgb += grain[..., None] * (1.0 - protected[..., None])
    result = Image.fromarray(np.uint8(np.clip(rgb, 0, 255)), "RGB")
    result = result.filter(ImageFilter.UnsharpMask(radius=0.55, percent=54, threshold=2))

    MASTER.parent.mkdir(parents=True, exist_ok=True)
    result.save(MASTER, format="PNG", optimize=False, compress_level=4)
    preview = result.resize((1536, 1024), Image.Resampling.LANCZOS)
    preview = preview.filter(ImageFilter.UnsharpMask(radius=0.5, percent=38, threshold=2))
    preview.save(PREVIEW, format="PNG", optimize=True, compress_level=8)

    TILE_DIR.mkdir(parents=True, exist_ok=True)
    for row in range(TILE_ROWS):
        for column in range(TILE_COLUMNS):
            left = column * TILE_SIZE
            top = row * TILE_SIZE
            tile = result.crop((left, top, left + TILE_SIZE, top + TILE_SIZE))
            tile.save(TILE_DIR / f"r{row}-c{column}.png", format="PNG", optimize=True, compress_level=8)

    print(f"master={MASTER} ({MASTER.stat().st_size} bytes)")
    print(f"preview={PREVIEW} ({PREVIEW.stat().st_size} bytes)")
    print(f"tiles={TILE_DIR} ({sum(path.stat().st_size for path in TILE_DIR.glob('*.png'))} bytes)")


if __name__ == "__main__":
    build()

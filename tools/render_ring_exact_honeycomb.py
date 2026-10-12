from __future__ import annotations

import json
import math
import random
from collections import Counter
from pathlib import Path

from PIL import Image, ImageChops, ImageDraw, ImageEnhance, ImageFilter, ImageOps


ROOT = Path(__file__).resolve().parents[1]
WORLD_DIR = ROOT / "concept-art" / "world-structure"
TERRAIN_DIR = ROOT / "concept-art" / "terrain-prototypes"

OUT_ART = WORLD_DIR / "ring-segment-honeycomb-v07-complete-complex.png"
OUT_PROOF = WORLD_DIR / "ring-segment-honeycomb-v07-grid-proof.png"
OUT_REPORT = WORLD_DIR / "ring-segment-honeycomb-v07-geometry.json"

W, H = 2400, 1400
SIDE = 112.0
SQRT3 = math.sqrt(3)
GRID_TOP = 286
GRID_BOTTOM = 1130
GRID_LEFT = 55
GRID_RIGHT = W - 55

HOME_SOURCE = TERRAIN_DIR / "home-hive-landscape-v03-honeycomb.png"
CENTRAL_SOURCE = TERRAIN_DIR / "central-complex-landscape-v02-honeycomb.png"
NEUTRAL_SOURCE = TERRAIN_DIR / "neutral-hive-landscape-v02-honeycomb.png"


def hex_points(cx: float, cy: float, side: float = SIDE) -> list[tuple[float, float]]:
    """Mathematically regular flat-top hexagon."""
    return [
        (
            cx + side * math.cos(math.radians(60 * i)),
            cy + side * math.sin(math.radians(60 * i)),
        )
        for i in range(6)
    ]


def axial_to_px(q: int, r: int) -> tuple[float, float]:
    return (
        W / 2 + 1.5 * SIDE * q,
        700 + SQRT3 * SIDE * (r + q / 2),
    )


def hex_distance(a: tuple[int, int], b: tuple[int, int]) -> int:
    dq = a[0] - b[0]
    dr = a[1] - b[1]
    return max(abs(dq), abs(dr), abs(dq + dr))


def valid_cell(q: int, r: int) -> bool:
    cx, cy = axial_to_px(q, r)
    pts = hex_points(cx, cy)
    return (
        min(x for x, _ in pts) >= GRID_LEFT
        and max(x for x, _ in pts) <= GRID_RIGHT
        and min(y for _, y in pts) >= GRID_TOP
        and max(y for _, y in pts) <= GRID_BOTTOM
    )


FORCED_NEUTRALS = {(0, -2), (0, 2)}


CELLS = sorted(
    [
        (q, r)
        for q in range(-12, 13)
        for r in range(-12, 13)
        if valid_cell(q, r) or (q, r) in FORCED_NEUTRALS
    ],
    key=lambda cell: (axial_to_px(*cell)[1], axial_to_px(*cell)[0]),
)


def is_complex_centre(cell: tuple[int, int]) -> bool:
    q, r = cell
    cx, cy = axial_to_px(q, r)
    # Keep only complete seven-cell flowers in the visible study. Partial
    # centres at the crop would make the repeated complex look irregular.
    return 320 <= cx <= W - 320 and 445 <= cy <= 945 and (q + 3 * r) % 7 == 0


CENTRES = [cell for cell in CELLS if is_complex_centre(cell)]


def owner(cell: tuple[int, int]) -> tuple[int, int] | None:
    candidates = [centre for centre in CENTRES if hex_distance(cell, centre) <= 1]
    return candidates[0] if len(candidates) == 1 else None


def terrain_kind(cell: tuple[int, int]) -> str:
    _, cy = axial_to_px(*cell)
    if cell in FORCED_NEUTRALS:
        return "neutral"
    if cy < 430 or cy > 975:
        return "neutral"
    own = owner(cell)
    if own is None:
        return "neutral" if cy < 500 or cy > 920 else "home"
    return "central" if cell == own else "home"


SOURCES = {
    "home": Image.open(HOME_SOURCE).convert("RGB"),
    "central": Image.open(CENTRAL_SOURCE).convert("RGB"),
    "neutral": Image.open(NEUTRAL_SOURCE).convert("RGB"),
}


def deterministic_crop(source: Image.Image, cell: tuple[int, int], kind: str) -> Image.Image:
    """Create repeatable texture variation without changing the cell outline."""
    rng = random.Random((cell[0] + 40) * 1009 + (cell[1] + 40) * 9176 + len(kind) * 37)
    sw, sh = source.size
    crop_w = int(sw * (0.36 if kind == "home" else 0.40))
    crop_h = int(sh * 0.48)
    center_x = sw // 2 + rng.randint(-sw // 7, sw // 7)
    center_y = sh // 2 + rng.randint(-sh // 9, sh // 9)
    left = max(0, min(sw - crop_w, center_x - crop_w // 2))
    top = max(0, min(sh - crop_h, center_y - crop_h // 2))
    crop = source.crop((left, top, left + crop_w, top + crop_h))
    crop = ImageOps.fit(crop, (int(SIDE * 2 + 8), int(SQRT3 * SIDE + 8)), method=Image.Resampling.LANCZOS)
    if kind == "home":
        crop = ImageEnhance.Color(crop).enhance(0.92)
        crop = ImageEnhance.Brightness(crop).enhance(0.92)
    elif kind == "central":
        crop = ImageEnhance.Color(crop).enhance(0.78)
        crop = ImageEnhance.Contrast(crop).enhance(1.08)
    else:
        crop = ImageEnhance.Color(crop).enhance(0.82)
        crop = ImageEnhance.Brightness(crop).enhance(0.88)
    return crop


def radial_gradient(size: tuple[int, int], inner: tuple[int, int, int], outer: tuple[int, int, int]) -> Image.Image:
    w, h = size
    im = Image.new("RGB", size)
    px = im.load()
    cx, cy = w * 0.52, h * 0.22
    max_d = math.hypot(max(cx, w - cx), max(cy, h - cy))
    for y in range(h):
        for x in range(w):
            t = min(1.0, math.hypot(x - cx, y - cy) / max_d)
            t = t ** 0.78
            px[x, y] = tuple(int(inner[i] * (1 - t) + outer[i] * t) for i in range(3))
    return im


def draw_shell(base: Image.Image) -> None:
    d = ImageDraw.Draw(base, "RGBA")
    # Distant rigid backing: the curvature is expressed outside the gameplay grid.
    d.ellipse((-540, -690, W + 540, 610), fill=(18, 35, 50, 255), outline=(107, 150, 169, 150), width=16)
    d.ellipse((-400, -520, W + 400, 530), fill=(8, 21, 34, 255), outline=(82, 196, 214, 120), width=7)

    # Silus, seen along the ring radius.
    for radius, alpha in [(150, 18), (105, 35), (72, 80), (44, 220)]:
        d.ellipse((W / 2 - radius, 96 - radius, W / 2 + radius, 96 + radius), fill=(255, 235, 170, alpha))
    d.ellipse((W / 2 - 28, 68, W / 2 + 28, 124), fill=(255, 252, 225, 255))

    # Two transport rivers in the lips of the shell, kept outside the hex field.
    for y, width, alpha in [(247, 30, 170), (263, 9, 255), (1173, 38, 150), (1162, 10, 255)]:
        d.arc((-250, y - 210, W + 250, y + 220), 188, 352, fill=(82, 222, 255, alpha), width=width)
    d.arc((-280, 1044, W + 280, 1460), 188, 352, fill=(240, 246, 240, 100), width=18)

    # Structural shoulders make the playable band read as an inward recess.
    d.polygon([(0, 225), (W, 225), (W, 315), (0, 355)], fill=(22, 44, 55, 235))
    d.polygon([(0, 1110), (W, 1060), (W, 1250), (0, 1250)], fill=(10, 25, 36, 238))
    for y, alpha in [(303, 130), (320, 80), (1082, 105), (1110, 155)]:
        d.line((0, y, W, y), fill=(116, 196, 207, alpha), width=5)


def draw_cell(layer: Image.Image, cell: tuple[int, int], proof: bool = False) -> None:
    q, r = cell
    cx, cy = axial_to_px(q, r)
    pts = hex_points(cx, cy)
    kind = terrain_kind(cell)
    if proof:
        fill = {"home": (50, 108, 90, 255), "central": (50, 145, 182, 255), "neutral": (177, 126, 70, 255)}[kind]
        d = ImageDraw.Draw(layer, "RGBA")
        d.polygon(pts, fill=fill)
    else:
        bbox = (int(cx - SIDE - 4), int(cy - SQRT3 * SIDE / 2 - 4), int(cx + SIDE + 4), int(cy + SQRT3 * SIDE / 2 + 4))
        tile = deterministic_crop(SOURCES[kind], cell, kind).convert("RGBA")
        mask = Image.new("L", tile.size, 0)
        md = ImageDraw.Draw(mask)
        local_pts = [(x - bbox[0], y - bbox[1]) for x, y in pts]
        md.polygon(local_pts, fill=255)
        layer.alpha_composite(tile.putalpha(mask) or tile, dest=(bbox[0], bbox[1]))

    d = ImageDraw.Draw(layer, "RGBA")
    # One common border is drawn after the terrain. No terrain artwork defines topology.
    d.line(pts + [pts[0]], fill=(152, 204, 211, 220 if proof else 190), width=5, joint="curve")
    d.line(pts + [pts[0]], fill=(25, 50, 58, 210), width=2, joint="curve")

    if kind == "central":
        for vx, vy in pts:
            d.line((cx, cy, vx, vy), fill=(116, 221, 244, 210), width=4)
        d.ellipse((cx - 15, cy - 15, cx + 15, cy + 15), fill=(95, 225, 255, 235), outline=(225, 252, 255, 245), width=3)


def apply_concavity_lighting(layer: Image.Image) -> Image.Image:
    """Shade a bowl without deforming the exact lattice."""
    shade = Image.new("L", (W, H), 255)
    sp = shade.load()
    for y in range(GRID_TOP, GRID_BOTTOM + 1):
        # Centre is recessed and slightly darker; both lips catch the radial light.
        v = abs((y - (GRID_TOP + GRID_BOTTOM) / 2) / ((GRID_BOTTOM - GRID_TOP) / 2))
        level = int(178 + 72 * (v ** 1.55))
        for x in range(W):
            sp[x, y] = level
    shaded_rgb = ImageChops.multiply(layer.convert("RGB"), Image.merge("RGB", (shade, shade, shade)))
    shaded = shaded_rgb.convert("RGBA")
    shaded.putalpha(layer.getchannel("A"))
    return shaded


def draw_dome(base: Image.Image) -> None:
    dome = Image.new("RGBA", (W, H), (0, 0, 0, 0))
    d = ImageDraw.Draw(dome, "RGBA")
    box = (-230, 88, W + 230, 1565)
    d.arc(box, 186, 354, fill=(153, 229, 245, 125), width=8)
    d.arc((-170, 120, W + 170, 1510), 191, 349, fill=(224, 251, 255, 70), width=3)
    d.pieslice(box, 186, 354, fill=(106, 205, 232, 18))
    # Sparse dome ribs; they belong to the shell, not to the cell topology.
    for x in (300, 760, 1200, 1640, 2100):
        d.arc((x - 610, 130, x + 610, 1500), 215, 325, fill=(173, 229, 239, 32), width=3)
    base.alpha_composite(dome)


def edge_key(a: tuple[float, float], b: tuple[float, float]) -> tuple[tuple[int, int], tuple[int, int]]:
    pa = (round(a[0] * 1000), round(a[1] * 1000))
    pb = (round(b[0] * 1000), round(b[1] * 1000))
    return tuple(sorted((pa, pb)))  # type: ignore[return-value]


def geometry_report() -> dict[str, object]:
    lengths: list[float] = []
    edges: Counter[tuple[tuple[int, int], tuple[int, int]]] = Counter()
    vertices: Counter[tuple[int, int]] = Counter()
    for cell in CELLS:
        pts = hex_points(*axial_to_px(*cell))
        for i, point in enumerate(pts):
            nxt = pts[(i + 1) % 6]
            lengths.append(math.dist(point, nxt))
            edges[edge_key(point, nxt)] += 1
            vertices[(round(point[0] * 1000), round(point[1] * 1000))] += 1
    return {
        "version": "v07",
        "projection": "orthographic flat-top axial grid",
        "cell_count": len(CELLS),
        "complex_centres_visible": len(CENTRES),
        "hex_side_px": SIDE,
        "edge_length_min_px": min(lengths),
        "edge_length_max_px": max(lengths),
        "edge_length_spread_px": max(lengths) - min(lengths),
        "shared_edge_multiplicity": dict(sorted(Counter(edges.values()).items())),
        "max_cells_at_vertex": max(vertices.values()),
        "all_cells_regular": max(lengths) - min(lengths) < 1e-9,
        "no_edge_shared_by_more_than_two_cells": max(edges.values()) <= 2,
        "no_vertex_shared_by_more_than_three_cells": max(vertices.values()) <= 3,
    }


def render() -> None:
    WORLD_DIR.mkdir(parents=True, exist_ok=True)

    base = radial_gradient((W, H), (20, 53, 70), (2, 10, 20)).convert("RGBA")
    draw_shell(base)

    # Soft contact shadow under the complete gameplay lattice.
    shadow = Image.new("RGBA", (W, H), (0, 0, 0, 0))
    sd = ImageDraw.Draw(shadow, "RGBA")
    for cell in CELLS:
        pts = [(x + 9, y + 15) for x, y in hex_points(*axial_to_px(*cell))]
        sd.polygon(pts, fill=(0, 0, 0, 95))
    shadow = shadow.filter(ImageFilter.GaussianBlur(18))
    base.alpha_composite(shadow)

    grid = Image.new("RGBA", (W, H), (0, 0, 0, 0))
    for cell in CELLS:
        draw_cell(grid, cell)
    grid = apply_concavity_lighting(grid)
    base.alpha_composite(grid)

    # Reassert exact geometry after lighting so the lattice stays readable.
    outlines = Image.new("RGBA", (W, H), (0, 0, 0, 0))
    od = ImageDraw.Draw(outlines, "RGBA")
    for cell in CELLS:
        pts = hex_points(*axial_to_px(*cell))
        od.line(pts + [pts[0]], fill=(135, 196, 205, 190), width=4, joint="curve")
        if terrain_kind(cell) == "central":
            cx, cy = axial_to_px(*cell)
            for vx, vy in pts:
                od.line((cx, cy, vx, vy), fill=(104, 212, 239, 185), width=3)
    base.alpha_composite(outlines)
    draw_dome(base)

    # Vignette keeps the centre readable while leaving the exact grid untouched.
    vignette = Image.new("L", (W, H), 0)
    vp = vignette.load()
    for y in range(H):
        for x in range(W):
            nx = abs(x - W / 2) / (W / 2)
            ny = abs(y - H / 2) / (H / 2)
            vp[x, y] = int(max(0, min(115, 115 * (max(nx, ny) ** 2.8))))
    black = Image.new("RGBA", (W, H), (0, 2, 8, 255))
    black.putalpha(vignette)
    base.alpha_composite(black)
    base.convert("RGB").save(OUT_ART, quality=95, optimize=True)

    proof = Image.new("RGBA", (W, H), (5, 20, 29, 255))
    pd = ImageDraw.Draw(proof, "RGBA")
    pd.rectangle((0, GRID_TOP - 30, W, GRID_BOTTOM + 30), fill=(11, 37, 48, 255))
    for cell in CELLS:
        draw_cell(proof, cell, proof=True)
    # Draw exact circumcircles for several representative cells.
    for cell in CENTRES[:4]:
        cx, cy = axial_to_px(*cell)
        pd.ellipse((cx - SIDE, cy - SIDE, cx + SIDE, cy + SIDE), outline=(255, 221, 112, 220), width=3)
    proof.convert("RGB").save(OUT_PROOF, optimize=True)

    report = geometry_report()
    assert report["all_cells_regular"]
    assert report["no_edge_shared_by_more_than_two_cells"]
    assert report["no_vertex_shared_by_more_than_three_cells"]
    assert report["complex_centres_visible"] == 3
    OUT_REPORT.write_text(json.dumps(report, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    print(json.dumps(report, ensure_ascii=False, indent=2))
    print(OUT_ART)
    print(OUT_PROOF)


if __name__ == "__main__":
    render()

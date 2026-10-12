from __future__ import annotations

import json
import math
import random
from collections import Counter, defaultdict
from pathlib import Path

from PIL import Image, ImageChops, ImageDraw, ImageEnhance, ImageFilter, ImageOps


ROOT = Path(__file__).resolve().parents[1]
WORLD_DIR = ROOT / "concept-art" / "world-structure"
TERRAIN_DIR = ROOT / "concept-art" / "terrain-prototypes"

OUT_ART = WORLD_DIR / "ring-segment-honeycomb-v08-repeating-complexes.png"
OUT_PROOF = WORLD_DIR / "ring-segment-honeycomb-v08-tiling-proof.png"
OUT_REPORT = WORLD_DIR / "ring-segment-honeycomb-v08-geometry.json"

W, H = 2400, 1400
SQRT3 = math.sqrt(3)

# The accepted 13-cell module from V07: one shared centre, six homes and
# six outer neutrals. Offsets are axial coordinates relative to the centre.
CENTRAL = {(0, 0)}
HOMES = {(1, 0), (1, -1), (0, -1), (-1, 0), (-1, 1), (0, 1)}
NEUTRALS = {(-1, -1), (0, -2), (1, -2), (-1, 2), (0, 2), (1, 1)}
MOTIF = CENTRAL | HOMES | NEUTRALS

# This sublattice has determinant 13. MOTIF contains one representative of
# every residue q + 3r (mod 13), so translations tile the axial grid exactly.
BASIS_A = (3, -1)
BASIS_B = (1, 4)

# Ten complete, identical complexes in two staggered rows. This is enough to
# prove two-dimensional repetition while keeping individual hives readable.
CENTRES = [
    (i * BASIS_A[0] + j * BASIS_B[0], i * BASIS_A[1] + j * BASIS_B[1])
    for j in range(0, 2)
    for i in range(-2, 3)
]

HOME_SOURCE = Image.open(TERRAIN_DIR / "home-hive-landscape-v03-honeycomb.png").convert("RGB")
CENTRAL_SOURCE = Image.open(TERRAIN_DIR / "central-complex-landscape-v02-honeycomb.png").convert("RGB")
NEUTRAL_SOURCE = Image.open(TERRAIN_DIR / "neutral-hive-landscape-v02-honeycomb.png").convert("RGB")
SOURCES = {"central": CENTRAL_SOURCE, "home": HOME_SOURCE, "neutral": NEUTRAL_SOURCE}


def role(offset: tuple[int, int]) -> str:
    if offset in CENTRAL:
        return "central"
    if offset in HOMES:
        return "home"
    if offset in NEUTRALS:
        return "neutral"
    raise ValueError(offset)


CELL_OWNER: dict[tuple[int, int], tuple[int, int]] = {}
CELL_ROLE: dict[tuple[int, int], str] = {}
for centre in CENTRES:
    for offset in MOTIF:
        cell = (centre[0] + offset[0], centre[1] + offset[1])
        if cell in CELL_OWNER:
            raise RuntimeError(f"complex overlap at {cell}: {CELL_OWNER[cell]} and {centre}")
        CELL_OWNER[cell] = centre
        CELL_ROLE[cell] = role(offset)

CELLS = sorted(CELL_OWNER)


def axial_unit(q: int, r: int) -> tuple[float, float]:
    return 1.5 * q, SQRT3 * (r + q / 2)


def unit_hex(cx: float, cy: float) -> list[tuple[float, float]]:
    return [
        (cx + math.cos(math.radians(60 * i)), cy + math.sin(math.radians(60 * i)))
        for i in range(6)
    ]


# Fit the complete 15-complex patch without clipping any cell.
all_unit_points: list[tuple[float, float]] = []
for q, r in CELLS:
    all_unit_points.extend(unit_hex(*axial_unit(q, r)))
min_ux = min(x for x, _ in all_unit_points)
max_ux = max(x for x, _ in all_unit_points)
min_uy = min(y for _, y in all_unit_points)
max_uy = max(y for _, y in all_unit_points)
SIDE = min((W - 150) / (max_ux - min_ux), 850 / (max_uy - min_uy))
OX = W / 2 - SIDE * (min_ux + max_ux) / 2
OY = 715 - SIDE * (min_uy + max_uy) / 2


def axial_to_px(q: int, r: int) -> tuple[float, float]:
    ux, uy = axial_unit(q, r)
    return OX + SIDE * ux, OY + SIDE * uy


def hex_points(cell: tuple[int, int]) -> list[tuple[float, float]]:
    cx, cy = axial_to_px(*cell)
    return [
        (cx + SIDE * math.cos(math.radians(60 * i)), cy + SIDE * math.sin(math.radians(60 * i)))
        for i in range(6)
    ]


def edge_key(a: tuple[float, float], b: tuple[float, float]) -> tuple[tuple[int, int], tuple[int, int]]:
    pa = (round(a[0] * 1000), round(a[1] * 1000))
    pb = (round(b[0] * 1000), round(b[1] * 1000))
    return tuple(sorted((pa, pb)))  # type: ignore[return-value]


def texture(source: Image.Image, cell: tuple[int, int], kind: str) -> Image.Image:
    rng = random.Random((cell[0] + 80) * 1597 + (cell[1] + 80) * 7919 + len(kind) * 101)
    sw, sh = source.size
    cw, ch = int(sw * 0.34), int(sh * 0.44)
    cx = sw // 2 + rng.randint(-sw // 6, sw // 6)
    cy = sh // 2 + rng.randint(-sh // 10, sh // 10)
    left = max(0, min(sw - cw, cx - cw // 2))
    top = max(0, min(sh - ch, cy - ch // 2))
    crop = source.crop((left, top, left + cw, top + ch))
    crop = ImageOps.fit(
        crop,
        (math.ceil(2 * SIDE + 8), math.ceil(SQRT3 * SIDE + 8)),
        method=Image.Resampling.LANCZOS,
    )
    if kind == "home":
        crop = ImageEnhance.Color(crop).enhance(0.90)
        crop = ImageEnhance.Brightness(crop).enhance(0.90)
    elif kind == "central":
        crop = ImageEnhance.Color(crop).enhance(0.75)
        crop = ImageEnhance.Contrast(crop).enhance(1.10)
    else:
        crop = ImageEnhance.Color(crop).enhance(0.78)
        crop = ImageEnhance.Brightness(crop).enhance(0.86)
    return crop.convert("RGBA")


def radial_background() -> Image.Image:
    im = Image.new("RGB", (W, H))
    px = im.load()
    for y in range(H):
        for x in range(W):
            nx = (x - W / 2) / (W / 2)
            ny = (y - 260) / H
            t = min(1.0, math.sqrt(nx * nx + ny * ny))
            px[x, y] = (
                int(18 * (1 - t) + 2 * t),
                int(48 * (1 - t) + 10 * t),
                int(64 * (1 - t) + 20 * t),
            )
    return im.convert("RGBA")


def draw_shell(base: Image.Image) -> None:
    d = ImageDraw.Draw(base, "RGBA")
    # Far rigid wall and Silus.
    d.ellipse((-560, -700, W + 560, 610), fill=(14, 30, 44, 255), outline=(100, 154, 174, 120), width=13)
    d.ellipse((-390, -520, W + 390, 520), fill=(6, 18, 31, 255), outline=(72, 198, 222, 95), width=6)
    for radius, alpha in [(150, 20), (102, 42), (67, 90), (35, 235)]:
        d.ellipse((W / 2 - radius, 102 - radius, W / 2 + radius, 102 + radius), fill=(255, 233, 164, alpha))

    # Two visible transport rivers. They never define cell edges.
    for y, width, alpha in [(250, 30, 150), (263, 8, 245), (1164, 34, 145), (1152, 9, 245)]:
        d.arc((-280, y - 210, W + 280, y + 220), 188, 352, fill=(73, 218, 250, alpha), width=width)

    # Back and front lips of the inward recess.
    d.polygon([(0, 224), (W, 224), (W, 330), (0, 360)], fill=(24, 46, 58, 225))
    d.polygon([(0, 1085), (W, 1045), (W, 1248), (0, 1248)], fill=(8, 23, 34, 238))
    d.line((0, 328, W, 300), fill=(137, 209, 218, 120), width=5)
    d.line((0, 1100, W, 1060), fill=(137, 209, 218, 120), width=5)


def paint_cells(proof: bool = False) -> Image.Image:
    layer = Image.new("RGBA", (W, H), (0, 0, 0, 0))
    draw = ImageDraw.Draw(layer, "RGBA")
    proof_colours = {"central": (55, 151, 188, 255), "home": (57, 113, 92, 255), "neutral": (188, 137, 77, 255)}
    for cell in sorted(CELLS, key=lambda c: (axial_to_px(*c)[1], axial_to_px(*c)[0])):
        points = hex_points(cell)
        kind = CELL_ROLE[cell]
        if proof:
            draw.polygon(points, fill=proof_colours[kind])
        else:
            cx, cy = axial_to_px(*cell)
            bbox = (
                math.floor(cx - SIDE - 4),
                math.floor(cy - SQRT3 * SIDE / 2 - 4),
                math.ceil(cx + SIDE + 4),
                math.ceil(cy + SQRT3 * SIDE / 2 + 4),
            )
            tile = texture(SOURCES[kind], cell, kind)
            tile = ImageOps.fit(tile, (bbox[2] - bbox[0], bbox[3] - bbox[1]), method=Image.Resampling.LANCZOS)
            mask = Image.new("L", tile.size, 0)
            md = ImageDraw.Draw(mask)
            md.polygon([(x - bbox[0], y - bbox[1]) for x, y in points], fill=255)
            tile.putalpha(mask)
            layer.alpha_composite(tile, dest=(bbox[0], bbox[1]))

        if kind == "central":
            cx, cy = axial_to_px(*cell)
            for vx, vy in points:
                draw.line((cx, cy, vx, vy), fill=(117, 226, 247, 210), width=3)
            draw.ellipse((cx - 10, cy - 10, cx + 10, cy + 10), fill=(102, 232, 255, 235))

    # Draw every common edge once. Complex boundaries are visible only on proof.
    edge_cells: defaultdict[tuple[tuple[int, int], tuple[int, int]], list[tuple[int, int]]] = defaultdict(list)
    edge_geom: dict[tuple[tuple[int, int], tuple[int, int]], tuple[tuple[float, float], tuple[float, float]]] = {}
    for cell in CELLS:
        points = hex_points(cell)
        for idx, point in enumerate(points):
            nxt = points[(idx + 1) % 6]
            key = edge_key(point, nxt)
            edge_cells[key].append(cell)
            edge_geom[key] = (point, nxt)
    for key, cells in edge_cells.items():
        a, b = edge_geom[key]
        cross_complex = len(cells) == 2 and CELL_OWNER[cells[0]] != CELL_OWNER[cells[1]]
        colour = (249, 215, 111, 230) if proof and cross_complex else (139, 202, 211, 205)
        width = 4 if proof and cross_complex else (3 if proof else 2)
        draw.line((a, b), fill=colour, width=width)
    return layer


def apply_bowl_light(layer: Image.Image) -> Image.Image:
    shade = Image.new("L", (W, H), 255)
    p = shade.load()
    for y in range(250, 1190):
        v = abs((y - 720) / 470)
        level = int(max(166, min(248, 172 + 72 * (v ** 1.6))))
        for x in range(W):
            p[x, y] = level
    rgb = ImageChops.multiply(layer.convert("RGB"), Image.merge("RGB", (shade, shade, shade)))
    out = rgb.convert("RGBA")
    out.putalpha(layer.getchannel("A"))
    return out


def draw_dome(base: Image.Image) -> None:
    dome = Image.new("RGBA", (W, H), (0, 0, 0, 0))
    d = ImageDraw.Draw(dome, "RGBA")
    box = (-240, 86, W + 240, 1570)
    d.arc(box, 186, 354, fill=(164, 232, 245, 120), width=8)
    d.arc((-160, 125, W + 160, 1510), 191, 349, fill=(231, 253, 255, 65), width=3)
    base.alpha_composite(dome)


def geometry_report() -> dict[str, object]:
    lengths: list[float] = []
    edges: Counter[tuple[tuple[int, int], tuple[int, int]]] = Counter()
    vertices: Counter[tuple[int, int]] = Counter()
    per_complex: dict[str, Counter[str]] = {}
    for centre in CENTRES:
        counts: Counter[str] = Counter()
        for offset in MOTIF:
            counts[role(offset)] += 1
        per_complex[f"{centre[0]},{centre[1]}"] = counts
    for cell in CELLS:
        points = hex_points(cell)
        for idx, point in enumerate(points):
            nxt = points[(idx + 1) % 6]
            lengths.append(math.dist(point, nxt))
            edges[edge_key(point, nxt)] += 1
            vertices[(round(point[0] * 1000), round(point[1] * 1000))] += 1
    unique_patterns = {tuple(sorted(counts.items())) for counts in per_complex.values()}
    residues = {(q + 3 * r) % 13 for q, r in MOTIF}
    return {
        "version": "v08",
        "tiling_rule": "q + 3r mod 13",
        "translation_basis": [BASIS_A, BASIS_B],
        "translation_determinant": abs(BASIS_A[0] * BASIS_B[1] - BASIS_A[1] * BASIS_B[0]),
        "motif_residue_count": len(residues),
        "motif_has_all_residues": residues == set(range(13)),
        "complex_count": len(CENTRES),
        "cell_count": len(CELLS),
        "cells_per_complex": len(MOTIF),
        "roles_per_complex": dict(next(iter(per_complex.values()))),
        "unique_role_patterns": len(unique_patterns),
        "hex_side_px": SIDE,
        "edge_length_spread_px": max(lengths) - min(lengths),
        "shared_edge_multiplicity": dict(sorted(Counter(edges.values()).items())),
        "max_cells_at_vertex": max(vertices.values()),
        "no_cell_overlap": len(CELLS) == len(CENTRES) * len(MOTIF),
        "all_complexes_identical": len(unique_patterns) == 1,
        "all_cells_regular": max(lengths) - min(lengths) < 1e-9,
        "no_edge_shared_by_more_than_two_cells": max(edges.values()) <= 2,
        "no_vertex_shared_by_more_than_three_cells": max(vertices.values()) <= 3,
    }


def render() -> None:
    WORLD_DIR.mkdir(parents=True, exist_ok=True)
    base = radial_background()
    draw_shell(base)

    shadow = Image.new("RGBA", (W, H), (0, 0, 0, 0))
    sd = ImageDraw.Draw(shadow, "RGBA")
    for cell in CELLS:
        sd.polygon([(x + 7, y + 12) for x, y in hex_points(cell)], fill=(0, 0, 0, 75))
    base.alpha_composite(shadow.filter(ImageFilter.GaussianBlur(14)))
    base.alpha_composite(apply_bowl_light(paint_cells(False)))
    draw_dome(base)
    base.convert("RGB").save(OUT_ART, optimize=True)

    proof = Image.new("RGBA", (W, H), (4, 17, 26, 255))
    pd = ImageDraw.Draw(proof, "RGBA")
    pd.rectangle((0, 220, W, 1210), fill=(11, 38, 49, 255))
    proof.alpha_composite(paint_cells(True))
    for index, centre in enumerate(CENTRES, start=1):
        cx, cy = axial_to_px(*centre)
        pd.ellipse((cx - SIDE, cy - SIDE, cx + SIDE, cy + SIDE), outline=(255, 226, 123, 215), width=2)
        pd.text((cx - 9, cy - 13), str(index), fill=(255, 255, 245, 240), stroke_width=2, stroke_fill=(15, 43, 54, 255))
    proof.convert("RGB").save(OUT_PROOF, optimize=True)

    report = geometry_report()
    assert report["translation_determinant"] == 13
    assert report["motif_has_all_residues"]
    assert report["no_cell_overlap"]
    assert report["all_complexes_identical"]
    assert report["all_cells_regular"]
    assert report["no_edge_shared_by_more_than_two_cells"]
    assert report["no_vertex_shared_by_more_than_three_cells"]
    OUT_REPORT.write_text(json.dumps(report, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    print(json.dumps(report, ensure_ascii=False, indent=2))
    print(OUT_ART)
    print(OUT_PROOF)


if __name__ == "__main__":
    render()

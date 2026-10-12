from __future__ import annotations

import json
import math
import random
from collections import Counter, defaultdict, deque
from pathlib import Path

from PIL import Image, ImageChops, ImageDraw, ImageEnhance, ImageFilter, ImageOps


ROOT = Path(__file__).resolve().parents[1]
WORLD_DIR = ROOT / "concept-art" / "world-structure"
TERRAIN_DIR = ROOT / "concept-art" / "terrain-prototypes"

OUT_ART = WORLD_DIR / "ring-single-row-v09-standard-world.png"
OUT_PROOF = WORLD_DIR / "ring-single-row-v09-tiling-proof.png"
OUT_REPORT = WORLD_DIR / "ring-single-row-v09-geometry.json"

W, H = 2400, 1400
SQRT3 = math.sqrt(3)

CENTRAL = {(0, 0)}
HOMES = {(1, 0), (1, -1), (0, -1), (-1, 0), (-1, 1), (0, 1)}
NEUTRALS = {(-1, -1), (0, -2), (1, -2), (-1, 2), (0, 2), (1, 1)}
MOTIF = CENTRAL | HOMES | NEUTRALS
STEP = (3, -1)

# Nine complete modules demonstrate the ordinary world strip. The same step is
# repeated until the first and last module are identified at the ring seam.
CENTRES = [(i * STEP[0], i * STEP[1]) for i in range(-4, 5)]

SOURCES = {
    "home": Image.open(TERRAIN_DIR / "home-hive-landscape-v03-honeycomb.png").convert("RGB"),
    "central": Image.open(TERRAIN_DIR / "central-complex-landscape-v02-honeycomb.png").convert("RGB"),
    "neutral": Image.open(TERRAIN_DIR / "neutral-hive-landscape-v02-honeycomb.png").convert("RGB"),
}


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
            raise RuntimeError(f"complex overlap at {cell}")
        CELL_OWNER[cell] = centre
        CELL_ROLE[cell] = role(offset)
CELLS = sorted(CELL_OWNER)


def raw_axial(q: int, r: int) -> tuple[float, float]:
    return 1.5 * q, SQRT3 * (r + q / 2)


step_x, step_y = raw_axial(*STEP)
ROTATION = -math.atan2(step_y, step_x)


def rotated_unit(q: int, r: int) -> tuple[float, float]:
    x, y = raw_axial(q, r)
    cs, sn = math.cos(ROTATION), math.sin(ROTATION)
    return x * cs - y * sn, x * sn + y * cs


def rotated_hex(q: int, r: int) -> list[tuple[float, float]]:
    cx, cy = rotated_unit(q, r)
    points = []
    for index in range(6):
        angle = math.radians(60 * index) + ROTATION
        points.append((cx + math.cos(angle), cy + math.sin(angle)))
    return points


unit_points = [point for cell in CELLS for point in rotated_hex(*cell)]
min_ux = min(x for x, _ in unit_points)
max_ux = max(x for x, _ in unit_points)
min_uy = min(y for _, y in unit_points)
max_uy = max(y for _, y in unit_points)
SIDE = min((W - 120) / (max_ux - min_ux), 690 / (max_uy - min_uy))
OX = W / 2 - SIDE * (min_ux + max_ux) / 2
OY = 710 - SIDE * (min_uy + max_uy) / 2


def centre_px(cell: tuple[int, int]) -> tuple[float, float]:
    x, y = rotated_unit(*cell)
    return OX + SIDE * x, OY + SIDE * y


def hex_points(cell: tuple[int, int]) -> list[tuple[float, float]]:
    return [(OX + SIDE * x, OY + SIDE * y) for x, y in rotated_hex(*cell)]


def edge_key(a: tuple[float, float], b: tuple[float, float]) -> tuple[tuple[int, int], tuple[int, int]]:
    pa = (round(a[0] * 1000), round(a[1] * 1000))
    pb = (round(b[0] * 1000), round(b[1] * 1000))
    return tuple(sorted((pa, pb)))  # type: ignore[return-value]


def tile_texture(cell: tuple[int, int], kind: str) -> Image.Image:
    source = SOURCES[kind]
    rng = random.Random((cell[0] + 100) * 2017 + (cell[1] + 100) * 8101 + len(kind) * 97)
    sw, sh = source.size
    cw, ch = int(sw * 0.34), int(sh * 0.44)
    cx = sw // 2 + rng.randint(-sw // 6, sw // 6)
    cy = sh // 2 + rng.randint(-sh // 10, sh // 10)
    left = max(0, min(sw - cw, cx - cw // 2))
    top = max(0, min(sh - ch, cy - ch // 2))
    crop = source.crop((left, top, left + cw, top + ch))
    crop = ImageOps.fit(crop, (math.ceil(2.4 * SIDE), math.ceil(2.4 * SIDE)), method=Image.Resampling.LANCZOS)
    if kind == "home":
        crop = ImageEnhance.Brightness(ImageEnhance.Color(crop).enhance(0.90)).enhance(0.91)
    elif kind == "central":
        crop = ImageEnhance.Contrast(ImageEnhance.Color(crop).enhance(0.72)).enhance(1.10)
    else:
        crop = ImageEnhance.Brightness(ImageEnhance.Color(crop).enhance(0.78)).enhance(0.87)
    return crop.convert("RGBA")


def background() -> Image.Image:
    im = Image.new("RGB", (W, H))
    px = im.load()
    for y in range(H):
        for x in range(W):
            nx = abs(x - W / 2) / (W / 2)
            ny = abs(y - 630) / H
            t = min(1.0, math.sqrt(nx * nx + ny * ny))
            px[x, y] = (int(17 - 13 * t), int(46 - 34 * t), int(62 - 39 * t))
    return im.convert("RGBA")


def draw_world_shell(base: Image.Image) -> None:
    d = ImageDraw.Draw(base, "RGBA")
    d.ellipse((-520, -720, W + 520, 600), fill=(9, 24, 38, 245), outline=(116, 174, 193, 105), width=11)
    for radius, alpha in [(145, 22), (98, 45), (62, 100), (34, 235)]:
        d.ellipse((W / 2 - radius, 95 - radius, W / 2 + radius, 95 + radius), fill=(255, 234, 166, alpha))

    # The two rivers follow the long edges of the single gameplay strip.
    for y, width, alpha in [(294, 34, 150), (307, 9, 250), (1123, 34, 145), (1111, 9, 250)]:
        d.arc((-260, y - 190, W + 260, y + 205), 188, 352, fill=(69, 220, 252, alpha), width=width)

    # Non-playable shoulders make the strip look recessed into the ring shell.
    d.polygon([(0, 246), (W, 246), (W, 350), (0, 377)], fill=(26, 49, 61, 225))
    d.polygon([(0, 1062), (W, 1032), (W, 1228), (0, 1228)], fill=(8, 24, 35, 240))
    d.line((0, 347, W, 320), fill=(132, 206, 218, 125), width=5)
    d.line((0, 1082, W, 1050), fill=(132, 206, 218, 125), width=5)


def draw_cells(proof: bool) -> Image.Image:
    layer = Image.new("RGBA", (W, H), (0, 0, 0, 0))
    draw = ImageDraw.Draw(layer, "RGBA")
    colours = {"central": (54, 151, 189, 255), "home": (56, 113, 92, 255), "neutral": (190, 139, 78, 255)}

    for cell in sorted(CELLS, key=lambda item: (centre_px(item)[1], centre_px(item)[0])):
        points = hex_points(cell)
        kind = CELL_ROLE[cell]
        if proof:
            draw.polygon(points, fill=colours[kind])
        else:
            min_x = math.floor(min(x for x, _ in points)) - 3
            max_x = math.ceil(max(x for x, _ in points)) + 3
            min_y = math.floor(min(y for _, y in points)) - 3
            max_y = math.ceil(max(y for _, y in points)) + 3
            tile = ImageOps.fit(tile_texture(cell, kind), (max_x - min_x, max_y - min_y), method=Image.Resampling.LANCZOS)
            mask = Image.new("L", tile.size, 0)
            md = ImageDraw.Draw(mask)
            md.polygon([(x - min_x, y - min_y) for x, y in points], fill=255)
            tile.putalpha(mask)
            layer.alpha_composite(tile, dest=(min_x, min_y))

        if kind == "central":
            cx, cy = centre_px(cell)
            for vx, vy in points:
                draw.line((cx, cy, vx, vy), fill=(116, 226, 247, 215), width=3)
            draw.ellipse((cx - 10, cy - 10, cx + 10, cy + 10), fill=(104, 232, 255, 240))

    edges: defaultdict[tuple[tuple[int, int], tuple[int, int]], list[tuple[int, int]]] = defaultdict(list)
    geometry: dict[tuple[tuple[int, int], tuple[int, int]], tuple[tuple[float, float], tuple[float, float]]] = {}
    for cell in CELLS:
        points = hex_points(cell)
        for index, point in enumerate(points):
            nxt = points[(index + 1) % 6]
            key = edge_key(point, nxt)
            edges[key].append(cell)
            geometry[key] = point, nxt
    for key, adjacent in edges.items():
        a, b = geometry[key]
        complex_boundary = len(adjacent) == 2 and CELL_OWNER[adjacent[0]] != CELL_OWNER[adjacent[1]]
        colour = (251, 220, 112, 235) if proof and complex_boundary else (139, 204, 213, 210)
        draw.line((a, b), fill=colour, width=4 if proof and complex_boundary else 2)
    return layer


def bowl_light(layer: Image.Image) -> Image.Image:
    shade = Image.new("L", (W, H), 255)
    p = shade.load()
    for y in range(260, 1170):
        v = abs((y - 710) / 455)
        level = int(max(168, min(248, 175 + 70 * (v ** 1.6))))
        for x in range(W):
            p[x, y] = level
    rgb = ImageChops.multiply(layer.convert("RGB"), Image.merge("RGB", (shade, shade, shade)))
    out = rgb.convert("RGBA")
    out.putalpha(layer.getchannel("A"))
    return out


def draw_dome(base: Image.Image) -> None:
    dome = Image.new("RGBA", (W, H), (0, 0, 0, 0))
    d = ImageDraw.Draw(dome, "RGBA")
    d.arc((-240, 84, W + 240, 1565), 186, 354, fill=(164, 232, 245, 115), width=8)
    d.arc((-155, 126, W + 155, 1500), 191, 349, fill=(230, 252, 255, 62), width=3)
    base.alpha_composite(dome)


def axial_neighbours(cell: tuple[int, int]) -> list[tuple[int, int]]:
    q, r = cell
    return [(q + dq, r + dr) for dq, dr in HOMES]


def connected() -> bool:
    seen = {CELLS[0]}
    queue = deque([CELLS[0]])
    cells = set(CELLS)
    while queue:
        current = queue.popleft()
        for nxt in axial_neighbours(current):
            if nxt in cells and nxt not in seen:
                seen.add(nxt)
                queue.append(nxt)
    return len(seen) == len(cells)


def geometry_report() -> dict[str, object]:
    lengths: list[float] = []
    edges: Counter[tuple[tuple[int, int], tuple[int, int]]] = Counter()
    vertices: Counter[tuple[int, int]] = Counter()
    for cell in CELLS:
        points = hex_points(cell)
        for index, point in enumerate(points):
            nxt = points[(index + 1) % 6]
            lengths.append(math.dist(point, nxt))
            edges[edge_key(point, nxt)] += 1
            vertices[(round(point[0] * 1000), round(point[1] * 1000))] += 1
    return {
        "version": "v09",
        "layout": "single repeating row for standard worlds",
        "step": STEP,
        "screen_rotation_deg": math.degrees(ROTATION),
        "complex_count": len(CENTRES),
        "cell_count": len(CELLS),
        "cells_per_complex": len(MOTIF),
        "roles_per_complex": {"central": 1, "home": 6, "neutral": 6},
        "hex_side_px": SIDE,
        "edge_length_spread_px": max(lengths) - min(lengths),
        "shared_edge_multiplicity": dict(sorted(Counter(edges.values()).items())),
        "max_cells_at_vertex": max(vertices.values()),
        "no_cell_overlap": len(CELLS) == len(CENTRES) * len(MOTIF),
        "strip_connected": connected(),
        "all_cells_regular": max(lengths) - min(lengths) < 1e-9,
        "no_edge_shared_by_more_than_two_cells": max(edges.values()) <= 2,
        "no_vertex_shared_by_more_than_three_cells": max(vertices.values()) <= 3,
    }


def render() -> None:
    WORLD_DIR.mkdir(parents=True, exist_ok=True)
    base = background()
    draw_world_shell(base)

    shadow = Image.new("RGBA", (W, H), (0, 0, 0, 0))
    sd = ImageDraw.Draw(shadow, "RGBA")
    for cell in CELLS:
        sd.polygon([(x + 7, y + 12) for x, y in hex_points(cell)], fill=(0, 0, 0, 78))
    base.alpha_composite(shadow.filter(ImageFilter.GaussianBlur(15)))
    base.alpha_composite(bowl_light(draw_cells(False)))
    draw_dome(base)
    base.convert("RGB").save(OUT_ART, optimize=True)

    proof = Image.new("RGBA", (W, H), (4, 18, 27, 255))
    pd = ImageDraw.Draw(proof, "RGBA")
    pd.rectangle((0, 250, W, 1170), fill=(11, 39, 50, 255))
    proof.alpha_composite(draw_cells(True))
    for index, centre in enumerate(CENTRES, start=1):
        cx, cy = centre_px(centre)
        pd.ellipse((cx - SIDE, cy - SIDE, cx + SIDE, cy + SIDE), outline=(255, 228, 124, 215), width=2)
        pd.text((cx - 9, cy - 13), str(index), fill=(255, 255, 244, 245), stroke_width=2, stroke_fill=(14, 42, 53, 255))
    proof.convert("RGB").save(OUT_PROOF, optimize=True)

    report = geometry_report()
    assert report["no_cell_overlap"]
    assert report["strip_connected"]
    assert report["all_cells_regular"]
    assert report["no_edge_shared_by_more_than_two_cells"]
    assert report["no_vertex_shared_by_more_than_three_cells"]
    OUT_REPORT.write_text(json.dumps(report, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    print(json.dumps(report, ensure_ascii=False, indent=2))
    print(OUT_ART)
    print(OUT_PROOF)


if __name__ == "__main__":
    render()

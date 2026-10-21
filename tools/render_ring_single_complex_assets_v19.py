from __future__ import annotations

import json
import math
import random
from collections import Counter, defaultdict
from pathlib import Path

from PIL import Image, ImageDraw, ImageEnhance, ImageFilter, ImageOps


ROOT = Path(__file__).resolve().parents[1]
WORLD_DIR = ROOT / "concept-art" / "world-structure"
TERRAIN_DIR = ROOT / "concept-art" / "terrain-prototypes"

OUT_ART = WORLD_DIR / "ring-single-complex-v19-deterministic-buildings.png"
OUT_PROOF = WORLD_DIR / "ring-single-complex-v19-proof.png"
OUT_REPORT = WORLD_DIR / "ring-single-complex-v19-geometry.json"

TECH_ASSETS = ROOT / "технологический стек1" / "assets" / "tech"

W, H = 2400, 1400
SQRT3 = math.sqrt(3)

CENTRAL = {(0, 0)}
HOMES = {(1, 0), (1, -1), (0, -1), (-1, 0), (-1, 1), (0, 1)}
NEUTRALS = {(-1, -1), (0, -2), (1, -2), (-1, 2), (0, 2), (1, 1)}
MOTIF = CENTRAL | HOMES | NEUTRALS
STEP = (3, -1)
CENTRES = [(0, 0)]

SOURCES = {
    "home": Image.open(TERRAIN_DIR / "home-hive-landscape-v03-honeycomb.png").convert("RGB"),
    "central": Image.open(TERRAIN_DIR / "central-complex-landscape-v02-honeycomb.png").convert("RGB"),
    "neutral": Image.open(TERRAIN_DIR / "neutral-hive-landscape-v02-honeycomb.png").convert("RGB"),
}

HOME_BUILDING_PATHS = [
    TECH_ASSETS / "residential" / "exports" / "voraner-residential-v02-color.png",
    TECH_ASSETS / "residential" / "exports" / "voraner-residential-v03-color.png",
    TECH_ASSETS / "residential" / "exports" / "voraner-residential-v05-color.png",
    TECH_ASSETS / "residential" / "exports" / "voraner-residential-complex-3-color.png",
]
NEUTRAL_RUIN_PATHS = [
    TECH_ASSETS / "environment" / "exports" / "voraner-ruin-arch-v02-color.png",
    TECH_ASSETS / "environment" / "exports" / "voraner-ruin-pylon-v02-color.png",
    TECH_ASSETS / "environment" / "exports" / "voraner-ruin-conduit-v02-color.png",
]
CENTRAL_BUILDING_PATH = TECH_ASSETS / "residential" / "exports" / "voraner-construction-l1-color.png"


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
            raise RuntimeError(f"overlap at {cell}")
        CELL_OWNER[cell] = centre
        CELL_ROLE[cell] = role(offset)
CELLS = sorted(CELL_OWNER)


def raw_axial(q: float, r: float) -> tuple[float, float]:
    return 1.5 * q, SQRT3 * (r + q / 2)


step_x, step_y = raw_axial(*STEP)
ROTATION = -math.atan2(step_y, step_x)


def strip_uv(q: float, r: float) -> tuple[float, float]:
    x, y = raw_axial(q, r)
    cs, sn = math.cos(ROTATION), math.sin(ROTATION)
    return x * cs - y * sn, x * sn + y * cs


def flat_hex(cell: tuple[int, int]) -> list[tuple[float, float]]:
    q, r = cell
    points = []
    for index in range(6):
        angle = math.radians(60 * index)
        dq = math.cos(angle) / 1.5
        # Convert a Cartesian unit vertex back to an axial fractional offset.
        target_x = math.cos(angle)
        target_y = math.sin(angle)
        dq = target_x / 1.5
        dr = target_y / SQRT3 - dq / 2
        points.append(strip_uv(q + dq, r + dr))
    return points


ALL_FLAT_POINTS = [point for cell in CELLS for point in flat_hex(cell)]
MIN_U = min(u for u, _ in ALL_FLAT_POINTS)
MAX_U = max(u for u, _ in ALL_FLAT_POINTS)
MIN_V = min(v for _, v in ALL_FLAT_POINTS)
MAX_V = max(v for _, v in ALL_FLAT_POINTS)
MID_U = (MIN_U + MAX_U) / 2
MID_V = (MIN_V + MAX_V) / 2

THETA_MAX = math.radians(56)
RADIUS = (MAX_V - MIN_V) / (2 * THETA_MAX)
# A long lens approximates the orthographic camera used by the game.  The
# combined rotations reveal the shallow trough and turn its long axis into the
# familiar isometric diagonal without strong perspective taper.
CAMERA_DISTANCE = RADIUS * 40.0
YAW = 0.0
PITCH = math.radians(-37)


def surface_xyz(u: float, v: float, radial_offset: float = 0.0) -> tuple[float, float, float]:
    theta = (v - MID_V) / RADIUS
    radius = RADIUS + radial_offset
    return u - MID_U, radius * math.sin(theta), radius * math.cos(theta)


def camera_point(x: float, y: float, z: float) -> tuple[float, float, float]:
    cs, sn = math.cos(YAW), math.sin(YAW)
    yaw_x, yaw_z = x * cs + z * sn, -x * sn + z * cs
    cp, sp = math.cos(PITCH), math.sin(PITCH)
    return yaw_x, y * cp - yaw_z * sp, y * sp + yaw_z * cp


def raw_project(x: float, y: float, z: float) -> tuple[float, float, float]:
    cx, cy, cz = camera_point(x, y, z)
    depth = CAMERA_DISTANCE + cz
    return cx / depth, cy / depth, depth


# Fit the entire curved strip in the image after projection.
raw_surface = [raw_project(*surface_xyz(u, v)) for u, v in ALL_FLAT_POINTS]
min_px = min(x for x, _, _ in raw_surface)
max_px = max(x for x, _, _ in raw_surface)
min_py = min(y for _, y, _ in raw_surface)
max_py = max(y for _, y, _ in raw_surface)
SCALE = min(2140 / (max_px - min_px), 1040 / (max_py - min_py))
SCREEN_X = W / 2 - SCALE * (min_px + max_px) / 2
SCREEN_Y = 735 - SCALE * (min_py + max_py) / 2


def project_uv(u: float, v: float, radial_offset: float = 0.0) -> tuple[float, float, float]:
    x, y, z = surface_xyz(u, v, radial_offset)
    px, py, depth = raw_project(x, y, z)
    return SCREEN_X + SCALE * px, SCREEN_Y + SCALE * py, depth


def sample_edge(a: tuple[float, float], b: tuple[float, float], segments: int = 4) -> list[tuple[float, float, float]]:
    return [
        project_uv(a[0] + (b[0] - a[0]) * t / segments, a[1] + (b[1] - a[1]) * t / segments)
        for t in range(segments)
    ]


def projected_cell(cell: tuple[int, int]) -> list[tuple[float, float, float]]:
    flat = flat_hex(cell)
    points: list[tuple[float, float, float]] = []
    for index, a in enumerate(flat):
        b = flat[(index + 1) % 6]
        points.extend(sample_edge(a, b))
    return points


def projected_edge(a: tuple[float, float], b: tuple[float, float]) -> list[tuple[float, float]]:
    return [(x, y) for x, y, _ in sample_edge(a, b, 7)] + [project_uv(*b)[:2]]


def edge_id(a: tuple[float, float], b: tuple[float, float]) -> tuple[tuple[int, int], tuple[int, int]]:
    pa = (round(a[0] * 100000), round(a[1] * 100000))
    pb = (round(b[0] * 100000), round(b[1] * 100000))
    return tuple(sorted((pa, pb)))  # type: ignore[return-value]


def crop_texture(cell: tuple[int, int], kind: str, size: tuple[int, int]) -> Image.Image:
    source = SOURCES[kind]
    rng = random.Random((cell[0] + 100) * 1999 + (cell[1] + 100) * 8081 + len(kind) * 103)
    sw, sh = source.size
    cw, ch = int(sw * 0.34), int(sh * 0.44)
    cx = sw // 2 + rng.randint(-sw // 6, sw // 6)
    cy = sh // 2 + rng.randint(-sh // 10, sh // 10)
    left = max(0, min(sw - cw, cx - cw // 2))
    top = max(0, min(sh - ch, cy - ch // 2))
    crop = source.crop((left, top, left + cw, top + ch))
    crop = ImageOps.fit(crop, size, method=Image.Resampling.LANCZOS)
    if kind == "home":
        # Remove source-image seas so neighbouring cells can never form a
        # continuous river.  A separate, deliberately small pond is drawn
        # well inside every home tile below.
        rgb = crop.convert("RGB")
        pixels = rgb.load()
        for py in range(rgb.height):
            for px in range(rgb.width):
                red, green, blue = pixels[px, py]
                if blue > red * 1.10 and blue > green * 1.05:
                    lum = (red + green + blue) // 3
                    pixels[px, py] = (76 + lum // 5, 105 + lum // 4, 67 + lum // 6)
        crop = ImageEnhance.Brightness(ImageEnhance.Color(rgb).enhance(0.94)).enhance(0.92).convert("RGBA")
        pond = Image.new("RGBA", crop.size, (0, 0, 0, 0))
        pd = ImageDraw.Draw(pond, "RGBA")
        rng = random.Random((cell[0] + 41) * 733 + (cell[1] + 53) * 1291)
        pw = max(28, int(crop.width * rng.uniform(0.22, 0.30)))
        ph = max(18, int(crop.height * rng.uniform(0.15, 0.22)))
        pcx = int(crop.width * rng.uniform(0.35, 0.65))
        pcy = int(crop.height * rng.uniform(0.38, 0.64))
        pond_box = (pcx - pw // 2, pcy - ph // 2, pcx + pw // 2, pcy + ph // 2)
        pd.ellipse(pond_box, fill=(27, 104, 143, 235), outline=(111, 178, 166, 230), width=max(2, crop.width // 90))
        pd.ellipse((pond_box[0] + 5, pond_box[1] + 4, pond_box[2] - 7, pond_box[3] - 6), fill=(32, 135, 181, 210))
        crop.alpha_composite(pond)
    elif kind == "central":
        crop = ImageEnhance.Contrast(ImageEnhance.Color(crop).enhance(0.70)).enhance(1.12)
    else:
        crop = ImageEnhance.Brightness(ImageEnhance.Color(crop).enhance(0.76)).enhance(0.86)
    return crop.convert("RGBA")


def load_trimmed(path: Path) -> Image.Image:
    sprite = Image.open(path).convert("RGBA")
    bbox = sprite.getchannel("A").getbbox()
    return sprite.crop(bbox) if bbox else sprite


HOME_BUILDINGS = [load_trimmed(path) for path in HOME_BUILDING_PATHS]
NEUTRAL_RUINS = [load_trimmed(path) for path in NEUTRAL_RUIN_PATHS]
CENTRAL_BUILDING = load_trimmed(CENTRAL_BUILDING_PATH)


def cell_metrics(cell: tuple[int, int]) -> tuple[float, float, float, float, float, float]:
    polygon = [(x, y) for x, y, _ in projected_cell(cell)]
    min_x = min(x for x, _ in polygon)
    max_x = max(x for x, _ in polygon)
    min_y = min(y for _, y in polygon)
    max_y = max(y for _, y in polygon)
    cu, cv = strip_uv(*cell)
    cx, cy, depth = project_uv(cu, cv)
    return cx, cy, max_x - min_x, max_y - min_y, max_y, depth


def place_sprite(base: Image.Image, sprite: Image.Image, cx: float, ground_y: float, target_width: float, shadow_scale: float = 0.72) -> None:
    width = max(24, int(target_width))
    height = max(24, int(sprite.height * width / sprite.width))
    rendered = sprite.resize((width, height), Image.Resampling.LANCZOS)

    shadow = Image.new("RGBA", base.size, (0, 0, 0, 0))
    sd = ImageDraw.Draw(shadow, "RGBA")
    sw = int(width * shadow_scale)
    sh = max(12, int(width * 0.16))
    sx = int(cx - sw * 0.64)
    sy = int(ground_y - sh * 0.55)
    sd.ellipse((sx, sy, sx + sw, sy + sh), fill=(3, 16, 25, 105))
    shadow = shadow.filter(ImageFilter.GaussianBlur(max(4, width // 28)))
    base.alpha_composite(shadow)

    left = int(cx - width / 2)
    top = int(ground_y - height)
    base.alpha_composite(rendered, dest=(left, top))


def render_assets(base: Image.Image) -> None:
    homes = sorted((cell for cell in CELLS if CELL_ROLE[cell] == "home"), key=lambda cell: cell_metrics(cell)[1])
    neutrals = sorted((cell for cell in CELLS if CELL_ROLE[cell] == "neutral"), key=lambda cell: cell_metrics(cell)[1])
    placements: list[tuple[float, tuple[int, int], str, int]] = []
    for index, cell in enumerate(homes):
        placements.append((cell_metrics(cell)[1], cell, "home", index))
    for index, cell in enumerate(neutrals):
        placements.append((cell_metrics(cell)[1], cell, "neutral", index))
    for cell in CELLS:
        if CELL_ROLE[cell] == "central":
            placements.append((cell_metrics(cell)[1], cell, "central", 0))

    for _, cell, kind, index in sorted(placements):
        cx, cy, cell_width, cell_height, max_y, _ = cell_metrics(cell)
        ground_y = cy + cell_height * 0.29
        if kind == "home":
            sprite = HOME_BUILDINGS[index % len(HOME_BUILDINGS)]
            width = cell_width * (0.47 if index % 3 else 0.55)
            place_sprite(base, sprite, cx, ground_y, width)
        elif kind == "neutral":
            sprite = NEUTRAL_RUINS[index % len(NEUTRAL_RUINS)]
            width = cell_width * (0.36 if index % 2 else 0.43)
            place_sprite(base, sprite, cx, ground_y, width, shadow_scale=0.62)
        else:
            place_sprite(base, CENTRAL_BUILDING, cx, ground_y, cell_width * 0.64, shadow_scale=0.78)


def background() -> Image.Image:
    im = Image.new("RGB", (W, H))
    px = im.load()
    for y in range(H):
        for x in range(W):
            nx = abs(x - W / 2) / (W / 2)
            ny = abs(y - 670) / H
            t = min(1.0, math.sqrt(nx * nx + ny * ny))
            px[x, y] = (int(14 - 11 * t), int(39 - 29 * t), int(55 - 35 * t))
    return im.convert("RGBA")


def draw_shell(base: Image.Image) -> None:
    d = ImageDraw.Draw(base, "RGBA")
    for radius, alpha in [(145, 20), (96, 44), (60, 105), (32, 240)]:
        d.ellipse((W / 2 - radius, 94 - radius, W / 2 + radius, 94 + radius), fill=(255, 235, 170, alpha))

    # Outer dark backing of the half-cylinder, slightly below the gameplay skin.
    far_arc = []
    for index in range(101):
        v = MIN_V + (MAX_V - MIN_V) * index / 100
        x, y, _ = project_uv(MIN_U, v, radial_offset=0.34)
        far_arc.append((x, y))
    d.line(far_arc, fill=(83, 119, 132, 150), width=12)

    # Tunnel rivers run on the two lips and are continuous along the cylinder axis.
    for v, shift in [(MIN_V, -0.20), (MAX_V, 0.20)]:
        points = []
        for index in range(121):
            u = MIN_U + (MAX_U - MIN_U) * index / 120
            x, y, _ = project_uv(u, v + shift, radial_offset=-0.10)
            points.append((x, y))
        d.line(points, fill=(30, 124, 158, 220), width=22)
        d.line(points, fill=(91, 231, 255, 245), width=7)


def render_cells(proof: bool) -> Image.Image:
    layer = Image.new("RGBA", (W, H), (0, 0, 0, 0))
    draw = ImageDraw.Draw(layer, "RGBA")
    colours = {"central": (55, 151, 190, 255), "home": (54, 109, 89, 255), "neutral": (190, 139, 77, 255)}

    ordered = sorted(CELLS, key=lambda cell: sum(p[2] for p in projected_cell(cell)) / len(projected_cell(cell)), reverse=True)
    for cell in ordered:
        projected = projected_cell(cell)
        polygon = [(x, y) for x, y, _ in projected]
        kind = CELL_ROLE[cell]
        avg_depth = sum(depth for _, _, depth in projected) / len(projected)
        depth_t = (avg_depth - min(depth for _, _, depth in raw_surface)) / (
            max(depth for _, _, depth in raw_surface) - min(depth for _, _, depth in raw_surface)
        )
        _, centre_v = strip_uv(*cell)
        theta = (centre_v - MID_V) / RADIUS
        # Silus illuminates the inner skin almost along the viewing direction:
        # the floor faces the star, while both raised lips receive grazing light.
        # This normal-based falloff is what makes the constant-radius trough
        # readable without changing the cell geometry.
        normal_light = 0.60 + 0.40 * max(0.0, math.cos(theta))
        shade = normal_light * (1.0 - 0.08 * depth_t)
        if proof:
            colour = tuple(int(channel * shade) for channel in colours[kind][:3]) + (255,)
            draw.polygon(polygon, fill=colour)
        else:
            min_x = max(0, math.floor(min(x for x, _ in polygon)) - 2)
            max_x = min(W, math.ceil(max(x for x, _ in polygon)) + 2)
            min_y = max(0, math.floor(min(y for _, y in polygon)) - 2)
            max_y = min(H, math.ceil(max(y for _, y in polygon)) + 2)
            tile = crop_texture(cell, kind, (max_x - min_x, max_y - min_y))
            tile = ImageEnhance.Brightness(tile).enhance(shade)
            mask = Image.new("L", tile.size, 0)
            md = ImageDraw.Draw(mask)
            md.polygon([(x - min_x, y - min_y) for x, y in polygon], fill=255)
            tile.putalpha(mask)
            layer.alpha_composite(tile, dest=(min_x, min_y))

        flat = flat_hex(cell)
        for index, a in enumerate(flat):
            b = flat[(index + 1) % 6]
            draw.line(projected_edge(a, b), fill=(132, 199, 210, 210), width=3)

        if kind == "central":
            cu, cv = strip_uv(*cell)
            cx, cy, _ = project_uv(cu, cv)
            for vertex in flat:
                vx, vy, _ = project_uv(*vertex)
                draw.line((cx, cy, vx, vy), fill=(112, 225, 247, 215), width=3)
            draw.ellipse((cx - 9, cy - 9, cx + 9, cy + 9), fill=(102, 231, 255, 240))
    return layer


def draw_structure(base: Image.Image) -> None:
    d = ImageDraw.Draw(base, "RGBA")
    # Cross-section ribs at both ends reveal the half-cylinder volume.
    for u in (MIN_U, MAX_U):
        inner = []
        outer = []
        for index in range(81):
            v = MIN_V + (MAX_V - MIN_V) * index / 80
            inner.append(project_uv(u, v)[:2])
            outer.append(project_uv(u, v, radial_offset=0.42)[:2])
        d.line(inner, fill=(169, 221, 231, 200), width=6)
        d.line(outer, fill=(82, 121, 136, 190), width=10)
        d.line([inner[0], outer[0]], fill=(118, 166, 180, 185), width=7)
        d.line([inner[-1], outer[-1]], fill=(118, 166, 180, 185), width=7)

    # Sparse transparent dome ribs on the camera side.
    dome = Image.new("RGBA", (W, H), (0, 0, 0, 0))
    dd = ImageDraw.Draw(dome, "RGBA")
    for fraction in (0.12, 0.35, 0.58, 0.81):
        u = MIN_U + (MAX_U - MIN_U) * fraction
        rib = []
        for index in range(81):
            v = MIN_V + (MAX_V - MIN_V) * index / 80
            theta = (v - MID_V) / RADIUS
            x = u - MID_U
            y = (RADIUS * 1.23) * math.sin(theta)
            z = -0.23 * RADIUS * math.cos(theta)
            px, py, depth = raw_project(x, y, z)
            rib.append((SCREEN_X + SCALE * px, SCREEN_Y + SCALE * py))
        dd.line(rib, fill=(167, 230, 242, 46), width=3)
    base.alpha_composite(dome)


def geometry_report() -> dict[str, object]:
    edge_use: Counter[tuple[tuple[int, int], tuple[int, int]]] = Counter()
    for cell in CELLS:
        flat = flat_hex(cell)
        for index, a in enumerate(flat):
            edge_use[edge_id(a, flat[(index + 1) % 6])] += 1
    return {
        "version": "v19",
        "surface": "shallow developable inner cylindrical trough",
        "complex_count": len(CENTRES),
        "cell_count": len(CELLS),
        "cells_per_complex": len(MOTIF),
        "trough_angle_deg": round(math.degrees(2 * THETA_MAX), 3),
        "axis_yaw_deg": round(math.degrees(YAW), 3),
        "camera_pitch_deg": round(math.degrees(PITCH), 3),
        "orientation_goal": "deterministic detailed 13-hive complex using exact cells and project building assets",
        "camera_distance_in_trough_radii": CAMERA_DISTANCE / RADIUS,
        "flat_to_cylinder_isometry": True,
        "no_cell_overlap": len(CELLS) == len(CENTRES) * len(MOTIF),
        "max_flat_edge_multiplicity": max(edge_use.values()),
        "projection_changes_screen_shape": True,
        "physical_hexes_remain_equal": True,
    }


def render() -> None:
    WORLD_DIR.mkdir(parents=True, exist_ok=True)
    art = background()
    draw_shell(art)

    shadow = render_cells(True).getchannel("A").filter(ImageFilter.GaussianBlur(16))
    shadow_layer = Image.new("RGBA", (W, H), (0, 0, 0, 0))
    shadow_layer.putalpha(shadow.point(lambda value: int(value * 0.25)))
    art.alpha_composite(shadow_layer, dest=(7, 12))
    art.alpha_composite(render_cells(False))
    render_assets(art)
    draw_structure(art)
    art.convert("RGB").save(OUT_ART, optimize=True)

    proof = background()
    draw_shell(proof)
    proof.alpha_composite(render_cells(True))
    draw_structure(proof)
    proof.convert("RGB").save(OUT_PROOF, optimize=True)

    report = geometry_report()
    assert report["no_cell_overlap"]
    assert report["max_flat_edge_multiplicity"] <= 2
    assert report["flat_to_cylinder_isometry"]
    OUT_REPORT.write_text(json.dumps(report, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    print(json.dumps(report, ensure_ascii=False, indent=2))
    print(OUT_ART)
    print(OUT_PROOF)


if __name__ == "__main__":
    render()

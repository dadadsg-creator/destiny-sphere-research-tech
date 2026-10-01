"""Export aligned color, cast-shadow and emissive layers from the Voraner blockout."""

from __future__ import annotations

import argparse
import sys
from pathlib import Path

import bpy


ROOT = Path(__file__).resolve().parent


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser()
    parser.add_argument("--input", default=str(ROOT / "voraner-l1-blockout-light220-e33-v02.blend"))
    parser.add_argument("--output-dir", default=str(ROOT / "exports"))
    parser.add_argument("--prefix", default="voraner-l1-blockout")
    values = sys.argv[sys.argv.index("--") + 1 :] if "--" in sys.argv else []
    return parser.parse_args(values)


def members(collection_name: str) -> list[bpy.types.Object]:
    return list(bpy.data.collections[collection_name].all_objects)


def render(path: Path, samples: int = 64) -> None:
    scene = bpy.context.scene
    scene.cycles.samples = samples
    scene.render.resolution_percentage = 100
    scene.render.film_transparent = True
    scene.render.filepath = str(path)
    bpy.ops.render.render(write_still=True)


def render_pixels(path: Path, samples: int = 32) -> tuple[list[float], int, int]:
    scene = bpy.context.scene
    scene.cycles.samples = samples
    scene.render.resolution_percentage = 100
    scene.render.filepath = str(path)
    bpy.ops.render.render(write_still=True)
    result = bpy.data.images.load(str(path), check_existing=False)
    width = round(scene.render.resolution_x * scene.render.resolution_percentage / 100)
    height = round(scene.render.resolution_y * scene.render.resolution_percentage / 100)
    pixels = list(result.pixels)
    bpy.data.images.remove(result)
    return pixels, width, height


def save_shadow_difference(path: Path, baseline: list[float], shadowed: list[float], width: int, height: int) -> None:
    pixels = [0.0] * (width * height * 4)
    for index in range(width * height):
        offset = index * 4
        base_luma = (
            baseline[offset] * 0.2126
            + baseline[offset + 1] * 0.7152
            + baseline[offset + 2] * 0.0722
        )
        shadow_luma = (
            shadowed[offset] * 0.2126
            + shadowed[offset + 1] * 0.7152
            + shadowed[offset + 2] * 0.0722
        )
        alpha = 0.0 if base_luma <= 1e-5 else max(0.0, min(1.0, 1.0 - shadow_luma / base_luma))
        pixels[offset + 3] = alpha
    image = bpy.data.images.new("DS_CAST_SHADOW_EXPORT", width=width, height=height, alpha=True)
    image.pixels.foreach_set(pixels)
    image.file_format = "PNG"
    image.filepath_raw = str(path)
    image.save()
    bpy.data.images.remove(image)


def main() -> None:
    options = parse_args()
    source = Path(options.input).resolve()
    output = Path(options.output_dir).resolve()
    prefix = options.prefix
    output.mkdir(parents=True, exist_ok=True)
    bpy.ops.wm.open_mainfile(filepath=str(source))

    receiver = bpy.data.objects["DS_SHADOW_RECEIVER"]
    building = members("DS_BUILDING")
    emissive = members("DS_EMISSIVE")

    receiver.hide_render = True
    receiver.is_shadow_catcher = False
    for obj in building + emissive:
        obj.hide_render = False
        if hasattr(obj, "visible_camera"):
            obj.visible_camera = True
    render(output / f"{prefix}-color.png")

    receiver.hide_render = False
    receiver.is_shadow_catcher = False
    for obj in building + emissive:
        if hasattr(obj, "visible_camera"):
            obj.visible_camera = False
        if hasattr(obj, "visible_shadow"):
            obj.visible_shadow = False
    bpy.context.scene.render.film_transparent = False
    baseline_path = output / "_shadow-baseline.png"
    shadowed_path = output / "_shadow-with-caster.png"
    baseline, width, height = render_pixels(baseline_path)
    for obj in building + emissive:
        if hasattr(obj, "visible_shadow"):
            obj.visible_shadow = True
    shadowed, shadow_width, shadow_height = render_pixels(shadowed_path)
    if (shadow_width, shadow_height) != (width, height):
        raise RuntimeError("Shadow render dimensions changed between passes")
    save_shadow_difference(
        output / f"{prefix}-cast-shadow.png",
        baseline,
        shadowed,
        width,
        height,
    )
    baseline_path.unlink(missing_ok=True)
    shadowed_path.unlink(missing_ok=True)

    receiver.hide_render = True
    receiver.is_shadow_catcher = False
    for obj in building:
        obj.hide_render = True
    for obj in emissive:
        obj.hide_render = False
        if hasattr(obj, "visible_camera"):
            obj.visible_camera = True
    world = bpy.context.scene.world
    background = world.node_tree.nodes.get("Background") if world and world.use_nodes else None
    previous_strength = background.inputs["Strength"].default_value if background else None
    if background:
        background.inputs["Strength"].default_value = 0.0
    bpy.context.scene.render.film_transparent = True
    render(output / f"{prefix}-emissive.png", samples=16)
    if background and previous_strength is not None:
        background.inputs["Strength"].default_value = previous_strength

    print(f"Exported aligned layers to: {output}")


if __name__ == "__main__":
    main()

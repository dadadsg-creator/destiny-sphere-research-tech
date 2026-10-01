"""Build a shadow-only 3D proxy matching the tall-tower Voraner v02 sprite."""

from __future__ import annotations

import argparse
import math
import sys
from pathlib import Path

import bpy

ROOT = Path(__file__).resolve().parent
sys.path.insert(0, str(ROOT))
import build_voraner_l1_blockout as base


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser()
    parser.add_argument("--template", default=str(ROOT / "ds-building-template-light220-e33-v02.blend"))
    parser.add_argument("--output", default=str(ROOT / "voraner-v02-shadow-proxy.blend"))
    parser.add_argument("--preview", default=str(ROOT.parent.parent / "tmp" / "voraner-v02-shadow-proxy-preview.png"))
    values = sys.argv[sys.argv.index("--") + 1 :] if "--" in sys.argv else []
    return parser.parse_args(values)


def remove_old_tower() -> None:
    for name in ("DS_MODEST_TOWER", "DS_TOWER_GOLD_CAP", "DS_TOWER_TIP"):
        obj = bpy.data.objects.get(name)
        if obj:
            bpy.data.objects.remove(obj, do_unlink=True)


def build_tall_tower() -> None:
    white = bpy.data.materials["DS_WHITE_CERAMIC"]
    gold = bpy.data.materials["DS_WARM_GOLD"]
    blue = bpy.data.materials["DS_SAPPHIRE_GLASS"]
    tower_x, tower_y = -0.78, 0.54

    shaft = base.cylinder("DS_V02_TALL_TOWER", 0.27, 2.18, 1.22, white, 64)
    shaft.location.x = tower_x
    shaft.location.y = tower_y
    base.bevel(shaft, 0.045)
    window = base.cube(
        "DS_V02_TOWER_WINDOW",
        base.Vector((tower_x + 0.20, tower_y - 0.20, 1.35)),
        (0.16, 0.09, 1.22),
        math.radians(45),
        blue,
        0.035,
    )
    base.move_to(window, "DS_EMISSIVE")
    cap = base.cylinder("DS_V02_TOWER_CAP", 0.30, 0.10, 2.34, gold, 64)
    cap.location.x = tower_x
    cap.location.y = tower_y
    bpy.ops.mesh.primitive_cone_add(
        vertices=64,
        radius1=0.25,
        radius2=0.02,
        depth=0.82,
        location=(tower_x, tower_y, 2.80),
    )
    spire = bpy.context.object
    spire.name = "DS_V02_TOWER_SPIRE"
    base.assign(spire, white)
    base.smooth(spire)

    side_x, side_y = -0.48, 0.78
    side = base.cylinder("DS_V02_SIDE_TOWER", 0.16, 1.42, 0.86, white, 48)
    side.location.x = side_x
    side.location.y = side_y
    side_cap = base.cylinder("DS_V02_SIDE_TOWER_CAP", 0.18, 0.08, 1.60, gold, 48)
    side_cap.location.x = side_x
    side_cap.location.y = side_y
    bpy.ops.mesh.primitive_cone_add(
        vertices=48,
        radius1=0.15,
        radius2=0.015,
        depth=0.54,
        location=(side_x, side_y, 1.91),
    )
    side_spire = bpy.context.object
    side_spire.name = "DS_V02_SIDE_SPIRE"
    base.assign(side_spire, white)
    base.smooth(side_spire)


def main() -> None:
    options = parse_args()
    bpy.ops.wm.open_mainfile(filepath=str(Path(options.template).resolve()))
    base.build()
    remove_old_tower()
    build_tall_tower()

    protected = {"DS_CAMERA_ORTHO_45_30", "DS_SUN_SCREEN_220_E33", "DS_SHADOW_RECEIVER", "DS_CELL_GUIDE", "DS_GROUND_ANCHOR"}
    emissive = {obj.name for obj in bpy.data.collections["DS_EMISSIVE"].all_objects}
    for obj in list(bpy.context.scene.objects):
        if obj.name in protected or obj.name in emissive:
            continue
        if obj.type in {"MESH", "CURVE"}:
            base.move_to(obj, "DS_BUILDING")

    scene = bpy.context.scene
    scene["ds_asset_id"] = "tech/residential/voraner-v02-shadow-proxy"
    output = Path(options.output).resolve()
    output.parent.mkdir(parents=True, exist_ok=True)
    bpy.ops.wm.save_as_mainfile(filepath=str(output))

    scene.render.resolution_percentage = 75
    scene.cycles.samples = 32
    scene.render.film_transparent = False
    preview = Path(options.preview).resolve()
    preview.parent.mkdir(parents=True, exist_ok=True)
    scene.render.filepath = str(preview)
    bpy.ops.render.render(write_still=True)
    print(f"Saved proxy: {output}")
    print(f"Saved preview: {preview}")


if __name__ == "__main__":
    main()

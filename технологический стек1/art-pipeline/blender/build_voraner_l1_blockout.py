"""Build the first real Voraner L1 housing blockout in the fixed DS scene."""

from __future__ import annotations

import argparse
import math
import sys
from pathlib import Path

import bpy
from mathutils import Vector


ROOT = Path(__file__).resolve().parent


def args() -> argparse.Namespace:
    parser = argparse.ArgumentParser()
    parser.add_argument("--template", default=str(ROOT / "ds-building-template-light220-e33-v02.blend"))
    parser.add_argument("--output", default=str(ROOT / "voraner-l1-blockout-light220-e33-v02.blend"))
    parser.add_argument("--preview", default=str(ROOT.parent.parent / "tmp" / "voraner-l1-blockout-light220-e33-preview-v02.png"))
    values = sys.argv[sys.argv.index("--") + 1 :] if "--" in sys.argv else []
    return parser.parse_args(values)


def material(name: str, color: tuple[float, float, float, float], *, metallic=0.0, roughness=0.35,
             emission: tuple[float, float, float, float] | None = None, emission_strength=0.0,
             transmission=0.0) -> bpy.types.Material:
    mat = bpy.data.materials.new(name)
    mat.diffuse_color = color
    mat.use_nodes = True
    node = mat.node_tree.nodes.get("Principled BSDF")
    node.inputs["Base Color"].default_value = color
    node.inputs["Metallic"].default_value = metallic
    node.inputs["Roughness"].default_value = roughness
    if "Transmission Weight" in node.inputs:
        node.inputs["Transmission Weight"].default_value = transmission
    if emission and "Emission Color" in node.inputs:
        node.inputs["Emission Color"].default_value = emission
        node.inputs["Emission Strength"].default_value = emission_strength
    return mat


def smooth(obj: bpy.types.Object) -> None:
    if obj.type == "MESH":
        for polygon in obj.data.polygons:
            polygon.use_smooth = True


def bevel(obj: bpy.types.Object, width: float, segments: int = 3) -> None:
    modifier = obj.modifiers.new("DS_SOFT_EDGES", "BEVEL")
    modifier.width = width
    modifier.segments = segments


def assign(obj: bpy.types.Object, mat: bpy.types.Material) -> bpy.types.Object:
    obj.data.materials.append(mat)
    return obj


def cylinder(name: str, radius: float, depth: float, z: float, mat: bpy.types.Material,
             vertices: int = 96) -> bpy.types.Object:
    bpy.ops.mesh.primitive_cylinder_add(vertices=vertices, radius=radius, depth=depth, location=(0, 0, z))
    obj = bpy.context.object
    obj.name = name
    assign(obj, mat)
    smooth(obj)
    return obj


def cube(name: str, location: Vector, dimensions: tuple[float, float, float], rotation_z: float,
         mat: bpy.types.Material, bevel_width=0.025) -> bpy.types.Object:
    bpy.ops.mesh.primitive_cube_add(location=location, rotation=(0, 0, rotation_z))
    obj = bpy.context.object
    obj.name = name
    obj.dimensions = dimensions
    bpy.ops.object.transform_apply(location=False, rotation=False, scale=True)
    assign(obj, mat)
    bevel(obj, bevel_width)
    return obj


def torus(name: str, major_radius: float, minor_radius: float, z: float,
          mat: bpy.types.Material) -> bpy.types.Object:
    bpy.ops.mesh.primitive_torus_add(
        major_radius=major_radius,
        minor_radius=minor_radius,
        major_segments=96,
        minor_segments=16,
        location=(0, 0, z),
    )
    obj = bpy.context.object
    obj.name = name
    assign(obj, mat)
    smooth(obj)
    return obj


def dome_rib(angle: float, radius: float, height: float, base_z: float,
             mat: bpy.types.Material, index: int) -> bpy.types.Object:
    curve = bpy.data.curves.new(f"DS_DOME_RIB_{index:02d}", "CURVE")
    curve.dimensions = "3D"
    curve.bevel_depth = 0.025
    curve.bevel_resolution = 3
    spline = curve.splines.new("POLY")
    samples = 20
    spline.points.add(samples - 1)
    for point, step in zip(spline.points, range(samples)):
        t = (math.pi * 0.5) * step / (samples - 1)
        radial = radius * math.sin(t)
        point.co = (
            radial * math.cos(angle),
            radial * math.sin(angle),
            base_z + height * math.cos(t),
            1.0,
        )
    obj = bpy.data.objects.new(curve.name, curve)
    bpy.data.collections["DS_BUILDING"].objects.link(obj)
    curve.materials.append(mat)
    return obj


def move_to(obj: bpy.types.Object, collection_name: str) -> None:
    target = bpy.data.collections[collection_name]
    for owner in list(obj.users_collection):
        owner.objects.unlink(obj)
    target.objects.link(obj)


def build() -> None:
    white = material("DS_WHITE_CERAMIC", (0.82, 0.77, 0.66, 1), roughness=0.22)
    gold = material("DS_WARM_GOLD", (0.62, 0.31, 0.055, 1), metallic=0.82, roughness=0.18)
    blue = material(
        "DS_SAPPHIRE_GLASS",
        (0.012, 0.12, 0.34, 1),
        metallic=0.12,
        roughness=0.12,
        transmission=0.3,
    )
    glow = material(
        "DS_CYAN_ENERGY",
        (0.0, 0.18, 0.55, 1),
        roughness=0.12,
        emission=(0.0, 0.4, 1.0, 1),
        emission_strength=5.0,
    )
    dark = material("DS_DARK_BASE", (0.055, 0.07, 0.085, 1), metallic=0.3, roughness=0.4)
    ground_mat = material("DS_PREVIEW_GROUND", (0.32, 0.38, 0.40, 1), roughness=0.62)

    placeholder = bpy.data.objects.get("DS_REPLACE_WITH_BUILDING")
    if placeholder:
        bpy.data.objects.remove(placeholder, do_unlink=True)

    receiver = bpy.data.objects["DS_SHADOW_RECEIVER"]
    receiver.data.materials.clear()
    receiver.data.materials.append(ground_mat)

    base = cylinder("DS_BASE_PLINTH", 1.48, 0.22, 0.11, dark)
    bevel(base, 0.045)
    lower_ring = cylinder("DS_LOWER_WHITE_RING", 1.39, 0.18, 0.26, white)
    bevel(lower_ring, 0.055)
    body = cylinder("DS_MAIN_BODY", 1.22, 0.62, 0.62, white)
    bevel(body, 0.07)
    torus("DS_BASE_GOLD_TRIM", 1.37, 0.035, 0.36, gold)
    torus("DS_DOME_GOLD_TRIM", 1.12, 0.045, 0.92, gold)

    bpy.ops.mesh.primitive_uv_sphere_add(segments=128, ring_count=64, location=(0, 0, 0.91))
    dome = bpy.context.object
    dome.name = "DS_GLASS_DOME"
    dome.scale = (1.08, 1.08, 0.76)
    assign(dome, blue)
    smooth(dome)

    for index in range(10):
        dome_rib(index * math.tau / 10, 1.09, 0.78, 0.91, gold, index)
    torus("DS_DOME_CROWN", 0.20, 0.045, 1.67, gold)

    front = Vector((math.sqrt(0.5), -math.sqrt(0.5), 0))
    tangent = Vector((math.sqrt(0.5), math.sqrt(0.5), 0))
    facing = math.radians(45)
    door_center = front * 1.24 + Vector((0, 0, 0.65))
    door = cube("DS_ENTRY_ENERGY_DOOR", door_center, (0.48, 0.09, 0.72), facing, blue, 0.055)
    move_to(door, "DS_EMISSIVE")
    for sign in (-1, 1):
        column_pos = front * 1.29 + tangent * (0.34 * sign) + Vector((0, 0, 0.60))
        cube(f"DS_ENTRY_COLUMN_{sign:+d}", column_pos, (0.13, 0.18, 0.82), facing, white, 0.04)
    cube("DS_ENTRY_LINTEL", front * 1.29 + Vector((0, 0, 1.02)), (0.82, 0.18, 0.13), facing, gold, 0.04)

    for step in range(3):
        step_center = front * (1.43 + step * 0.11) + Vector((0, 0, 0.10 - step * 0.035))
        cube(f"DS_ENTRY_STEP_{step}", step_center, (0.72 + step * 0.12, 0.24, 0.10), facing, white, 0.025)

    tower_xy = Vector((-0.78, 0.54, 0))
    tower = cylinder("DS_MODEST_TOWER", 0.20, 0.95, 0.83, white, 64)
    tower.location.x = tower_xy.x
    tower.location.y = tower_xy.y
    bevel(tower, 0.035)
    tower_cap = cylinder("DS_TOWER_GOLD_CAP", 0.23, 0.09, 1.33, gold, 64)
    tower_cap.location.x = tower_xy.x
    tower_cap.location.y = tower_xy.y
    bpy.ops.mesh.primitive_cone_add(vertices=64, radius1=0.16, radius2=0.025, depth=0.42,
                                    location=(tower_xy.x, tower_xy.y, 1.58))
    tower_tip = bpy.context.object
    tower_tip.name = "DS_TOWER_TIP"
    assign(tower_tip, white)
    smooth(tower_tip)

    bpy.ops.mesh.primitive_cylinder_add(vertices=48, radius=0.055, depth=0.48, location=(0, 0, 1.91))
    beacon = bpy.context.object
    beacon.name = "DS_ROOF_BEACON"
    assign(beacon, glow)
    move_to(beacon, "DS_EMISSIVE")

    protected = {
        "DS_CAMERA_ORTHO_45_30",
        "DS_SUN_SCREEN_220_E33",
        "DS_SHADOW_RECEIVER",
        "DS_CELL_GUIDE",
        "DS_GROUND_ANCHOR",
    }
    emissive_names = {obj.name for obj in bpy.data.collections["DS_EMISSIVE"].all_objects}
    for obj in list(bpy.context.scene.objects):
        if obj.name in protected or obj.name in emissive_names:
            continue
        if obj.type in {"MESH", "CURVE"}:
            move_to(obj, "DS_BUILDING")

    for obj in list(bpy.data.collections["DS_BUILDING"].all_objects):
        if obj.name.startswith("DS_"):
            obj["ds_asset_role"] = "building"

    scene = bpy.context.scene
    scene.world.color = (0.018, 0.028, 0.045)
    if scene.world.use_nodes:
        background = scene.world.node_tree.nodes.get("Background")
        background.inputs["Color"].default_value = (0.018, 0.028, 0.045, 1)
        background.inputs["Strength"].default_value = 0.32
    scene["ds_asset_id"] = "tech/residential/voraner-l1-blockout"
    scene["ds_blockout_version"] = 1


def main() -> None:
    options = args()
    template = Path(options.template).resolve()
    output = Path(options.output).resolve()
    preview = Path(options.preview).resolve()
    bpy.ops.wm.open_mainfile(filepath=str(template))
    build()
    output.parent.mkdir(parents=True, exist_ok=True)
    bpy.ops.wm.save_as_mainfile(filepath=str(output))

    scene = bpy.context.scene
    scene.render.resolution_percentage = 75
    scene.cycles.samples = 32
    scene.render.film_transparent = False
    scene.render.filepath = str(preview)
    preview.parent.mkdir(parents=True, exist_ok=True)
    bpy.ops.render.render(write_still=True)
    print(f"Saved blockout: {output}")
    print(f"Saved preview: {preview}")


if __name__ == "__main__":
    main()

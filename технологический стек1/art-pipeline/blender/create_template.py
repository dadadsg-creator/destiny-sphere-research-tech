"""Create the fixed DestinySphere building-render scene.

Run inside Blender:
blender --background --python create_template.py -- --output ds-building-template.blend
"""

from __future__ import annotations

import argparse
import math
import sys
from pathlib import Path

import bpy
from mathutils import Matrix, Vector


def point_at(obj: bpy.types.Object, target: Vector) -> None:
    obj.rotation_euler = (target - obj.location).to_track_quat("-Z", "Y").to_euler()


def ground_direction_from_screen(camera: bpy.types.Object, screen_azimuth_degrees: float) -> Vector:
    """Convert a Canvas angle (x right, y down) into a horizontal world vector."""
    theta = math.radians(screen_azimuth_degrees)
    desired_screen = Vector((math.cos(theta), -math.sin(theta)))
    orientation = camera.matrix_world.to_quaternion()
    screen_right = orientation @ Vector((1.0, 0.0, 0.0))
    screen_up = orientation @ Vector((0.0, 1.0, 0.0))
    world_xy = Matrix((
        (screen_right.x, screen_right.y),
        (screen_up.x, screen_up.y),
    )).inverted() @ desired_screen
    return Vector((world_xy.x, world_xy.y, 0.0)).normalized()


def collection(name: str) -> bpy.types.Collection:
    result = bpy.data.collections.new(name)
    bpy.context.scene.collection.children.link(result)
    return result


def move_to_collection(obj: bpy.types.Object, target: bpy.types.Collection) -> None:
    for owner in list(obj.users_collection):
        owner.objects.unlink(obj)
    target.objects.link(obj)


def build_scene() -> None:
    bpy.ops.object.select_all(action="SELECT")
    bpy.ops.object.delete(use_global=False)
    for existing in list(bpy.data.collections):
        bpy.data.collections.remove(existing)

    scene = bpy.context.scene
    scene.unit_settings.system = "METRIC"
    scene.unit_settings.scale_length = 1.0
    scene.render.engine = "CYCLES"
    scene.cycles.device = "CPU"
    scene.cycles.samples = 64
    scene.cycles.use_denoising = True
    scene.render.resolution_x = 1024
    scene.render.resolution_y = 1024
    scene.render.resolution_percentage = 100
    scene.render.film_transparent = True
    scene.render.image_settings.file_format = "PNG"
    scene.render.image_settings.color_mode = "RGBA"
    scene.render.image_settings.color_depth = "8"

    building = collection("DS_BUILDING")
    collection("DS_EMISSIVE")
    ground = collection("DS_GROUND_RECEIVER")
    guides = collection("DS_GUIDES")

    camera_radius = 10.0
    camera_elevation = math.radians(30.0)
    camera_azimuth = math.radians(-45.0)
    camera_location = (
        camera_radius * math.cos(camera_elevation) * math.cos(camera_azimuth),
        camera_radius * math.cos(camera_elevation) * math.sin(camera_azimuth),
        camera_radius * math.sin(camera_elevation),
    )
    bpy.ops.object.camera_add(location=camera_location)
    camera = bpy.context.object
    camera.name = "DS_CAMERA_ORTHO_45_30"
    camera.data.type = "ORTHO"
    camera.data.ortho_scale = 8.0
    point_at(camera, Vector((0.0, 0.0, 0.0)))
    scene.camera = camera
    move_to_collection(camera, guides)
    bpy.context.view_layer.update()

    sun_distance = 10.0
    sun_elevation_degrees = 33.0
    shadow_screen_azimuth_degrees = 220.0
    sun_elevation = math.radians(sun_elevation_degrees)
    shadow_world_direction = ground_direction_from_screen(camera, shadow_screen_azimuth_degrees)
    sun_world_direction = -shadow_world_direction
    sun_location = (
        sun_distance * math.cos(sun_elevation) * sun_world_direction.x,
        sun_distance * math.cos(sun_elevation) * sun_world_direction.y,
        sun_distance * math.sin(sun_elevation),
    )
    bpy.ops.object.light_add(type="SUN", location=sun_location)
    sun = bpy.context.object
    sun.name = "DS_SUN_SCREEN_220_E33"
    sun.data.energy = 3.0
    sun.data.angle = math.radians(0.75)
    point_at(sun, Vector((0.0, 0.0, 0.0)))
    sun["ds_elevation_degrees"] = sun_elevation_degrees
    sun["ds_shadow_screen_azimuth_degrees"] = shadow_screen_azimuth_degrees
    sun["ds_world_azimuth_degrees"] = math.degrees(math.atan2(sun_world_direction.y, sun_world_direction.x))
    sun["ds_angular_diameter_degrees"] = 0.75
    move_to_collection(sun, guides)

    bpy.ops.mesh.primitive_plane_add(size=7.0, location=(0.0, 0.0, 0.0))
    receiver = bpy.context.object
    receiver.name = "DS_SHADOW_RECEIVER"
    move_to_collection(receiver, ground)

    bpy.ops.mesh.primitive_cylinder_add(vertices=6, radius=1.0, depth=0.04, location=(0.0, 0.0, 0.02))
    footprint = bpy.context.object
    footprint.name = "DS_CELL_GUIDE"
    footprint.display_type = "WIRE"
    footprint.hide_render = True
    move_to_collection(footprint, guides)

    bpy.ops.mesh.primitive_cylinder_add(vertices=48, radius=0.72, depth=0.32, location=(0.0, 0.0, 0.18))
    blockout = bpy.context.object
    blockout.name = "DS_REPLACE_WITH_BUILDING"
    move_to_collection(blockout, building)

    bpy.ops.object.empty_add(type="PLAIN_AXES", location=(0.0, 0.0, 0.0))
    anchor = bpy.context.object
    anchor.name = "DS_GROUND_ANCHOR"
    move_to_collection(anchor, guides)

    scene["ds_camera_azimuth_degrees"] = 45.0
    scene["ds_camera_elevation_degrees"] = 30.0
    scene["ds_sun_elevation_degrees"] = sun_elevation_degrees
    scene["ds_shadow_screen_azimuth_degrees"] = shadow_screen_azimuth_degrees
    scene["ds_pipeline_version"] = 2


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser()
    parser.add_argument("--output", required=True)
    args = sys.argv[sys.argv.index("--") + 1 :] if "--" in sys.argv else []
    return parser.parse_args(args)


def main() -> None:
    args = parse_args()
    output = Path(args.output).resolve()
    output.parent.mkdir(parents=True, exist_ok=True)
    build_scene()
    bpy.ops.wm.save_as_mainfile(filepath=str(output))
    print(f"Saved DestinySphere template: {output}")


if __name__ == "__main__":
    main()

"""Split a transparent 3x2 environment atlas into trimmed game sprites."""

from __future__ import annotations

import argparse
from pathlib import Path

from PIL import Image


FULL_NAMES = (
    "voraner-courtyard-intact",
    "voraner-courtyard-weathered",
    "voraner-courtyard-service",
    "voraner-ruin-arch",
    "voraner-ruin-pylon",
    "voraner-ruin-conduit",
)

RUIN_NAMES = (
    "voraner-ruin-arch-v02",
    "voraner-ruin-pylon-v02",
    "voraner-ruin-conduit-v02",
)


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument("source", type=Path)
    parser.add_argument("destination", type=Path)
    parser.add_argument("--max-size", type=int, default=448)
    parser.add_argument("--layout", choices=("full", "ruins"), default="full")
    args = parser.parse_args()

    atlas = Image.open(args.source).convert("RGBA")
    rows = 2 if args.layout == "full" else 1
    names = FULL_NAMES if args.layout == "full" else RUIN_NAMES
    if atlas.width % 3 or atlas.height % rows:
        raise ValueError(f"atlas dimensions must be divisible by a 3x{rows} grid")

    args.destination.mkdir(parents=True, exist_ok=True)
    cell_width = atlas.width // 3
    cell_height = atlas.height // rows
    for index, name in enumerate(names):
        column = index % 3
        row = index // 3
        sprite = atlas.crop((
            column * cell_width,
            row * cell_height,
            (column + 1) * cell_width,
            (row + 1) * cell_height,
        ))
        bounds = sprite.getchannel("A").getbbox()
        if bounds is None:
            raise ValueError(f"atlas cell {index} is empty")
        padding = 8
        left = max(0, bounds[0] - padding)
        top = max(0, bounds[1] - padding)
        right = min(sprite.width, bounds[2] + padding)
        bottom = min(sprite.height, bounds[3] + padding)
        sprite = sprite.crop((left, top, right, bottom))
        scale = min(1.0, args.max_size / max(sprite.size))
        if scale < 1:
            sprite = sprite.resize(
                (round(sprite.width * scale), round(sprite.height * scale)),
                Image.Resampling.LANCZOS,
            )
        sprite.save(args.destination / f"{name}-color.png", optimize=True, compress_level=9)


if __name__ == "__main__":
    main()

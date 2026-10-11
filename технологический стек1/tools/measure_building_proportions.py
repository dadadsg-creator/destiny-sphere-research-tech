"""Measure visible width-to-height ratios of transparent building renders."""

from __future__ import annotations

import argparse
from pathlib import Path
import sys

import numpy as np
from PIL import Image


def measure(path: Path, threshold: int) -> dict[str, float | int | str]:
    image = Image.open(path).convert("RGBA")
    alpha = np.asarray(image)[:, :, 3]
    ys, xs = np.where(alpha >= threshold)
    if not len(xs):
        raise ValueError(f"No pixels with alpha >= {threshold}: {path}")

    x0, x1 = int(xs.min()), int(xs.max())
    y0, y1 = int(ys.min()), int(ys.max())
    width = x1 - x0 + 1
    height = y1 - y0 + 1
    lower_start = y0 + round(height * 0.65)
    lower_spans = []
    for y in range(lower_start, y1 + 1):
        row_x = np.where(alpha[y] >= threshold)[0]
        if len(row_x):
            lower_spans.append(int(row_x.max() - row_x.min() + 1))
    foundation_width = max(lower_spans)

    return {
        "file": str(path),
        "canvas_width": image.width,
        "canvas_height": image.height,
        "x0": x0,
        "y0": y0,
        "x1": x1,
        "y1": y1,
        "visible_width": width,
        "visible_height": height,
        "bbox_width_per_height": width / height,
        "foundation_width": foundation_width,
        "foundation_width_per_height": foundation_width / height,
    }


def main() -> None:
    if hasattr(sys.stdout, "reconfigure"):
        sys.stdout.reconfigure(encoding="utf-8")
    parser = argparse.ArgumentParser()
    parser.add_argument("images", nargs="+", type=Path)
    parser.add_argument("--alpha", type=int, default=64)
    args = parser.parse_args()
    for image in args.images:
        result = measure(image, args.alpha)
        print(
            f"{result['file']}\n"
            f"  canvas: {result['canvas_width']} x {result['canvas_height']}\n"
            f"  alpha bbox: {result['visible_width']} x {result['visible_height']} "
            f"(W/H={result['bbox_width_per_height']:.3f})\n"
            f"  lower-35% foundation span: {result['foundation_width']} "
            f"(base/H={result['foundation_width_per_height']:.3f})\n"
            f"  bbox: {result['x0']},{result['y0']} .. {result['x1']},{result['y1']}"
        )


if __name__ == "__main__":
    main()

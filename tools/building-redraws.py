import argparse
import base64
import json
import re
from pathlib import Path

from PIL import Image


ATLAS_DEFAULT = Path("building-atlas.html")
MAX_SIDE_DEFAULT = 512


def read_atlas(atlas_path: Path) -> str:
    return atlas_path.read_text(encoding="utf-8")


def write_atlas(atlas_path: Path, html: str) -> None:
    atlas_path.write_text(html, encoding="utf-8")


def parse_manifest(html: str) -> list[dict]:
    match = re.search(r'<script type="application/json" id="atlas-data">(.*?)</script>', html, re.S)
    if not match:
        raise RuntimeError("atlas-data JSON script not found")
    return json.loads(match.group(1))["manifest"]


def parse_redraw_keys(html: str) -> set[str]:
    return set(re.findall(r"'([^']+)': 'data:image/png;base64,", html))


def data_uri_for(path: Path) -> str:
    return "data:image/png;base64," + base64.b64encode(path.read_bytes()).decode("ascii")


def resize_preserving_alpha(
    source: Path,
    target: Path,
    max_side: int,
    corner_alpha_tolerance: int,
    padding: int,
) -> tuple[int, int]:
    image = Image.open(source).convert("RGBA")
    alpha = image.getchannel("A")
    corners = [
        alpha.getpixel((0, 0)),
        alpha.getpixel((image.width - 1, 0)),
        alpha.getpixel((0, image.height - 1)),
        alpha.getpixel((image.width - 1, image.height - 1)),
    ]
    if max(corners) > corner_alpha_tolerance:
        raise RuntimeError(f"{source}: non-transparent corners {corners}")
    if padding:
        padded = Image.new("RGBA", (image.width + padding * 2, image.height + padding * 2), (0, 0, 0, 0))
        padded.alpha_composite(image, (padding, padding))
        image = padded

    scale = max_side / max(image.size)
    size = (max(1, round(image.width * scale)), max(1, round(image.height * scale)))
    if size != image.size:
        image = image.resize(size, Image.Resampling.LANCZOS)

    target.parent.mkdir(parents=True, exist_ok=True)
    image.save(target)
    return size


def set_redraw_data(html: str, key: str, data_uri: str) -> str:
    entry = f"'{key}': '{data_uri}'"
    pattern = re.compile(rf"'{re.escape(key)}': 'data:image/png;base64,[^']*'")
    if pattern.search(html):
        return pattern.sub(entry, html)

    marker = "  const REDRAW_DATA = {\n"
    if marker not in html:
        raise RuntimeError("REDRAW_DATA marker not found")
    return html.replace(marker, f"  const REDRAW_DATA = {{\n    {entry},\n", 1)


def verify_key(html: str, key: str, max_side: int) -> tuple[int, int, int]:
    match = re.search(rf"'{re.escape(key)}': 'data:image/png;base64,([^']*)'", html)
    if not match:
        raise RuntimeError(f"missing REDRAW_DATA entry: {key}")

    path = Path(key)
    actual = path.read_bytes()
    embedded = base64.b64decode(match.group(1))
    if embedded != actual:
        raise RuntimeError(f"embedded bytes differ from file: {key}")

    image = Image.open(path).convert("RGBA")
    alpha = image.getchannel("A")
    corners = [
        alpha.getpixel((0, 0)),
        alpha.getpixel((image.width - 1, 0)),
        alpha.getpixel((0, image.height - 1)),
        alpha.getpixel((image.width - 1, image.height - 1)),
    ]
    if max(image.size) > max_side:
        raise RuntimeError(f"image too large: {key} {image.size}")
    if max(corners) != 0:
        raise RuntimeError(f"non-transparent corners: {key} {corners}")

    return image.width, image.height, len(actual)


def cmd_next(args: argparse.Namespace) -> None:
    html = read_atlas(args.atlas)
    manifest = parse_manifest(html)
    redraw = parse_redraw_keys(html)
    missing = [item for item in manifest if item["file"] not in redraw]

    print(f"redrawn {len(redraw)}")
    print(f"missing {len(missing)}")
    for item in missing[: args.count]:
        parts = [item["file"], item["race"], item["machine"], item["category"]]
        if args.with_ru:
            parts.append(item["ru"])
        print("\t".join(parts))


def cmd_apply(args: argparse.Namespace) -> None:
    html = read_atlas(args.atlas)
    rows = []

    for mapping in args.map:
        if "=" not in mapping:
            raise RuntimeError(f"mapping must be TARGET=SOURCE: {mapping}")
        key, source_name = mapping.split("=", 1)
        target = Path(key)
        source = Path(source_name)
        size = resize_preserving_alpha(
            source,
            target,
            args.max_side,
            args.corner_alpha_tolerance,
            args.padding,
        )
        html = set_redraw_data(html, key, data_uri_for(target))
        rows.append((key, size, target.stat().st_size))

    write_atlas(args.atlas, html)
    for key, size, byte_count in rows:
        print(f"applied {key} {size[0]}x{size[1]} {byte_count}")


def cmd_verify(args: argparse.Namespace) -> None:
    html = read_atlas(args.atlas)
    manifest = parse_manifest(html)
    redraw = parse_redraw_keys(html)
    keys = args.key or [item["file"] for item in manifest if item["file"] in redraw]

    if "REDRAW_FILES" in html:
        raise RuntimeError("REDRAW_FILES still present")

    print(f"data-uri-count {html.count('data:image/png;base64,')}")
    for key in keys:
        width, height, byte_count = verify_key(html, key, args.max_side)
        print(f"ok {key} {width}x{height} {byte_count}")


def build_parser() -> argparse.ArgumentParser:
    parser = argparse.ArgumentParser(
        description="Manage building redraw PNGs and embedded REDRAW_DATA in building-atlas.html."
    )
    parser.add_argument("--atlas", type=Path, default=ATLAS_DEFAULT)
    parser.add_argument("--max-side", type=int, default=MAX_SIDE_DEFAULT)

    subparsers = parser.add_subparsers(dest="command", required=True)

    next_parser = subparsers.add_parser("next", help="Print next missing building redraws from atlas order.")
    next_parser.add_argument("count", type=int, nargs="?", default=10)
    next_parser.add_argument("--with-ru", action="store_true", help="Also print the atlas Russian label.")
    next_parser.set_defaults(func=cmd_next)

    apply_parser = subparsers.add_parser("apply", help="Resize/copy generated PNGs, embed them, and print results.")
    apply_parser.add_argument(
        "--map",
        action="append",
        required=True,
        help="TARGET=SOURCE, for example client/public/assets/buildings/bio/CITY.png=C:/.../generated.png",
    )
    apply_parser.add_argument("--corner-alpha-tolerance", type=int, default=0)
    apply_parser.add_argument("--padding", type=int, default=0, help="Transparent padding added before resize.")
    apply_parser.set_defaults(func=cmd_apply)

    verify_parser = subparsers.add_parser("verify", help="Verify embedded bytes, dimensions, and transparent corners.")
    verify_parser.add_argument("--key", action="append", help="Specific building path to verify. Defaults to all redraws.")
    verify_parser.set_defaults(func=cmd_verify)

    return parser


def main() -> None:
    parser = build_parser()
    args = parser.parse_args()
    args.func(args)


if __name__ == "__main__":
    main()

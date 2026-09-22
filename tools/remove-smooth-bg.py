from collections import deque
from pathlib import Path
import sys

from PIL import Image, ImageFilter


def color_dist(a, b):
    return sum((int(a[i]) - int(b[i])) ** 2 for i in range(3)) ** 0.5


def main():
    if len(sys.argv) != 3:
        raise SystemExit("usage: remove-smooth-bg.py INPUT OUTPUT")

    src_path = Path(sys.argv[1])
    out_path = Path(sys.argv[2])

    src = Image.open(src_path).convert("RGBA")
    rgb = src.convert("RGB")
    w, h = rgb.size
    pix = rgb.load()

    bg = bytearray(w * h)
    q = deque()

    def push(x, y):
        i = y * w + x
        if not bg[i]:
            bg[i] = 1
            q.append((x, y))

    for x in range(w):
        push(x, 0)
        push(x, h - 1)
    for y in range(h):
        push(0, y)
        push(w - 1, y)

    # The generated bad cases have a soft smooth backdrop. Flood fill from the
    # border across smooth color changes, but stop at sharp creature edges.
    while q:
        x, y = q.popleft()
        here = pix[x, y]
        for nx, ny in ((x - 1, y), (x + 1, y), (x, y - 1), (x, y + 1)):
            if nx < 0 or ny < 0 or nx >= w or ny >= h:
                continue
            i = ny * w + nx
            if bg[i]:
                continue
            there = pix[nx, ny]
            if color_dist(here, there) <= 14:
                bg[i] = 1
                q.append((nx, ny))

    mask = Image.new("L", (w, h), 0)
    mask.putdata([255 if v else 0 for v in bg])
    mask = mask.filter(ImageFilter.MaxFilter(3)).filter(ImageFilter.GaussianBlur(0.8))

    alpha = Image.new("L", (w, h), 255)
    alpha_data = []
    for old_a, m in zip(src.getchannel("A").getdata(), mask.getdata()):
        alpha_data.append(max(0, min(255, int(old_a) - int(m))))
    alpha.putdata(alpha_data)

    out = src.copy()
    out.putalpha(alpha)
    out.save(out_path)


if __name__ == "__main__":
    main()

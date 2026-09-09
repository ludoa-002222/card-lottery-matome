"""
マスコット素材（JPEG・白背景）を、サイトで使う透過WebPに変換する。

やること:
  1. 白背景をフラッドフィルで抜く（genre-icons/remove-bg.py と同じ方式）
  2. 残った被写体のバウンディングボックスで切り詰める（余白のせいで小さく見えるため）
  3. 用途ごとの最大サイズへ縮小して WebP で書き出す

【なぜ切り詰めが要るか・2026-09-09】
元素材は正方形キャンバスの中央に小さく描かれており、
そのまま縮小するとヘッダーの32pxではキャラクターが10px程度にしかならない。
透過後のアルファのbboxで切ると、同じ表示サイズでもキャラクターが2〜3倍大きく見える。

【しきい値(tol)について】
背景と近い色が被写体内にあるとフラッドフィルが漏れる。
guide-pose は下部に白い看板があり、これは背景として抜いてよい（キャラクターだけ使うため）。

実行: python3 build.py
出力先: ../../wp-content/themes/oripa-market/assets/img/
"""
import subprocess
import sys
from collections import deque
from pathlib import Path

import numpy as np
from PIL import Image, ImageFilter

OUT_DIR = Path(__file__).resolve().parents[2] / "wp-content/themes/oripa-market/assets/img"

# (元ファイル, 出力名, 最大サイズ, tol, 上から使う割合)
JOBS = [
    ("hero.jpeg", "mascot-hero", 900, 22, 1.0),
    ("footer.jpeg", "mascot-footer", 420, 22, 1.0),
    ("point.jpeg", "mascot-point", 320, 22, 1.0),
    # ヘッダー用は顔と腕だけ使う（全身だと32px枠で顔がつぶれる）
    ("guide-pose.jpeg", "mascot-guide", 220, 22, 0.78),
]


def remove_background(img, tol):
    a = np.asarray(img.convert("RGB")).astype(np.int16)
    h, w, _ = a.shape
    border = np.concatenate([a[0, :], a[-1, :], a[:, 0], a[:, -1]])
    bg = np.median(border, axis=0)
    near_bg = np.max(np.abs(a - bg), axis=2) <= tol

    visited = np.zeros((h, w), dtype=bool)
    q = deque()
    for x in range(w):
        for y in (0, h - 1):
            if near_bg[y, x] and not visited[y, x]:
                visited[y, x] = True
                q.append((y, x))
    for y in range(h):
        for x in (0, w - 1):
            if near_bg[y, x] and not visited[y, x]:
                visited[y, x] = True
                q.append((y, x))
    while q:
        y, x = q.popleft()
        x0 = x
        while x0 > 0 and near_bg[y, x0 - 1] and not visited[y, x0 - 1]:
            x0 -= 1
            visited[y, x0] = True
        x1 = x
        while x1 < w - 1 and near_bg[y, x1 + 1] and not visited[y, x1 + 1]:
            x1 += 1
            visited[y, x1] = True
        for ny in (y - 1, y + 1):
            if 0 <= ny < h:
                for nx in range(x0, x1 + 1):
                    if near_bg[ny, nx] and not visited[ny, nx]:
                        visited[ny, nx] = True
                        q.append((ny, nx))

    alpha = Image.fromarray(np.where(visited, 0, 255).astype(np.uint8), "L")
    alpha = alpha.filter(ImageFilter.MinFilter(3))      # 輪郭の白フチを1px削る
    alpha = alpha.filter(ImageFilter.GaussianBlur(0.7))  # 境界をなめらかに
    return Image.merge("RGBA", (*img.convert("RGB").split(), alpha))


def main():
    src_dir = Path(__file__).resolve().parent
    for name, out_name, max_size, tol, top_ratio in JOBS:
        img = Image.open(src_dir / name)
        if top_ratio < 1.0:
            img = img.crop((0, 0, img.width, int(img.height * top_ratio)))
        rgba = remove_background(img, tol)
        bbox = rgba.getbbox()
        if bbox:
            rgba = rgba.crop(bbox)
        rgba.thumbnail((max_size, max_size), Image.LANCZOS)
        png = src_dir / f"{out_name}.png"
        rgba.save(png)
        webp = OUT_DIR / f"{out_name}.webp"
        subprocess.run(["cwebp", "-quiet", "-q", "88", "-alpha_q", "100", str(png), "-o", str(webp)], check=True)
        png.unlink()
        print(f"  {out_name:14s} {rgba.size[0]}x{rgba.size[1]}  {webp.stat().st_size // 1024}KB")


if __name__ == "__main__":
    sys.exit(main())

"""
商品写真の背景だけを透過にする（v2）。

v1からの修正:
 - 影を「半透明で残す」実装にしたところ、元の淡いグレーのまま半透明になり
   白い霞として残ってしまった。影は残さず完全に抜く方針に変更。
 - 被写体の輪郭に背景色が混ざった1px（アンチエイリアス）が白い縁として残るため、
   背景マスクを1px広げてから境界をぼかす。
 - 「モンスターボールの白い下半分」のように被写体内に背景色と近い部分があると
   フラッドフィルが漏れるため、しきい値は画像ごとに指定できるようにした。
"""
import sys
from collections import deque
import numpy as np
from PIL import Image, ImageFilter

def remove_background(path, tol):
    img = Image.open(path).convert("RGB")
    a = np.asarray(img).astype(np.int16)
    h, w, _ = a.shape
    border = np.concatenate([a[0, :], a[-1, :], a[:, 0], a[:, -1]])
    bg = np.median(border, axis=0)
    near_bg = np.max(np.abs(a - bg), axis=2) <= tol

    visited = np.zeros((h, w), dtype=bool)
    q = deque()
    for x in range(w):
        for y in (0, h - 1):
            if near_bg[y, x] and not visited[y, x]:
                visited[y, x] = True; q.append((y, x))
    for y in range(h):
        for x in (0, w - 1):
            if near_bg[y, x] and not visited[y, x]:
                visited[y, x] = True; q.append((y, x))
    while q:
        y, x = q.popleft()
        x0 = x
        while x0 > 0 and near_bg[y, x0 - 1] and not visited[y, x0 - 1]:
            x0 -= 1; visited[y, x0] = True
        x1 = x
        while x1 < w - 1 and near_bg[y, x1 + 1] and not visited[y, x1 + 1]:
            x1 += 1; visited[y, x1] = True
        for ny in (y - 1, y + 1):
            if 0 <= ny < h:
                for nx in range(x0, x1 + 1):
                    if near_bg[ny, nx] and not visited[ny, nx]:
                        visited[ny, nx] = True; q.append((ny, nx))

    alpha = Image.fromarray(np.where(visited, 0, 255).astype(np.uint8), "L")
    alpha = alpha.filter(ImageFilter.MinFilter(3))       # 前景を1px削って輪郭の白フチを除去
    alpha = alpha.filter(ImageFilter.GaussianBlur(0.7))  # 境界をなめらかに
    out = Image.merge("RGBA", (*img.split(), alpha))
    return out, 100.0 * float(np.mean(visited))

if __name__ == "__main__":
    src, dst, tol = sys.argv[1], sys.argv[2], int(sys.argv[3])
    out, pct = remove_background(src, tol)
    out.thumbnail((600, 600), Image.LANCZOS)
    out.save(dst)
    print(f"  {dst.split('/')[-1]:18s} tol={tol:3d}  背景として抜いた面積={pct:.1f}%")

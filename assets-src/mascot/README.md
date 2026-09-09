# マスコット素材

サイトで使うマスコット画像の元データと変換スクリプト。
配布物（透過WebP）は `wp-content/themes/oripa-market/assets/img/mascot-*.webp`。

## 元素材と用途

| 元ファイル | 出力 | 使う場所 |
|---|---|---|
| `hero.jpeg`（メインビジュアル） | `mascot-hero.webp` | トップページのヒーロー右下 |
| `footer.jpeg`（フッター用） | `mascot-footer.webp` | フッター上辺から顔を出す位置 |
| `point.jpeg`（ワンポイント用） | `mascot-point.webp` | 「データの信頼性について」見出しの右 |
| `guide-pose.jpeg`（右側案内ポーズ） | `mascot-guide.webp` | ヘッダーのサイト名の左 |
| `card-pose.jpeg`（トレカ持ちポーズ） | （未使用） | ヘッダー候補だったが不採用 |

ヘッダーは「右側案内ポーズ」を採用した。指差しがサイト名の方を向くため、
名前へ視線を誘導できる。トレカ持ちポーズは全身で、32px前後の枠だと顔がつぶれる。

## 変換

```bash
python3 build.py
```

やっていること:

1. 白背景をフラッドフィルで抜く（`../genre-icons/remove-bg.py` と同じ方式）
2. **透過後のアルファのbboxで切り詰める**
3. 用途ごとの最大サイズへ縮小し、`cwebp` で透過WebPにする

## やってはいけないこと・つまずいた点

- **切り詰めを省かない。** 元素材は正方形キャンバスの中央に小さく描かれており、
  そのまま縮小するとヘッダーの表示サイズでキャラクターが10px程度にしかならない。
- **ヘッダーに全身像を使わない。** `guide-pose` は上78%だけを切り出して顔と腕を使っている。
- **画像を差し替えたら `functions.php` の `ORIPA_THEME_VERSION` を必ず上げる。**
  静的アセットは `Cache-Control: max-age=31536000` で配信されるため、
  URLが変わらないと本番に反映されない（[[cache-bust-needs-url-change]]）。
  テンプレート側は `?ver=` を明示的に付けている（画像はWordPressが自動で付けてくれない）。

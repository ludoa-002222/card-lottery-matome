# ジャンルアイコンの元画像と作り方

トップページ「カードの種類から探す」で使うジャンル別サムネイルの**元画像**と、
配信用ファイルを作り直すための手順を置いてある。

配信しているのは `wp-content/themes/oripa-market/assets/img/genre-<slug>.webp`（背景透過WebP）。
このフォルダの中身はサイトからは直接参照していない。

| slug | 元画像 | 被写体 |
|---|---|---|
| `pokeka` | `pokeka.jpeg` | ボール |
| `onepiece` | `onepiece.jpeg` | 麦わら帽子 |
| `yugioh` | `yugioh.jpeg` | 三角のペンダント |
| `dragonball` | `dragonball.jpeg` | 星入りの球 |
| `duema` | `duema.jpeg` | カード |

## 作り直す手順

### 1. 背景を透過にする

**4枚（pokeka / onepiece / yugioh / duema）は macOS の Vision（被写体抽出）を使う。**

```bash
swift cut-subject.swift pokeka.jpeg /tmp/pokeka.png
```

**ドラゴンボールだけは Vision を使わない。**
半透明の球で、上部の白いハイライトを背景と誤認して球の上が欠けるため。
背景が均一な白で球がオレンジ＝高コントラストなので、塗りつぶし方式で問題なく抜ける。

```bash
python3 remove-bg.py dragonball.jpeg /tmp/dragonball.png 60
```

> しきい値 60 は影まで消えて球が欠けない値。26 だと球の下に影が白く残る。

### 2. 余白を切り詰めて 600x600 に統一 → WebP へ

透明部分の外周を切り詰め、正方形キャンバスに8%の余白をつけて600x600へ。
5枚で被写体の大きさをそろえるための処理。

```bash
cwebp -q 78 -m 6 -alpha_q 92 -alpha_filter best /tmp/pokeka.png \
  -o ../../wp-content/themes/oripa-market/assets/img/genre-pokeka.webp
```

### 3. テーマのバージョンを上げる

`wp-content/themes/oripa-market/functions.php` の `ORIPA_THEME_VERSION` を必ず上げる。
本番のCSS/JS・画像には1年キャッシュがかかっており、上げないと更新が反映されない。

## やってはいけないこと・試して駄目だったこと

- **明度のしきい値だけで背景を抜かない。** ボールの白い下半分など、被写体の内部にも
  背景と同じ明るさの部分があり、そこまで抜けてしまう。
  外周からつながっている領域だけを抜く（`remove-bg.py` はこの方式）。
- **ポケカを塗りつぶし方式で抜かない。** ボールの白い下半分と背景がほぼ同色で隣接しているため、
  しきい値を10〜26のどれにしても背景が球の内部へ漏れて三日月形に欠ける。Vision を使うこと。
- **影を半透明で残そうとしない。** 元の淡いグレーのまま半透明にすると、
  白い霞が残って汚く見える。影は消す。

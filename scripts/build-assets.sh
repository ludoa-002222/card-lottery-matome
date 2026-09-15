#!/usr/bin/env bash
# CSS/JSのminify版を生成する。
#
# 本番は「サーバー上でgit pull」方式（docs/deploy-server-git-pull.md）で
# ビルド工程が無いため、*.min.css / *.min.js はソースと一緒にコミットする。
# assets/css/style.css・assets/js/*.js を編集したら、コミット前に必ず
#   npm run build:assets
# を実行してminファイルを更新すること（ORIPA_THEME_VERSIONのバージョン
# 上げと同様、忘れると本番にminファイルの内容が反映されない）。
set -euo pipefail

cd "$(dirname "$0")/.."

THEME=wp-content/themes/oripa-market
CSS_DIR="$THEME/assets/css"
JS_DIR="$THEME/assets/js"

## --charset=utf8 が無いとesbuildは既定で日本語などの非ASCII文字を
## \uXXXX エスケープに展開してしまい、UTF-8のままの方が短い日本語主体の
## ファイルではminify後の方が大きくなる（apply-guides.jsで実際に発生・
## 2026-09-15）。必ず指定すること。

npx esbuild "$CSS_DIR/style.css" --minify --charset=utf8 --outfile="$CSS_DIR/style.min.css"

for f in common app apply-guides shop-groups; do
  npx esbuild "$JS_DIR/$f.js" --minify --charset=utf8 --outfile="$JS_DIR/$f.min.js"
done

echo "build:assets 完了"

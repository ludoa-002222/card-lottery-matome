#!/usr/bin/env bash
# ローカルから本番（エックスサーバー）へテーマを rsync でデプロイする。
#   使い方:  sh scripts/deploy-local.sh            # dry-run で差分表示 → y で本実行
#            sh scripts/deploy-local.sh --yes      # 確認なしで本実行
#
# GitHub Actions 版（docs/deploy-github-actions.md）と同じ除外・バックアップ方針。
#
# 実サーバーのSSHユーザー名・パスはリポジトリに書かない。
# scripts/deploy-local.env（gitignore済み）に以下を書いておくか、
# シェル環境変数として export しておくこと:
#   ORIPA_SSH_USER=xxxxxxxx          # エックスサーバーのSSHユーザー名（必須）
#   ORIPA_SSH_KEY=~/.ssh/oripa_xserver     # 省略時のデフォルト
#   ORIPA_SSH_HOST=sv17093.xserver.jp      # 省略時のデフォルト
#   ORIPA_SSH_PORT=10022                   # 省略時のデフォルト
#   ORIPA_REMOTE_WP=/home/xxxxxxxx/oripa-market.com/public_html  # 省略時は ORIPA_SSH_USER から推測
set -euo pipefail

cd "$(dirname "$0")/.."

# ローカル専用設定（gitignore済み）。あれば読み込む。
[ -f scripts/deploy-local.env ] && source scripts/deploy-local.env

SSH_KEY="${ORIPA_SSH_KEY:-$HOME/.ssh/oripa_xserver}"
SSH_HOST="${ORIPA_SSH_HOST:-sv17093.xserver.jp}"
SSH_PORT="${ORIPA_SSH_PORT:-10022}"
: "${ORIPA_SSH_USER:?ORIPA_SSH_USER が未設定です。scripts/deploy-local.env に書くか export してください（例: xs165048 のようなエックスサーバーのSSHユーザー名）}"
SSH_USER="$ORIPA_SSH_USER"
REMOTE_WP="${ORIPA_REMOTE_WP:-/home/$SSH_USER/oripa-market.com/public_html}"
REMOTE_THEME="$REMOTE_WP/wp-content/themes/oripa-market"
LOCAL_THEME="wp-content/themes/oripa-market/"
SSH="ssh -p $SSH_PORT -i $SSH_KEY -o StrictHostKeyChecking=accept-new"

REV=$(git rev-parse --short HEAD)
BRANCH=$(git rev-parse --abbrev-ref HEAD)
if [ -n "$(git status --porcelain)" ]; then
  echo "!! 作業ツリーに未コミットの変更があります。中止します。" >&2
  git status --short >&2
  exit 1
fi
echo "デプロイ対象: $BRANCH @ $REV"
echo

echo "======== 1. サーバー側バックアップ ========"
$SSH "$SSH_USER@$SSH_HOST" bash -se <<REMOTE
set -euo pipefail
TS=\$(date +%Y%m%d-%H%M%S)
BK="\$HOME/backups/oripa-market"
mkdir -p "\$BK"
tar czf "\$BK/theme-\$TS.tar.gz" -C "$REMOTE_WP/wp-content/themes" oripa-market
echo "theme backup: \$BK/theme-\$TS.tar.gz"
if command -v wp >/dev/null 2>&1; then
  wp --path="$REMOTE_WP" db export "\$BK/db-\$TS.sql" --add-drop-table
  echo "db backup:    \$BK/db-\$TS.sql"
fi
ls -1t "\$BK"/theme-*.tar.gz | tail -n +11 | xargs -r rm -f || true
ls -1t "\$BK"/db-*.sql       | tail -n +11 | xargs -r rm -f || true
REMOTE
echo

RSYNC_OPTS=(-az --delete --itemize-changes
  --exclude='.git/' --exclude='bin/' --exclude='.DS_Store' --exclude='*.map'
  -e "$SSH")

echo "======== 2. dry-run（転送予定の差分） ========"
rsync --dry-run "${RSYNC_OPTS[@]}" "$LOCAL_THEME" "$SSH_USER@$SSH_HOST:$REMOTE_THEME/"
echo

if [ "${1:-}" != "--yes" ]; then
  printf "上記の内容で本番へ反映します。よろしいですか? [y/N] "
  read -r ans
  [ "$ans" = "y" ] || [ "$ans" = "Y" ] || { echo "中止しました。"; exit 0; }
fi

echo "======== 3. 本番へ rsync ========"
rsync "${RSYNC_OPTS[@]}" "$LOCAL_THEME" "$SSH_USER@$SSH_HOST:$REMOTE_THEME/"
echo

echo "======== 4. 検証（本番アセット vs repo） ========"
BASE="https://oripa-market.com/wp-content/themes/oripa-market"
fail=0
for f in assets/js/app.js assets/js/common.js assets/css/style.css; do
  curl -fsS "$BASE/$f" -o /tmp/oripa_remote_check
  if diff -q "wp-content/themes/oripa-market/$f" /tmp/oripa_remote_check >/dev/null; then
    echo "OK   $f"
  else
    echo "DIFF $f  ← 本番と repo が不一致"
    fail=1
  fi
done
echo
if [ "$fail" = 0 ]; then
  echo "デプロイ完了。本番テーマは $REV と一致しました。"
else
  echo "!! 一部アセットが一致しません。CDN/キャッシュの可能性もあるので数分後に再確認してください。"
  exit 1
fi

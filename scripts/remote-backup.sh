#!/usr/bin/env bash
# !! 非推奨 !! 本番デプロイは 2026-09-07 からサーバー上 git pull 方式に移行した
# （docs/deploy-server-git-pull.md）。deploy.yml の自動トリガーは止めてあるため
# 現在このスクリプトは呼ばれない。参考として残置。
#
# 本番サーバー上で実行される。GitHub Actions の deploy ワークフローが
#   ssh prod "DEPLOY_PATH=... WP_PATH=... bash -s" < scripts/remote-backup.sh
# の形で標準入力に流し込む。
#
# 環境変数:
#   DEPLOY_PATH  サーバー上のテーマディレクトリの絶対パス（必須）
#   WP_PATH      WordPress ルート。空なら DB バックアップはスキップ（任意）
set -euo pipefail

TS=$(date +%Y%m%d-%H%M%S)
BACKUP_DIR="$HOME/backups/oripa-market"
mkdir -p "$BACKUP_DIR"

if [ -n "${DEPLOY_PATH:-}" ] && [ -d "$DEPLOY_PATH" ]; then
  tar czf "$BACKUP_DIR/theme-${TS}.tar.gz" \
    -C "$(dirname "$DEPLOY_PATH")" "$(basename "$DEPLOY_PATH")"
  echo "theme backup: $BACKUP_DIR/theme-${TS}.tar.gz"
else
  echo "DEPLOY_PATH ($DEPLOY_PATH) が無いのでテーマバックアップはスキップ（初回デプロイ？）"
fi

if [ -n "${WP_PATH:-}" ] && command -v wp >/dev/null 2>&1; then
  wp --path="$WP_PATH" db export "$BACKUP_DIR/db-${TS}.sql" --add-drop-table
  echo "db backup: $BACKUP_DIR/db-${TS}.sql"
else
  echo "WP_PATH 未設定 または wp-cli なし → DB バックアップはスキップ"
fi

# 直近10世代だけ残す
ls -1t "$BACKUP_DIR"/theme-*.tar.gz 2>/dev/null | tail -n +11 | xargs -r rm -f || true
ls -1t "$BACKUP_DIR"/db-*.sql 2>/dev/null | tail -n +11 | xargs -r rm -f || true

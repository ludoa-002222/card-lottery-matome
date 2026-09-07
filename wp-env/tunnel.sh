#!/usr/bin/env sh
# 開発環境を ngrok の固定ドメインで公開する（npm run wp:tunnel から呼ばれる）。
# ドメインは次の優先順で解決する:
#   1. 環境変数 NGROK_DOMAIN
#   2. wp-env/.ngrok-domain ファイル（gitignore 済み。1行にドメインだけ書く）
# authtoken はリポジトリに置かず、各自 `ngrok config add-authtoken <TOKEN>` で設定する。

set -e

PORT=8888
SCRIPT_DIR=$(cd "$(dirname "$0")" && pwd)

DOMAIN="${NGROK_DOMAIN:-}"
if [ -z "$DOMAIN" ] && [ -f "$SCRIPT_DIR/.ngrok-domain" ]; then
	DOMAIN=$(tr -d '[:space:]' < "$SCRIPT_DIR/.ngrok-domain")
fi

if ! command -v ngrok >/dev/null 2>&1; then
	echo "ngrok が見つかりません。 brew install ngrok/ngrok/ngrok でインストールしてください。" >&2
	exit 1
fi

if [ -z "$DOMAIN" ]; then
	cat >&2 <<'MSG'
ngrok の固定ドメインが未設定です。初回のみ以下を実行してください:

  1) https://dashboard.ngrok.com/ で無料アカウントを作成
  2) ngrok config add-authtoken <YOUR_AUTHTOKEN>
  3) ダッシュボードの Domains で無料の静的ドメインを1つ作成
     （例: your-name.ngrok-free.app）
  4) そのドメインを次のいずれかで指定:
       echo 'your-name.ngrok-free.app' > wp-env/.ngrok-domain
       もしくは  export NGROK_DOMAIN=your-name.ngrok-free.app

以後 `npm run wp:tunnel` は毎回この同じURLで公開されます。
MSG
	exit 1
fi

echo "ngrok: https://$DOMAIN  ->  http://localhost:$PORT"
exec ngrok http "$PORT" --domain="$DOMAIN"

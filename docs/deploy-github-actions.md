# GitHub Actions で本番へ自動デプロイ

`main` の `wp-content/themes/oripa-market/**` が変わると、GitHub Actions が
エックスサーバーの本番テーマへ `rsync` する。手動実行（Actions タブ → Deploy theme
to production → Run workflow）でもデプロイでき、`dry_run` で転送内容だけ確認もできる。

ワークフロー本体: [`.github/workflows/deploy.yml`](../.github/workflows/deploy.yml)

---

## 一度だけやるセットアップ

### 1. デプロイ鍵をサーバーに登録

デプロイ用の鍵ペア（公開鍵コメント `oripa-market-deploy`）はすでに作成済み。

- **公開鍵** … エックスサーバー サーバーパネル →「SSH設定」→ 対象サーバーを選択
  →「公開鍵登録・設定」タブに貼り付けて登録。SSH は「ON」にしておく。
- **秘密鍵** … 下の Secrets `SSH_PRIVATE_KEY` に入れる（サーバーには置かない）。

> まだ鍵ペアを作っていない場合:
> `ssh-keygen -t ed25519 -C oripa-market-deploy -f ~/.ssh/oripa-market-deploy`
> 公開鍵 = `~/.ssh/oripa-market-deploy.pub` / 秘密鍵 = `~/.ssh/oripa-market-deploy`

### 2. GitHub Secrets を登録

リポジトリ → Settings → Secrets and variables → Actions → New repository secret

| 名前 | 値 | 例 |
|---|---|---|
| `SSH_PRIVATE_KEY` | 秘密鍵の全文（`-----BEGIN...` から `...END-----` まで、改行込み） | |
| `SSH_HOST` | SSH ホスト名 | `sv17093.xserver.jp` |
| `SSH_PORT` | SSH ポート | `10022` |
| `SSH_USER` | エックスサーバーの SSH ユーザー名 | サーバーパネルの「SSH設定」に表示される |
| `DEPLOY_PATH` | サーバー上の **テーマディレクトリの絶対パス** | `/home/xxxxx/oripa-market.com/public_html/wp-content/themes/oripa-market` |
| `WP_PATH` | （任意）WordPress ルート。設定すると毎回 `wp db export` で DB もバックアップ | `/home/xxxxx/oripa-market.com/public_html` |

`DEPLOY_PATH` は SSH で入って `pwd -P` で確認できる:

```
ssh -p 10022 <SSH_USER>@sv17093.xserver.jp
cd ~/oripa-market.com/public_html/wp-content/themes/oripa-market && pwd -P
```

### 3. production 環境（任意だが推奨）

Settings → Environments → New environment → `production`
→ Required reviewers に自分を追加すると、デプロイのたびに承認ボタンを挟める。
ワークフローは `environment: production` を指定済み。

---

## 使い方

- **通常**: `staging` で作業 → `main` にマージして push → 自動でデプロイ + 検証
- **手動**: Actions タブ → Deploy theme to production → Run workflow
  （`dry_run: true` にすると rsync の転送予定だけ表示して終了）

Claude Code からは「main にマージして push して」でデプロイまで走る。

---

## ワークフローがやること

1. デプロイ鍵で SSH 接続確認
2. サーバー上に **バックアップ**を作成
   - `~/backups/oripa-market/theme-<日時>.tar.gz`（現行テーマ）
   - `~/backups/oripa-market/db-<日時>.sql`（`WP_PATH` 設定時のみ）
   - 直近10世代だけ保持
3. `rsync -az --delete` でテーマを転送
   - **除外**: `bin/`（シードスクリプト）、`.git/`、`.DS_Store`、`*.map`
   - `wp-env/` `mu-plugins/` `static/` はそもそもテーマ外なので対象にならない
4. 本番の `assets/js/*.js` `assets/css/style.css` を取得し、repo と**バイト一致を検証**（違えば失敗）

---

## ロールバック

```
ssh -p 10022 <SSH_USER>@sv17093.xserver.jp
cd ~/backups/oripa-market
ls -t theme-*.tar.gz | head            # 戻したい世代を選ぶ
tar xzf theme-<日時>.tar.gz -C <DEPLOY_PATH の親ディレクトリ>
# DB も戻すなら:  wp --path=<WP_PATH> db import db-<日時>.sql
```

---

## 注意

- このワークフローが同期するのは **テーマファイルだけ**。投稿・タクソノミー・ACF の
  実データ（DB）と `wp-content/uploads/` は対象外。ACF の**フィールド定義**を同期したい
  なら `acf-json/` をコミットに含めること（`Local JSON` が有効なら自動で書き出される）。
- `bin/seed.php` / `bin/seed-web.php` は**本番に送らない**（除外済み）。誤って実行すると
  サンプルデータが投入される。
- キャッシュ更新のため、テーマ変更時は `functions.php` の `ORIPA_THEME_VERSION` を上げる
  （`wp_enqueue_*` の `?ver=` に使われる）。

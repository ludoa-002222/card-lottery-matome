# オリパマーケット（card-lottery-matome）

トレカ（ポケカ・ワンピ・遊戯王など）の抽選販売・予約情報まとめサイト。
`cardchusen.com` の導線・情報設計を参考にしつつ、独自デザインで構築。

このリポジトリには2つの実装が入っています。

| ディレクトリ | 内容 |
|---|---|
| `wp-content/themes/oripa-market/` | **WordPress クラシックPHPテーマ**（本命）。抽選＝カスタム投稿タイプ、カテゴリ/ボックス＝タクソノミー、ACF でメタ管理。詳細は [`docs/wordpress.md`](docs/wordpress.md) |
| `static/` | 最初に作った**静的プロトタイプ**（HTML/CSS/JS + ダミーJSON）。デザインの参照用に保持 |

## WordPress版（wp-env）

Docker Desktop が必要です。

```bash
npm install            # 初回のみ（@wordpress/env などを取得）
npm run wp:start       # WordPress を http://localhost:8888 で起動（初回はイメージDLで数分）
npm run wp:activate    # テーマ + ACF を有効化し、パーマリンクを /%postname%/ に
npm run wp:seed        # static/data/*.json 相当のサンプルデータを投入
```

- サイト: http://localhost:8888/ 　管理画面: http://localhost:8888/wp-admin/（`admin` / `password`）
- 停止 `npm run wp:stop` ／ 破棄 `npm run wp:destroy`
- `npm run wp:setup` は start → activate → seed をまとめて実行

投入されるもの: カード種類5・ボックス8・店舗8・抽選12・攻略コラム5・固定ページ11。

### 開発環境URLの発行（公開トンネル）

チームに公開URLを渡すときは ngrok の固定ドメインを使う。**毎回同じURL**で公開できる。

```bash
brew install ngrok/ngrok/ngrok            # 初回のみ
ngrok config add-authtoken <YOUR_TOKEN>   # 初回のみ（https://dashboard.ngrok.com/ で取得）
# ダッシュボードの Domains で無料の静的ドメインを1つ作成し、ローカルに登録:
echo 'your-name.ngrok-free.app' > wp-env/.ngrok-domain   # 初回のみ（gitignore 済み）

npm run wp:start           # wp-env が起動していること
npm run wp:tunnel          # → https://your-name.ngrok-free.app で公開（毎回同じURL）
```

- ドメインは `NGROK_DOMAIN` 環境変数でも指定可（`wp-env/.ngrok-domain` より優先）
- `wp-env/mu-plugins/00-dynamic-siteurl.php`（mu-plugin）がリクエストの Host に追従して
  `home` / `siteurl` / テーマ・アセット・REST の各URLを書き換えるため、トンネル経由でもリダイレクトせず動く
- この mu-plugin は **ローカル開発専用**。本番には配置しない
- トンネルを止めている間は公開URLにアクセスできない（再開すれば同じURLで復活）

トンネルではなく **サーバー上に公開して固定URLにする**場合:

- **本番 `oripa-market.com` へ自動デプロイ**（`main` に push すると GitHub Actions が rsync）
  … [`docs/deploy-github-actions.md`](docs/deploy-github-actions.md)
- エックスサーバーの `dev.oripa-market.com`（サブドメイン）に載せる手順 … [`docs/deploy-xserver.md`](docs/deploy-xserver.md)
  （既存サイトは触らない。FTP/SSH 不要。`npm run theme:zip` の `oripa-market.zip` を管理画面からアップロード）
- 無料ホスティング等の他の選択肢 … [`docs/deploy-free.md`](docs/deploy-free.md)

## 静的プロトタイプ（static/）

`fetch()` で `static/data/*.json` を読むため、`file://` では動きません。

```bash
npm run static:dev     # live-server で http://localhost:5500/（自動リロード）
npm run static:serve   # 依存なし: python3 -m http.server（static/ を配信）
```

## WordPress テーマの構成（要点）

- `functions.php` → `inc/` 分割ロード
  - `inc/post-types.php` … CPT `lottery` / `shop` / `column`
  - `inc/taxonomies.php` … `card_category`（正式名称の term meta 付き）/ `card_box`（階層・親=カテゴリ）/ `column_category` / `shop_area`
  - `inc/acf-fields.php` … ACF フィールド群をコードで登録（GUI 編集分は `acf-json/` に同期）
  - `inc/rest-api.php` … `/wp-json/oripa/v1/{categories,boxes,shops,lotteries,articles,bootstrap}` 。静的版 `data/*.json` と同じ形を返す
  - `inc/enqueue.php` … `assets/css/style.css` + `assets/js/common.js` + `app.js` を読み込み、`window.ORIPA` にREST URL等を渡す
  - `inc/members.php` … 会員登録・ログインを WP 標準フローに接続（ログイン後 `/mypage/` へ）
- テンプレート: `front-page.php` / `taxonomy-card_category.php` / `taxonomy-card_box.php` / `archive-lottery.php` / `single-lottery.php` / `archive-column.php` / `single-column.php` / `archive-shop.php` / `page-{online,store,calendar,trust,register,mypage}.php` / `page.php`
- 動的表示（カウントダウン・「◯分前に確認」・信頼スコア・絞り込み）は `assets/js/{common,app}.js` が担当。データ取得先は `window.ORIPA.restBase`

## データ更新運用（想定）

- 抽選の「最終確認日時」は ACF `last_checked`（未入力なら投稿の最終更新日時）。一覧の「最終更新 M/D H:M」「◯分前に確認」はここから算出
- 会社概要ページ（`/company/`）は正式情報が確定次第、本文の `［ ］` を差し替える

## 未実装（今回のスコープ外）

- 巡回クローラ・掲載審査ワークフロー自体（本リポジトリ外の別プロジェクト。受け口の
  `POST /wp-json/oripa/v1/sync/lotteries` はテーマ側に実装済み、詳細は
  [`docs/wordpress.md`](docs/wordpress.md) の REST API 節）
- 会員向け機能の中身（お気に入り抽選、新着通知）
- 当選率投票・買取価格予想などのUGC機能

## 本番デプロイ

`main` の `wp-content/themes/oripa-market/**` が変わると GitHub Actions が自動で
エックスサーバーの本番（`oripa-market.com`）へ rsync する。手順・ロールバックは
[`docs/deploy-github-actions.md`](docs/deploy-github-actions.md)、本番 `.htaccess` の設定は
[`docs/production-server-config.md`](docs/production-server-config.md) を参照。
ローカルから直接デプロイしたい場合は `scripts/deploy-local.sh` も使える
（`scripts/deploy-local.env` にSSHユーザー名等を設定。gitignore済み）。

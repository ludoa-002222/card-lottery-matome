# WordPress版 詳細

静的プロトタイプ（`static/`）を、クラシックPHPテーマ `oripa-market` として WordPress 化したもの。
ローカル環境は [wp-env](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/)（Docker）。

## セットアップ

```bash
npm install
npm run wp:start      # http://localhost:8888（初回はイメージ取得で数分）
npm run wp:activate   # theme + ACF 有効化、パーマリンク /%postname%/
npm run wp:seed       # サンプルデータ投入（冪等。再実行で更新）
```

| コマンド | 内容 |
|---|---|
| `npm run wp:setup` | start + activate + seed |
| `npm run wp:cli -- <args>` | 例: `npm run wp:cli -- post list --post_type=lottery` |
| `npm run wp:stop` / `wp:destroy` | 停止 / 破棄 |
| `npm run wp:logs` | PHP/Apache ログ |

管理画面: `http://localhost:8888/wp-admin/`（`admin` / `password`）

## データモデル

### カスタム投稿タイプ

| CPT | slug | 用途 | 主なメタ（ACF） |
|---|---|---|---|
| `lottery` | `/lottery/<slug>/` | 抽選1件 | `method`(online/store), `shop`(post_object→shop), `deadline`(datetime), `member_required`, `id_required`, `round_no`, `round_total`, `last_checked` |
| `shop` | `/shop/<slug>/` | 店舗 | `is_online`, `is_store`（エリアは `shop_area` タクソノミー） |
| `column` | `/column/<slug>/` | 攻略コラム | `read_min`（本文=標準エディタ、抜粋=標準抜粋） |

### タクソノミー

| タクソノミー | 対象 | 階層 | 備考 |
|---|---|---|---|
| `card_category` | lottery, column | あり | term meta `oripa_full_name`（正式名称。見出しに使用） |
| `card_box` | lottery | あり | 親 term = カテゴリ相当（`card_category` と同 slug の別タクソノミー term）。子 term = 実際のボックス |
| `column_category` | column | あり | 安く入手 / 高く売る / 応募方法 |
| `shop_area` | shop | なし | 東京 / 大阪 / … |

> `lottery` には「親 term（カテゴリ相当）＋子 term（ボックス）」の両方を `card_box` に付与している。
> REST は親を除いた子 term の slug を `box` として返す。

## REST API

すべて公開（読み取り専用）。静的版 `data/*.json` と同じ形。

```
GET /wp-json/oripa/v1/categories   → [{slug,name,fullName}]
GET /wp-json/oripa/v1/boxes        → [{slug,category,name}]
GET /wp-json/oripa/v1/shops        → [{id,name,area,isOnline,isStore}]   id=投稿ID（文字列）
GET /wp-json/oripa/v1/lotteries    → [{id,category,box,shopId,method,memberRequired,idRequired,deadline,roundNo,roundTotal,updatedAt,permalink}]
GET /wp-json/oripa/v1/articles     → [{slug,category,title,excerpt,updatedAt,readMin,body[],permalink}]
GET /wp-json/oripa/v1/bootstrap    → {categories,boxes,shops,lotteries,articles}   ← トップ等はこれ1本
```

- 日時は `wp_timezone()`（Asia/Tokyo）基準の ISO8601 で返す
- `lottery.updatedAt` = `last_checked` があればそれ、無ければ投稿の最終更新日時

## フロントの描画

`assets/js/common.js`（ヘルパ）+ `assets/js/app.js`（ページ別 init）。
`<body data-page="...">`（`inc/template-helpers.php::oripa_page_key()`）で分岐。

| data-page | 描画内容 | データ源 |
|---|---|---|
| `home` | カテゴリタイル / 信頼統計 / 全抽選＋絞り込み | `/bootstrap` |
| `category` `box` | ボックスグリッド / オンライン・店頭の絞り込み一覧 | `/bootstrap` + `<main data-cat data-box>` |
| `lottery-archive` | 全抽選＋絞り込み | `/bootstrap` |
| `guide` | 記事グリッド / カテゴリメニュー / ランキング | `/articles`（`?colcat=` or `<main data-colcat>` で絞り込み） |
| `article` | 右カラムのランキングのみ（本文はPHP） | `/articles` |
| `shop` | 店舗テーブル（信頼スコアはJSでダミー生成） | `/bootstrap` |
| `page-online` `page-store` | 方式固定の絞り込み一覧 | `/bootstrap` |
| `page-calendar` | 月カレンダー＋日別ドリルダウン | `/bootstrap` |

`window.ORIPA`（`inc/enqueue.php` で localize）: `restBase` / `nonce` / `assetsBase` / `categoryBase` / `boxBase` / `isLoggedIn` / `loginUrl` / `registerUrl` ほか。

## 会員機能

`inc/members.php`。

- 新規登録は `wp-login.php?action=register`（`page-register.php` のフォームがPOST）。`users_can_register` は常時ON
- ログインは `page-mypage.php` の `wp_login_form()`。ログイン後は `/mypage/` へリダイレクト（管理者は通常挙動）
- `/mypage/` はログイン時に簡易ダッシュボード（表示名・メール・登録日・ログアウト）

## サンプルデータ投入（seed）

`wp-content/themes/oripa-market/bin/seed.php`（`wp eval-file`）。
`bin/seed-data/*.json`（`static/data/*.json` のコピー）を読み、CPT・タクソノミー・ACF値・固定ページを作成。
**slug で冪等** なので何度実行してもよい（コラムは更新日時も上書き）。

固定ページ11件を作成: `online` `store` `calendar` `trust` `about` `faq` `company` `terms` `privacy` `mypage` `register`
（`online/store/calendar/trust/register/mypage` は `page-<slug>.php` が自動採用、`about/faq/company/terms/privacy` は `page.php` ＋ 本文HTML）。

## ACF フィールド

`inc/acf-fields.php` で `acf_add_local_field_group()` によりコード登録（プラグイン無効でも `register_post_meta` のフォールバックあり）。
管理画面でフィールドを編集すると `acf-json/` に JSON が書き出され、Git 管理できる。

## よくある変更

| やりたいこと | 場所 |
|---|---|
| 抽選のメタ項目を追加 | `inc/acf-fields.php`（＋必要なら `inc/rest-api.php` の `oripa_rest_lotteries()`） |
| ヘッダー/フッターのリンク | `inc/template-helpers.php` の `oripa_header_nav_items()` / `oripa_footer_link_items()` |
| 一覧カードの見た目 | `assets/js/common.js` の `lotteryCardHtml()` |
| 配色・レイアウト | `assets/css/style.css`（静的版と共通のもの） |
| サンプルデータ | `bin/seed-data/*.json` を差し替えて `npm run wp:seed` |

## 未対応 / 今後

- 本番デプロイ構成（現状 wp-env のみ）。テーマは `wp-content/themes/oripa-market/` をそのまま持ち出せる
- 管理画面の一覧カラム／絞り込みの作り込み（現状は WP 既定 + `show_admin_column`）
- 会員向け機能の実装（お気に入り、通知）
- `card_box` の「親=カテゴリ」を term 二重管理している点は、将来 `card_category` との関連メタに寄せる余地あり

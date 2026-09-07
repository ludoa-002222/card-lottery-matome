# エックスサーバーの「dev.oripa-market.com」に新サイトを立てる

`oripa-market.com` には既存サイトが動いているので、**触らない**。
リニューアル版（このリポジトリ）は **サブドメイン `dev.oripa-market.com`** に
まったく別の WordPress として入れる。既存サイトのファイルもデータベースも一切変更しない。

- **FTP も SSH も不要**。すべてブラウザ（サーバーパネル + WordPress管理画面）だけで完結する。
- テーマの ZIP はビルド済み: リポジトリ直下の **`oripa-market.zip`**（`npm run theme:zip` で作り直せる）。
- サブドメインは既存サイトとは別ディレクトリ・別DBになるので安全。公開URLの準備ができたら
  この中身をそのまま本ドメインへ移せばよい（移行は後日の作業）。

---

## 1. サブドメインを作る

1. サーバーパネル → **「サブドメイン設定」** → `oripa-market.com` を選択
2. 「サブドメイン設定追加」タブ → サブドメイン欄に **`dev`** と入力 → 確認 → 追加
3. 同じネームサーバー内なので数分〜十数分で `dev.oripa-market.com` が有効になる
   （既存の `oripa-market.com` の表示には影響しない）

## 2. サブドメインに無料SSL（https）を追加

1. サーバーパネル → **「SSL設定」** → `oripa-market.com`
2. 「独自SSL設定追加」タブ → 対象に **`dev.oripa-market.com`** を選択 → 追加
3. 反映まで最大1時間ほど

## 3. サブドメインを検索エンジン・第三者から隠す（作りかけを見せない）

1. サーバーパネル → **「アクセス制限」** → `dev.oripa-market.com` を選択 → **ONにする**
2. 「ユーザー設定」でユーザー名・パスワードを登録（チームに共有する用）

これで `dev.oripa-market.com` はBasic認証がかかり、Googleにも拾われない。

## 4. WordPress をインストール

1. サーバーパネル → **「WordPress簡単インストール」**
2. **`dev.oripa-market.com`** を選択 →「WordPressインストール」タブ
3. 入力:
   - サイトURL: `dev.oripa-market.com`（右の欄は空のまま）
   - サイト名: 任意（後で変更可）
   - ユーザー名 / パスワード / メールアドレス: 控えておく（既存サイトとは別のログイン）
   - データベース: **「自動でデータベースを生成する」**（← 既存サイトのDBとは別物が作られる）
4. 「インストールする」→ 確認 →「インストールする」
5. 5〜10分待って `https://dev.oripa-market.com/wp-admin/` にログインできればOK
   （手順3のBasic認証 → WordPressのログイン、の2段階になる）

## 5. テーマを入れる

1. `wp-admin` → **外観 → テーマ → 新規追加 → テーマのアップロード**
2. リポジトリ直下の **`oripa-market.zip`** を選択 →「今すぐインストール」
3. インストール後「有効化」

## 6. ACF プラグインを入れる

1. `wp-admin` → **プラグイン → 新規追加**
2. 「Advanced Custom Fields」を検索 →「今すぐインストール」→「有効化」

## 7. パーマリンクを設定

`wp-admin` → **設定 → パーマリンク** → **「投稿名」** を選んで保存

## 8. サンプルデータを入れる

WordPress管理画面に**管理者でログインした状態のまま**、ブラウザで次のURLを開く:

```
https://dev.oripa-market.com/wp-content/themes/oripa-market/bin/seed-web.php?token=x
```

- ログが流れて最後に **「seed 完了。」** と出れば成功。`https://dev.oripa-market.com/` を確認。
- `403 Forbidden` が出たらログインが切れている。管理画面に入り直してから再度開く。
  （ログインを使わずトークンで実行したいときは、サーバーパネルの「ファイルマネージャ」で
  `dev.oripa-market.com/public_html/wp-config.php` を開き
  `define( 'ORIPA_SEED_TOKEN', '16文字以上の好きな文字列' );` を1行足してから
  `?token=その文字列` でアクセスし、終わったらその1行を消す。）

## 9. 後片付け

- 気になるなら、ファイルマネージャで
  `dev.oripa-market.com/public_html/wp-content/themes/oripa-market/bin/seed-web.php` を削除。
- 手順8でトークン方式を使ったなら、`wp-config.php` に足した1行も削除。

---

## テーマを更新したいとき

1. ローカルで `npm run theme:zip` → `oripa-market.zip` が新しくなる
2. `wp-admin` → 外観 → テーマ → 新規追加 → テーマのアップロード → 同じ ZIP を上げる
   （同名なので「テーマを更新」扱いになる）

頻繁に更新するなら、エックスサーバーの SSH + `git` 運用に切り替えられる（`docs/deploy-free.md` の A 参照）。

## 本番（oripa-market.com）へ切り替えるとき（後日）

- 方法1: 既存サイトを別サブドメイン等に退避し、`oripa-market.com` の「WordPress簡単インストール」or
  「WordPress移行」で `dev` の内容を移す
- 方法2: プラグイン（All-in-One WP Migration 等）で `dev` → 本ドメインへエクスポート／インポート
- どちらも今やる必要はない。準備ができてから相談。

## アップロードしてはいけないもの

`oripa-market.zip` に入っているのは `wp-content/themes/oripa-market/` だけ。
`wp-env/`、`wp-content/mu-plugins/00-dynamic-siteurl.php`、`static/`、`node_modules/` は
**ローカル専用なので載せない**（zip にも含まれていない）。

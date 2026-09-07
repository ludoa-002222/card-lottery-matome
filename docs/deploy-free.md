# 常時見れる開発環境URLを作る

トンネル（ngrok / cloudflared）は「自分のPCを一時的に公開する」方式なので、PCを閉じると切れる。
チームがいつでも開けるURLが欲しい場合は、**サーバー上にデプロイする**。

| 方式 | URL | 費用 | 常時稼働 | 手間 |
|---|---|---|---|---|
| **A. エックスサーバー**（契約済みならこれ） | 独自ドメイン or `xxx.xsrv.jp` | 契約済みなら追加0円 | ○ | 小〜中 |
| B. InfinityFree | `https://xxx.infinityfreeapp.com` | 完全無料・期限なし | ○ | 中 |
| C. GitHub Codespaces | `https://xxx-8888.app.github.dev` | 無料枠 月60時間 | ×（30分放置で停止） | 小 |
| D. Oracle Cloud Always Free | 無料ドメイン + Let's Encrypt | 無料・期限なし | ○ | 大 |

**このプロジェクトは WordPress テーマ**なので、PHP + MySQL が動くホストが必要。
エックスサーバーを持っているなら A が最短・最安（追加費用なし）。

## Firebase / Supabase は使えるか？

**どちらも WordPress とは組み合わせられない。**

- **Firebase Hosting** … 静的ファイル配信のみで PHP を実行できない → WordPress 本体が動かない。
  `static/`（静的プロトタイプ）の公開先としてなら使える（`firebase deploy` で `static/` を配信）。
- **Supabase** … PostgreSQL ベース。WordPress は MySQL / MariaDB 必須なので DB として使えない。
  Supabase を活かすなら「WordPress をやめてフロントを静的サイト + Supabase API に作り替える」
  という別プロジェクト級のリライトになる。今回のスコープ外。

→ WordPress のまま進めるなら **A（エックスサーバー）** が答え。

---

## A. エックスサーバー（推奨・契約済みの場合）

WordPress 専用に作られた共有レンタルサーバー。管理パネルに WordPress 自動インストール、
無料独自SSL、SSH + WP-CLI が揃っているので、ローカルの `npm run wp:seed` とほぼ同じ手順で入る。

### 手順

1. **設置先ドメインを決める**
   - サーバーパネル → 「ドメイン設定」。独自ドメイン、サブドメイン（例 `dev.example.com`）、
     または初期ドメイン `<サーバーID>.xsrv.jp` のいずれか
   - 「無料独自SSL」を有効化（反映まで最大1時間ほど）

2. **WordPress を導入**
   - サーバーパネル → 「WordPress簡単インストール」→ 上のドメインを選択
   - サイトURL・管理ユーザー・パスワードを設定。DB は自動作成される
   - 「サイトURL」は空欄（ドメイン直下）を推奨

3. **SSH を有効化**
   - サーバーパネル → 「SSH設定」→ ON、公開鍵を登録
   - `ssh <サーバーID>@<サーバーID>.xsrv.jp` で接続確認

4. **テーマを配置**（SSH + git がラク）
   ```bash
   # SSH接続後。WordPress の設置ディレクトリへ
   cd ~/<ドメイン>/public_html/wp-content/themes/
   git clone <このリポジトリのURL> _oripa && mv _oripa/wp-content/themes/oripa-market ./ && rm -rf _oripa
   # もしくは git を使わず、ローカルから SFTP で
   #   wp-content/themes/oripa-market/ を同じ場所へアップロード
   ```
   - **アップロード不要**: `wp-env/`、`wp-content/mu-plugins/`、`node_modules/`、`static/`、`docs/`
     （`00-dynamic-siteurl.php` はローカルのトンネル専用。固定ドメインでは不要）

5. **ACF プラグイン + テーマ有効化 + パーマリンク**（WP-CLI）
   ```bash
   cd ~/<ドメイン>/public_html/
   wp plugin install advanced-custom-fields --activate
   wp theme activate oripa-market
   wp rewrite structure '/%postname%/' --hard
   ```
   （WP-CLI が見つからない場合は `wp-admin` から手動でも可）

6. **サンプルデータ投入**（ローカルと同じスクリプトがそのまま使える）
   ```bash
   wp eval-file wp-content/themes/oripa-market/bin/seed.php
   ```
   SSH が使えない場合のみ、ブラウザ実行版（`bin/seed-web.php`）を使う（手順は下の「B」参照）。

7. 完了。`https://<ドメイン>/` が常時アクセスできる開発環境URL。

### 更新のしかた

SSH で該当ディレクトリに入って `git pull`（git clone で入れた場合）、
または SFTP で `wp-content/themes/oripa-market/` を上書きアップロード。

---

## B. InfinityFree（無料で済ませたい場合）

無料の PHP + MySQL ホスティング。cPanel と WordPress 自動インストーラ付き。

### 手順

1. **アカウント作成 / サイト作成**
   - <https://infinityfree.com> で登録 → 「Create Account」
   - サブドメインを選ぶ（例: `oripa-market.infinityfreeapp.com`）か、無料独自ドメインを設定
   - コントロールパネルが使えるようになるまで数分待つ

2. **WordPress を導入**
   - コントロールパネル → 「WordPress」または「Softaculous Apps Installer」
   - インストール先ディレクトリは空（ドメイン直下）
   - 管理ユーザー / パスワードを控える。DB は自動作成される

3. **無料SSLを有効化**
   - パネルの「Free SSL Certificate」で Let's Encrypt を発行（反映まで最大数十分）
   - 反映後、`wp-admin` → 設定 → 一般 で「WordPress アドレス」「サイトアドレス」を `https://` に

4. **ACF プラグインを入れる**
   - `wp-admin` → プラグイン → 新規追加 → 「Advanced Custom Fields」（無料版）→ インストール＆有効化

5. **テーマを配置**（FTP）
   - パネルの FTP アカウント情報で FileZilla などから接続
   - ローカルの `wp-content/themes/oripa-market/` を、サーバーの `htdocs/wp-content/themes/oripa-market/` へアップロード
   - **アップロード不要**: `wp-env/`、`wp-content/mu-plugins/`、`node_modules/`、`static/`、`docs/`
     （`00-dynamic-siteurl.php` は**ローカル専用**。固定ドメインでは不要）
   - `wp-admin` → 外観 → テーマ → 「オリパマーケット」を有効化

6. **パーマリンク**
   - `wp-admin` → 設定 → パーマリンク → 「投稿名」を選択して保存

7. **サンプルデータ投入**（InfinityFree は WP-CLI が無いのでブラウザ実行版を使う）
   - 16文字以上のランダム文字列を用意（例: `openssl rand -hex 24`）
   - それを `wp-content/themes/oripa-market/bin/.seed-token` というファイルに書き、
     `bin/seed-web.php` と一緒に FTP でアップロード
   - ブラウザで開く:
     `https://<サイト>/wp-content/themes/oripa-market/bin/seed-web.php?token=<その文字列>`
   - `seed 完了。` が出たら、**`seed-web.php` と `.seed-token` を FTP で削除**

8. 完了。`https://<サイト>/` が常時アクセスできる開発環境URL。

### InfinityFree の制限（開発用途なら許容範囲）

- 1日あたりのアクセス数上限、PHP実行時間 10秒程度の上限
- WP-Cron は不定期（予約投稿などは遅延しうる）
- REST API は動くが高負荷には向かない
- サイトへの広告挿入は**されない**（表示は自分のWordPressそのまま）

### 更新のしかた

テーマを直したら `wp-content/themes/oripa-market/` を FTP で上書きアップロード。
（Git 連携はできないので手動。頻繁に更新するなら方式 A か D を検討）

---

## C. GitHub Codespaces

このリポジトリをクラウド上の VS Code コンテナで開き、`wp-env` をそのまま動かす。
ポート転送で `https://<codespace>-8888.app.github.dev` が発行される。

- 長所: リポジトリそのまま動く。新しいホスティング契約が不要。無料枠 月60コア時間
- 短所: **30分アクセスが無いと自動停止**し、その間 URL は 502。再度開くまで復帰しない
- 手順の要点:
  1. GitHub のリポジトリページ → Code → Codespaces → Create
  2. ターミナルで `npm install && npm run wp:setup`
  3. 「Ports」タブで 8888 を「Public」に変更 → URLをコピー
  4. `wp-env` は Docker を使うので、Codespace の spec は 4-core 以上推奨

---

## D. Oracle Cloud Always Free

無料のクラウドVM（ARM Ampere など、期限なし）に Docker で立てる。広告なし・制限ゆるめだが構築の手間が最大。

- VM 作成（Always Free 対象シェイプ）→ SSH → Docker / Docker Compose 導入
- `wp-env` 相当の `docker-compose.yml`（WordPress + MariaDB）を置いて起動
- 無料ドメイン（`*.duckdns.org` など）+ Caddy か Nginx + Let's Encrypt で HTTPS
- テーマは `git clone` で配置、シードは `docker compose exec` で WP-CLI 実行

必要ならこの構成の `docker-compose.yml` と手順を用意する。

---

## どれを選んでも共通の注意

- `wp-env/mu-plugins/00-dynamic-siteurl.php` は**デプロイしない**（ローカルのトンネル専用）
- 固定ドメインなので、WordPress の「一般設定」で URL を普通に設定すればよい
- `bin/seed-web.php` と `bin/.seed-token` は**投入後に必ず削除**する

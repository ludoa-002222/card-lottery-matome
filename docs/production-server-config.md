# 本番サーバー設定（.htaccess等）

このリポジトリは `wp-content/themes/oripa-market/`（テーマ本体）のみを管理対象としており、
`.htaccess` 等のドキュメントルート直下のサーバー設定ファイルは通常バージョン管理していない。

ただし、2026-09-07 に本番へ直接適用された重要な設定変更（Application Password認証を
機能させるためのAuthorizationヘッダー透過設定）があるため、バックアップ・記録として
現在の本番 `.htaccess`（`oripa-market.com/public_html/.htaccess`）の内容をここに残す。

## 現在の本番 `.htaccess`（2026-09-07時点）

```apache
<IfModule mod_expires.c>
  ExpiresActive On
  # ファイル名にコンテンツハッシュが入っているVite成果物（/assets/配下）は
  # 内容が変われば必ずファイル名も変わるため、1年キャッシュしても安全。
  ExpiresByType application/javascript "access plus 1 year"
  ExpiresByType text/css "access plus 1 year"
  ExpiresByType font/woff2 "access plus 1 year"
  # 商品画像・アイコン等はファイル名が固定のまま差し替えられる可能性があるため、
  # 1年ではなく30日に留める。
  ExpiresByType image/webp "access plus 30 days"
  ExpiresByType image/png "access plus 30 days"
  ExpiresByType image/jpeg "access plus 30 days"
</IfModule>
<IfModule mod_headers.c>
  Header set Cache-Control "public, max-age=31536000, immutable" "expr=%{REQUEST_URI} =~ m#^/assets/#"
  Header set Cache-Control "public, max-age=31536000, immutable" "expr=%{REQUEST_URI} =~ m#^/fonts/.+\.woff2$#"
  # wp-content/uploads配下（商品画像等）はXServer側のデフォルト設定で
  # 7日キャッシュに固定されており、上のExpiresByTypeが効かないため、
  # mod_headersで明示的に上書きする。管理画面から差し替えられる可能性が
  # あるファイルのため、assetsやfontsとは異なりimmutableは付けない。
  Header set Cache-Control "public, max-age=2592000" "expr=%{REQUEST_URI} =~ m#^/wp-content/uploads/.*\.(webp|jpe?g|png|gif|svg)$#"
</IfModule>

<IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteBase /

  # Application Password（REST API Basic認証）が機能するために必須。
  # XServer等の一部レンタルサーバーでは、Authorizationヘッダーが
  # デフォルトでPHPまで届かず、WP REST APIのApplication Password認証が
  # 常に401（rest_not_logged_in）になる。この2行で明示的に透過させる。
  # （2026-09-07: Notion→WordPress同期の疎通確認中に発覚・追加）
  RewriteCond %{HTTP:Authorization} ^(.*)
  RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

  # www あり -> www なし（https化も同時に行う）
  RewriteCond %{HTTP_HOST} ^www\.(.+)$ [NC]
  RewriteRule ^ https://%1%{REQUEST_URI} [L,R=301]

  # http -> https
  RewriteCond %{HTTPS} off
  RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

  # WordPress標準のパーマリンクルール。
  # 実在しないファイル/ディレクトリへのリクエストは index.php（WordPress）に渡す。
  # wp-admin・wp-content・wp-includes・wp-json は実ファイルとして存在するため、
  # ここより前で直接配信される。それ以外（Reactのルーティング対象パス）は
  # WordPressのテーマ（wp-content/themes/oripa-spa-theme/index.php）が index.html を返す。
  RewriteRule ^index\.php$ - [L]
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{REQUEST_FILENAME} !-d
  RewriteRule . /index.php [L]
</IfModule>
```

## 補足

- コメント内の `oripa-spa-theme` という記述は、現行のクラシックPHPテーマ `oripa-market` に
  移行する前の旧構成（React SPA）の名残と思われる。動作には影響しない。
- このファイルを新しいサーバーに移設する場合、必ず `RewriteCond %{HTTP:Authorization}` の
  2行を含めること（含めないとNotion→WordPress同期のApplication Password認証が失敗する）。

# 本番デプロイ（サーバー上 git pull 方式）— 現行の方法

2026-09-07 以降、本番（`oripa-market.com`、エックスサーバー）へのテーマ反映は
**サーバー上でこのリポジトリを直接 `git pull` する方式**を正としている。
GitHub Actions による rsync 方式（[`docs/deploy-github-actions.md`](./deploy-github-actions.md)）は
この方式と競合するため**使用しない**（Secretsも設定していない。詳細は同ドキュメントの追記を参照）。

## 仕組み

サーバー上に本リポジトリを clone した作業ツリーがあり、本番WordPressのテーマディレクトリは
そこへの **シンボリックリンク**になっている。

```
~/repos/card-lottery-matome/                              ← このリポジトリの git clone（main を追跡）
  └─ wp-content/themes/oripa-market/                       ← 実体

~/oripa-market.com/public_html/wp-content/themes/
  └─ oripa-market -> ~/repos/card-lottery-matome/wp-content/themes/oripa-market   ← symlink
```

そのため **`git pull` した瞬間に本番の配信内容が変わる**。rsync方式のような
「dry-run → 確認 → 本転送」という段階も、自動バックアップも無い。

## デプロイ手順

```bash
ssh -i ~/.ssh/oripa_xserver -p 10022 xs165048@sv17093.xserver.jp
cd ~/repos/card-lottery-matome
git status              # 作業ツリーがクリーンであることを確認（汚れていたら pull できない）
git checkout main       # 通常は既に main のはず
git pull origin main --ff-only
```

## デプロイ後の検証

本番はCSS/JSに**1年キャッシュ**（ファイル名にハッシュが入らない構成、
[`docs/production-server-config.md`](./production-server-config.md)）がかかっており、
かつエックスサーバー側に nginx のエッジキャッシュが前段にあるため、素のURLで直後に見ると
更新前の内容がしばらく返ることがある。確認するときはクエリでキャッシュを回避する:

```bash
curl -s "https://oripa-market.com/wp-content/themes/oripa-market/assets/css/style.css?bust=$(date +%s)" \
  | diff - wp-content/themes/oripa-market/assets/css/style.css && echo MATCH
```

## ロールバック

```bash
ssh -i ~/.ssh/oripa_xserver -p 10022 xs165048@sv17093.xserver.jp
cd ~/repos/card-lottery-matome
git log --oneline -5          # 戻したいコミットを確認
git checkout <commit-ish>      # 即座に本番へ反映される（detached HEADになる点に注意）
# main に戻すときは: git checkout main
```

移行時点のバックアップとして `~/oripa-market.com/public_html/wp-content/themes/oripa-market.bak-20260907224956`
（symlink化前の実ディレクトリ）がサーバー上に残っている。

## 注意事項

- **サーバー上のファイルを直接編集しない。** 作業ツリー = 本番配信物なので、直接編集すると
  次の `git pull` が「ローカルに変更がある」エラーで失敗し、最悪 `git checkout -- .` で
  その場の変更が失われる。変更は必ずこのリポジトリ（ローカル）でコミット→pushしてから、
  サーバー側で `git pull` する。
- DB・`wp-content/uploads/` はこの仕組みの対象外（従来通り、テーマファイルのみ）。
- GitHub Actions ワークフロー（`.github/workflows/deploy.yml`）はこの方式と衝突するため
  自動トリガーを止めてある。有効化しない。

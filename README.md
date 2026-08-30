# オリパマーケット（card-lottery-matome）

トレカ（ポケカ・ワンピ・遊戯王など）の抽選販売・予約情報まとめサイトのUIプロトタイプ。
`cardchusen.com` の導線・情報設計を参考にしつつ、独自デザインで一から構築（静的HTML/CSS/JS、ダミーデータ）。

## 構成

- `index.html` — トップページ
- `pages/category.html?cat=<slug>&box=<slug>` — カテゴリ別・ボックス別抽選一覧
- `pages/online.html` / `pages/store.html` — オンライン／店頭抽選まとめ
- `pages/shop.html` — 全店舗一覧
- `pages/calendar.html` — 抽選締切カレンダー（月表示・日別ドリルダウン）
- `pages/guide.html` — BOX定価購入攻略ガイド
- `pages/about.html` / `pages/faq.html` / `pages/company.html`（プレースホルダー） / `pages/terms.html` / `pages/privacy.html`
- `pages/register.html` / `pages/mypage.html` — 会員登録・ログイン導線（デモUIのみ、実認証なし）
- `assets/css/style.css` — 共通スタイル
- `assets/js/common.js` — データ読込・ヘッダーフッター描画・一覧絞り込み・カウントダウン共通処理
- `data/*.json` — ダミーデータ（categories / boxes / shops / lotteries）

## データ更新運用（将来）

- `data/lotteries.json` の各レコードは `updatedAt`（最終更新日時）を持つ。今後はAIによる巡回＋人の確認で随時更新し、この値を都度更新する運用を想定
- 各一覧ページは `updatedAt` の最大値から「最終更新 M/D H:M」を自動表示するため、データ更新のみで表示が追従する
- 会社概要ページ（`pages/company.html`）は正式な会社情報が確定次第、表内の `［ ］` プレースホルダーを差し替える

## 未実装（今回のスコープ外）

- 実データ収集・DB化・管理画面
- 会員登録・ログインの実認証（LINE/Google等）
- 当選率投票・買取価格予想などのUGC機能

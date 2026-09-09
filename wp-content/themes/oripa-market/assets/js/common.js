// 共通ユーティリティ（WordPress版）
// データ取得は /wp-json/oripa/v1/*（window.ORIPA.restBase）。
// ヘッダー/フッターは PHP 側で描画するため、ここでは扱わない。

const CFG = window.ORIPA || {};
const REST_BASE = CFG.restBase || "/wp-json/oripa/v1/";
const ASSETS = CFG.assetsBase || "/wp-content/themes/oripa-market/assets/img/";
const CATEGORY_BASE = CFG.categoryBase || "/card-category/";
const BOX_BASE = CFG.boxBase || "/card-box/";
const THEME_VERSION = CFG.version || "0";

/**
 * テーマ内の画像URLを作る。
 * 本番は静的ファイルに1年キャッシュ（max-age=31536000）がかかっており、
 * 同名で画像を差し替えても古いものが返り続ける。CSS/JSはWordPressが付ける ?ver= で
 * 回避できているが、JSから直接組み立てる画像URLには付かないため、ここで同じ値を付ける。
 */
function assetUrl(file) {
  return `${ASSETS}${file}?v=${THEME_VERSION}`;
}

async function loadJSON(name) {
  const res = await fetch(REST_BASE + name, { headers: { "X-WP-Nonce": CFG.nonce || "" } });
  if (!res.ok) throw new Error(`REST ${name} ${res.status}`);
  return res.json();
}

async function loadAllData() {
  const res = await fetch(REST_BASE + "bootstrap", { headers: { "X-WP-Nonce": CFG.nonce || "" } });
  if (!res.ok) throw new Error(`REST bootstrap ${res.status}`);
  const data = await res.json();
  // 「応募方法をみる」ポップアップをカード側から開く際、クリック起点のイベント委任だけで
  // 該当データを引けるよう、直近取得分をグローバルに保持しておく（ページごとに再取得済み）。
  window.__oripaCtx = { lotteries: data.lotteries || [], shops: data.shops || [] };
  return {
    lotteries: data.lotteries || [],
    shops: data.shops || [],
    boxes: data.boxes || [],
    categories: data.categories || [],
    articles: data.articles || [],
  };
}

async function loadArticles() {
  return loadJSON("articles");
}

function fmtDateSlash(iso) {
  const d = new Date(iso);
  return `${d.getFullYear()}/${String(d.getMonth() + 1).padStart(2, "0")}/${String(d.getDate()).padStart(2, "0")}`;
}

function fmtDateTime(iso) {
  const d = new Date(iso);
  return `${d.getMonth() + 1}/${d.getDate()} ${String(d.getHours()).padStart(2, "0")}:${String(d.getMinutes()).padStart(2, "0")}`;
}

function fmtUpdated(iso) {
  const d = new Date(iso);
  return `最終更新 ${d.getMonth() + 1}/${d.getDate()} ${String(d.getHours()).padStart(2, "0")}:${String(d.getMinutes()).padStart(2, "0")}`;
}

function latestUpdatedAt(items) {
  if (!items.length) return null;
  return items.reduce((a, b) => (new Date(a.updatedAt) > new Date(b.updatedAt) ? a : b)).updatedAt;
}

/**
 * 締切が近い順に並べ替えた新しい配列を返す。
 * すでに締め切られた（終了）ものは末尾へ回し、その中でも締切が早い順にする。
 */
function sortByDeadline(items) {
  const now = Date.now();
  return [...items].sort((a, b) => {
    const ta = new Date(a.deadline).getTime();
    const tb = new Date(b.deadline).getTime();
    const aEnded = ta <= now;
    const bEnded = tb <= now;
    if (aEnded !== bEnded) return aEnded ? 1 : -1;
    return ta - tb;
  });
}

function countdownParts(iso) {
  const diffMs = new Date(iso).getTime() - Date.now();
  if (diffMs <= 0) return { label: "終了", urgent: true, ended: true };
  const hours = diffMs / 36e5;
  // 締切1時間前を切ったら分単位で出す（従来は「残1時間」で止まり、
  // 残り5分なのか55分なのか判断できなかった。2026-09-09追加）。
  if (hours < 1) {
    // 切り上げ（残り2分50秒を「残2分」と切り捨てると1分損した表示になるため）。
    return { num: Math.min(59, Math.max(1, Math.ceil(diffMs / 6e4))), unit: "分", urgent: true };
  }
  if (hours < 24) {
    return { num: Math.floor(hours), unit: "時間", urgent: hours < 3 };
  }
  return { num: Math.floor(hours / 24), unit: "日", urgent: false };
}

/**
 * ジャンル別サムネイル（2026-09-09: 自作SVGイラスト → 指定の実写画像に差し替え）。
 *
 * 画像は assets/img/genre-<ジャンルslug>.webp。
 * 元画像（static/assets/img/*.jpeg・1枚300〜750KB）をWebPへ変換し、
 * 長辺600pxに縮小して取り込んでいる（合計2.5MB→145KB）。
 * 差し替えるときは同じファイル名で置き換えるだけでよい。
 */
const CATEGORY_PHOTOS = {
  pokeka: "genre-pokeka.webp",
  onepiece: "genre-onepiece.webp",
  yugioh: "genre-yugioh.webp",
  dragonball: "genre-dragonball.webp",
  duema: "genre-duema.webp",
};

function categoryThumbHtml(slug, cls) {
  const file = CATEGORY_PHOTOS[slug];
  // 未対応のジャンルが増えた場合に画像リンク切れにならないよう、既定画像へ退避する。
  const src = assetUrl(file || "icon-cards.svg");
  // 呼び出し側が "cat-thumb" を渡してくる箇所があるため、クラスの重複を避ける。
  const extra = cls && cls !== "cat-thumb" ? ` ${cls}` : "";
  return `<img class="cat-thumb${extra}" src="${src}" alt="" loading="lazy" decoding="async">`;
}

// 記事カテゴリ別トーン。
// 【サムネイルの方針・2026-09-09】
// マスコットやジャンルアイコン（ボール等）、箱画像は使わない。記事の中身と関係がなく、
// 一覧に並べたとき全部同じ絵に見えてしまうため。
// 代わりに「記事タイトルそのもの」を図版にする。記事ごとに必ず違う絵になり、
// タイトルが読めるぶん一覧での判別も速い。
// 元記事・公式サイトの画像は他社の著作物なので使わない（引用の要件を満たさない）。
const ARTICLE_TONES = {
  "安く入手": "#e8f0ff", "高く売る": "#e7f8ec", "応募方法": "#f1e9fe",
  "デッキ解説": "#fff2e0", "大会レポート": "#ffe9ef", "初心者ガイド": "#e6f7f8",
};
const ARTICLE_FG = {
  "安く入手": "#2f6fed", "高く売る": "#16a34a", "応募方法": "#7c3aed",
  "デッキ解説": "#e07a1f", "大会レポート": "#d63864", "初心者ガイド": "#0e9aa7",
};

/**
 * サムネイルに載せるタイトルを整える。
 * 【】で囲まれた煽り文句はサムネイルでは邪魔になるので落とし、本題だけを残す。
 */
function thumbTitleLines(title, perLine = 13, maxLines = 3) {
  const core = String(title || "")
    .replace(/【[^】]*】/g, "")
    .replace(/[｜|]/g, " ")
    .trim() || String(title || "");
  const lines = [];
  for (let i = 0; i < core.length && lines.length < maxLines; i += perLine) {
    lines.push(core.slice(i, i + perLine));
  }
  if (core.length > perLine * maxLines && lines.length) {
    lines[lines.length - 1] = lines[lines.length - 1].slice(0, perLine - 1) + "…";
  }
  return lines;
}

function escapeXml(s) {
  return String(s).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
}

let _artThumbSeq = 0;
/**
 * 記事サムネイル。カテゴリ配色の下地に、記事タイトルを組んで描く。
 * @param {string} category
 * @param {string} cls
 * @param {string} title
 */
function articleThumbHtml(category, cls, title) {
  const fg = ARTICLE_FG[category] || "#2f6fed";
  const tone = ARTICLE_TONES[category] || "#e8f0ff";
  const gid = `art-g-${_artThumbSeq++}`;
  const lines = thumbTitleLines(title);
  const startY = 62 - (lines.length - 1) * 9;
  const text = lines
    .map((l, i) => `<tspan x="16" y="${startY + i * 18}">${escapeXml(l)}</tspan>`)
    .join("");
  return `<svg class="${cls}" viewBox="0 0 200 120" preserveAspectRatio="xMidYMid slice" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="${escapeXml(title || "")}">
    <defs><linearGradient id="${gid}" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="${tone}"/><stop offset="100%" stop-color="#ffffff"/>
    </linearGradient></defs>
    <rect width="200" height="120" fill="url(#${gid})"/>
    <rect x="0" y="0" width="5" height="120" fill="${fg}"/>
    <circle cx="182" cy="108" r="46" fill="${fg}" opacity=".07"/>
    <text x="16" y="24" font-size="9" font-weight="700" fill="${fg}" letter-spacing="0.5">${escapeXml(category || "COLUMN")}</text>
    <text font-size="13" font-weight="700" fill="#1c2536" style="line-height:1.4">${text}</text>
    <text x="16" y="108" font-size="7.5" fill="#8a93a6">oripa-market.com</text>
  </svg>`;
}

function articleCardHtml(a) {
  return `<a class="article-card" href="${a.permalink}">
    ${a.thumbnail ? `<img class="art-thumb" src="${a.thumbnail}" alt="" loading="lazy" decoding="async">` : articleThumbHtml(a.category, "art-thumb", a.title)}
    <div class="art-body">
      <span class="art-cat">${a.category}</span>
      <div class="art-title">${a.title}</div>
      <p class="art-excerpt">${a.excerpt}</p>
      <div class="verified-row" style="margin-top:2px;"><span class="check">✓</span>編集部確認済み</div>
      <div class="art-meta">更新 ${fmtDateSlash(a.updatedAt)}・${a.readMin}分で読める</div>
      ${(a.tags && a.tags.length) ? `<div class="art-tags">${a.tags.slice(0, 4).map(t => `<span class="art-tag">#${t}</span>`).join("")}</div>` : ""}
    </div>
  </a>`;
}

function rankingBoxHtml(articles, n = 5) {
  const sorted = [...articles].sort((a, b) => new Date(b.updatedAt) - new Date(a.updatedAt)).slice(0, n);
  return `<div class="ranking-box">
    <h3>📊 人気の攻略記事</h3>
    ${sorted.map((a, i) => `<a class="ranking-item" href="${a.permalink}">
      <span class="rank-no">${i + 1}</span>
      <span class="rank-title">${a.title}</span>
    </a>`).join("")}
  </div>`;
}

/** 「n分前に確認」の鮮度表示（現在時刻との差分） */
function freshnessLabel(iso) {
  const diffMin = Math.max(0, Math.round((Date.now() - new Date(iso).getTime()) / 60000));
  if (diffMin < 1) return "たった今確認";
  if (diffMin < 60) return `${diffMin}分前に確認`;
  const h = Math.floor(diffMin / 60);
  if (h < 24) return `${h}時間前に確認`;
  return `${Math.floor(h / 24)}日前に確認`;
}

/** 店舗の信頼スコア（ダミー：店舗idから決定的に80〜99を生成） */
function trustScoreOf(shopId) {
  let h = 0;
  for (const ch of String(shopId)) h = (h * 31 + ch.charCodeAt(0)) % 1000;
  return 80 + (h % 20);
}

function trustScoreBarHtml(score) {
  return `<span class="trust-score"><span class="bar-track"><span class="bar-fill" style="width:${score}%"></span></span>${score}点</span>`;
}

function badgeHtml(l) {
  const b = [];
  if (l.memberRequired) b.push(`<span class="badge tag">会員登録要</span>`);
  if (l.idRequired) b.push(`<span class="badge tag">本人確認要</span>`);
  if (!l.memberRequired && !l.idRequired) b.push(`<span class="badge tag">登録不要</span>`);
  return b.join("");
}


// 実写BOX画像。WordPress側（card_boxタクソノミーのimage_url term meta、
// Notion「パック種類マスタ」DB由来）から取得したURLを最優先で使う。
// 未設定のボックスのみ、テーマ内蔵のデフォルト画像にフォールバックする
// （2026-09-07: 従来はここに全パック共通のハードコード対応表しかなく、
// ほぼ全ての商品が同じデフォルト画像になっていた）。
function lotteryThumbHtml(l, box) {
  // 商品画像が未登録のボックスは、ジャンルのアイコンにフォールバックする。
  // 2026-09-09修正: 以前は既定のBOX写真（30th CELEBRATION BOX）を出していたため、
  // 「その他」など別のボックスの抽選に無関係な商品の写真が付き、
  // どの商品の抽選なのか誤認させる状態だった（ボックス一覧側は先に同じ修正済み）。
  const img = box && box.image
    ? `<img class="lottery-thumb" src="${box.image}" alt="" loading="lazy">`
    : categoryThumbHtml(l.category, "lottery-thumb");
  // サムネイル画像タップでボックス別の抽選情報ページへ
  return l.box
    ? `<a class="lottery-thumb-link" href="${BOX_BASE}${l.box}/" aria-label="このボックスの抽選一覧を見る">${img}</a>`
    : img;
}

// 「抽選に応募する！」の遷移先優先順位:
// ①購入導線リンク（アフィリエイト） → ②店舗のX（旧Twitter） → ③店舗の公式サイト → ④応募URL（実際の応募フォーム）。
// 個別詳細ページを廃止したため、最終手段として必ず応募URLへフォールバックする（2026-09-09）。
function lotteryCtaUrl(l, shop) {
  return l.purchaseLinkUrl || (shop && (shop.snsUrl || shop.officialUrl)) || l.applyUrl || "";
}

function lotteryCardHtml(l, ctx) {
  const shop = ctx.shops.find(s => s.id === l.shopId);
  const box = ctx.boxes.find(b => b.slug === l.box);
  const cd = countdownParts(l.deadline);
  const ctaUrl = lotteryCtaUrl(l, shop);
  // 締切を過ぎたものに「抽選に応募する！」を出すと、応募できないページへ誘導してしまうため出さない
  // （2026-09-09修正。それまでは終了済みでも応募ボタンが出ていた）。
  const ctaHtml = cd.ended
    ? `<span class="btn primary block is-disabled" aria-disabled="true">受付終了</span>`
    : ctaUrl
      ? `<a class="btn primary block" href="${ctaUrl}" target="_blank" rel="noopener nofollow">抽選に応募する！</a>`
      : `<span class="btn primary block is-disabled" aria-disabled="true">応募先未定</span>`;
  // 「応募方法をみる」はテキストリンクとして、応募ボタンのすぐ下に小さく配置する（個別詳細ページは廃止）。
  // 終了済みは応募できないので出さない。
  const methodLinkHtml = l.applyUrl && !cd.ended
    ? `<button type="button" class="text-link oripa-method-open" data-lottery-id="${l.id}">応募方法をみる</button>`
    : "";
  return `
  <div class="lottery-card ${cd.urgent && !cd.ended ? "urgent-card" : ""} ${cd.ended ? "is-ended" : ""}">
    <div class="thumb-wrap">
      ${lotteryThumbHtml(l, box)}
      <span class="ribbon ${cd.ended ? "ended" : cd.urgent ? "urgent" : ""}">${cd.ended ? "受付終了" : `残${cd.num}${cd.unit}`}</span>
      <span class="method-chip ${l.method}">${l.method === "online" ? "オンライン" : "店頭"}</span>
    </div>
    <div class="card-body">
      <div class="verified-row"><span class="check">✓</span>運営確認済み・<span class="freshness">${freshnessLabel(l.updatedAt)}</span></div>
      <div class="badges">${badgeHtml(l)}</div>
      <div class="shop-name">${shop ? shop.name : "店舗名未定"}</div>
      <div class="meta">${box ? box.name : ""}</div>
      <div class="meta">締切 ${fmtDateTime(l.deadline)}（${shop ? shop.area : "-"}）・第${l.roundNo}回／全${l.roundTotal}回</div>
      ${ctaHtml}
      ${methodLinkHtml}
    </div>
  </div>`;
}

/**
 * 一覧カードの「応募方法をみる」用ポップアップ（動的生成・全カード共通で1個だけ使い回す）。
 * single-lottery.php側のモーダルとはDOMが別だが、見た目のCSSクラスは共通化している。
 */
let oripaDynamicMethodModal = null;
function ensureDynamicMethodModal() {
  if (oripaDynamicMethodModal) return oripaDynamicMethodModal;
  const el = document.createElement("div");
  el.className = "oripa-modal";
  el.id = "oripa-dynamic-method-modal";
  el.hidden = true;
  el.innerHTML = `
    <div class="oripa-modal-backdrop" data-modal-close></div>
    <div class="oripa-modal-panel" role="dialog" aria-modal="true">
      <button type="button" class="oripa-modal-close" data-modal-close aria-label="閉じる">×</button>
      <h2 class="oripa-modal-title">抽選方法</h2>
      <div class="oripa-modal-shop-row">
        <span class="oripa-modal-shop-name" data-slot="shop"></span>
        <span class="method-chip" data-slot="method-chip"></span>
      </div>
      <p class="oripa-modal-platform" data-slot="platform"></p>
      <h3 class="oripa-modal-heading">応募の手順</h3>
      <ol class="oripa-modal-steps" data-slot="steps"></ol>
      <div class="oripa-modal-section" data-slot="requirements-wrap">
        <h3 class="oripa-modal-heading">応募に必要なもの・条件</h3>
        <ul class="oripa-modal-list" data-slot="requirements"></ul>
      </div>
      <div class="oripa-modal-section" data-slot="result-wrap">
        <h3 class="oripa-modal-heading">当選発表の確認方法</h3>
        <p class="oripa-modal-text" data-slot="result"></p>
      </div>
      <div class="oripa-modal-section" data-slot="bring-wrap">
        <h3 class="oripa-modal-heading">当選後・購入時に必要なもの</h3>
        <ul class="oripa-modal-list" data-slot="bring"></ul>
      </div>
      <div class="oripa-modal-section" data-slot="cautions-wrap">
        <h3 class="oripa-modal-heading">注意点</h3>
        <ul class="oripa-modal-list caution" data-slot="cautions"></ul>
      </div>
      <p class="oripa-modal-note">※ この案内は応募先サイトの記載をまとめたものです。応募条件・締切は回や店舗によって変わるため、応募前に必ず応募先の公式ページでご確認ください。</p>
      <a class="btn primary" data-slot="apply-link" target="_blank" rel="noopener nofollow" style="display:block;">抽選に応募する！</a>
    </div>`;
  document.body.appendChild(el);
  oripaDynamicMethodModal = el;
  return el;
}

/** 文字列の配列をリスト要素へ流し込む。空なら見出しごと隠す。 */
function fillModalList(modal, slot, items) {
  const list = modal.querySelector(`[data-slot="${slot}"]`);
  const wrap = modal.querySelector(`[data-slot="${slot}-wrap"]`);
  list.textContent = "";
  (items || []).forEach((text) => {
    const li = document.createElement("li");
    li.textContent = text;
    list.appendChild(li);
  });
  if (wrap) wrap.hidden = !(items && items.length);
}

function openDynamicMethodModal(l, shop) {
  if (!l.applyUrl) return;
  const modal = ensureDynamicMethodModal();
  modal.querySelector('[data-slot="shop"]').textContent = shop ? shop.name : "店舗名未定";
  const chip = modal.querySelector('[data-slot="method-chip"]');
  chip.textContent = l.method === "online" ? "オンライン" : "店頭";
  chip.className = `method-chip ${l.method}`;

  // 応募先のプラットフォームごとに、実際の手順・条件を出し分ける（apply-guides.js）。
  const guide = window.ORIPA_APPLY_GUIDES.resolve(l.applyUrl, l.method);
  modal.querySelector('[data-slot="platform"]').textContent = `応募先：${guide.label}`;
  fillModalList(modal, "steps", guide.steps);
  fillModalList(modal, "requirements", guide.requirements);
  fillModalList(modal, "bring", guide.bring);
  fillModalList(modal, "cautions", guide.cautions);
  const resultWrap = modal.querySelector('[data-slot="result-wrap"]');
  modal.querySelector('[data-slot="result"]').textContent = guide.result || "";
  resultWrap.hidden = !guide.result;

  modal.querySelector('[data-slot="apply-link"]').href = l.applyUrl;
  openModal(modal);
}

function openModal(modal) {
  if (!modal) return;
  modal.hidden = false;
  document.body.classList.add("oripa-modal-open");
}
function closeModal(modal) {
  if (!modal) return;
  modal.hidden = true;
  document.body.classList.remove("oripa-modal-open");
}

// モーダルの開閉はイベント委任で一括処理（PHP側の静的モーダル／JS側の動的モーダル両対応）。
document.addEventListener("click", (e) => {
  const openBtn = e.target.closest(".oripa-method-open");
  if (openBtn) {
    const targetSel = openBtn.getAttribute("data-modal-target");
    if (targetSel) {
      openModal(document.querySelector(targetSel));
      return;
    }
    const lotteryId = openBtn.getAttribute("data-lottery-id");
    if (lotteryId && window.__oripaCtx) {
      const l = window.__oripaCtx.lotteries.find(x => x.id === lotteryId);
      const shop = l && window.__oripaCtx.shops.find(s => s.id === l.shopId);
      if (l) openDynamicMethodModal(l, shop);
    }
    return;
  }
  if (e.target.closest("[data-modal-close]")) {
    closeModal(e.target.closest(".oripa-modal"));
  }
});
document.addEventListener("keydown", (e) => {
  if (e.key === "Escape") {
    document.querySelectorAll(".oripa-modal:not([hidden])").forEach(closeModal);
  }
});

/**
 * リスト（行）表示の1件分。ボックス別ページで使う。
 * カード表示と同じ情報を、縦に積める横長の行として並べる（2026-09-09追加）。
 * 商品画像はページ上部に1枚だけ出すため、行には含めない。
 */
function lotteryRowHtml(l, ctx) {
  const shop = ctx.shops.find(s => s.id === l.shopId);
  const box = ctx.boxes.find(b => b.slug === l.box);
  const cd = countdownParts(l.deadline);
  const ctaUrl = lotteryCtaUrl(l, shop);
  // カード表示と同じ扱い: 終了済みには応募導線を出さない。
  const ctaHtml = cd.ended
    ? `<span class="btn primary block is-disabled" aria-disabled="true">受付終了</span>`
    : ctaUrl
      ? `<a class="btn primary block" href="${ctaUrl}" target="_blank" rel="noopener nofollow">抽選に応募する！</a>`
      : `<span class="btn primary block is-disabled" aria-disabled="true">応募先未定</span>`;
  const methodLinkHtml = l.applyUrl && !cd.ended
    ? `<button type="button" class="text-link oripa-method-open" data-lottery-id="${l.id}">応募方法をみる</button>`
    : "";
  return `
  <div class="lottery-row ${cd.ended ? "is-ended" : ""}">
    <div class="row-head">
      <div class="row-tags">
        <span class="method-chip static ${l.method}">${l.method === "online" ? "オンライン" : "店頭"}</span>
        ${badgeHtml(l)}
      </div>
      <span class="row-countdown ${cd.ended ? "ended" : cd.urgent ? "urgent" : ""}">${cd.ended ? "受付終了" : `残${cd.num}${cd.unit}`}</span>
    </div>
    <div class="row-shop">${shop ? shop.name : "店舗名未定"}</div>
    <div class="row-meta">${box ? box.name : ""}${box ? "・" : ""}締切 ${fmtDateTime(l.deadline)}（${shop ? shop.area : "-"}）・第${l.roundNo}回／全${l.roundTotal}回</div>
    <div class="row-verified"><span class="check">✓</span>運営確認済み・${freshnessLabel(l.updatedAt)}</div>
    ${ctaHtml}
    ${methodLinkHtml}
  </div>`;
}

/**
 * 一覧＋絞り込み＋もっと見る のセット描画
 */
function renderLotteryList(listElId, items, ctx, pageSize = 8) {
  const listEl = document.getElementById(listElId);
  if (!listEl) return;
  const moreWrapId = listElId + "-more-wrap";
  let shown = pageSize;

  function draw() {
    if (!items.length) {
      listEl.innerHTML = `<div class="empty-state"><img src="${assetUrl("logo-mark.svg")}" alt=""><br>条件に合う抽選情報が見つかりませんでした。</div>`;
      const w = document.getElementById(moreWrapId);
      if (w) w.innerHTML = "";
      return;
    }
    // 親要素に .lottery-list--rows が付いている場合は行レイアウトで描画する
    // （ボックス別ページ。2026-09-09追加）。
    const rowMode = listEl.classList.contains("lottery-list--rows");
    const render = rowMode ? lotteryRowHtml : lotteryCardHtml;
    listEl.innerHTML = items.slice(0, shown).map(l => render(l, ctx)).join("");
    const w = document.getElementById(moreWrapId);
    if (w) {
      const rest = items.length - shown;
      w.innerHTML = rest > 0 ? `<button class="more-btn" id="${listElId}-more">もっと見る（残り${rest}件）</button>` : "";
      const btn = document.getElementById(`${listElId}-more`);
      if (btn) btn.addEventListener("click", () => { shown += pageSize; draw(); });
    }
  }
  draw();
}

// 共通ユーティリティ（WordPress版）
// データ取得は /wp-json/oripa/v1/*（window.ORIPA.restBase）。
// ヘッダー/フッターは PHP 側で描画するため、ここでは扱わない。

const CFG = window.ORIPA || {};
const REST_BASE = CFG.restBase || "/wp-json/oripa/v1/";
const ASSETS = CFG.assetsBase || "/wp-content/themes/oripa-market/assets/img/";
const CATEGORY_BASE = CFG.categoryBase || "/card-category/";
const BOX_BASE = CFG.boxBase || "/card-box/";

async function loadJSON(name) {
  const res = await fetch(REST_BASE + name, { headers: { "X-WP-Nonce": CFG.nonce || "" } });
  if (!res.ok) throw new Error(`REST ${name} ${res.status}`);
  return res.json();
}

async function loadAllData() {
  const res = await fetch(REST_BASE + "bootstrap", { headers: { "X-WP-Nonce": CFG.nonce || "" } });
  if (!res.ok) throw new Error(`REST bootstrap ${res.status}`);
  const data = await res.json();
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
  if (hours < 24) {
    return { num: Math.max(1, Math.floor(hours)), unit: "時間", urgent: hours < 3 };
  }
  return { num: Math.floor(hours / 24), unit: "日", urgent: false };
}

// カテゴリ別のアイコン背景色
const CATEGORY_TONES = {
  pokeka: "#ffe9dc", onepiece: "#dbeeff", yugioh: "#f0e4ff", dragonball: "#fff2c9", duema: "#e2f3ec"
};
const CATEGORY_ICON_COLORS = {
  pokeka: "#f2712f", onepiece: "#3a7bd5", yugioh: "#8b4fd1", dragonball: "#d19a1a", duema: "#2f8f6e"
};

let _thumbSeq = 0;
function categoryThumbHtml(slug, cls) {
  const fg = CATEGORY_ICON_COLORS[slug] || "#f2712f";
  const tone = CATEGORY_TONES[slug] || "#ffe9dc";
  const gid = `thumb-g-${slug}-${_thumbSeq++}`;
  return `<svg class="${cls}" viewBox="0 0 200 130" preserveAspectRatio="xMidYMid slice" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="">
    <defs><linearGradient id="${gid}" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="${tone}"/><stop offset="100%" stop-color="#ffffff"/>
    </linearGradient></defs>
    <rect width="200" height="130" fill="url(#${gid})"/>
    <g transform="translate(100 65) rotate(8)">
      <rect x="-34" y="-42" width="68" height="84" rx="8" fill="#fff" stroke="${fg}" stroke-width="4" opacity=".9"/>
    </g>
    <g transform="translate(100 65) rotate(-8)">
      <rect x="-34" y="-42" width="68" height="84" rx="8" fill="#fff" stroke="${fg}" stroke-width="4"/>
      <circle cx="0" cy="-14" r="15" fill="${fg}" opacity=".85"/>
      <rect x="-20" y="10" width="40" height="7" rx="3.5" fill="${fg}" opacity=".5"/>
      <rect x="-20" y="23" width="26" height="7" rx="3.5" fill="${fg}" opacity=".3"/>
    </g>
  </svg>`;
}

function categoryIconHtml(slug) {
  const bg = CATEGORY_TONES[slug] || "var(--gold-soft)";
  const fg = CATEGORY_ICON_COLORS[slug] || "var(--gold)";
  return `<span class="cat-icon" style="background:${bg};">
    <svg width="24" height="24" viewBox="0 0 64 64">
      <rect x="17" y="11" width="30" height="42" rx="4" transform="rotate(-10 32 32)" fill="#fff" stroke="${fg}" stroke-width="3"/>
      <circle cx="32" cy="26" r="6" fill="${fg}" opacity=".85"/>
      <rect x="24" y="38" width="16" height="4" rx="2" fill="${fg}" opacity=".55"/>
    </svg>
  </span>`;
}

// 記事カテゴリ別トーン
const ARTICLE_TONES = { "安く入手": "#e8f0ff", "高く売る": "#e7f8ec", "応募方法": "#f1e9fe" };
const ARTICLE_FG = { "安く入手": "#2f6fed", "高く売る": "#16a34a", "応募方法": "#7c3aed" };

let _artThumbSeq = 0;
function articleThumbHtml(category, cls) {
  const fg = ARTICLE_FG[category] || "#2f6fed";
  const tone = ARTICLE_TONES[category] || "#e8f0ff";
  const gid = `art-g-${_artThumbSeq++}`;
  return `<svg class="${cls}" viewBox="0 0 200 120" preserveAspectRatio="xMidYMid slice" xmlns="http://www.w3.org/2000/svg">
    <defs><linearGradient id="${gid}" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="${tone}"/><stop offset="100%" stop-color="#ffffff"/>
    </linearGradient></defs>
    <rect width="200" height="120" fill="url(#${gid})"/>
    <g transform="translate(100 60)">
      <rect x="-46" y="-32" width="92" height="64" rx="6" fill="#fff" stroke="${fg}" stroke-width="3"/>
      <line x1="-30" y1="-12" x2="30" y2="-12" stroke="${fg}" stroke-width="3" opacity=".55"/>
      <line x1="-30" y1="0" x2="18" y2="0" stroke="${fg}" stroke-width="3" opacity=".35"/>
      <line x1="-30" y1="12" x2="24" y2="12" stroke="${fg}" stroke-width="3" opacity=".35"/>
      <circle cx="34" cy="-20" r="12" fill="${fg}"/>
      <path d="M29 -20l3.4 3.4L40 -25" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
    </g>
  </svg>`;
}

function articleCardHtml(a) {
  return `<a class="article-card" href="${a.permalink}">
    ${articleThumbHtml(a.category, "art-thumb")}
    <div class="art-body">
      <span class="art-cat">${a.category}</span>
      <div class="art-title">${a.title}</div>
      <p class="art-excerpt">${a.excerpt}</p>
      <div class="verified-row" style="margin-top:2px;"><span class="check">✓</span>編集部確認済み</div>
      <div class="art-meta">更新 ${fmtDateSlash(a.updatedAt)}・${a.readMin}分で読める</div>
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

/** カテゴリの丸アイコン横スクロールレール */
function categoryRailHtml(categories, lotteries, activeSlug) {
  return categories.map(c => {
    const count = lotteries.filter(l => l.category === c.slug).length;
    const active = c.slug === activeSlug ? "active" : "";
    return `<a class="cat-chip ${active}" href="${CATEGORY_BASE}${c.slug}/">
      <span class="avatar">${categoryThumbHtml(c.slug, "")}</span>
      <span class="cat-chip-name">${c.name}</span>
      <span class="cat-chip-count">${count}件</span>
    </a>`;
  }).join("");
}

// 実写BOX画像（ボックスごとに用意できたものを差し替え。無いボックスは共通のデフォルト画像を使用）
const BOX_PHOTOS = {
  "30th-anniversary": "30th-celebration.webp"
};
const DEFAULT_BOX_PHOTO = "30th-celebration.webp";

function lotteryThumbHtml(l) {
  const photo = BOX_PHOTOS[l.box] || DEFAULT_BOX_PHOTO;
  const img = `<img class="lottery-thumb" src="${ASSETS}${photo}" alt="" loading="lazy">`;
  // サムネイル画像タップでボックス別の抽選情報ページへ
  return l.box
    ? `<a class="lottery-thumb-link" href="${BOX_BASE}${l.box}/" aria-label="このボックスの抽選一覧を見る">${img}</a>`
    : img;
}

function lotteryCardHtml(l, ctx) {
  const shop = ctx.shops.find(s => s.id === l.shopId);
  const box = ctx.boxes.find(b => b.slug === l.box);
  const cd = countdownParts(l.deadline);
  const href = l.permalink || "#";
  return `
  <div class="lottery-card ${cd.urgent && !cd.ended ? "urgent-card" : ""} ${cd.ended ? "is-ended" : ""}">
    <div class="thumb-wrap">
      ${lotteryThumbHtml(l)}
      <span class="ribbon ${cd.ended ? "ended" : cd.urgent ? "urgent" : ""}">${cd.ended ? "受付終了" : `残${cd.num}${cd.unit}`}</span>
      <button class="save-btn" aria-label="保存する" type="button">♡</button>
      <span class="method-chip ${l.method}">${l.method === "online" ? "オンライン" : "店頭"}</span>
    </div>
    <div class="card-body">
      <div class="verified-row"><span class="check">✓</span>運営確認済み・<span class="freshness">${freshnessLabel(l.updatedAt)}</span></div>
      <div class="badges">${badgeHtml(l)}</div>
      <div class="shop-name">${shop ? shop.name : "店舗名未定"}</div>
      <div class="meta">${box ? box.name : ""}</div>
      <div class="meta">締切 ${fmtDateTime(l.deadline)}（${shop ? shop.area : "-"}）・第${l.roundNo}回／全${l.roundTotal}回</div>
      <a class="btn primary block" href="${href}">抽選の詳細を見る</a>
    </div>
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
      listEl.innerHTML = `<div class="empty-state"><img src="${ASSETS}logo-mark.svg" alt=""><br>条件に合う抽選情報が見つかりませんでした。</div>`;
      const w = document.getElementById(moreWrapId);
      if (w) w.innerHTML = "";
      return;
    }
    listEl.innerHTML = items.slice(0, shown).map(l => lotteryCardHtml(l, ctx)).join("");
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

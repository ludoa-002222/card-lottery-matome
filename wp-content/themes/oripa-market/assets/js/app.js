// ページ別の初期化。<body data-page="..."> で分岐。
// 各ハンドラは静的プロトタイプの各ページ内 <script> を移植したもの。

(function () {
  const BOX_BASE = (window.ORIPA && window.ORIPA.boxBase) || "/card-box/";
  const CAT_BASE = (window.ORIPA && window.ORIPA.categoryBase) || "/card-category/";

  const page = document.body.dataset.page || "";
  const main = document.querySelector("main");

  const isEnded = l => new Date(l.deadline).getTime() <= Date.now();

  /**
   * 「終了済の抽選販売」開閉セクションを描画する共通処理。
   * ページ側に #ended-section / #ended-toggle / #ended-count / #ended-list が必要。
   */
  function renderEndedSection(endedItems, ctx, pageSize = 6) {
    const section = document.getElementById("ended-section");
    if (!section) return;
    const toggle = document.getElementById("ended-toggle");
    if (toggle && !toggle.dataset.wired) {
      toggle.dataset.wired = "1";
      toggle.addEventListener("click", () => {
        const collapsed = section.classList.toggle("collapsed");
        toggle.setAttribute("aria-expanded", collapsed ? "false" : "true");
      });
    }
    const cnt = document.getElementById("ended-count");
    if (cnt) cnt.textContent = String(endedItems.length);
    section.hidden = endedItems.length === 0;
    renderLotteryList("ended-list", endedItems, ctx, pageSize);
  }

  const handlers = {
    "home": initHome,
    "category": initCategory,
    "box": initCategory,
    "lottery-archive": initAllLotteriesPage,
    "guide": initGuide,
    "article": initArticle,
    "shop": initShop,
    "page-online": () => initMethodPage("online"),
    "page-store": () => initMethodPage("store"),
    "page-calendar": initCalendar,
  };

  const fn = handlers[page];
  if (fn) {
    fn().catch(err => {
      console.error("[oripa]", page, err);
      const box = document.getElementById("all-list") || document.getElementById("list") || document.getElementById("article-grid");
      if (box) box.innerHTML = `<div class="empty-state">データの読み込みに失敗しました。時間をおいて再度お試しください。</div>`;
    });
  }

  // ---------------------------------------------------------------- home
  async function initHome() {
    const { lotteries, shops, boxes, categories } = await loadAllData();
    const ctx = { shops, boxes };

    const grid = document.getElementById("category-grid");
    if (grid) {
      // 【受付中があるジャンルだけ出す・2026-09-10】
      // 全ジャンルを常に並べると「0件」のタイルが並び、探しに来た人が空振りする。
      // 件数も掲載総数ではなく受付中の数を出す（押した先で応募できる数と一致させる）。
      const withActive = categories
        .map(c => ({ cat: c, count: lotteries.filter(l => l.category === c.slug && !isEnded(l)).length }))
        .filter(x => x.count > 0)
        .sort((a, b) => b.count - a.count);

      grid.innerHTML = withActive.map(({ cat, count }) => `<a class="category-card" href="${CAT_BASE}${cat.slug}/">
          ${categoryThumbHtml(cat.slug, "cat-thumb")}
          <div class="cat-body">
            <div class="cat-name">${cat.name}</div>
            <div class="cat-count">受付中 ${count}件</div>
          </div>
        </a>`).join("");

      // 受付中が1件も無いときだけ、セクションごと隠す（見出しだけ残ると壊れて見える）
      const section = document.getElementById("category-section");
      if (section) section.hidden = withActive.length === 0;
    }

    const stats = document.getElementById("trust-stats");
    if (stats) {
      const verifiedShops = new Set(lotteries.map(l => l.shopId)).size;
      stats.innerHTML = `
        <div class="stat"><div class="stat-num">${lotteries.length}<span class="unit">件</span></div><div class="stat-label">検証済み掲載件数</div></div>
        <div class="stat"><div class="stat-num">${verifiedShops}<span class="unit">店舗</span></div><div class="stat-label">確認済み店舗数</div></div>
        <div class="stat"><div class="stat-num">2<span class="unit">重</span></div><div class="stat-label">AI＋人によるチェック</div></div>
        <div class="stat"><div class="stat-num">30<span class="unit">分以内</span></div><div class="stat-label">平均再確認間隔</div></div>`;
    }

    wireAllLotteries(lotteries, shops, boxes, ctx);
  }

  // ------------------------------------------------- all-lotteries (共通)
  function wireAllLotteries(lotteries, shops, boxes, ctx) {
    const boxSel = document.getElementById("f-box");
    const methodSel = document.getElementById("f-method");
    const shopSel = document.getElementById("f-shop");
    const areaSel = document.getElementById("f-area");
    if (!boxSel || !document.getElementById("all-list")) return;

    // 【受付中があるものだけを選択肢に出す・2026-09-10】
    // 全件を並べると、選んでも0件になる選択肢がほとんどになる。
    // 「選べる＝結果がある」状態にしておく。
    const active = lotteries.filter(l => !isEnded(l));
    const activeBoxes = new Set(active.map(l => l.box));
    const activeShops = new Set(active.map(l => String(l.shopId)));
    const activeAreas = new Set(
      active.map(l => (shops.find(s => s.id === l.shopId) || {}).area).filter(Boolean)
    );

    boxes.filter(b => activeBoxes.has(b.slug))
      .forEach(b => boxSel.insertAdjacentHTML("beforeend", `<option value="${b.slug}">${b.name}</option>`));
    shops.filter(s => activeShops.has(String(s.id)))
      .forEach(s => shopSel.insertAdjacentHTML("beforeend", `<option value="${s.id}">${s.name}</option>`));
    [...activeAreas].forEach(a => areaSel.insertAdjacentHTML("beforeend", `<option value="${a}">${a}</option>`));

    function applyFilter() {
      const box = boxSel.value, method = methodSel.value, shop = shopSel.value, area = areaSel.value;
      const filtered = lotteries.filter(l => {
        const s = shops.find(x => x.id === l.shopId);
        if (box && l.box !== box) return false;
        if (method && l.method !== method) return false;
        if (shop && l.shopId !== shop) return false;
        if (area && (!s || s.area !== area)) return false;
        return true;
      });

      // 受付中 = 購入導線リンク（アフィリエイト）を持つ抽選を先頭に、その中では締切が近い順。
      // 終了済 = 直近に終わった順。
      // 【並び順と応募先は別物・2026-09-10】
      // ここで上位に出すのは並び順の話であって、応募ボタンの飛び先には影響しない。
      // 応募ボタンは必ず本当の応募先へ飛ぶ（common.js の lotteryCtaUrl）。
      const activeAll = sortByDeadline(filtered.filter(l => !isEnded(l)));
      const withLink = activeAll.filter(l => l.purchaseLinkUrl);
      const withoutLink = activeAll.filter(l => !l.purchaseLinkUrl);
      const active = [...withLink, ...withoutLink];
      const ended = filtered.filter(isEnded).sort((a, b) => new Date(b.deadline) - new Date(a.deadline));

      const cnt = document.getElementById("result-count");
      if (cnt) cnt.innerHTML = `受付中 <b>${active.length}</b> 件・${fmtUpdated(latestUpdatedAt(lotteries) || new Date().toISOString())}`;
      renderLotteryList("all-list", active, ctx, 6);
      renderEndedSection(ended, ctx, 6);
    }
    [boxSel, methodSel, shopSel, areaSel].forEach(s => s.addEventListener("change", applyFilter));
    applyFilter();
  }

  async function initAllLotteriesPage() {
    const { lotteries, shops, boxes } = await loadAllData();
    wireAllLotteries(lotteries, shops, boxes, { shops, boxes });
  }

  // ------------------------------------------------------------ category
  async function initCategory() {
    const catSlug = main.dataset.cat || "";
    const boxSlugParam = main.dataset.box || new URLSearchParams(location.search).get("box") || "";

    const { lotteries, shops, boxes, categories } = await loadAllData();
    const ctx = { shops, boxes };
    const cat = categories.find(c => c.slug === catSlug) || categories[0];
    if (!cat) return;
    const catLotteries = lotteries.filter(l => l.category === cat.slug);
    const catBoxes = boxes.filter(b => b.category === cat.slug);

    const badge = document.getElementById("updated-badge");
    if (badge) badge.textContent = fmtUpdated(latestUpdatedAt(catLotteries) || new Date().toISOString());

    // ボックス別ページ: 商品画像はページ上部に1枚だけ出す（各行には画像を出さない）。
    // 2026-09-09: ボックス別ページをギャラリーからリスト表示に変更したため追加。
    const hero = document.getElementById("box-hero");
    if (hero && boxSlugParam) {
      const heroBox = boxes.find(b => b.slug === boxSlugParam);
      // 商品画像が未登録なら、別商品の写真ではなくジャンルのアイコンを出す（カード側と同じ扱い）。
      hero.innerHTML = heroBox && heroBox.image
        ? `<img class="box-hero-img" src="${heroBox.image}" alt="${heroBox.name}">`
        : categoryThumbHtml(cat.slug, "box-hero-img");
      hero.hidden = false;
    }

    const boxGrid = document.getElementById("box-grid");
    if (boxGrid) {
      // 受付中があるボックスだけを出す（0件のタイルを並べない）
      const shownBoxes = catBoxes.filter(b => catLotteries.some(l => l.box === b.slug && !isEnded(l)));
      boxGrid.innerHTML = shownBoxes.map(b => {
        const count = catLotteries.filter(l => l.box === b.slug && !isEnded(l)).length;
        // ボックスごとの商品画像を出す（抽選カードのサムネイルと同じ画像）。
        // 2026-09-09修正: ここはジャンルのアイコンを出していたため、
        // 同じカテゴリのボックスが全部同じ絵になり、どのパックか見分けがつかなかった。
        // 商品画像が未登録のボックスだけ、従来どおりジャンルのアイコンにフォールバックする
        // （別商品の写真を出すと、どのパックの抽選か誤認させてしまうため）。
        const thumb = b.image
          ? `<img class="cat-thumb box-thumb" src="${b.image}" alt="" loading="lazy" decoding="async">`
          : categoryThumbHtml(cat.slug, "cat-thumb");
        return `<a class="category-card" href="${BOX_BASE}${b.slug}/">
          ${thumb}
          <div class="cat-body">
            <div class="cat-name" style="font-size:.92rem;">${b.name}</div>
            <div class="cat-count">受付中 ${count}件</div>
          </div>
        </a>`;
      }).join("") || `<p class="footer-note">現在このカテゴリで受付中の抽選はありません。</p>`;
    }

    // 選択肢は受付中があるものだけ（0件になる選択肢を並べない）
    const activeCatLotteries = catLotteries.filter(l => !isEnded(l));
    const activeBoxSlugs = new Set(activeCatLotteries.map(l => l.box));

    const boxSel = document.getElementById("f-box");
    catBoxes.filter(b => activeBoxSlugs.has(b.slug))
      .forEach(b => boxSel.insertAdjacentHTML("beforeend", `<option value="${b.slug}" ${b.slug === boxSlugParam ? "selected" : ""}>${b.name}</option>`));
    const shopSel = document.getElementById("f-shop");
    const shopIds = new Set(activeCatLotteries.map(l => l.shopId));
    shops.filter(s => shopIds.has(s.id)).forEach(s => shopSel.insertAdjacentHTML("beforeend", `<option value="${s.id}">${s.name}</option>`));
    const areaSel = document.getElementById("f-area");
    [...new Set(shops.filter(s => shopIds.has(s.id)).map(s => s.area))].filter(Boolean)
      .forEach(a => areaSel.insertAdjacentHTML("beforeend", `<option value="${a}">${a}</option>`));

    function matchFilters(l) {
      const box = boxSel.value, shop = shopSel.value, area = areaSel.value;
      const s = shops.find(x => x.id === l.shopId);
      if (box && l.box !== box) return false;
      if (shop && l.shopId !== shop) return false;
      if (area && (!s || s.area !== area)) return false;
      return true;
    }
    function draw() {
      const matched = catLotteries.filter(matchFilters);
      const online = sortByDeadline(matched.filter(l => l.method === "online" && !isEnded(l)));
      const store = sortByDeadline(matched.filter(l => l.method === "store" && !isEnded(l)));
      const ended = matched.filter(isEnded).sort((a, b) => new Date(b.deadline) - new Date(a.deadline));

      const oc = document.getElementById("online-count");
      if (oc) oc.innerHTML = `受付中 オンライン<b>${online.length}</b>件・店頭<b>${store.length}</b>件`;
      renderLotteryList("online-list", online, ctx, 5);
      renderLotteryList("store-list", store, ctx, 5);
      renderEndedSection(ended, ctx, 6);
    }
    [boxSel, shopSel, areaSel].forEach(s => s.addEventListener("change", draw));
    draw();
  }

  // --------------------------------------------------------------- guide
  async function initGuide() {
    const articles = await loadArticles();
    const cat = (main && main.dataset.colcat) || new URLSearchParams(location.search).get("colcat") || "";

    const cats = [...new Set(articles.map(a => a.category))].filter(Boolean);
    const menu = document.getElementById("cat-menu");
    if (menu) {
      const base = location.pathname;
      menu.innerHTML =
        `<a href="${base}" class="${!cat ? "active" : ""}">すべて</a>` +
        cats.map(c => `<a href="${base}?colcat=${encodeURIComponent(c)}" class="${cat === c ? "active" : ""}">${c}</a>`).join("");
    }

    const list = cat ? articles.filter(a => a.category === cat) : articles;
    const title = document.getElementById("list-title");
    if (title) title.textContent = cat ? `${cat}の記事` : "新着記事";

    const grid = document.getElementById("article-grid");
    if (grid) {
      grid.innerHTML = list.slice()
        .sort((a, b) => new Date(b.updatedAt) - new Date(a.updatedAt))
        .map(articleCardHtml).join("");
    }
    const rk = document.getElementById("ranking-slot");
    if (rk) rk.innerHTML = rankingBoxHtml(articles);
  }

  // ------------------------------------------------------------- article
  async function initArticle() {
    const slug = main.dataset.slug || "";
    const articles = await loadArticles();
    const rk = document.getElementById("ranking-slot");
    if (rk) rk.innerHTML = rankingBoxHtml(articles.filter(a => a.slug !== slug));
  }

  // ---------------------------------------------------------------- shop
  async function initShop() {
    const { lotteries, shops, categories } = await loadAllData();
    const wrap = document.getElementById("shop-groups");
    if (!wrap) return;

    const nameBySlug = Object.fromEntries((categories || []).map(c => [c.slug, c.name]));
    const groups = window.ORIPA_SHOP_GROUPS.groupShops(shops, lotteries, nameBySlug);

    const summary = document.getElementById("shop-summary");
    if (summary) {
      const shopTotal = groups.reduce((n, g) => n + g.shops.length, 0);
      summary.textContent = `${groups.length}系列・${shopTotal}店舗を掲載しています。`;
    }

    function render() {
      const q = (document.getElementById("shop-q")?.value || "").trim().toLowerCase();
      const area = document.getElementById("shop-area")?.value || "";

      const view = groups
        .map(g => ({
          ...g,
          // 検索・エリアの絞り込みは店舗単位でかける。
          // 系列名が一致した場合は、その系列の店舗をすべて残す（探し方に合わせる）。
          shops: g.shops.filter(s => {
            if (area && s.area !== area) return false;
            if (!q) return true;
            return `${s.name} ${g.label}`.toLowerCase().includes(q);
          }),
        }))
        .filter(g => g.shops.length > 0);

      if (!view.length) {
        wrap.innerHTML = `<div class="empty-state">条件に合う店舗がありません。</div>`;
        return;
      }

      // 絞り込み中は中身を開いた状態にする（閉じたままだと結果が見えない）
      const openAll = Boolean(q || area);

      wrap.innerHTML = view.map(g => `
        <details class="shop-group"${openAll ? " open" : ""}>
          <summary class="shop-group-head">
            <span class="shop-group-name">${g.label}<span class="shop-group-num">（${g.shops.length}店舗）</span></span>
            <span class="shop-group-meta">
              ${g.genres.length ? `<span class="shop-group-genres">${g.genres.map(x => `<span class="shop-genre">${x}</span>`).join("")}</span>` : ""}
              <span class="shop-group-count">掲載${g.lotteryCount}件</span>
            </span>
          </summary>
          <table class="shop-table">
            <thead><tr><th>店舗名</th><th>エリア</th><th>オンライン</th><th>店頭</th><th>掲載件数</th></tr></thead>
            <tbody>
              ${g.shops.map(s => `<tr>
                <td>${s.name}</td>
                <td>${s.area || "-"}</td>
                <td>${s.isOnline ? "◯" : "―"}</td>
                <td>${s.isStore ? "◯" : "―"}</td>
                <td class="num">${s.lotteryCount}件</td>
              </tr>`).join("")}
            </tbody>
          </table>
        </details>`).join("");
    }

    const areaSel = document.getElementById("shop-area");
    if (areaSel) {
      const areas = [...new Set(groups.flatMap(g => g.shops.map(s => s.area).filter(Boolean)))].sort();
      areaSel.innerHTML = `<option value="">すべてのエリア</option>` +
        areas.map(a => `<option value="${a}">${a}</option>`).join("");
      areaSel.addEventListener("change", render);
    }
    document.getElementById("shop-q")?.addEventListener("input", render);
    render();
  }

  // -------------------------------------------------- online / store page
  async function initMethodPage(method) {
    const { lotteries, shops, boxes, categories } = await loadAllData();
    const ctx = { shops, boxes };
    const base = lotteries.filter(l => l.method === method);

    const badge = document.getElementById("updated-badge");
    if (badge) badge.textContent = fmtUpdated(latestUpdatedAt(base) || new Date().toISOString());

    // 選択肢は受付中があるものだけ（選んでも0件になる選択肢を並べない）
    const activeBase = base.filter(l => !isEnded(l));
    const hasCat = new Set(activeBase.map(l => l.category));
    const hasBox = new Set(activeBase.map(l => l.box));
    const hasArea = new Set(
      activeBase.map(l => (shops.find(s => s.id === l.shopId) || {}).area).filter(Boolean)
    );

    const catSel = document.getElementById("f-cat");
    categories.filter(c => hasCat.has(c.slug))
      .forEach(c => catSel.insertAdjacentHTML("beforeend", `<option value="${c.slug}">${c.name}</option>`));
    const boxSel = document.getElementById("f-box");
    boxes.filter(b => hasBox.has(b.slug))
      .forEach(b => boxSel.insertAdjacentHTML("beforeend", `<option value="${b.slug}">${b.name}</option>`));
    const areaSel = document.getElementById("f-area");
    [...hasArea].forEach(a => areaSel.insertAdjacentHTML("beforeend", `<option value="${a}">${a}</option>`));

    function draw() {
      const c = catSel.value, box = boxSel.value, area = areaSel.value;
      const f = base.filter(l => {
        const s = shops.find(x => x.id === l.shopId);
        if (c && l.category !== c) return false;
        if (box && l.box !== box) return false;
        if (area && (!s || s.area !== area)) return false;
        return true;
      });
      const cnt = document.getElementById("cnt");
      if (cnt) cnt.innerHTML = `全<b>${f.length}</b>件`;
      renderLotteryList("list", f, ctx, 8);
    }
    [catSel, boxSel, areaSel].forEach(s => s.addEventListener("change", draw));
    draw();
  }

  // ------------------------------------------------------------ calendar
  async function initCalendar() {
    const { lotteries, shops, boxes, categories } = await loadAllData();
    const ctx = { shops, boxes };
    const dow = ["日", "月", "火", "水", "木", "金", "土"];
    document.getElementById("cal-dow").innerHTML = dow.map(d => `<div class="cal-dow">${d}</div>`).join("");

    const view = new Date();
    view.setDate(1);
    let selected = null;   // 選択中の日付 {y,m,d}
    let showAll = false;   // 件数が多い日の「すべて表示」

    const DAY_PAGE_SIZE = 8; // 一度に見せる件数。これを超えたら折りたたむ

    const onDay = (l, y, m, d) => {
      const dl = new Date(l.deadline);
      return dl.getFullYear() === y && dl.getMonth() === m && dl.getDate() === d;
    };
    const countByDay = (y, m, d) => lotteries.filter(l => onDay(l, y, m, d)).length;

    // 絞り込みの入力。ジャンルは実際に抽選があるものだけ出す。
    const catSelect = document.getElementById("day-cat");
    if (catSelect) {
      const used = new Set(lotteries.map(l => l.category));
      const opts = (categories || []).filter(c => used.has(c.slug));
      catSelect.innerHTML = `<option value="">すべてのジャンル</option>` +
        opts.map(c => `<option value="${c.slug}">${c.name}</option>`).join("");
    }

    function drawCalendar() {
      const y = view.getFullYear(), m = view.getMonth();
      document.getElementById("cal-title").textContent = `${y}年${m + 1}月`;
      const firstDow = new Date(y, m, 1).getDay();
      const daysInMonth = new Date(y, m + 1, 0).getDate();
      const today = new Date();
      const cells = [];
      for (let i = 0; i < firstDow; i++) cells.push(`<div class="cal-cell empty"></div>`);
      for (let d = 1; d <= daysInMonth; d++) {
        const cnt = countByDay(y, m, d);
        const isToday = today.getFullYear() === y && today.getMonth() === m && today.getDate() === d;
        const isSel = selected && selected.y === y && selected.m === m && selected.d === d;
        cells.push(`<button type="button" class="cal-cell${isToday ? " today" : ""}${isSel ? " selected" : ""}" data-y="${y}" data-m="${m}" data-d="${d}" aria-pressed="${isSel ? "true" : "false"}">
          <span class="d">${d}</span>
          ${cnt > 0 ? `<span class="count">${cnt}件</span>` : ""}
        </button>`);
      }
      document.getElementById("cal-grid").innerHTML = cells.join("");
      document.querySelectorAll(".cal-cell:not(.empty)").forEach(c =>
        c.addEventListener("click", () => {
          showAll = false;
          selectDay(+c.dataset.y, +c.dataset.m, +c.dataset.d);
        }));
    }

    /** 日付を選ぶ。押した日をカレンダー上でも目立たせる（押し間違いに気づけるように）。 */
    function selectDay(y, m, d) {
      const isFirstOpen = selected === null;
      selected = { y, m, d };
      drawCalendar();
      renderDay();
      // 初回だけスクロールする。開いている状態で毎回動かすと、
      // スクロール中に次のタップが隣の日付へ当たってしまう（押した日と違う日が出る原因）。
      if (isFirstOpen) {
        document.getElementById("day-section").scrollIntoView({ behavior: "smooth", block: "start" });
      }
    }

    /** 選択中の日のリストを、検索語とジャンルで絞って描く。 */
    function renderDay() {
      if (!selected) return;
      const { y, m, d } = selected;
      const q = (document.getElementById("day-q")?.value || "").trim().toLowerCase();
      const cat = document.getElementById("day-cat")?.value || "";

      const all = lotteries.filter(l => onDay(l, y, m, d));
      const filtered = all.filter(l => {
        if (cat && l.category !== cat) return false;
        if (!q) return true;
        const shop = shops.find(s => String(s.id) === String(l.shopId));
        return `${l.title || ""} ${shop ? shop.name : ""}`.toLowerCase().includes(q);
      });

      document.getElementById("day-section").hidden = false;
      document.getElementById("day-title").textContent = `${m + 1}月${d}日 締切の抽選`;

      const countEl = document.getElementById("day-count");
      if (countEl) {
        countEl.textContent = filtered.length === all.length
          ? `${all.length}件`
          : `${all.length}件中 ${filtered.length}件を表示`;
      }
      // 絞り込みの入力欄は、件数が少ない日では出す意味がないので隠す。
      const tools = document.getElementById("day-tools");
      if (tools) tools.hidden = all.length <= 3;

      const shown = showAll ? filtered : filtered.slice(0, DAY_PAGE_SIZE);
      const list = document.getElementById("day-list");
      list.innerHTML = shown.length
        ? shown.map(l => lotteryCardHtml(l, ctx)).join("")
        : `<div class="empty-state">${all.length ? "条件に合う抽選がありません。" : "この日に締め切られる抽選はありません。"}</div>`;

      const more = document.getElementById("day-more");
      if (more) {
        const rest = filtered.length - shown.length;
        more.hidden = rest <= 0;
        more.textContent = `残り${rest}件を表示`;
      }
    }

    document.getElementById("day-q")?.addEventListener("input", () => { showAll = false; renderDay(); });
    document.getElementById("day-cat")?.addEventListener("change", () => { showAll = false; renderDay(); });
    document.getElementById("day-more")?.addEventListener("click", () => { showAll = true; renderDay(); });
    document.getElementById("day-close")?.addEventListener("click", () => {
      selected = null;
      document.getElementById("day-section").hidden = true;
      drawCalendar();
    });

    document.getElementById("prev").addEventListener("click", () => { view.setMonth(view.getMonth() - 1); drawCalendar(); });
    document.getElementById("next").addEventListener("click", () => { view.setMonth(view.getMonth() + 1); drawCalendar(); });
    drawCalendar();
  }
})();

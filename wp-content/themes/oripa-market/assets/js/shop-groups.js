/**
 * 店舗をチェーン系列にまとめる。
 *
 * 【なぜ要るか・2026-09-11】
 * 全店舗一覧が170行のフラットな表になっており、
 * 「Bee本舗」と「Bee本舗通販」、「GIRAFULL(ジラフル)」と「GIRAFULL各店」が
 * 別々の行に並んでいて探しにくかった。
 * さらに「『遊戯王ラッシュデュエル』公認トーナメントバトル【5月開催】」のような
 * **大会情報が店舗として登録されていた**。
 *
 * ここでやるのは表示上のグループ分けだけで、データは変えない。
 * （データ側の名寄せは scraper-poc/lib/shopName.js が担当）
 */

(function () {
  "use strict";

  /**
   * チェーンの辞書。左から順に照合し、最初に当たったものを採用する。
   * **長い名前を先に置くこと。** 「ホビーオフ」より先に「ブックオフ」を置くと
   * 「ブックオフ・ホビーオフ」がブックオフ側に入ってしまう、といった取り違えを防ぐ。
   */
  const CHAINS = [
    { key: "bookoff", label: "ブックオフ / ホビーオフ", match: ["ブックオフ", "BOOKOFF", "ホビーオフ", "ハードオフ"] },
    { key: "pokemoncenter", label: "ポケモンセンター", match: ["ポケモンセンター", "ポケセン", "POKEMON CARD LOUNGE", "ポケモンカードラウンジ"] },
    { key: "geo", label: "ゲオ", match: ["ゲオ", "GEO"] },
    { key: "tsutaya", label: "TSUTAYA / 蔦屋書店", match: ["TSUTAYA", "蔦屋"] },
    { key: "yodobashi", label: "ヨドバシカメラ", match: ["ヨドバシ"] },
    { key: "bic", label: "ビックカメラ", match: ["ビックカメラ", "ビック"] },
    { key: "kojima", label: "コジマ", match: ["コジマ"] },
    { key: "joshin", label: "Joshin（上新電機）", match: ["Joshin", "上新"] },
    { key: "yamada", label: "ヤマダデンキ", match: ["ヤマダ"] },
    { key: "edion", label: "エディオン", match: ["エディオン"] },
    { key: "donki", label: "ドン・キホーテ", match: ["ドン・キホーテ", "ドンキ", "majica"] },
    { key: "aeon", label: "イオン系", match: ["イオン", "ミニストップ"] },
    { key: "familymart", label: "ファミリーマート", match: ["ファミリーマート", "ファミマ"] },
    { key: "lawson", label: "ローソン", match: ["ローソン"] },
    { key: "sevennet", label: "セブンネット / セブン-イレブン", match: ["セブンネット", "セブン-イレブン", "セブンイレブン"] },
    { key: "shimamura", label: "しまむら / バースデイ", match: ["しまむら", "バースデイ"] },
    { key: "surugaya", label: "駿河屋", match: ["駿河屋"] },
    { key: "mandarake", label: "まんだらけ", match: ["まんだらけ"] },
    { key: "hareruya", label: "晴れる屋", match: ["晴れる屋"] },
    { key: "cardrush", label: "カードラッシュ", match: ["カードラッシュ", "CARD RUSH", "カードショップラッシュ"] },
    { key: "hobbystation", label: "ホビーステーション", match: ["ホビーステーション", "ホビステ"] },
    { key: "fullcomp", label: "フルコンプ", match: ["フルコンプ"] },
    { key: "mint", label: "MINT", match: ["MINT"] },
    { key: "bee", label: "Bee本舗", match: ["Bee本舗"] },
    { key: "girafull", label: "GIRAFULL（ジラフル）", match: ["GIRAFULL", "ジラフル"] },
    { key: "tcg193", label: "TCGショップ193", match: ["TCGショップ193", "193"] },
    { key: "hmv", label: "HMV", match: ["HMV"] },
    { key: "amazon", label: "Amazon", match: ["Amazon", "アマゾン"] },
    { key: "rakuten", label: "楽天", match: ["楽天"] },
    { key: "yahoo", label: "Yahoo!ショッピング", match: ["Yahoo"] },
    { key: "konami", label: "KONAMI STYLE", match: ["KONAMI"] },
    { key: "toysrus", label: "トイザらス", match: ["トイザらス", "トイザラス"] },
    { key: "animate", label: "アニメイト", match: ["アニメイト"] },
    { key: "kiddyland", label: "キデイランド", match: ["キデイランド", "キディランド"] },
    { key: "seagull", label: "シーガル", match: ["シーガル"] },
    { key: "cardwings", label: "CARD WINGS", match: ["CARD WINGS"] },
    { key: "cardmax", label: "CARDMAX", match: ["CARDMAX"] },
    { key: "furu1", label: "フル１（フルイチ）", match: ["フル1", "フル１", "フルイチ"] },
  ];

  /**
   * 店舗ではないもの。大会・イベントの告知が店舗として登録されてしまうことがある。
   * 名前でしか判断できないので、明確に大会・イベントを指す語だけを見る。
   */
  const NOT_A_SHOP = [
    "公認トーナメント",
    "トーナメントバトル",
    "デュエリストカップ",
    "選手権",
    "大会",
    "フェス",
    "体験会",
    "販売会エントリー",
  ];

  function norm(s) {
    return String(s || "")
      .replace(/[Ａ-Ｚａ-ｚ０-９]/g, (c) => String.fromCharCode(c.charCodeAt(0) - 0xfee0))
      .toUpperCase();
  }

  /** 店舗として一覧に出してよいか。 */
  function isRealShop(name) {
    const n = norm(name);
    return !NOT_A_SHOP.some((k) => n.includes(norm(k)));
  }

  /** 店舗名からチェーンを引く。当たらなければ「その他の店舗」。 */
  function chainOf(name) {
    const n = norm(name);
    const hit = CHAINS.find((c) => c.match.some((m) => n.includes(norm(m))));
    return hit || { key: "other", label: "その他の店舗", match: [] };
  }

  /**
   * 店舗の配列をチェーン単位にまとめる。
   * @returns {Array<{key,label,shops,lotteryCount,genres,areas}>} 掲載件数の多い順
   */
  function groupShops(shops, lotteries, categoryNameBySlug) {
    const countByShop = new Map();
    const genreByShop = new Map();
    for (const l of lotteries) {
      const id = String(l.shopId);
      countByShop.set(id, (countByShop.get(id) ?? 0) + 1);
      if (!genreByShop.has(id)) genreByShop.set(id, new Set());
      if (l.category) genreByShop.get(id).add(l.category);
    }

    const groups = new Map();
    for (const s of shops) {
      if (!isRealShop(s.name)) continue;
      const c = chainOf(s.name);
      if (!groups.has(c.key)) {
        groups.set(c.key, { key: c.key, label: c.label, shops: [], lotteryCount: 0, genres: new Set(), areas: new Set() });
      }
      const g = groups.get(c.key);
      const cnt = countByShop.get(String(s.id)) ?? 0;
      g.shops.push({ ...s, lotteryCount: cnt });
      g.lotteryCount += cnt;
      (genreByShop.get(String(s.id)) || new Set()).forEach((x) => g.genres.add(x));
      if (s.area) g.areas.add(s.area);
    }

    return [...groups.values()]
      .map((g) => ({
        ...g,
        genres: [...g.genres].map((slug) => (categoryNameBySlug && categoryNameBySlug[slug]) || slug),
        areas: [...g.areas].sort(),
        shops: g.shops.sort((a, b) => b.lotteryCount - a.lotteryCount || a.name.localeCompare(b.name, "ja")),
      }))
      // 「その他の店舗」は個別店が集まるだけなので必ず最後に置く
      .sort((a, b) => {
        if (a.key === "other") return 1;
        if (b.key === "other") return -1;
        return b.shops.length - a.shops.length || b.lotteryCount - a.lotteryCount;
      });
  }

  window.ORIPA_SHOP_GROUPS = { groupShops, chainOf, isRealShop, CHAINS };
})();

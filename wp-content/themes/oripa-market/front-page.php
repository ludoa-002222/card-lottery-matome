<?php
/**
 * トップページ。静的プロトタイプ index.html を移植。
 * 動的部分（カテゴリタイル・統計・絞り込み一覧）は app.js(initHome) が描画。
 *
 * @package oripa-market
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<main class="wrap">
	<section class="hero">
		<div class="hero-copy">
			<span class="kicker">TRUSTED LOTTERY DATA PLATFORM</span>
			<h1>公式情報のみを検証して届ける、トレカ抽選の信頼できる情報基盤</h1>
			<p>AIによる一次収集と人による二重確認を経た抽選・予約情報だけを掲載。掲載後も定期的に再確認し、鮮度と正確性を保っています。</p>
		</div>
		<div class="trust-stats" id="trust-stats"></div>
	</section>

	<section class="section" id="category-section">
		<div class="section-heading"><span class="bar"></span><h2>カードの種類から探す</h2></div>
		<div class="category-grid" id="category-grid"></div>
	</section>

	<section class="section" style="padding-top:0;">
		<div class="section-heading"><span class="bar"></span><h2>データの信頼性について</h2></div>
		<div class="trust-badges">
			<div class="trust-badge"><span class="ico">🛡️</span><div><h3>二重チェック体制</h3><p>AIが一次収集した情報を、人が公式ページと突合して確認してから掲載します。</p></div></div>
			<div class="trust-badge"><span class="ico">🕒</span><div><h3>継続的な鮮度管理</h3><p>掲載後も定期的に再確認し、締切変更や終了を検知次第すぐに反映します。</p></div></div>
			<div class="trust-badge"><span class="ico">🔗</span><div><h3>一次情報へのリンク</h3><p>各抽選から公式ページへ直接遷移できるようにし、応募前の最終確認を徹底できます。</p></div></div>
		</div>
	</section>

	<section class="section">
		<div class="promo-grid promo-grid-3">
			<a class="promo-card" href="<?php echo esc_url( home_url( '/calendar/' ) ); ?>">
				<span class="icon">📅</span>
				<div><h3>抽選締切カレンダー</h3><p>締切日ごとにまとめてチェック</p></div>
				<span class="go">見る</span>
			</a>
			<a class="promo-card" href="<?php echo esc_url( get_post_type_archive_link( 'column' ) ); ?>">
				<span class="icon">📘</span>
				<div><h3>トレカ攻略コラム</h3><p>定価入手・買取相場を検証済み記事で解説</p></div>
				<span class="go">見る</span>
			</a>
			<a class="promo-card" href="<?php echo esc_url( home_url( '/trust/' ) ); ?>">
				<span class="icon">🛡️</span>
				<div><h3>情報の正確性について</h3><p>収集から掲載までの検証プロセスを公開</p></div>
				<span class="go">見る</span>
			</a>
		</div>
		<p class="footer-note" style="margin-top:10px;">※当ページにはアフィリエイト広告（PR）が含まれます。</p>
	</section>

	<section class="section">
		<div class="section-heading"><span class="bar"></span><h2>すべての抽選情報</h2></div>
		<div class="layout-with-sidebar">
			<aside class="filter-sidebar">
				<div class="fs-title">🔍 絞り込み</div>
				<div>
					<label>ボックス</label>
					<select id="f-box"><option value="">すべて</option></select>
				</div>
				<div>
					<label>抽選方式</label>
					<select id="f-method">
						<option value="">すべて</option>
						<option value="online">オンライン</option>
						<option value="store">店頭</option>
					</select>
				</div>
				<div>
					<label>店舗</label>
					<select id="f-shop"><option value="">すべて</option></select>
				</div>
				<div>
					<label>エリア</label>
					<select id="f-area"><option value="">すべて</option></select>
				</div>
				<div class="fs-count" id="result-count"></div>
			</aside>
			<div>
				<div class="lottery-list" id="all-list"></div>
				<div id="all-list-more-wrap"></div>

				<div class="ended-section collapsed" id="ended-section" hidden>
					<button type="button" class="ended-toggle" id="ended-toggle" aria-expanded="false">
						<span class="tri" aria-hidden="true">▼</span>終了済の抽選販売（<span id="ended-count">0</span>件）
					</button>
					<div class="lottery-list ended-list" id="ended-list"></div>
					<div id="ended-list-more-wrap"></div>
				</div>
			</div>
		</div>
	</section>
</main>
<?php
get_footer();

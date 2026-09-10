<?php
/**
 * 全店舗一覧。静的プロトタイプ pages/shop.html を移植。
 * テーブル行は app.js(initShop) が描画。
 *
 * @package oripa-market
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<main class="wrap" data-page="shop">
	<?php echo oripa_breadcrumb( array( array( 'label' => '全店舗一覧' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<div class="page-title"><h1>全店舗一覧</h1></div>
	<p class="footer-note" id="shop-summary">読み込み中…</p>

	<section class="section" style="padding-top:0;">
		<!-- チェーン系列ごとにまとめて折りたたむ。
		     170店舗をそのまま並べると探せないため（2026-09-11）。 -->
		<div class="shop-tools">
			<input type="search" id="shop-q" class="shop-q" placeholder="店舗名・チェーン名でしぼり込む" autocomplete="off">
			<select id="shop-area" class="shop-area"></select>
		</div>
		<div id="shop-groups"></div>
		<p class="footer-note" style="margin-top:14px;">掲載件数は、当サイトが掲載している抽選・予約情報の数です。系列名をタップすると各店舗を表示します。</p>
	</section>
</main>
<?php
get_footer();

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
	<section class="section" style="padding-top:0;">
		<table class="shop-table">
			<thead><tr><th>店舗名</th><th>エリア</th><th>オンライン抽選</th><th>店頭抽選</th><th>掲載中の抽選件数</th><th>信頼スコア</th></tr></thead>
			<tbody id="shop-rows"></tbody>
		</table>
		<p class="footer-note" style="margin-top:10px;">信頼スコアは、公式情報との一致率・情報の安定性をもとにした参考指標です（ダミー値）。</p>
	</section>
</main>
<?php
get_footer();

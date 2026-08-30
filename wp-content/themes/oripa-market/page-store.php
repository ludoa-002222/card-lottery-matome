<?php
/**
 * 店頭抽選まとめ。静的プロトタイプ pages/store.html を移植。
 * 一覧は app.js(initMethodPage 'store')。
 *
 * @package oripa-market
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<main class="wrap">
	<?php echo oripa_breadcrumb( array( array( 'label' => '店頭抽選' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<div class="page-title"><span class="updated-badge" id="updated-badge"></span><h1>店頭抽選まとめ</h1></div>
	<section class="section" style="padding-top:0;">
		<div class="layout-with-sidebar">
			<aside class="filter-sidebar">
				<div class="fs-title">🔍 絞り込み</div>
				<div><label>カード種類</label><select id="f-cat"><option value="">すべて</option></select></div>
				<div><label>ボックス</label><select id="f-box"><option value="">すべて</option></select></div>
				<div><label>エリア</label><select id="f-area"><option value="">すべて</option></select></div>
				<div class="fs-count" id="cnt"></div>
			</aside>
			<div>
				<div class="lottery-list" id="list"></div>
				<div id="list-more-wrap"></div>
			</div>
		</div>
	</section>
</main>
<?php
get_footer();

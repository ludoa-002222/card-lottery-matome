<?php
/**
 * 抽選アーカイブ（すべての抽選情報）。
 *
 * @package oripa-market
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<main class="wrap" data-page="lottery-archive">
	<?php echo oripa_breadcrumb( array( array( 'label' => 'すべての抽選情報' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<div class="page-title"><h1>すべての抽選情報</h1></div>

	<section class="section" style="padding-top:0;">
		<div class="layout-with-sidebar">
			<aside class="filter-sidebar">
				<div class="fs-title">🔍 絞り込み</div>
				<div><label>ボックス</label><select id="f-box"><option value="">すべて</option></select></div>
				<div>
					<label>抽選方式</label>
					<select id="f-method">
						<option value="">すべて</option>
						<option value="online">オンライン</option>
						<option value="store">店頭</option>
					</select>
				</div>
				<div><label>店舗</label><select id="f-shop"><option value="">すべて</option></select></div>
				<div><label>エリア</label><select id="f-area"><option value="">すべて</option></select></div>
				<div class="fs-count" id="result-count"></div>
			</aside>
			<div>
				<div class="lottery-list" id="all-list"></div>
				<div id="all-list-more-wrap"></div>

				<div class="ended-section" id="ended-section" hidden>
					<button type="button" class="ended-toggle" id="ended-toggle" aria-expanded="true">
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

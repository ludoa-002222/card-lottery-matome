<?php
/**
 * カテゴリ別 抽選一覧。静的プロトタイプ pages/category.html を移植。
 * 動的部分（ボックスグリッド・絞り込み・一覧）は app.js(initCategory) が描画。
 *
 * @package oripa-market
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$term      = get_queried_object();
$full_name = oripa_category_full_name( $term );

get_header();
?>
<main class="wrap" data-cat="<?php echo esc_attr( $term->slug ); ?>" data-box="">
	<?php
	echo oripa_breadcrumb( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		array(
			array(
				'label' => $term->name,
				'url'   => get_term_link( $term ),
			),
		)
	);
	?>
	<div class="page-title">
		<span class="updated-badge" id="updated-badge"></span>
		<h1><?php echo esc_html( $full_name ); ?> 抽選販売一覧</h1>
	</div>

	<section class="section" style="padding-top:0;">
		<div class="section-heading"><span class="bar"></span><h2>ボックスから選ぶ</h2></div>
		<div class="category-grid" id="box-grid" style="grid-template-columns:repeat(auto-fill,minmax(150px,1fr));"></div>
	</section>

	<section class="section" style="padding-top:0;">
		<div class="layout-with-sidebar">
			<aside class="filter-sidebar">
				<div class="fs-title">🔍 絞り込み</div>
				<div><label>ボックス</label><select id="f-box"><option value="">すべて</option></select></div>
				<div><label>店舗</label><select id="f-shop"><option value="">すべて</option></select></div>
				<div><label>エリア</label><select id="f-area"><option value="">すべて</option></select></div>
				<div class="fs-count" id="online-count"></div>
			</aside>
			<div>
				<div class="section-heading"><span class="bar"></span><h2>オンラインで応募できる抽選</h2></div>
				<div class="lottery-list" id="online-list"></div>
				<div id="online-list-more-wrap"></div>

				<div class="section-heading" style="margin-top:28px;"><span class="bar"></span><h2>店頭で応募できる抽選</h2></div>
				<div class="lottery-list" id="store-list"></div>
				<div id="store-list-more-wrap"></div>
			</div>
		</div>
	</section>

	<?php get_template_part( 'template-parts/lottery-faq' ); ?>

	<p class="footer-note">掲載内容は各店舗の公式発表をもとにしていますが、受付期間・応募条件は予告なく変更される場合があります。応募の前に必ず各店舗の公式ページで最新の情報をご確認ください。当サイトは抽選の実施主体ではありません。</p>
</main>
<?php
get_footer();

<?php
/**
 * ボックス別 抽選一覧。カテゴリ別テンプレートの絞り込み部分を流用し、
 * 当該ボックスを初期選択した状態で表示する。
 *
 * @package oripa-market
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$term        = get_queried_object();
$parent      = $term->parent ? get_term( $term->parent, 'card_box' ) : null;
$parent_slug = $parent && ! is_wp_error( $parent ) ? $parent->slug : '';
$cat_term    = $parent_slug ? get_term_by( 'slug', $parent_slug, 'card_category' ) : null;

$crumbs = array();
if ( $cat_term && ! is_wp_error( $cat_term ) ) {
	$crumbs[] = array(
		'label' => $cat_term->name,
		'url'   => get_term_link( $cat_term ),
	);
}
$crumbs[] = array( 'label' => $term->name );

get_header();
?>
<main class="wrap" data-cat="<?php echo esc_attr( $parent_slug ); ?>" data-box="<?php echo esc_attr( $term->slug ); ?>">
	<?php echo oripa_breadcrumb( $crumbs ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<div class="page-title">
		<span class="updated-badge" id="updated-badge"></span>
		<h1><?php echo esc_html( $term->name ); ?> の抽選情報一覧</h1>
	</div>

	<div id="box-grid" hidden></div>

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
</main>
<?php
get_footer();

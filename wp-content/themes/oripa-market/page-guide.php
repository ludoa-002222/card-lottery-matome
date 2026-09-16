<?php
/**
 * トレカ攻略ガイド（/guide）。コラムの入口となるハブページ。
 *
 * 【なぜハブページを作るか・2026-09-09】
 * コラムが増えるほど、記事同士がつながっていないと回遊も検索評価も伸びない。
 * カテゴリ・読む順番・実施中の店舗・パックを1枚にまとめ、
 * 「いま出ている抽選」と「読み物」を行き来できるようにする。
 *
 * 販売店・パックの件数は抽選データから自動で出すので、放っておいても中身が新しくなる。
 *
 * @package oripa-market
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$categories = get_terms(
	array(
		'taxonomy'   => 'column_category',
		'hide_empty' => true,
		'orderby'    => 'count',
		'order'      => 'DESC',
	)
);

// 読む順番のおすすめ。スラッグではなく「新しい順に3本」だと毎回変わってしまうため、
// 分類の並びで固定する（安く入手 → 応募方法 → 高く売る）。
$recommended = array();
foreach ( array( '安く入手', '応募方法', '高く売る' ) as $cat_name ) {
	$found = get_posts(
		array(
			'post_type'      => 'column',
			'posts_per_page' => 1,
			'orderby'        => 'modified',
			'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => 'column_category',
					'field'    => 'name',
					'terms'    => $cat_name,
				),
			),
		)
	);
	if ( $found ) {
		$recommended[] = $found[0];
	}
}

$shop_rows = oripa_guide_active_shops( 12 );
$box_rows  = oripa_guide_active_boxes( 12 );
?>
<main class="wrap">
	<?php echo oripa_breadcrumb( array( array( 'label' => 'トレカ攻略ガイド' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

	<section class="column-hero">
		<span class="kicker">VERIFIED GUIDE</span>
		<h1>トレカBOXを定価で手に入れる攻略ガイド</h1>
		<p>ポケカ・ワンピースカード・遊戯王のBOXを定価で買うための記事をまとめています。当サイトが記録した抽選・予約情報の集計をもとに整理しました。</p>
	</section>

	<section class="section guide-section">
		<div class="section-heading"><span class="bar"></span><h2>分類から探す</h2></div>
		<div class="guide-cat-row">
			<?php if ( $categories && ! is_wp_error( $categories ) ) : ?>
				<?php foreach ( $categories as $cat ) : ?>
					<a class="guide-cat" href="<?php echo esc_url( get_term_link( $cat ) ); ?>">
						<?php echo esc_html( $cat->name ); ?><b><?php echo (int) $cat->count; ?>本</b>
					</a>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
	</section>

	<?php if ( $recommended ) : ?>
	<section class="section guide-section" style="padding-top:0;">
		<div class="section-heading"><span class="bar"></span><h2>はじめての人はこの順に</h2></div>
		<ol class="guide-steps">
			<?php foreach ( $recommended as $i => $post_item ) : ?>
				<a class="guide-step" href="<?php echo esc_url( get_permalink( $post_item ) ); ?>">
					<span class="no"><?php echo (int) ( $i + 1 ); ?></span>
					<span>
						<h3><?php echo esc_html( get_the_title( $post_item ) ); ?></h3>
						<p><?php echo esc_html( wp_trim_words( wp_strip_all_tags( $post_item->post_content ), 46, '…' ) ); ?></p>
					</span>
				</a>
			<?php endforeach; ?>
		</ol>
	</section>
	<?php endif; ?>

	<section class="section guide-section" style="padding-top:0;">
		<div class="section-heading"><span class="bar"></span><h2>いま出ている抽選から探す</h2></div>
		<div class="promo-grid promo-grid-3">
			<a class="promo-card" href="<?php echo esc_url( home_url( '/calendar/' ) ); ?>">
				<span class="icon">📅</span><div><h3>抽選締切カレンダー</h3><p>締切日から逆算して応募する</p></div><span class="go">見る</span>
			</a>
			<a class="promo-card" href="<?php echo esc_url( home_url( '/online/' ) ); ?>">
				<span class="icon">💻</span><div><h3>オンライン抽選</h3><p>自宅から応募できる抽選だけ</p></div><span class="go">見る</span>
			</a>
			<a class="promo-card" href="<?php echo esc_url( home_url( '/store/' ) ); ?>">
				<span class="icon">🏬</span><div><h3>店頭抽選</h3><p>来店・店頭応募が必要な抽選</p></div><span class="go">見る</span>
			</a>
		</div>
	</section>

	<?php if ( $shop_rows ) : ?>
	<section class="section guide-section" style="padding-top:0;">
		<div class="section-heading"><span class="bar"></span><h2>販売店別の実施状況</h2></div>
		<p class="footer-note" style="margin:0 0 10px;">受付中の抽選・予約の件数です（<?php echo esc_html( gmdate( 'Y年n月j日', current_time( 'timestamp' ) ) ); ?>時点）。</p>
		<table class="guide-table">
			<thead><tr><th>店舗</th><th class="num">受付中</th></tr></thead>
			<tbody>
			<?php foreach ( $shop_rows as $row ) : ?>
				<tr>
					<td><a href="<?php echo esc_url( $row['url'] ); ?>"><?php echo esc_html( $row['name'] ); ?></a></td>
					<td class="num"><?php echo (int) $row['count']; ?>件</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<a class="btn ghost" style="margin-top:10px;display:inline-block;" href="<?php echo esc_url( get_post_type_archive_link( 'shop' ) ); ?>">すべての販売店を見る</a>
	</section>
	<?php endif; ?>

	<?php if ( $box_rows ) : ?>
	<section class="section guide-section" style="padding-top:0;">
		<div class="section-heading"><span class="bar"></span><h2>パックから探す</h2></div>
		<div class="guide-cat-row">
			<?php foreach ( $box_rows as $row ) : ?>
				<a class="guide-cat" href="<?php echo esc_url( $row['url'] ); ?>"><?php echo esc_html( $row['name'] ); ?><b><?php echo (int) $row['count']; ?>件</b></a>
			<?php endforeach; ?>
		</div>
	</section>
	<?php endif; ?>

	<section class="section guide-section" style="padding-top:0;">
		<div class="section-heading"><span class="bar"></span><h2>すべての攻略記事</h2></div>
		<a class="btn primary" href="<?php echo esc_url( get_post_type_archive_link( 'column' ) ); ?>">攻略コラム一覧へ</a>
	</section>

	<?php
	// 自サイトのコラムへ回遊させる。外部メディアへは送らない（2026-09-11）。
	echo oripa_related_columns_html( 0, 6, '新着のコラム' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	?>
</main>
<?php
get_footer();

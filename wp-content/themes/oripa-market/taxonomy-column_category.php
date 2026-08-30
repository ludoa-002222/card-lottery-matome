<?php
/**
 * 攻略コラム一覧。静的プロトタイプ pages/guide.html を移植。
 * 記事グリッド・カテゴリメニュー・ランキングは app.js(initGuide) が描画。
 *
 * @package oripa-market
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<main class="wrap" data-page="guide" data-colcat="<?php echo esc_attr( get_queried_object()->name ); ?>">
	<?php
	$oripa_ccat = get_queried_object();
	echo oripa_breadcrumb( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		array(
			array(
				'label' => '攻略コラム',
				'url'   => get_post_type_archive_link( 'column' ),
			),
			array( 'label' => $oripa_ccat->name ),
		)
	);
	?>

	<section class="column-hero">
		<span class="kicker">VERIFIED GUIDE COLUMN</span>
		<h1>トレカ攻略コラム</h1>
		<p>抽選・予約情報を継続調査してきた当サイトが、公式情報と実績データをもとに検証した記事のみを掲載しています。</p>
		<div class="cat-menu" id="cat-menu"></div>
	</section>

	<div class="column-layout">
		<div>
			<section class="section" style="padding-top:20px;">
				<div class="section-heading"><span class="bar"></span><h2 id="list-title">新着記事</h2></div>
				<div class="article-grid" id="article-grid"></div>
			</section>
		</div>
		<aside id="ranking-slot"></aside>
	</div>

	<section class="section">
		<div class="section-heading"><span class="bar"></span><h2>便利なツール・ページ</h2></div>
		<div class="promo-grid">
			<a class="promo-card" href="<?php echo esc_url( home_url( '/calendar/' ) ); ?>">
				<span class="icon">📅</span>
				<div><h3>抽選締切カレンダー</h3><p>締切日から逆算して応募する</p></div>
				<span class="go">見る</span>
			</a>
			<a class="promo-card" href="<?php echo esc_url( home_url( '/trust/' ) ); ?>">
				<span class="icon">🛡️</span>
				<div><h3>情報の正確性について</h3><p>収集から掲載までの検証プロセス</p></div>
				<span class="go">見る</span>
			</a>
		</div>
	</section>
</main>
<?php
get_footer();

<?php
/**
 * 汎用固定ページ（運営者について / よくある質問 / 会社概要 / 利用規約 / プライバシーポリシー等）。
 * 本文は管理画面の本文欄で編集する。
 *
 * @package oripa-market
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<main class="wrap legal">
		<?php echo oripa_breadcrumb( array( array( 'label' => get_the_title() ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<h1><?php the_title(); ?></h1>
		<?php the_content(); ?>
	</main>
	<?php
endwhile;

get_footer();

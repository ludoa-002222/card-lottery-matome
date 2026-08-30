<?php
/**
 * 汎用フォールバック。通常は個別テンプレートが使われる。
 *
 * @package oripa-market
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<main class="wrap">
	<?php echo oripa_breadcrumb(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<div class="page-title"><h1><?php echo esc_html( wp_get_document_title() ); ?></h1></div>

	<?php if ( have_posts() ) : ?>
		<div class="lottery-list">
			<?php
			while ( have_posts() ) :
				the_post();
				?>
				<article class="lottery-card">
					<div class="card-body">
						<a class="shop-name" href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
						<div class="meta"><?php echo esc_html( get_the_date() ); ?></div>
						<div class="art-excerpt"><?php echo esc_html( get_the_excerpt() ); ?></div>
					</div>
				</article>
			<?php endwhile; ?>
		</div>
		<div class="pager"><?php the_posts_pagination(); ?></div>
	<?php else : ?>
		<div class="empty-state">コンテンツが見つかりませんでした。</div>
	<?php endif; ?>
</main>
<?php
get_footer();

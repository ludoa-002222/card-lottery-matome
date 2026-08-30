<?php
/**
 * 攻略コラム 詳細。静的プロトタイプ pages/article.html を移植。
 * 本文はサーバー側で描画。右カラムのランキングのみ app.js(initArticle)。
 *
 * @package oripa-market
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();

	$post_id   = get_the_ID();
	$cats      = get_the_terms( $post_id, 'column_category' );
	$cat_name  = $cats && ! is_wp_error( $cats ) ? $cats[0]->name : '';
	$read_min  = (int) ( get_post_meta( $post_id, 'read_min', true ) ?: 5 );
	$updated   = get_the_modified_date( 'Y/m/d' );
	?>
	<main class="wrap" data-slug="<?php echo esc_attr( get_post_field( 'post_name', $post_id ) ); ?>">
		<?php
		echo oripa_breadcrumb( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			array(
				array(
					'label' => '攻略コラム',
					'url'   => get_post_type_archive_link( 'column' ),
				),
				array( 'label' => get_the_title() ),
			)
		);
		?>

		<div class="column-layout" style="margin-top:16px;">
			<article>
				<?php if ( $cat_name ) : ?>
					<span class="badge" style="margin-bottom:8px;display:inline-block;"><?php echo esc_html( $cat_name ); ?></span>
				<?php endif; ?>
				<h1 style="font-size:1.4rem;margin:6px 0 10px;"><?php the_title(); ?></h1>
				<div class="article-meta-row">
					<span>更新 <?php echo esc_html( $updated ); ?></span>
					<span>・</span>
					<span><?php echo (int) $read_min; ?>分で読める</span>
					<span class="verified-row" style="margin-left:6px;"><span class="check">✓</span>編集部確認済み</span>
				</div>

				<div><?php echo articleThumbHtml_php( $cat_name ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>

				<?php if ( has_excerpt() ) : ?>
				<div class="toc-box">
					<div class="toc-title">この記事のポイント</div>
					<p style="margin:0;color:var(--ink-soft);"><?php echo esc_html( get_the_excerpt() ); ?></p>
				</div>
				<?php endif; ?>

				<div class="article-body"><?php the_content(); ?></div>

				<p class="footer-note" style="margin-top:24px;">本記事は当サイトが収集した抽選・予約情報の傾向をもとにした参考情報です。実際の当落・相場を保証するものではありません。最新情報は必ず各店舗・公式サイトでご確認ください。</p>

				<a href="<?php echo esc_url( get_post_type_archive_link( 'column' ) ); ?>" class="btn ghost" style="margin-top:10px;display:inline-block;">← 攻略コラム一覧へ戻る</a>
			</article>
			<aside id="ranking-slot"></aside>
		</div>
	</main>
	<?php
endwhile;

get_footer();

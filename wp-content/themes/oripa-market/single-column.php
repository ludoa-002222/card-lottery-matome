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
	$tags        = get_the_terms( $post_id, 'column_tag' );
	$source_url  = get_post_meta( $post_id, 'source_url', true );
	// 広告を含む記事だけに注記を出す。含まない記事に出すとそれ自体が誤った表示になる。
	$affiliates  = array_filter( explode( ',', (string) get_post_meta( $post_id, 'affiliate_services', true ) ) );
	$source_site = get_post_meta( $post_id, 'source_site', true );
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

				<div>
				<?php
				if ( has_post_thumbnail() ) {
					the_post_thumbnail( 'large', array( 'class' => 'article-detail-hero' ) );
				} else {
					echo articleThumbHtml_php( $cat_name, 'article-detail-hero', get_the_title() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
				?>
				</div>

				<?php if ( has_excerpt() ) : ?>
				<div class="toc-box">
					<div class="toc-title">この記事のポイント</div>
					<p style="margin:0;color:var(--ink-soft);"><?php echo esc_html( get_the_excerpt() ); ?></p>
				</div>
				<?php endif; ?>

				<?php if ( $affiliates ) : ?>
					<p class="article-pr-notice">※本記事にはアフィリエイト広告（PR）が含まれます。掲載順や評価は広告の有無で変えていません。</p>
				<?php endif; ?>

				<div class="article-body"><?php the_content(); ?></div>

				<?php if ( $tags && ! is_wp_error( $tags ) ) : ?>
				<div class="article-tags">
					<?php foreach ( $tags as $tag ) : ?>
						<a class="article-tag" href="<?php echo esc_url( get_term_link( $tag ) ); ?>">#<?php echo esc_html( $tag->name ); ?></a>
					<?php endforeach; ?>
				</div>
				<?php endif; ?>

				<?php if ( $source_url ) : ?>
				<p class="article-credit">
					参考元：<a href="<?php echo esc_url( $source_url ); ?>" target="_blank" rel="noopener nofollow"><?php echo esc_html( $source_site ? $source_site : $source_url ); ?></a>
					<br><span>本記事は上記の記事を参考に、当サイトが独自に構成・執筆したものです。画像・本文の転載はしていません。</span>
				</p>
				<?php endif; ?>

				<p class="footer-note" style="margin-top:24px;">本記事は当サイトが収集した抽選・予約情報の傾向をもとにした参考情報です。実際の当落・相場を保証するものではありません。最新情報は必ず各店舗・公式サイトでご確認ください。</p>

				<a href="<?php echo esc_url( get_post_type_archive_link( 'column' ) ); ?>" class="btn ghost" style="margin-top:10px;display:inline-block;">← 攻略コラム一覧へ戻る</a>
			</article>
			<aside id="ranking-slot"></aside>
		</div>
	</main>
	<?php
endwhile;

get_footer();

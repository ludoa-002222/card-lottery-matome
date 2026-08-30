<?php
/**
 * 抽選 詳細ページ（新規）。
 *
 * @package oripa-market
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();

	$post_id     = get_the_ID();
	$shop_id     = (int) get_post_meta( $post_id, 'shop', true );
	$shop_title  = $shop_id ? get_the_title( $shop_id ) : '店舗未定';
	$shop_link   = $shop_id ? get_permalink( $shop_id ) : '';
	$method      = get_post_meta( $post_id, 'method', true );
	$deadline    = get_post_meta( $post_id, 'deadline', true );
	$round_no    = (int) ( get_post_meta( $post_id, 'round_no', true ) ?: 1 );
	$round_total = (int) ( get_post_meta( $post_id, 'round_total', true ) ?: 1 );
	$member_req  = (bool) get_post_meta( $post_id, 'member_required', true );
	$id_req      = (bool) get_post_meta( $post_id, 'id_required', true );

	$cat_terms = get_the_terms( $post_id, 'card_category' );
	$box_terms = get_the_terms( $post_id, 'card_box' );
	$cat_term  = $cat_terms && ! is_wp_error( $cat_terms ) ? $cat_terms[0] : null;
	$box_term  = null;
	foreach ( (array) $box_terms as $bt ) {
		if ( ! is_wp_error( $bt ) && (int) $bt->parent !== 0 ) {
			$box_term = $bt;
			break;
		}
	}

	$deadline_ts = $deadline ? strtotime( $deadline ) : 0;
	$deadline_fmt = $deadline_ts ? wp_date( 'n/j H:i', $deadline_ts ) : '未定';

	$crumbs = array();
	if ( $cat_term ) {
		$crumbs[] = array(
			'label' => $cat_term->name,
			'url'   => get_term_link( $cat_term ),
		);
	}
	if ( $box_term ) {
		$crumbs[] = array(
			'label' => $box_term->name,
			'url'   => get_term_link( $box_term ),
		);
	}
	$crumbs[] = array( 'label' => get_the_title() );
	?>
	<main class="wrap">
		<?php echo oripa_breadcrumb( $crumbs ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

		<div class="column-layout" style="margin-top:16px;">
			<article>
				<div class="verified-row" style="margin-bottom:6px;"><span class="check">✓</span>運営確認済み</div>
				<h1 style="font-size:1.4rem;margin:6px 0 12px;"><?php the_title(); ?></h1>

				<div class="lottery-detail-facts">
					<table class="shop-table">
						<tbody>
							<tr><th style="width:140px;">店舗</th><td><?php echo $shop_link ? '<a href="' . esc_url( $shop_link ) . '">' . esc_html( $shop_title ) . '</a>' : esc_html( $shop_title ); ?></td></tr>
							<tr><th>ボックス</th><td><?php echo $box_term ? esc_html( $box_term->name ) : '—'; ?></td></tr>
							<tr><th>抽選方式</th><td><?php echo 'online' === $method ? 'オンライン' : '店頭'; ?></td></tr>
							<tr><th>応募締切</th><td><?php echo esc_html( $deadline_fmt ); ?></td></tr>
							<tr><th>実施回</th><td>第<?php echo (int) $round_no; ?>回／全<?php echo (int) $round_total; ?>回</td></tr>
							<tr><th>応募条件</th><td>
								<?php
								$conds = array();
								if ( $member_req ) {
									$conds[] = '会員登録要';
								}
								if ( $id_req ) {
									$conds[] = '本人確認要';
								}
								echo esc_html( $conds ? implode( '・', $conds ) : '登録不要' );
								?>
							</td></tr>
						</tbody>
					</table>
				</div>

				<div class="article-body" style="margin-top:20px;">
					<?php the_content(); ?>
				</div>

				<p class="footer-note" style="margin-top:20px;">応募条件・締切は予告なく変更される場合があります。応募の前に必ず店舗の公式ページで最新情報をご確認ください。当サイトは抽選の実施主体ではありません。</p>

				<a href="<?php echo esc_url( get_post_type_archive_link( 'lottery' ) ); ?>" class="btn ghost" style="margin-top:10px;display:inline-block;">← 抽選一覧へ戻る</a>
			</article>
			<aside>
				<div class="ranking-box">
					<h3>この店舗の他の抽選</h3>
					<?php
					$others = $shop_id ? get_posts(
						array(
							'post_type'      => 'lottery',
							'posts_per_page' => 5,
							'post__not_in'   => array( $post_id ),
							'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
								array(
									'key'   => 'shop',
									'value' => $shop_id,
								),
							),
						)
					) : array();
					if ( $others ) :
						foreach ( $others as $i => $o ) :
							?>
							<a class="ranking-item" href="<?php echo esc_url( get_permalink( $o ) ); ?>">
								<span class="rank-no"><?php echo (int) ( $i + 1 ); ?></span>
								<span class="rank-title"><?php echo esc_html( get_the_title( $o ) ); ?></span>
							</a>
							<?php
						endforeach;
					else :
						echo '<p class="footer-note" style="padding:0 4px;">他の掲載はありません。</p>';
					endif;
					?>
				</div>
			</aside>
		</div>
	</main>
	<?php
endwhile;

get_footer();

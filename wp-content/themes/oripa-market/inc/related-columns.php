<?php
/**
 * 関連コラム（自サイト内）。
 *
 * 【なぜ外部リンクから置き換えたか・2026-09-11】
 * 「あわせて読みたい」で外部メディア（トレカの地図）へ12本リンクしていた。
 * 読者はそこで販売リンクを踏むため、**当サイトは送客するだけで何も得ない**。
 * 抽選を見に来た人には、自サイトの中で商品ページまで辿り着いてもらう必要がある。
 *
 * 外部リンクを置いてよいのは、当サイトが収益化できる場合（提携リンクがある場合）だけ。
 * それ以外は自サイト内で回遊させる。
 *
 * 【関連の決め方】
 * 同じカテゴリ → 同じタグ → 新着 の順に埋める。
 * 「関連」と名乗る以上、無関係なものを混ぜない。
 * 一致が無いときは見出しを「他のコラム」に変える。
 *
 * @package oripa-market
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 関連コラムを取り出す。
 *
 * @param int $post_id 今見ている記事。0 なら全体から選ぶ。
 * @param int $limit   件数。
 * @return array{posts: WP_Post[], matched: bool}
 */
function oripa_related_columns( $post_id = 0, $limit = 6 ) {
	$exclude = $post_id ? array( $post_id ) : array();
	$picked  = array();
	$matched = false;

	$base = array(
		'post_type'           => 'column',
		'post_status'         => 'publish',
		'posts_per_page'      => $limit,
		'post__not_in'        => $exclude,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	);

	if ( $post_id ) {
		// 1. 同じカテゴリ
		$cats = wp_get_post_terms( $post_id, 'column_category', array( 'fields' => 'ids' ) );
		if ( $cats && ! is_wp_error( $cats ) ) {
			$q = get_posts(
				$base + array(
					'tax_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
						array(
							'taxonomy' => 'column_category',
							'field'    => 'term_id',
							'terms'    => $cats,
						),
					),
				)
			);
			if ( $q ) {
				$picked  = $q;
				$matched = true;
			}
		}

		// 2. 足りなければ同じタグで補う
		if ( count( $picked ) < $limit ) {
			$tags = wp_get_post_terms( $post_id, 'column_tag', array( 'fields' => 'ids' ) );
			if ( $tags && ! is_wp_error( $tags ) ) {
				$have = array_merge( $exclude, wp_list_pluck( $picked, 'ID' ) );
				$q    = get_posts(
					array_merge(
						$base,
						array(
							'posts_per_page' => $limit - count( $picked ),
							'post__not_in'   => $have,
							'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
								array(
									'taxonomy' => 'column_tag',
									'field'    => 'term_id',
									'terms'    => $tags,
								),
							),
						)
					)
				);
				if ( $q ) {
					$picked  = array_merge( $picked, $q );
					$matched = true;
				}
			}
		}
	}

	// 3. それでも足りなければ新着で埋める
	if ( count( $picked ) < $limit ) {
		$have = array_merge( $exclude, wp_list_pluck( $picked, 'ID' ) );
		$q    = get_posts(
			array_merge(
				$base,
				array(
					'posts_per_page' => $limit - count( $picked ),
					'post__not_in'   => $have,
				)
			)
		);
		$picked = array_merge( $picked, $q );
	}

	return array(
		'posts'   => $picked,
		'matched' => $matched,
	);
}

/**
 * 関連コラムのセクションを描く。
 *
 * @param int    $post_id 今見ている記事。
 * @param int    $limit   件数。
 * @param string $heading 見出し。空なら一致の有無で自動。
 */
function oripa_related_columns_html( $post_id = 0, $limit = 6, $heading = '' ) {
	$result = oripa_related_columns( $post_id, $limit );
	if ( empty( $result['posts'] ) ) {
		return '';
	}
	if ( '' === $heading ) {
		$heading = $result['matched'] ? 'あわせて読みたい' : '他のコラム';
	}

	ob_start();
	?>
	<section class="section reco-section">
		<div class="section-heading">
			<span class="bar"></span>
			<h2><?php echo esc_html( $heading ); ?></h2>
		</div>
		<div class="reco-grid">
			<?php
			foreach ( $result['posts'] as $p ) :
				$cats     = get_the_terms( $p->ID, 'column_category' );
				$cat_name = ( $cats && ! is_wp_error( $cats ) ) ? $cats[0]->name : 'コラム';
				$read_min = (int) get_post_meta( $p->ID, 'read_min', true );
				?>
				<a class="reco-card" href="<?php echo esc_url( get_permalink( $p ) ); ?>">
					<span class="reco-tag"><?php echo esc_html( $cat_name ); ?></span>
					<span class="reco-title"><?php echo esc_html( get_the_title( $p ) ); ?></span>
					<?php if ( $read_min ) : ?>
						<span class="reco-desc">約<?php echo (int) $read_min; ?>分で読めます</span>
					<?php endif; ?>
					<span class="reco-go">読む</span>
				</a>
			<?php endforeach; ?>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

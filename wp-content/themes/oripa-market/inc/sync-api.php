<?php
/**
 * Notion → WordPress 同期用の内部APIエンドポイント。
 *
 * LUDOA側（scraper-poc/sync-to-wordpress.js）から、1日3回のパイプライン実行後に
 * バッチでPOSTされる。WordPressユーザーのログインは一切不要で、共有シークレット
 * （X-Oripa-Sync-Key ヘッダー）のみで認証する。
 *
 * notion_page_id をキーにしたUpsertにより、繰り返し実行しても投稿が増殖しない。
 *
 * シークレットの設定方法（どちらか）:
 *   1. wp-config.php に define( 'ORIPA_SYNC_KEY', 'xxxxx' ); を追加（推奨）
 *   2. 管理画面から一般設定 > oripa_sync_secret オプションを設定
 *      （wp option update oripa_sync_secret "xxxxx" でも可）
 *
 * @package oripa-market
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 設定済みの同期シークレットを取得する。未設定ならnull（エンドポイント自体を無効化）。
 */
function oripa_sync_get_secret() {
	if ( defined( 'ORIPA_SYNC_KEY' ) && ORIPA_SYNC_KEY ) {
		return ORIPA_SYNC_KEY;
	}
	$opt = get_option( 'oripa_sync_secret' );
	return $opt ? $opt : null;
}

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'oripa/v1',
			'/sync/lotteries',
			array(
				'methods'             => 'POST',
				'callback'            => 'oripa_sync_lotteries_handler',
				'permission_callback' => 'oripa_sync_permission_check',
			)
		);
	}
);

/**
 * X-Oripa-Sync-Key ヘッダーがシークレットと一致するかだけを見る、単純な認証。
 */
function oripa_sync_permission_check( WP_REST_Request $request ) {
	$secret = oripa_sync_get_secret();
	if ( ! $secret ) {
		return new WP_Error( 'oripa_sync_disabled', '同期シークレットが未設定のため、このエンドポイントは無効です。', array( 'status' => 503 ) );
	}
	$given = $request->get_header( 'x-oripa-sync-key' );
	if ( ! $given || ! hash_equals( $secret, $given ) ) {
		return new WP_Error( 'oripa_sync_forbidden', '認証に失敗しました。', array( 'status' => 403 ) );
	}
	return true;
}

/**
 * 店舗名（正規化キー）から shop 投稿を検索し、なければ作成する。
 * @param string $shop_name 表示名
 * @param string $shop_area shop_area タクソノミーに設定するエリア名（空なら未設定のまま）
 * @param string $official_url 店舗公式サイトURL（空なら更新しない）
 * @return int post ID
 */
function oripa_sync_upsert_shop( $shop_name, $shop_area = '', $official_url = '' ) {
	$shop_name = trim( wp_strip_all_tags( $shop_name ) );
	if ( '' === $shop_name ) {
		$shop_name = '店舗不明';
	}
	$key = mb_strtolower( $shop_name );

	$existing = get_posts(
		array(
			'post_type'      => 'shop',
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'   => 'notion_shop_key',
					'value' => $key,
				),
			),
			'fields'         => 'ids',
		)
	);

	if ( $existing ) {
		$shop_id = (int) $existing[0];
	} else {
		// notion_shop_keyでの一致がなければ、タイトル完全一致もフォールバックで見ておく
		// （手動登録済みshopとの重複作成を避けるため。get_page_by_titleはWP 6.2で非推奨のため使わない）。
		$by_title = get_posts(
			array(
				'post_type'      => 'shop',
				'post_status'    => 'any',
				'title'          => $shop_name,
				'posts_per_page' => 1,
				'fields'         => 'ids',
			)
		);
		if ( $by_title ) {
			$shop_id = (int) $by_title[0];
		} else {
			$shop_id = wp_insert_post(
				array(
					'post_type'   => 'shop',
					'post_status' => 'publish',
					'post_title'  => $shop_name,
				)
			);
			if ( is_wp_error( $shop_id ) ) {
				return 0;
			}
		}
		update_post_meta( $shop_id, 'notion_shop_key', $key );
	}

	if ( $shop_area ) {
		$term = term_exists( $shop_area, 'shop_area' );
		if ( ! $term ) {
			$term = wp_insert_term( $shop_area, 'shop_area' );
		}
		if ( ! is_wp_error( $term ) ) {
			wp_set_object_terms( $shop_id, (int) $term['term_id'], 'shop_area' );
		}
	}
	if ( $official_url ) {
		update_post_meta( $shop_id, 'official_url', esc_url_raw( $official_url ) );
	}
	// method情報から大まかにis_online/is_storeを立てておく（既存値がある場合は尊重し、trueのみ追記）。
	return (int) $shop_id;
}

/**
 * card_category（ジャンル）を見つける／なければ作成し、term_idを返す。
 * @param string $genre_slug 例: pokeka, onepiece
 * @param string $genre_name 表示名（例: ポケカ）。新規作成時のみ使用。
 */
function oripa_sync_ensure_category_term( $genre_slug, $genre_name ) {
	$term = get_term_by( 'slug', $genre_slug, 'card_category' );
	if ( $term ) {
		return (int) $term->term_id;
	}
	$res = wp_insert_term( $genre_name ?: $genre_slug, 'card_category', array( 'slug' => $genre_slug ) );
	return is_wp_error( $res ) ? 0 : (int) $res['term_id'];
}

/**
 * card_box（パック種類）をcard_categoryの子termとして見つける／なければ作成する。
 */
function oripa_sync_ensure_box_term( $box_name, $category_term_id ) {
	$box_name = trim( $box_name );
	if ( '' === $box_name ) {
		return 0;
	}
	$slug = sanitize_title( $box_name );
	$term = get_term_by( 'slug', $slug, 'card_box' );
	if ( $term ) {
		return (int) $term->term_id;
	}
	$res = wp_insert_term(
		$box_name,
		'card_box',
		array(
			'slug'   => $slug,
			'parent' => $category_term_id,
		)
	);
	return is_wp_error( $res ) ? 0 : (int) $res['term_id'];
}

/**
 * lottery 投稿を notion_page_id でUpsertする。
 */
function oripa_sync_upsert_lottery( array $rec ) {
	$notion_id = isset( $rec['notion_page_id'] ) ? sanitize_text_field( $rec['notion_page_id'] ) : '';
	if ( '' === $notion_id ) {
		return array(
			'status' => 'error',
			'error'  => 'notion_page_id is required',
		);
	}

	$shop_id = oripa_sync_upsert_shop(
		$rec['shop_name'] ?? '',
		$rec['shop_area'] ?? '',
		$rec['shop_official_url'] ?? ''
	);

	$genre_slug = $rec['genre_slug'] ?? 'pokeka';
	$genre_name = $rec['genre_name'] ?? 'ポケカ';
	$cat_term_id = oripa_sync_ensure_category_term( $genre_slug, $genre_name );
	$box_term_id = oripa_sync_ensure_box_term( $rec['pack_type'] ?? '', $cat_term_id );

	$existing = get_posts(
		array(
			'post_type'      => 'lottery',
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'   => 'notion_page_id',
					'value' => $notion_id,
				),
			),
			'fields'         => 'ids',
		)
	);

	$title   = trim( wp_strip_all_tags( $rec['product_name'] ?? '' ) );
	$content = isset( $rec['note'] ) ? wp_kses_post( $rec['note'] ) : '';

	$postarr = array(
		'post_type'    => 'lottery',
		'post_title'   => '' !== $title ? $title : ( ( $rec['shop_name'] ?? '店舗' ) . 'の抽選' ),
		'post_content' => $content,
	);

	$is_update = ! empty( $existing );
	if ( $is_update ) {
		$postarr['ID'] = (int) $existing[0];
		$post_id       = wp_update_post( $postarr, true );
	} else {
		// 新規は下書きとして作成し、人が確認してから公開する運用を維持する。
		$postarr['post_status'] = 'draft';
		$post_id                = wp_insert_post( $postarr, true );
	}

	if ( is_wp_error( $post_id ) ) {
		return array(
			'status' => 'error',
			'error'  => $post_id->get_error_message(),
		);
	}

	update_post_meta( $post_id, 'notion_page_id', $notion_id );
	update_post_meta( $post_id, 'shop', $shop_id );
	update_post_meta( $post_id, 'method', ( 'store' === ( $rec['method'] ?? '' ) ) ? 'store' : 'online' );
	if ( ! empty( $rec['deadline'] ) ) {
		update_post_meta( $post_id, 'deadline', gmdate( 'Y-m-d H:i:s', strtotime( $rec['deadline'] ) ) );
	}
	update_post_meta( $post_id, 'member_required', ! empty( $rec['member_required'] ) ? 1 : 0 );
	update_post_meta( $post_id, 'id_required', ! empty( $rec['id_required'] ) ? 1 : 0 );
	update_post_meta( $post_id, 'round_no', (int) ( $rec['round_no'] ?? 1 ) );
	update_post_meta( $post_id, 'round_total', (int) ( $rec['round_total'] ?? 1 ) );
	if ( ! empty( $rec['apply_url'] ) ) {
		update_post_meta( $post_id, 'apply_url', esc_url_raw( $rec['apply_url'] ) );
	}
	if ( ! empty( $rec['source_url'] ) ) {
		update_post_meta( $post_id, 'source_url', esc_url_raw( $rec['source_url'] ) );
	}
	if ( isset( $rec['confidence_score'] ) ) {
		update_post_meta( $post_id, 'confidence_score', (float) $rec['confidence_score'] );
	}
	update_post_meta( $post_id, 'last_checked', current_time( 'mysql' ) );

	if ( $cat_term_id ) {
		wp_set_object_terms( $post_id, array( $cat_term_id ), 'card_category' );
	}
	if ( $box_term_id ) {
		wp_set_object_terms( $post_id, array( $box_term_id ), 'card_box', true );
	}

	return array(
		'status'  => $is_update ? 'updated' : 'created',
		'post_id' => (int) $post_id,
	);
}

/**
 * POST /wp-json/oripa/v1/sync/lotteries
 * body: { "records": [ {notion_page_id, shop_name, shop_area, shop_official_url,
 *                        product_name, pack_type, genre_slug, genre_name,
 *                        method, deadline, apply_url, source_url,
 *                        confidence_score, round_no, round_total,
 *                        member_required, id_required, note}, ... ] }
 */
function oripa_sync_lotteries_handler( WP_REST_Request $request ) {
	$body    = $request->get_json_params();
	$records = isset( $body['records'] ) && is_array( $body['records'] ) ? $body['records'] : array();

	if ( empty( $records ) ) {
		return new WP_Error( 'oripa_sync_empty', 'records is empty', array( 'status' => 400 ) );
	}

	$results = array();
	foreach ( $records as $rec ) {
		$results[] = oripa_sync_upsert_lottery( $rec );
	}

	$created = count( array_filter( $results, fn( $r ) => 'created' === $r['status'] ) );
	$updated = count( array_filter( $results, fn( $r ) => 'updated' === $r['status'] ) );
	$errors  = count( array_filter( $results, fn( $r ) => 'error' === $r['status'] ) );

	return array(
		'summary' => array(
			'created' => $created,
			'updated' => $updated,
			'errors'  => $errors,
			'total'   => count( $results ),
		),
		'results' => $results,
	);
}

<?php
/**
 * カスタム REST ルート  /wp-json/oripa/v1/*
 *
 * 静的プロトタイプの data/*.json と同じ形を返すことで、フロントの common.js /
 * app.js をほぼ書き換えずに使えるようにする。
 *
 *   /categories  … data/categories.json 相当
 *   /boxes       … data/boxes.json 相当
 *   /shops       … data/shops.json 相当
 *   /lotteries   … data/lotteries.json 相当
 *   /articles    … data/articles.json 相当
 *   /bootstrap   … 上記まとめ取り（トップページ用）
 *
 * @package oripa-market
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'rest_api_init',
	function () {
		$ns = 'oripa/v1';
		$ro = array(
			'methods'             => 'GET',
			'permission_callback' => '__return_true',
		);

		register_rest_route( $ns, '/categories', $ro + array( 'callback' => 'oripa_rest_categories' ) );
		register_rest_route( $ns, '/boxes', $ro + array( 'callback' => 'oripa_rest_boxes' ) );
		register_rest_route( $ns, '/shops', $ro + array( 'callback' => 'oripa_rest_shops' ) );
		register_rest_route( $ns, '/lotteries', $ro + array( 'callback' => 'oripa_rest_lotteries' ) );
		register_rest_route( $ns, '/articles', $ro + array( 'callback' => 'oripa_rest_articles' ) );
		register_rest_route(
			$ns,
			'/bootstrap',
			$ro + array(
				'callback' => function () {
					return array(
						'categories' => oripa_rest_categories(),
						'boxes'      => oripa_rest_boxes(),
						'shops'      => oripa_rest_shops(),
						'lotteries'  => oripa_rest_lotteries(),
						'articles'   => oripa_rest_articles(),
					);
				},
			)
		);
	}
);

/**
 * card_category の全 term。
 */
function oripa_rest_categories() {
	$terms = get_terms(
		array(
			'taxonomy'   => 'card_category',
			'hide_empty' => false,
			'orderby'    => 'term_order',
		)
	);
	$out = array();
	foreach ( $terms as $t ) {
		$out[] = array(
			'slug'     => $t->slug,
			'name'     => $t->name,
			'fullName' => get_term_meta( $t->term_id, 'oripa_full_name', true ) ?: $t->name,
		);
	}
	return $out;
}

/**
 * card_box の leaf term（親を持つもの）。category は親 term の slug。
 */
function oripa_rest_boxes() {
	$terms = get_terms(
		array(
			'taxonomy'   => 'card_box',
			'hide_empty' => false,
		)
	);
	$out = array();
	foreach ( $terms as $t ) {
		if ( 0 === (int) $t->parent ) {
			continue; // 親（カテゴリ相当）は除外。
		}
		$parent = get_term( $t->parent, 'card_box' );
		$out[]  = array(
			'slug'     => $t->slug,
			'category' => $parent && ! is_wp_error( $parent ) ? $parent->slug : '',
			'name'     => $t->name,
		);
	}
	return $out;
}

/**
 * shop CPT。id は投稿ID。
 */
function oripa_rest_shops() {
	$posts = get_posts(
		array(
			'post_type'      => 'shop',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		)
	);
	$out = array();
	foreach ( $posts as $p ) {
		$areas = wp_get_post_terms( $p->ID, 'shop_area', array( 'fields' => 'names' ) );
		$out[] = array(
			'id'       => (string) $p->ID,
			'name'     => $p->post_title,
			'area'     => $areas && ! is_wp_error( $areas ) ? $areas[0] : '',
			'isOnline' => (bool) get_post_meta( $p->ID, 'is_online', true ),
			'isStore'  => (bool) get_post_meta( $p->ID, 'is_store', true ),
		);
	}
	return $out;
}

/**
 * ISO8601（サイトタイムゾーン）へ整形。
 */
function oripa_to_iso( $datetime_string ) {
	if ( empty( $datetime_string ) ) {
		return '';
	}
	try {
		$dt = new DateTime( $datetime_string, wp_timezone() );
		return $dt->format( 'c' );
	} catch ( Exception $e ) {
		return '';
	}
}

/**
 * lottery CPT。
 */
function oripa_rest_lotteries() {
	$posts = get_posts(
		array(
			'post_type'      => 'lottery',
			'posts_per_page' => -1,
			'orderby'        => 'meta_value',
			'meta_key'       => 'deadline',
			'order'          => 'ASC',
		)
	);
	$out = array();
	foreach ( $posts as $p ) {
		$cat = wp_get_post_terms( $p->ID, 'card_category', array( 'fields' => 'slugs' ) );
		$box_terms = wp_get_post_terms( $p->ID, 'card_box' );
		$box_slug  = '';
		foreach ( (array) $box_terms as $bt ) {
			if ( ! is_wp_error( $bt ) && (int) $bt->parent !== 0 ) {
				$box_slug = $bt->slug;
				break;
			}
		}
		if ( '' === $box_slug && $box_terms && ! is_wp_error( $box_terms ) ) {
			$box_slug = $box_terms[0]->slug;
		}

		$last_checked = get_post_meta( $p->ID, 'last_checked', true );
		$updated_at   = $last_checked ? oripa_to_iso( $last_checked ) : oripa_to_iso( $p->post_modified );

		$out[] = array(
			'id'             => (string) $p->ID,
			'category'       => $cat && ! is_wp_error( $cat ) ? $cat[0] : '',
			'box'            => $box_slug,
			'shopId'         => (string) ( (int) get_post_meta( $p->ID, 'shop', true ) ),
			'method'         => get_post_meta( $p->ID, 'method', true ) ?: 'online',
			'memberRequired' => (bool) get_post_meta( $p->ID, 'member_required', true ),
			'idRequired'     => (bool) get_post_meta( $p->ID, 'id_required', true ),
			'deadline'       => oripa_to_iso( get_post_meta( $p->ID, 'deadline', true ) ),
			'roundNo'        => (int) ( get_post_meta( $p->ID, 'round_no', true ) ?: 1 ),
			'roundTotal'     => (int) ( get_post_meta( $p->ID, 'round_total', true ) ?: 1 ),
			'updatedAt'      => $updated_at,
			'permalink'      => get_permalink( $p ),
		);
	}
	return $out;
}

/**
 * column CPT。body は本文を段落配列に分解。
 */
function oripa_rest_articles() {
	$posts = get_posts(
		array(
			'post_type'      => 'column',
			'posts_per_page' => -1,
			'orderby'        => 'modified',
			'order'          => 'DESC',
		)
	);
	$out = array();
	foreach ( $posts as $p ) {
		$cats = wp_get_post_terms( $p->ID, 'column_category', array( 'fields' => 'names' ) );

		$content = trim( wp_strip_all_tags( apply_filters( 'the_content', $p->post_content ) ) );
		$paras   = preg_split( '/\n{2,}|\r\n{2,}/', $content );
		$paras   = array_values( array_filter( array_map( 'trim', $paras ) ) );
		if ( empty( $paras ) && $content ) {
			$paras = array( $content );
		}

		$out[] = array(
			'slug'      => $p->post_name,
			'category'  => $cats && ! is_wp_error( $cats ) ? $cats[0] : '',
			'title'     => $p->post_title,
			'excerpt'   => has_excerpt( $p ) ? get_the_excerpt( $p ) : wp_trim_words( $content, 60, '…' ),
			'updatedAt' => oripa_to_iso( $p->post_modified ),
			'readMin'   => (int) ( get_post_meta( $p->ID, 'read_min', true ) ?: 5 ),
			'body'      => $paras,
			'permalink' => get_permalink( $p ),
		);
	}
	return $out;
}

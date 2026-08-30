<?php
/**
 * カスタム投稿タイプ登録: lottery / shop / column
 *
 * @package oripa-market
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'init',
	function () {

		// 抽選（lottery）。
		register_post_type(
			'lottery',
			array(
				'label'         => '抽選',
				'labels'        => array(
					'name'               => '抽選',
					'singular_name'      => '抽選',
					'add_new'            => '新規抽選',
					'add_new_item'       => '抽選を追加',
					'edit_item'          => '抽選を編集',
					'new_item'           => '新規抽選',
					'view_item'          => '抽選を表示',
					'search_items'       => '抽選を検索',
					'not_found'          => '抽選が見つかりません',
					'not_found_in_trash' => 'ゴミ箱に抽選はありません',
					'all_items'          => '抽選一覧',
					'menu_name'          => '抽選',
				),
				'public'        => true,
				'has_archive'   => true,
				'menu_icon'     => 'dashicons-tickets-alt',
				'menu_position' => 5,
				'supports'      => array( 'title', 'editor', 'custom-fields', 'revisions' ),
				'rewrite'       => array(
					'slug'       => 'lottery',
					'with_front' => false,
				),
				'show_in_rest'  => true,
			)
		);

		// 店舗（shop）。
		register_post_type(
			'shop',
			array(
				'label'         => '店舗',
				'labels'        => array(
					'name'          => '店舗',
					'singular_name' => '店舗',
					'add_new_item'  => '店舗を追加',
					'edit_item'     => '店舗を編集',
					'all_items'     => '店舗一覧',
					'menu_name'     => '店舗',
				),
				'public'        => true,
				'has_archive'   => true,
				'menu_icon'     => 'dashicons-store',
				'menu_position' => 6,
				'supports'      => array( 'title', 'editor', 'custom-fields' ),
				'rewrite'       => array(
					'slug'       => 'shop',
					'with_front' => false,
				),
				'show_in_rest'  => true,
			)
		);

		// 攻略コラム（column）。
		register_post_type(
			'column',
			array(
				'label'         => '攻略コラム',
				'labels'        => array(
					'name'          => '攻略コラム',
					'singular_name' => 'コラム',
					'add_new_item'  => 'コラムを追加',
					'edit_item'     => 'コラムを編集',
					'all_items'     => 'コラム一覧',
					'menu_name'     => '攻略コラム',
				),
				'public'        => true,
				'has_archive'   => true,
				'menu_icon'     => 'dashicons-media-document',
				'menu_position' => 7,
				'supports'      => array( 'title', 'editor', 'excerpt', 'custom-fields', 'revisions', 'thumbnail' ),
				'rewrite'       => array(
					'slug'       => 'column',
					'with_front' => false,
				),
				'show_in_rest'  => true,
			)
		);
	}
);

/**
 * lottery のタイトルは「店舗名＋ボックス名」から自動生成できると運用が楽なので、
 * タイトル未入力時に保存直前で補完する。
 */
add_filter(
	'wp_insert_post_data',
	function ( $data, $postarr ) {
		if ( 'lottery' !== $data['post_type'] ) {
			return $data;
		}
		if ( '' !== trim( wp_strip_all_tags( $data['post_title'] ) ) ) {
			return $data;
		}
		$shop_id = isset( $postarr['acf']['shop'] ) ? (int) ( is_array( $postarr['acf']['shop'] ) ? reset( $postarr['acf']['shop'] ) : $postarr['acf']['shop'] ) : 0;
		$shop    = $shop_id ? get_the_title( $shop_id ) : '抽選';
		$data['post_title'] = $shop . ' の抽選';
		if ( empty( $data['post_name'] ) ) {
			$data['post_name'] = sanitize_title( 'lottery-' . wp_generate_password( 6, false ) );
		}
		return $data;
	},
	10,
	2
);

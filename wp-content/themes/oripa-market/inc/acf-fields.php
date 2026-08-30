<?php
/**
 * ACF フィールド定義（コード管理）。
 *
 * ACF プラグインが有効な場合のみ動作。フィールドグループは acf-json/ にも
 * 書き出されるので、GUI で編集した内容も Git 管理できる。
 *
 * @package oripa-market
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// acf-json の保存先・読込先をテーマ内に固定。
add_filter(
	'acf/settings/save_json',
	function () {
		return ORIPA_THEME_DIR . '/acf-json';
	}
);
add_filter(
	'acf/settings/load_json',
	function ( $paths ) {
		$paths[] = ORIPA_THEME_DIR . '/acf-json';
		return $paths;
	}
);

add_action(
	'acf/init',
	function () {
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}

		// ---- 抽選 lottery ----
		acf_add_local_field_group(
			array(
				'key'      => 'group_lottery',
				'title'    => '抽選の詳細',
				'fields'   => array(
					array(
						'key'           => 'field_lottery_method',
						'label'         => '抽選方式',
						'name'          => 'method',
						'type'          => 'select',
						'choices'       => array(
							'online' => 'オンライン',
							'store'  => '店頭',
						),
						'default_value' => 'online',
						'required'      => 1,
					),
					array(
						'key'            => 'field_lottery_shop',
						'label'          => '実施店舗',
						'name'           => 'shop',
						'type'           => 'post_object',
						'post_type'      => array( 'shop' ),
						'return_format'  => 'id',
						'ui'             => 1,
						'required'       => 1,
					),
					array(
						'key'             => 'field_lottery_deadline',
						'label'           => '応募締切',
						'name'            => 'deadline',
						'type'            => 'date_time_picker',
						'display_format'  => 'Y-m-d H:i',
						'return_format'   => 'Y-m-d H:i:s',
						'first_day'       => 0,
						'required'        => 1,
					),
					array(
						'key'           => 'field_lottery_member_required',
						'label'         => '会員登録が必要',
						'name'          => 'member_required',
						'type'          => 'true_false',
						'ui'            => 1,
						'default_value' => 0,
					),
					array(
						'key'           => 'field_lottery_id_required',
						'label'         => '本人確認が必要',
						'name'          => 'id_required',
						'type'          => 'true_false',
						'ui'            => 1,
						'default_value' => 0,
					),
					array(
						'key'           => 'field_lottery_round_no',
						'label'         => '第何回',
						'name'          => 'round_no',
						'type'          => 'number',
						'default_value' => 1,
						'min'           => 1,
					),
					array(
						'key'           => 'field_lottery_round_total',
						'label'         => '全何回',
						'name'          => 'round_total',
						'type'          => 'number',
						'default_value' => 1,
						'min'           => 1,
					),
					array(
						'key'          => 'field_lottery_last_checked',
						'label'        => '最終確認日時',
						'name'         => 'last_checked',
						'type'         => 'date_time_picker',
						'display_format' => 'Y-m-d H:i',
						'return_format'  => 'Y-m-d H:i:s',
						'instructions' => '空欄なら投稿の最終更新日時を使用します（カード上の「◯分前に確認」表示）。',
					),
				),
				'location' => array(
					array(
						array(
							'param'    => 'post_type',
							'operator' => '==',
							'value'    => 'lottery',
						),
					),
				),
				'menu_order' => 0,
				'position'   => 'normal',
				'style'      => 'default',
				'active'     => true,
			)
		);

		// ---- 店舗 shop ----
		acf_add_local_field_group(
			array(
				'key'      => 'group_shop',
				'title'    => '店舗の詳細',
				'fields'   => array(
					array(
						'key'           => 'field_shop_is_online',
						'label'         => 'オンライン抽選あり',
						'name'          => 'is_online',
						'type'          => 'true_false',
						'ui'            => 1,
						'default_value' => 1,
					),
					array(
						'key'           => 'field_shop_is_store',
						'label'         => '店頭抽選あり',
						'name'          => 'is_store',
						'type'          => 'true_false',
						'ui'            => 1,
						'default_value' => 1,
					),
				),
				'location' => array(
					array(
						array(
							'param'    => 'post_type',
							'operator' => '==',
							'value'    => 'shop',
						),
					),
				),
				'position' => 'side',
				'active'   => true,
			)
		);

		// ---- コラム column ----
		acf_add_local_field_group(
			array(
				'key'      => 'group_column',
				'title'    => 'コラムの詳細',
				'fields'   => array(
					array(
						'key'           => 'field_column_read_min',
						'label'         => '読了目安（分）',
						'name'          => 'read_min',
						'type'          => 'number',
						'default_value' => 5,
						'min'           => 1,
					),
				),
				'location' => array(
					array(
						array(
							'param'    => 'post_type',
							'operator' => '==',
							'value'    => 'column',
						),
					),
				),
				'position' => 'side',
				'active'   => true,
			)
		);
	}
);

/**
 * ACF が入っていない場合でも最低限メタが読めるよう register_post_meta しておく
 * （REST・テンプレの get_post_meta フォールバック用）。
 */
add_action(
	'init',
	function () {
		$lottery_meta = array(
			'method'          => 'string',
			'deadline'        => 'string',
			'member_required' => 'boolean',
			'id_required'     => 'boolean',
			'round_no'        => 'integer',
			'round_total'     => 'integer',
			'shop'            => 'integer',
			'last_checked'    => 'string',
		);
		foreach ( $lottery_meta as $key => $type ) {
			register_post_meta(
				'lottery',
				$key,
				array(
					'type'         => $type,
					'single'       => true,
					'show_in_rest' => true,
				)
			);
		}
		foreach ( array( 'is_online', 'is_store' ) as $key ) {
			register_post_meta(
				'shop',
				$key,
				array(
					'type'         => 'boolean',
					'single'       => true,
					'show_in_rest' => true,
				)
			);
		}
		register_post_meta(
			'column',
			'read_min',
			array(
				'type'         => 'integer',
				'single'       => true,
				'show_in_rest' => true,
			)
		);
	}
);

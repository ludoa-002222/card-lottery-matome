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
					array(
						'key'          => 'field_lottery_apply_url',
						'label'        => '応募URL',
						'name'         => 'apply_url',
						'type'         => 'url',
						'instructions' => '抽選応募フォーム・店舗公式ページ等、実際に応募できるページのURL。',
					),
					array(
						'key'          => 'field_lottery_source_url',
						'label'        => '情報源URL',
						'name'         => 'source_url',
						'type'         => 'url',
						'instructions' => '情報を検知した元記事・投稿のURL（突合の証跡）。',
					),
					array(
						'key'          => 'field_lottery_confidence_score',
						'label'        => '確信度スコア',
						'name'         => 'confidence_score',
						'type'         => 'number',
						'instructions' => 'Notion側STEP3の確信度スコア（0〜1）。',
					),
					array(
						'key'          => 'field_lottery_notion_page_id',
						'label'        => 'Notion Page ID',
						'name'         => 'notion_page_id',
						'type'         => 'text',
						'instructions' => 'Notion連携の同期キー。手動で変更しないでください。',
						'readonly'     => 1,
					),
					array(
						'key'          => 'field_lottery_purchase_link_url',
						'label'        => '購入導線リンク（アフィリエイト）',
						'name'         => 'purchase_link_url',
						'type'         => 'url',
						'instructions' => 'ジャンル別のアフィリエイトリンク（A8.net等）。応募URLとは別物で、上書きしない。',
					),
					array(
						'key'          => 'field_lottery_purchase_link_service',
						'label'        => '購入導線リンクのサービス名',
						'name'         => 'purchase_link_service',
						'type'         => 'text',
						'readonly'     => 1,
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
					array(
						'key'          => 'field_shop_official_url',
						'label'        => '公式サイトURL',
						'name'         => 'official_url',
						'type'         => 'url',
					),
					array(
						'key'          => 'field_shop_sns_url',
						'label'        => 'X（旧Twitter）URL',
						'name'         => 'sns_url',
						'type'         => 'url',
						'instructions' => '公式サイトが無い場合の応募導線フォールバックに使用。',
					),
					array(
						'key'          => 'field_shop_notion_shop_key',
						'label'        => '店舗名（同期用キー）',
						'name'         => 'notion_shop_key',
						'type'         => 'text',
						'instructions' => 'Notion連携で店舗を同定するための正規化名。手動で変更しないでください。',
						'readonly'     => 1,
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
			'method'           => 'string',
			'deadline'         => 'string',
			'member_required'  => 'boolean',
			'id_required'      => 'boolean',
			'round_no'         => 'integer',
			'round_total'      => 'integer',
			'shop'             => 'integer',
			'last_checked'     => 'string',
			// Notion連携（2026-09-06追加）。
			'apply_url'        => 'string',
			'source_url'       => 'string',
			'confidence_score' => 'number',
			'notion_page_id'   => 'string',
			// 購入導線リンク（2026-09-09追加）。
			'purchase_link_url'     => 'string',
			'purchase_link_service' => 'string',
			// 商品そのものの画像（2026-09-10追加）。グッズなどパック種類に当てはまらない商品用。
			'product_image_url'     => 'string',
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
		foreach ( array( 'official_url', 'sns_url', 'notion_shop_key' ) as $key ) {
			register_post_meta(
				'shop',
				$key,
				array(
					'type'         => 'string',
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
		// 新商品（release）用メタ（2026-09-10追加）。公式サイト由来の一次情報。
		foreach ( array( 'release_date', 'genre', 'official_url', 'source_site', 'notion_page_id', 'product_type', 'image_url' ) as $key ) {
			register_post_meta(
				'release',
				$key,
				array(
					'type'         => 'string',
					'single'       => true,
					'show_in_rest' => true,
				)
			);
		}

		// Notion「記事ソースDB」から同期するコラム用メタ（2026-09-09追加）。
		// source_url / source_site は出典クレジットの表示に使う。
		foreach ( array( 'source_url', 'source_site', 'notion_page_id', 'affiliate_services' ) as $key ) {
			register_post_meta(
				'column',
				$key,
				array(
					'type'         => 'string',
					'single'       => true,
					'show_in_rest' => true,
				)
			);
		}
	}
);

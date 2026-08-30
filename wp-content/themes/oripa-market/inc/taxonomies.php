<?php
/**
 * タクソノミー登録:
 *  - card_category : カード種類（ポケカ/ワンピ/遊戯王/…） lottery + column に付与
 *  - card_box      : ボックス（30周年記念パック/…） 階層。親 term = card_category 相当の名前で運用
 *  - column_category : コラムの分類（安く入手/高く売る/応募方法）
 *  - shop_area     : 店舗エリア（東京/大阪/…）
 *
 * card_box は「親カテゴリに属するボックス」を表現するため hierarchical=true にし、
 * seed 時に card_category と同名の親 term をぶら下げる運用にする。
 *
 * @package oripa-market
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'init',
	function () {

		register_taxonomy(
			'card_category',
			array( 'lottery', 'column' ),
			array(
				'label'             => 'カード種類',
				'labels'            => array(
					'name'          => 'カード種類',
					'singular_name' => 'カード種類',
					'menu_name'     => 'カード種類',
					'all_items'     => 'すべてのカード種類',
					'edit_item'     => 'カード種類を編集',
					'add_new_item'  => 'カード種類を追加',
				),
				'public'            => true,
				'hierarchical'     => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'rewrite'          => array(
					'slug'       => 'card-category',
					'with_front' => false,
				),
			)
		);

		register_taxonomy(
			'card_box',
			array( 'lottery' ),
			array(
				'label'             => 'ボックス',
				'labels'            => array(
					'name'          => 'ボックス',
					'singular_name' => 'ボックス',
					'menu_name'     => 'ボックス',
					'all_items'     => 'すべてのボックス',
					'edit_item'     => 'ボックスを編集',
					'add_new_item'  => 'ボックスを追加',
				),
				'public'            => true,
				'hierarchical'      => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'rewrite'           => array(
					'slug'       => 'card-box',
					'with_front' => false,
				),
			)
		);

		register_taxonomy(
			'column_category',
			array( 'column' ),
			array(
				'label'             => 'コラム分類',
				'labels'            => array(
					'name'          => 'コラム分類',
					'singular_name' => 'コラム分類',
					'menu_name'     => 'コラム分類',
				),
				'public'            => true,
				'hierarchical'      => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'rewrite'           => array(
					'slug'       => 'column-category',
					'with_front' => false,
				),
			)
		);

		register_taxonomy(
			'shop_area',
			array( 'shop' ),
			array(
				'label'             => 'エリア',
				'labels'            => array(
					'name'          => 'エリア',
					'singular_name' => 'エリア',
					'menu_name'     => 'エリア',
				),
				'public'            => true,
				'hierarchical'      => false,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'rewrite'           => array(
					'slug'       => 'shop-area',
					'with_front' => false,
				),
			)
		);
	}
);

/**
 * card_category の term に「正式名称（fullName）」を持たせるための term meta。
 * 例: pokeka -> ポケカ / fullName: ポケモンカード
 */
add_action(
	'card_category_add_form_fields',
	function () {
		?>
		<div class="form-field">
			<label for="oripa_full_name">正式名称（fullName）</label>
			<input type="text" name="oripa_full_name" id="oripa_full_name" value="">
			<p>例: 「ポケモンカード」。一覧見出しなどに使用します。</p>
		</div>
		<?php
	}
);

add_action(
	'card_category_edit_form_fields',
	function ( $term ) {
		$val = get_term_meta( $term->term_id, 'oripa_full_name', true );
		?>
		<tr class="form-field">
			<th scope="row"><label for="oripa_full_name">正式名称（fullName）</label></th>
			<td>
				<input type="text" name="oripa_full_name" id="oripa_full_name" value="<?php echo esc_attr( $val ); ?>">
				<p class="description">例: 「ポケモンカード」。一覧見出しなどに使用します。</p>
			</td>
		</tr>
		<?php
	}
);

foreach ( array( 'created_card_category', 'edited_card_category' ) as $hook ) {
	add_action(
		$hook,
		function ( $term_id ) {
			if ( isset( $_POST['oripa_full_name'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				update_term_meta( $term_id, 'oripa_full_name', sanitize_text_field( wp_unslash( $_POST['oripa_full_name'] ) ) );
			}
		}
	);
}

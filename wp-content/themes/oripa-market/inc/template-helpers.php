<?php
/**
 * テンプレート用ヘルパー（サーバー側）。
 *
 * 一覧カードや締切バッジの描画はフロント JS（common.js）に寄せる方針のため、
 * ここでは主にヘッダー/フッター/見出し・SEO・パンくずを組み立てる。
 *
 * @package oripa-market
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 現在ページのテンプレ識別子を返す（app.js が参照する data-page 値）。
 */
function oripa_page_key() {
	if ( is_front_page() ) {
		return 'home';
	}
	if ( is_singular( 'lottery' ) ) {
		return 'lottery-single';
	}
	if ( is_tax( 'card_category' ) ) {
		return 'category';
	}
	if ( is_tax( 'card_box' ) ) {
		return 'box';
	}
	if ( is_post_type_archive( 'lottery' ) ) {
		return 'lottery-archive';
	}
	if ( is_singular( 'column' ) ) {
		return 'article';
	}
	if ( is_post_type_archive( 'column' ) || is_tax( 'column_category' ) ) {
		return 'guide';
	}
	if ( is_post_type_archive( 'shop' ) ) {
		return 'shop';
	}
	if ( is_page() ) {
		$slug = get_post_field( 'post_name', get_queried_object_id() );
		return 'page-' . $slug;
	}
	return 'generic';
}

/**
 * パンくずリスト。$trail は [ ['label'=>..,'url'=>..], ... ]。
 */
function oripa_breadcrumb( $trail = array() ) {
	$items   = array_merge(
		array(
			array(
				'label' => 'ホーム',
				'url'   => home_url( '/' ),
			),
		),
		$trail
	);
	$last    = count( $items ) - 1;
	$out     = '<nav class="breadcrumb">';
	foreach ( $items as $i => $item ) {
		if ( $i > 0 ) {
			$out .= ' &gt; ';
		}
		if ( $i === $last || empty( $item['url'] ) ) {
			$out .= '<span>' . esc_html( $item['label'] ) . '</span>';
		} else {
			$out .= '<a href="' . esc_url( $item['url'] ) . '">' . esc_html( $item['label'] ) . '</a>';
		}
	}
	$out .= '</nav>';
	return $out;
}

/**
 * card_category term の正式名称。
 */
function oripa_category_full_name( $term ) {
	if ( ! $term instanceof WP_Term ) {
		return '';
	}
	$full = get_term_meta( $term->term_id, 'oripa_full_name', true );
	return $full ? $full : $term->name;
}

/**
 * ヘッダーナビ項目（renderHeader 相当）。
 */
function oripa_header_nav_items() {
	return array(
		array(
			'label' => '締切カレンダー',
			'url'   => home_url( '/calendar/' ),
		),
		array(
			'label' => '攻略コラム',
			'url'   => get_post_type_archive_link( 'column' ),
		),
		array(
			'label' => '店舗一覧',
			'url'   => get_post_type_archive_link( 'shop' ),
		),
		array(
			'label' => '情報の正確性について',
			'url'   => home_url( '/trust/' ),
		),
	);
}

/**
 * フッターリンク項目（renderFooter 相当）。
 */
function oripa_footer_link_items() {
	return array(
		array(
			'label' => '抽選締切カレンダー',
			'url'   => home_url( '/calendar/' ),
		),
		array(
			'label' => 'トレカ攻略コラム',
			'url'   => get_post_type_archive_link( 'column' ),
		),
		array(
			'label' => '情報の正確性について',
			'url'   => home_url( '/trust/' ),
		),
		array(
			'label' => '運営者について',
			'url'   => home_url( '/about/' ),
		),
		array(
			'label' => 'よくある質問',
			'url'   => home_url( '/faq/' ),
		),
		array(
			'label' => '全店舗一覧',
			'url'   => get_post_type_archive_link( 'shop' ),
		),
		array(
			'label' => '会社概要',
			'url'   => home_url( '/company/' ),
		),
		array(
			'label' => '利用規約',
			'url'   => home_url( '/terms/' ),
		),
		array(
			'label' => 'プライバシーポリシー',
			'url'   => home_url( '/privacy/' ),
		),
	);
}

/**
 * 記事サムネイル（common.js の articleThumbHtml の PHP 版）。
 */
function articleThumbHtml_php( $category, $cls = 'article-detail-hero' ) {
	$tones = array(
		'安く入手' => array( '#e8f0ff', '#2f6fed' ),
		'高く売る' => array( '#e7f8ec', '#16a34a' ),
		'応募方法' => array( '#f1e9fe', '#7c3aed' ),
	);
	$pair = isset( $tones[ $category ] ) ? $tones[ $category ] : array( '#e8f0ff', '#2f6fed' );
	$tone = $pair[0];
	$fg   = $pair[1];
	$gid  = 'art-g-php-' . wp_rand( 1000, 9999 );
	ob_start();
	?>
	<svg class="<?php echo esc_attr( $cls ); ?>" viewBox="0 0 200 120" preserveAspectRatio="xMidYMid slice" xmlns="http://www.w3.org/2000/svg">
		<defs><linearGradient id="<?php echo esc_attr( $gid ); ?>" x1="0" y1="0" x2="1" y2="1">
			<stop offset="0%" stop-color="<?php echo esc_attr( $tone ); ?>"/><stop offset="100%" stop-color="#ffffff"/>
		</linearGradient></defs>
		<rect width="200" height="120" fill="url(#<?php echo esc_attr( $gid ); ?>)"/>
		<g transform="translate(100 60)">
			<rect x="-46" y="-32" width="92" height="64" rx="6" fill="#fff" stroke="<?php echo esc_attr( $fg ); ?>" stroke-width="3"/>
			<line x1="-30" y1="-12" x2="30" y2="-12" stroke="<?php echo esc_attr( $fg ); ?>" stroke-width="3" opacity=".55"/>
			<line x1="-30" y1="0" x2="18" y2="0" stroke="<?php echo esc_attr( $fg ); ?>" stroke-width="3" opacity=".35"/>
			<line x1="-30" y1="12" x2="24" y2="12" stroke="<?php echo esc_attr( $fg ); ?>" stroke-width="3" opacity=".35"/>
			<circle cx="34" cy="-20" r="12" fill="<?php echo esc_attr( $fg ); ?>"/>
		</g>
	</svg>
	<?php
	return ob_get_clean();
}

/**
 * 抽選件数などの簡易統計（トップ / trust ページの信頼ダッシュボード用）。
 */
function oripa_trust_stats() {
	$lottery_count = (int) wp_count_posts( 'lottery' )->publish;
	$shop_ids      = array();
	$q             = get_posts(
		array(
			'post_type'      => 'lottery',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		)
	);
	foreach ( $q as $id ) {
		$sid = (int) get_post_meta( $id, 'shop', true );
		if ( $sid ) {
			$shop_ids[ $sid ] = true;
		}
	}
	return array(
		'lotteries'     => $lottery_count,
		'verifiedShops' => count( $shop_ids ),
		'totalShops'    => (int) wp_count_posts( 'shop' )->publish,
	);
}

<?php
/**
 * テーマ基本セットアップ。
 *
 * @package oripa-market
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'after_setup_theme',
	function () {
		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'automatic-feed-links' );
		add_theme_support(
			'html5',
			array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' )
		);
		register_nav_menus(
			array(
				'primary' => 'ヘッダーナビ',
				'footer'  => 'フッターリンク',
			)
		);
		load_theme_textdomain( 'oripa-market', ORIPA_THEME_DIR . '/languages' );
	}
);

/**
 * 固定ページの slug から使うテンプレートを解決するためのヘルパ。
 * page-{slug}.php があれば WordPress が自動採用するので追加処理は不要だが、
 * サイト初期化時に必要な固定ページを作るための slug 一覧をここで定義しておく。
 */
function oripa_required_pages() {
	return array(
		'online'   => 'オンライン抽選まとめ',
		'store'    => '店頭抽選まとめ',
		'calendar' => '抽選締切カレンダー',
		'trust'    => '情報の正確性について',
		'about'    => '運営者について',
		'faq'      => 'よくある質問',
		'company'  => '会社概要',
		'terms'    => '利用規約',
		'privacy'  => 'プライバシーポリシー',
		'mypage'   => 'マイページ',
		'register' => '無料会員登録',
	);
}

/**
 * body_class に現在のテンプレート種別を足しておく（CSS フック用）。
 */
add_filter(
	'body_class',
	function ( $classes ) {
		if ( is_singular( 'lottery' ) ) {
			$classes[] = 'tpl-lottery-single';
		}
		if ( is_post_type_archive( 'lottery' ) || is_tax( 'card_category' ) || is_tax( 'card_box' ) ) {
			$classes[] = 'tpl-lottery-archive';
		}
		return $classes;
	}
);

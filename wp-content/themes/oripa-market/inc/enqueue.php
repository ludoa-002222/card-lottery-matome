<?php
/**
 * CSS / JS の読み込み。
 *
 * 静的プロトタイプの assets/css/style.css と assets/js/common.js を流用し、
 * ページ別の初期化は app.js が担当。データ取得先は window.ORIPA.restBase。
 *
 * @package oripa-market
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'wp_enqueue_scripts',
	function () {
		wp_enqueue_style(
			'oripa-style',
			ORIPA_THEME_URI . '/assets/css/style.css',
			array(),
			ORIPA_THEME_VERSION
		);

		wp_enqueue_script(
			'oripa-common',
			ORIPA_THEME_URI . '/assets/js/common.js',
			array(),
			ORIPA_THEME_VERSION,
			true
		);
		wp_enqueue_script(
			'oripa-app',
			ORIPA_THEME_URI . '/assets/js/app.js',
			array( 'oripa-common' ),
			ORIPA_THEME_VERSION,
			true
		);

		$current_user = wp_get_current_user();
		wp_localize_script(
			'oripa-common',
			'ORIPA',
			array(
				'restBase'    => esc_url_raw( rest_url( 'oripa/v1/' ) ),
				'nonce'       => wp_create_nonce( 'wp_rest' ),
				'themeUri'    => ORIPA_THEME_URI,
				'assetsBase'  => ORIPA_THEME_URI . '/assets/img/',
				'homeUrl'     => home_url( '/' ),
				'categoryBase' => home_url( '/card-category/' ),
				'boxBase'     => home_url( '/card-box/' ),
				'isLoggedIn'  => is_user_logged_in(),
				'displayName' => is_user_logged_in() ? $current_user->display_name : '',
				'loginUrl'    => wp_login_url( home_url( '/' ) ),
				'logoutUrl'   => wp_logout_url( home_url( '/' ) ),
				'registerUrl' => home_url( '/register/' ),
				'myPageUrl'   => home_url( '/mypage/' ),
			)
		);
	}
);

/**
 * クラシックテーマだが念のためブロックライブラリ CSS など不要物を軽く外す。
 */
add_action(
	'wp_enqueue_scripts',
	function () {
		if ( ! is_admin() ) {
			wp_dequeue_style( 'wp-block-library' );
			wp_dequeue_style( 'wp-block-library-theme' );
			wp_dequeue_style( 'global-styles' );
			wp_dequeue_style( 'classic-theme-styles' );
		}
	},
	20
);

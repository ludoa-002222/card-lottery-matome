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
		// WPコア同様の慣習: SCRIPT_DEBUG が真の間（wp-env）はソースのまま、
		// それ以外（本番）は .min を読む。*.min.css/*.min.js は
		// `npm run build:assets` で生成してコミットしたものを使う
		// （本番はサーバー上 git pull 方式でビルド工程が無いため。
		// scripts/build-assets.sh 参照。2026-09-15）。
		$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		wp_enqueue_style(
			'oripa-style',
			ORIPA_THEME_URI . '/assets/css/style' . $suffix . '.css',
			array(),
			ORIPA_THEME_VERSION
		);

		// 応募方法ガイド（プラットフォーム別の定数）。common.js より先に読み込む。
		wp_enqueue_script(
			'oripa-apply-guides',
			ORIPA_THEME_URI . '/assets/js/apply-guides' . $suffix . '.js',
			array(),
			ORIPA_THEME_VERSION,
			true
		);
		wp_enqueue_script(
			'oripa-common',
			ORIPA_THEME_URI . '/assets/js/common' . $suffix . '.js',
			array( 'oripa-apply-guides' ),
			ORIPA_THEME_VERSION,
			true
		);
		// 店舗のチェーン系列まとめ（全店舗一覧で使う）。app.js より先に読み込む。
		wp_enqueue_script(
			'oripa-shop-groups',
			ORIPA_THEME_URI . '/assets/js/shop-groups' . $suffix . '.js',
			array(),
			ORIPA_THEME_VERSION,
			true
		);
		wp_enqueue_script(
			'oripa-app',
			ORIPA_THEME_URI . '/assets/js/app' . $suffix . '.js',
			array( 'oripa-common', 'oripa-shop-groups' ),
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
				// テーマ内画像のキャッシュ破棄に使う。画像URLには ?ver= が付かないため、
				// 差し替えても本番の1年キャッシュが古い画像を返し続ける問題があった（2026-09-09）。
				'version'     => ORIPA_THEME_VERSION,
				'homeUrl'     => home_url( '/' ),
				'categoryBase' => home_url( '/card-category/' ),
				'boxBase'     => home_url( '/card-box/' ),
				'isLoggedIn'  => is_user_logged_in(),
				'displayName' => is_user_logged_in() ? $current_user->display_name : '',
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

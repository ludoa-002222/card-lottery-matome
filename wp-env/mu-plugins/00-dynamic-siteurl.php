<?php
/**
 * Plugin Name: Dynamic Site URL (dev / tunnel)
 * Description: リクエストの Host（またはプロキシの X-Forwarded-Host）に合わせて WordPress が生成する
 *              絶対URLのオリジンを差し替える。cloudflared / ngrok などのトンネル越しでも localhost へ
 *              リダイレクトせず、テーマ・プラグイン・REST・アップロードの各URLも正しいホストになる。
 *              wp-env は wp-config.php に WP_HOME / WP_SITEURL / WP_CONTENT_URL を定数で焼き込むため、
 *              option フィルタだけでは足りず content_url 等も個別に書き換えている。
 *              ★ローカル開発専用。本番環境には配置しないこと。
 *
 * wp-env の mappings で wp-content/mu-plugins/ にマウントされる。
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 現在アクセスされているスキーム＋ホストのオリジン（例: https://xxx.trycloudflare.com）。
 * 取得できなければ空文字。
 */
function oripa_dev_current_origin() {
	static $cached = null;
	if ( null !== $cached ) {
		return $cached;
	}

	$host = '';
	if ( ! empty( $_SERVER['HTTP_X_FORWARDED_HOST'] ) ) {
		$host = trim( explode( ',', $_SERVER['HTTP_X_FORWARDED_HOST'] )[0] );
	} elseif ( ! empty( $_SERVER['HTTP_HOST'] ) ) {
		$host = trim( $_SERVER['HTTP_HOST'] );
	}
	if ( '' === $host ) {
		$cached = '';
		return $cached;
	}

	$scheme = 'http';
	if ( ! empty( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) ) {
		$scheme = strtolower( trim( explode( ',', $_SERVER['HTTP_X_FORWARDED_PROTO'] )[0] ) );
	} elseif ( ( ! empty( $_SERVER['HTTPS'] ) && 'off' !== $_SERVER['HTTPS'] ) || 443 === (int) ( $_SERVER['SERVER_PORT'] ?? 0 ) ) {
		$scheme = 'https';
	}
	if ( preg_match( '/(trycloudflare\.com|ngrok(-free)?\.app|ngrok\.io|loca\.lt)$/i', $host ) ) {
		$scheme = 'https';
	}

	$cached = $scheme . '://' . $host;
	return $cached;
}

/**
 * URL 文字列のオリジンが localhost / 127.0.0.1（任意ポート）なら現在のオリジンへ差し替える。
 */
function oripa_dev_rewrite_url( $url ) {
	if ( ! is_string( $url ) || '' === $url ) {
		return $url;
	}
	$origin = oripa_dev_current_origin();
	if ( ! $origin ) {
		return $url;
	}
	return preg_replace( '#^https?://(?:localhost|127\.0\.0\.1)(?::\d+)?#', $origin, $url );
}

if ( oripa_dev_current_origin() ) {

	// HTTPS 終端のトンネル越しでは is_ssl() を真にする。
	if ( 0 === strpos( oripa_dev_current_origin(), 'https://' ) ) {
		$_SERVER['HTTPS'] = 'on';
	}

	// home / siteurl（canonical・リダイレクト・管理バー等）。
	add_filter( 'option_home', 'oripa_dev_current_origin', 20 );
	add_filter( 'option_siteurl', 'oripa_dev_current_origin', 20 );

	// 定数（WP_CONTENT_URL 等）由来で組み立てられる各種URL。
	foreach ( array(
		'content_url',
		'plugins_url',
		'includes_url',
		'theme_root_uri',
		'stylesheet_directory_uri',
		'template_directory_uri',
		'home_url',
		'site_url',
		'admin_url',
		'rest_url',
		'network_site_url',
		'network_home_url',
	) as $oripa_dev_hook ) {
		add_filter( $oripa_dev_hook, 'oripa_dev_rewrite_url', 20 );
	}

	// アップロードURL。
	add_filter(
		'upload_dir',
		function ( $dirs ) {
			foreach ( array( 'url', 'baseurl' ) as $k ) {
				if ( ! empty( $dirs[ $k ] ) ) {
					$dirs[ $k ] = oripa_dev_rewrite_url( $dirs[ $k ] );
				}
			}
			return $dirs;
		},
		20
	);
}

<?php
/**
 * サンプルデータ投入（ブラウザ実行版）。
 *
 * WP-CLI が使えないホスティング（InfinityFree など）向け。
 * 中身は bin/seed.php をそのまま再利用する（WP_CLI のログ出力を簡易ポリフィルで受ける）。
 *
 * トークンの決め方（いずれか1つ）:
 *   - wp-config.php に1行追加（エックスサーバー等の「ファイルマネージャ」で編集できる。一番簡単）:
 *        define( 'ORIPA_SEED_TOKEN', '好きな16文字以上の文字列' );
 *   - サーバーの環境変数 ORIPA_SEED_TOKEN に設定
 *   - bin/.seed-token というファイルに16文字以上を書いて置く
 *
 * 実行:
 *   ブラウザで
 *     https://<サイト>/wp-content/themes/oripa-market/bin/seed-web.php?token=<そのトークン>
 *   「seed 完了。」が出たら、この seed-web.php（と使ったなら .seed-token / wp-config.php の1行）を消す。
 *
 * ★開発・初期投入専用。本番やステージングに置きっぱなしにしないこと。
 *
 * @package oripa-market
 */

// -------------------------------------------------------------- load WordPress

$wp_load = null;
foreach ( array(
	dirname( __DIR__, 4 ) . '/wp-load.php', // wp-content/themes/oripa-market/bin/ -> WP ルート
	dirname( __DIR__, 5 ) . '/wp-load.php', // 念のため1階層上も
) as $candidate ) {
	if ( is_readable( $candidate ) ) {
		$wp_load = $candidate;
		break;
	}
}
if ( ! $wp_load ) {
	http_response_code( 500 );
	exit( 'wp-load.php が見つかりませんでした。' );
}

define( 'WP_USE_THEMES', false );
require $wp_load;

// -------------------------------------------------------------- token guard

$expected = '';
if ( defined( 'ORIPA_SEED_TOKEN' ) ) {
	$expected = (string) ORIPA_SEED_TOKEN;
} elseif ( getenv( 'ORIPA_SEED_TOKEN' ) ) {
	$expected = (string) getenv( 'ORIPA_SEED_TOKEN' );
} elseif ( is_readable( __DIR__ . '/.seed-token' ) ) {
	$expected = trim( (string) file_get_contents( __DIR__ . '/.seed-token' ) );
}
$given = isset( $_GET['token'] ) ? (string) $_GET['token'] : '';

// トークンに加え、WordPress 管理者ログイン中でも実行を許可（どちらか満たせばOK）。
$ok = ( strlen( $expected ) >= 16 && hash_equals( $expected, $given ) ) || current_user_can( 'manage_options' );

if ( ! $ok ) {
	http_response_code( 403 );
	header( 'Content-Type: text/plain; charset=utf-8' );
	echo "403 Forbidden\n\n";
	echo "wp-config.php に define('ORIPA_SEED_TOKEN','16文字以上') を足して ?token= を付けるか、\n";
	echo "WordPress に管理者でログインした状態でアクセスしてください。\n";
	exit;
}

// -------------------------------------------------------------- WP_CLI polyfill

header( 'Content-Type: text/html; charset=utf-8' );
echo "<pre style=\"font:13px/1.6 ui-monospace,Menlo,Consolas,monospace;padding:16px;\">\n";

if ( ! class_exists( 'WP_CLI' ) ) {
	class WP_CLI {
		public static function log( $m ) {
			echo esc_html( $m ) . "\n";
			flush();
		}
		public static function warning( $m ) {
			echo 'warning: ' . esc_html( $m ) . "\n";
			flush();
		}
		public static function success( $m ) {
			echo "\n" . esc_html( $m ) . "\n";
			flush();
		}
		public static function error( $m ) {
			echo 'error: ' . esc_html( $m ) . "\n";
			exit( 1 );
		}
	}
}
if ( ! defined( 'WP_CLI' ) ) {
	define( 'WP_CLI', true );
}

// -------------------------------------------------------------- run seed.php

require __DIR__ . '/seed.php';

echo "\nseed-web.php と bin/.seed-token を削除してください。\n";
echo '</pre>';

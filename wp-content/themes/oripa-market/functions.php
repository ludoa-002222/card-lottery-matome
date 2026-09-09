<?php
/**
 * オリパマーケット テーマ ブートストラップ
 *
 * 各機能は inc/ 以下に分割。
 *
 * @package oripa-market
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ORIPA_THEME_VERSION', '0.3.4' );
define( 'ORIPA_THEME_DIR', get_template_directory() );
define( 'ORIPA_THEME_URI', get_template_directory_uri() );

require_once ORIPA_THEME_DIR . '/inc/theme-setup.php';
require_once ORIPA_THEME_DIR . '/inc/post-types.php';
require_once ORIPA_THEME_DIR . '/inc/taxonomies.php';
require_once ORIPA_THEME_DIR . '/inc/acf-fields.php';
require_once ORIPA_THEME_DIR . '/inc/template-helpers.php';
require_once ORIPA_THEME_DIR . '/inc/rest-api.php';
require_once ORIPA_THEME_DIR . '/inc/sync-api.php';
require_once ORIPA_THEME_DIR . '/inc/enqueue.php';
// 会員登録・ログイン機能は 2026-09-09 に停止（将来必要になったらこの行を戻す）。
// テンプレート（page-register.php / page-mypage.php）と inc/members.php はファイルとしては残してある。
// require_once ORIPA_THEME_DIR . '/inc/members.php';

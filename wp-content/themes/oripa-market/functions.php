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

define( 'ORIPA_THEME_VERSION', '0.1.3' );
define( 'ORIPA_THEME_DIR', get_template_directory() );
define( 'ORIPA_THEME_URI', get_template_directory_uri() );

require_once ORIPA_THEME_DIR . '/inc/theme-setup.php';
require_once ORIPA_THEME_DIR . '/inc/post-types.php';
require_once ORIPA_THEME_DIR . '/inc/taxonomies.php';
require_once ORIPA_THEME_DIR . '/inc/acf-fields.php';
require_once ORIPA_THEME_DIR . '/inc/template-helpers.php';
require_once ORIPA_THEME_DIR . '/inc/rest-api.php';
require_once ORIPA_THEME_DIR . '/inc/enqueue.php';
require_once ORIPA_THEME_DIR . '/inc/members.php';

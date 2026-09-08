<?php
/**
 * 会員登録／マイページ（廃止・2026-09-09）。
 *
 * ログイン・会員登録機能は運用しない方針になったため、このページは使わない。
 * 既存のリンクやブックマークから来た場合はトップページへ301リダイレクトする。
 * （inc/members.php の読み込みを止めたので、以前の実装をそのまま残すと
 *   oripa_member_notice() 未定義で致命的エラーになる。）
 *
 * @package oripa-market
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

wp_safe_redirect( home_url( '/' ), 301 );
exit;

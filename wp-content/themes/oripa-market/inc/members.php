<?php
/**
 * 会員まわり（WP 標準のユーザー登録・ログインに接続）。
 *
 * - 会員登録は wp-login.php?action=register の標準フローを利用
 * - ログイン後は /mypage/ に戻す
 * - テーマ側テンプレート: page-register.php / page-mypage.php
 *
 * @package oripa-market
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// 新規登録を常に許可（wp-env のデフォルトは無効）。運用で切り替えたい場合はこのフィルタを外す。
add_filter( 'option_users_can_register', '__return_true' );

// ログイン後のリダイレクト先。
add_filter(
	'login_redirect',
	function ( $redirect_to, $requested, $user ) {
		if ( $user instanceof WP_User && ! is_wp_error( $user ) ) {
			if ( in_array( 'administrator', (array) $user->roles, true ) ) {
				return $redirect_to; // 管理者は通常挙動。
			}
			return home_url( '/mypage/' );
		}
		return $redirect_to;
	},
	10,
	3
);

// 登録完了後のリダイレクト先（「確認メール送信」画面の戻り先）。
add_filter(
	'registration_redirect',
	function () {
		return home_url( '/mypage/?registered=1' );
	}
);

/**
 * 会員登録フォームの action URL。
 */
function oripa_register_action_url() {
	return esc_url( site_url( 'wp-login.php?action=register', 'login_post' ) );
}

/**
 * ログインフォームの action URL。
 */
function oripa_login_action_url() {
	return esc_url( site_url( 'wp-login.php', 'login_post' ) );
}

/**
 * ?registered / ?login=failed などのメッセージ文言。
 */
function oripa_member_notice() {
	$msg = '';
	if ( isset( $_GET['registered'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$msg = '確認メールを送信しました。メール内のリンクからパスワードを設定してください。';
	} elseif ( isset( $_GET['login'] ) && 'failed' === $_GET['login'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$msg = 'メールアドレスまたはパスワードが正しくありません。';
	} elseif ( isset( $_GET['loggedout'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$msg = 'ログアウトしました。';
	}
	return $msg;
}

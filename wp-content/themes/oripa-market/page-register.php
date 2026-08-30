<?php
/**
 * 無料会員登録。WP 標準のユーザー登録（wp-login.php?action=register）に接続。
 *
 * @package oripa-market
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( is_user_logged_in() ) {
	wp_safe_redirect( home_url( '/mypage/' ) );
	exit;
}

get_header();
$notice = oripa_member_notice();
?>
<main class="wrap">
	<?php echo oripa_breadcrumb( array( array( 'label' => '無料会員登録' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<div class="form-box">
		<img class="mascot-inline" src="<?php echo esc_url( ORIPA_THEME_URI . '/assets/img/logo-mark.svg' ); ?>" alt="" style="width:48px;">
		<h1 style="font-size:1.2rem;text-align:center;">無料会員登録</h1>
		<p class="footer-note" style="text-align:center;">登録すると、締切カレンダーの全件表示・お気に入り店舗の新着通知などが使えます。</p>

		<?php if ( $notice ) : ?>
			<p class="form-notice"><?php echo esc_html( $notice ); ?></p>
		<?php endif; ?>

		<form action="<?php echo oripa_register_action_url(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>" method="post" novalidate>
			<label for="user_login">ユーザー名</label>
			<input type="text" name="user_login" id="user_login" autocapitalize="off" autocomplete="username" required>
			<label for="user_email">メールアドレス</label>
			<input type="email" name="user_email" id="user_email" autocomplete="email" required>
			<p class="footer-note">確認メールが届きます。メール内リンクからパスワードを設定してください。</p>
			<button class="btn primary" type="submit">登録する</button>
		</form>

		<p class="footer-note" style="text-align:center;margin-top:14px;">登録済みの方は<a href="<?php echo esc_url( home_url( '/mypage/' ) ); ?>">ログイン</a></p>
	</div>
</main>
<?php
get_footer();

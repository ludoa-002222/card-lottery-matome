<?php
/**
 * マイページ。未ログイン時は WP 標準ログインフォーム、ログイン時は簡易ダッシュボード。
 *
 * @package oripa-market
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
$notice = oripa_member_notice();
?>
<main class="wrap">
	<?php echo oripa_breadcrumb( array( array( 'label' => 'マイページ' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

	<?php if ( is_user_logged_in() ) : ?>
		<?php $u = wp_get_current_user(); ?>
		<div class="page-title"><h1>マイページ</h1></div>
		<div class="form-box">
			<p>ようこそ、<strong><?php echo esc_html( $u->display_name ); ?></strong> さん。</p>
			<ul>
				<li>メールアドレス: <?php echo esc_html( $u->user_email ); ?></li>
				<li>会員登録日: <?php echo esc_html( mysql2date( 'Y/m/d', $u->user_registered ) ); ?></li>
			</ul>
			<p class="footer-note">お気に入り抽選・新着通知は今後のアップデートで提供予定です。</p>
			<a class="btn ghost" href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>">ログアウト</a>
			<a class="btn" href="<?php echo esc_url( admin_url( 'profile.php' ) ); ?>">プロフィール編集</a>
		</div>
	<?php else : ?>
		<div class="form-box">
			<h1 style="font-size:1.2rem;text-align:center;">ログイン</h1>
			<?php if ( $notice ) : ?>
				<p class="form-notice"><?php echo esc_html( $notice ); ?></p>
			<?php endif; ?>
			<?php
			wp_login_form(
				array(
					'redirect'       => home_url( '/mypage/' ),
					'label_username' => 'ユーザー名 または メールアドレス',
					'label_password' => 'パスワード',
					'label_remember' => 'ログイン状態を保持',
					'label_log_in'   => 'ログイン',
				)
			);
			?>
			<p class="footer-note" style="text-align:center;margin-top:14px;">
				<a href="<?php echo esc_url( wp_lostpassword_url() ); ?>">パスワードをお忘れの方</a>
			</p>
			<p class="footer-note" style="text-align:center;">未登録の方は<a href="<?php echo esc_url( home_url( '/register/' ) ); ?>">無料会員登録</a></p>
		</div>
	<?php endif; ?>
</main>
<?php
get_footer();

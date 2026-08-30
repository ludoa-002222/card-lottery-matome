<?php
/**
 * ヘッダー。静的プロトタイプの renderHeader() 相当をサーバー側で出力。
 *
 * @package oripa-market
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?> data-page="<?php echo esc_attr( oripa_page_key() ); ?>">
<?php wp_body_open(); ?>

<header id="site-header" class="site-header">
	<div class="wrap">
		<a class="logo" href="<?php echo esc_url( home_url( '/' ) ); ?>">
			<img class="mark" src="<?php echo esc_url( ORIPA_THEME_URI . '/assets/img/logo-mark.svg' ); ?>" alt="<?php bloginfo( 'name' ); ?>">
			<span class="logo-text"><?php bloginfo( 'name' ); ?><small>トレカ抽選・予約情報まとめ</small></span>
		</a>
		<nav class="nav-links">
			<?php foreach ( oripa_header_nav_items() as $item ) : ?>
				<a href="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html( $item['label'] ); ?></a>
			<?php endforeach; ?>
			<span class="live-pill"><span class="dot"></span>自動監視中</span>
		</nav>
		<?php if ( is_user_logged_in() ) : ?>
			<a class="nav-cta" href="<?php echo esc_url( home_url( '/mypage/' ) ); ?>">マイページ</a>
		<?php else : ?>
			<a class="nav-cta" href="<?php echo esc_url( home_url( '/register/' ) ); ?>">無料会員登録</a>
		<?php endif; ?>
	</div>
</header>

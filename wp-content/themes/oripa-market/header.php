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
<link rel="icon" href="<?php echo esc_url( ORIPA_THEME_URI . '/assets/img/favicon.ico?ver=' . ORIPA_THEME_VERSION ); ?>" sizes="any">
<link rel="icon" type="image/png" sizes="32x32" href="<?php echo esc_url( ORIPA_THEME_URI . '/assets/img/favicon-32x32.png?ver=' . ORIPA_THEME_VERSION ); ?>">
<link rel="icon" type="image/png" sizes="16x16" href="<?php echo esc_url( ORIPA_THEME_URI . '/assets/img/favicon-16x16.png?ver=' . ORIPA_THEME_VERSION ); ?>">
<link rel="apple-touch-icon" href="<?php echo esc_url( ORIPA_THEME_URI . '/assets/img/apple-touch-icon.png?ver=' . ORIPA_THEME_VERSION ); ?>">
<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','GTM-K3BZD9D4');</script>
<!-- End Google Tag Manager -->
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?> data-page="<?php echo esc_attr( oripa_page_key() ); ?>">
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-K3BZD9D4"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->
<?php wp_body_open(); ?>

<header id="site-header" class="site-header">
	<div class="wrap">
		<a class="logo" href="<?php echo esc_url( home_url( '/' ) ); ?>">
			<img class="mark mascot-mark" src="<?php echo esc_url( ORIPA_THEME_URI . '/assets/img/mascot-guide.webp?ver=' . ORIPA_THEME_VERSION ); ?>" alt="<?php bloginfo( 'name' ); ?>" width="44" height="36" decoding="async">
			<span class="logo-text"><?php bloginfo( 'name' ); ?><small>トレカ抽選・予約情報まとめ</small></span>
		</a>
		<nav class="nav-links">
			<?php foreach ( oripa_header_nav_items() as $item ) : ?>
				<a href="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html( $item['label'] ); ?></a>
			<?php endforeach; ?>
			<span class="live-pill"><span class="dot"></span>自動監視中</span>
		</nav>
	</div>
</header>

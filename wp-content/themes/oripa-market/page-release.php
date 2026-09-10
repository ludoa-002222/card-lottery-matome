<?php
/**
 * 新商品カレンダー（/release/）。各TCG公式サイトから取得した発売予定を月ごとに並べる。
 *
 * 【なぜ作ったか・2026-09-10】
 * これまで扱えていたのはポケカの抽選情報だけで、
 * ワンピース・遊戯王・デュエマは名前が出るだけで中身が無かった。
 * 発売日は公式サイトにしか無い一次情報で、抽選を待つ人にとっても
 * 「いつ出るか」は最初に知りたい情報なので、ここを埋める。
 *
 * 掲載するのは商品名・発売日・公式ページへのリンクという事実だけ。
 * 説明文や画像は他社の著作物なので持ってこない。
 *
 * @package oripa-market
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$releases = oripa_rest_releases();

// 月ごとにまとめる。発売日順に並んでいる前提。
$by_month = array();
foreach ( $releases as $r ) {
	$month                = substr( $r['releaseDate'], 0, 7 );
	$by_month[ $month ][] = $r;
}

$genre_colors = array(
	'ポケカ'           => '#f0b429',
	'ワンピースカード' => '#d63864',
	'遊戯王'           => '#7c3aed',
	'デュエマ'         => '#2f6fed',
	'ドラゴンボール'   => '#e07a1f',
);
?>
<main class="wrap">
	<?php echo oripa_breadcrumb( array( array( 'label' => '新商品カレンダー' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

	<section class="column-hero">
		<span class="kicker">OFFICIAL RELEASE CALENDAR</span>
		<h1>トレカ新商品の発売日カレンダー</h1>
		<p>ポケモンカード・ワンピースカード・遊戯王・デュエル・マスターズの発売予定を、各公式サイトの発表からまとめています。</p>
		<p class="release-lead-note">掲載しているのは<strong>通常販売の新商品</strong>です。抽選販売ではありません。</p>
	</section>

	<?php if ( empty( $by_month ) ) : ?>
		<p class="footer-note" style="margin-top:20px;">現在、発売予定の情報がありません。</p>
	<?php else : ?>
		<?php foreach ( $by_month as $month => $items ) : ?>
			<section class="section release-month">
				<div class="section-heading">
					<span class="bar"></span>
					<h2><?php echo esc_html( str_replace( '-', '年', $month ) . '月' ); ?></h2>
					<span class="release-count"><?php echo count( $items ); ?>件</span>
				</div>
				<ul class="release-list">
					<?php foreach ( $items as $r ) : ?>
						<li class="release-item">
							<span class="release-date"><?php echo esc_html( substr( $r['releaseDate'], 8, 2 ) ); ?><small>日</small></span>
							<?php if ( ! empty( $r['image'] ) ) : ?>
								<img class="release-thumb" src="<?php echo esc_url( $r['image'] ); ?>" alt="" loading="lazy" decoding="async" width="64" height="64">
							<?php endif; ?>
							<span class="release-body">
								<span class="release-genre" style="--genre-color:<?php echo esc_attr( $genre_colors[ $r['genre'] ] ?? '#8a93a6' ); ?>"><?php echo esc_html( $r['genre'] ); ?></span>
								<span class="release-title"><?php echo esc_html( $r['title'] ); ?></span>
								<?php if ( $r['productType'] ) : ?>
									<span class="release-type"><?php echo esc_html( $r['productType'] ); ?></span>
								<?php endif; ?>
							</span>
							<?php if ( $r['officialUrl'] ) : ?>
								<a class="release-link" href="<?php echo esc_url( $r['officialUrl'] ); ?>" target="_blank" rel="noopener">公式</a>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			</section>
		<?php endforeach; ?>
	<?php endif; ?>

	<p class="footer-note" style="margin-top:24px;">
		発売日は各カードゲームの公式サイトの発表をもとに掲載しています。<br>変更されることがあるため、購入前に必ず公式ページでご確認ください。
	</p>
	<p class="footer-note" style="margin-top:8px;">
		ここに載っているのは通常販売の新商品です。抽選販売の情報は<a href="<?php echo esc_url( get_post_type_archive_link( 'lottery' ) ); ?>">抽選・予約情報</a>にまとめています。
	</p>

	<section class="section">
		<div class="promo-grid promo-grid-3">
			<a class="promo-card" href="<?php echo esc_url( home_url( '/calendar/' ) ); ?>">
				<span class="icon">📅</span><div><h3>抽選締切カレンダー</h3><p>締切日から逆算して応募する</p></div><span class="go">見る</span>
			</a>
			<a class="promo-card" href="<?php echo esc_url( get_post_type_archive_link( 'lottery' ) ); ?>">
				<span class="icon">🎯</span><div><h3>抽選・予約情報</h3><p>いま応募できるものを探す</p></div><span class="go">見る</span>
			</a>
			<a class="promo-card" href="<?php echo esc_url( home_url( '/guide/' ) ); ?>">
				<span class="icon">📘</span><div><h3>攻略ガイド</h3><p>定価で手に入れるための記事</p></div><span class="go">見る</span>
			</a>
		</div>
	</section>
</main>
<?php
get_footer();

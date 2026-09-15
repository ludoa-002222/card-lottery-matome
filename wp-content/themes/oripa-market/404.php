<?php
/**
 * 404ページ。
 *
 * これまで存在せず、index.php（一覧テンプレート）への汎用フォールバックで
 * 「コンテンツが見つかりませんでした。」の一文だけを表示していた
 * （2026-09-15、リンク切れ調査時に発覚）。
 *
 * HTTPステータスは WP コア（WP::send_headers()）が is_404() を見て
 * テンプレート読み込み前に自動で404を返すため、ここで status_header() を
 * 呼ぶ必要はない。
 *
 * @package oripa-market
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$categories = get_terms(
	array(
		'taxonomy'   => 'card_category',
		'hide_empty' => false,
	)
);
?>
<main class="wrap legal">
	<?php echo oripa_breadcrumb( array( array( 'label' => 'ページが見つかりませんでした' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<h1>ページが見つかりませんでした</h1>
	<p>お探しのページは削除されたか、URLが変更・入力間違いになっている可能性があります。</p>

	<div class="link-grid" style="display:flex;flex-wrap:wrap;gap:10px;margin:22px 0 30px;">
		<a class="btn primary" href="<?php echo esc_url( home_url( '/' ) ); ?>">トップページへ戻る</a>
		<a class="btn ghost" href="<?php echo esc_url( home_url( '/calendar/' ) ); ?>">抽選締切カレンダー</a>
		<a class="btn ghost" href="<?php echo esc_url( get_post_type_archive_link( 'shop' ) ); ?>">店舗一覧</a>
		<a class="btn ghost" href="<?php echo esc_url( home_url( '/guide/' ) ); ?>">攻略ガイド</a>
	</div>

	<?php if ( $categories && ! is_wp_error( $categories ) ) : ?>
		<h2>カードの種類から探す</h2>
		<div class="link-grid" style="display:flex;flex-wrap:wrap;gap:10px;margin:14px 0 30px;">
			<?php foreach ( $categories as $term ) : ?>
				<a class="btn ghost" href="<?php echo esc_url( get_term_link( $term ) ); ?>"><?php echo esc_html( oripa_category_full_name( $term ) ); ?></a>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<p class="footer-note">お探しの抽選情報が見当たらない場合や、リンク切れにお気づきの場合は<a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">お問い合わせフォーム</a>よりご連絡ください。</p>
</main>
<?php
get_footer();

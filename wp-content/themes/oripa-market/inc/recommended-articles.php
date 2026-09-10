<?php
/**
 * おすすめ記事（外部サイト・トレカの地図）。
 *
 * 【なぜ外部リンクを置くか・2026-09-11】
 * 当サイトは「いつ・どこで・どう応募するか」を扱っている。
 * 一方で「そのパックの当たりカードは何か」「いまの環境で強いデッキは何か」は
 * 当サイトが持っていない情報で、抽選に応募する人が同時に知りたいことでもある。
 * 自前で書くには買取相場の継続調査が要るため、まとめている先へ案内する。
 *
 * 【表示の考え方】
 * - 外部サイトであることを明示する（クリックして初めて気づく状態にしない）
 * - 抽選一覧・応募ボタンには一切混ぜない。記事の周辺にだけ置く
 * - パック別の記事は、そのパックの抽選を見ている人にだけ出す（後述の box スラッグ）
 *
 * 【リンクの種別について】
 * 提携（アフィリエイト）かどうかは確認が取れていないため、
 * `sponsored` は付けていない。提携である場合は rel に sponsored を足し、
 * PR表記を出すこと（コラムSEO設計書「アフィリエイトの置き方」に従う）。
 *
 * @package oripa-market
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * おすすめ記事の一覧。
 *
 * pack … 対応する card_box の**名前**。抽選ページで「その商品の記事」を先頭に出すのに使う。
 *        スラッグではなく名前で照合する理由は、日本語のパック名がURLエンコードされた
 *        スラッグ（%e3%82%a2…）になっており、コードに書くと読めなくなるため。
 *        まだ登録されていないパックの記事もあるが、登録されれば自動で効く。
 *        空なら、どのページでも出してよい汎用記事。
 */
function oripa_recommended_articles() {
	return array(
		array(
			'title' => 'ポケカのオリパおすすめ優良店',
			'desc'  => '編集部が実際に試して比較したオリパの選び方',
			'url'   => 'https://torecamap.co.jp/column/oripa-pokemon/',
			'pack'  => '',
			'tag'   => 'オリパ',
		),
		array(
			'title' => 'ポケカ最新環境Tier表＆デッキ解説',
			'desc'  => '大会結果と使用率から見る、いま強いデッキ',
			'url'   => 'https://torecamap.co.jp/column/pokemon-environment/',
			'pack'  => '',
			'tag'   => '環境',
		),
		array(
			'title' => '30thセレブレーションの当たりカード',
			'desc'  => '収録カードの当たりランキングと買取相場',
			'url'   => 'https://torecamap.co.jp/column/celebration/',
			'pack'  => '30th CELEBRATION BOX',
			'tag'   => '当たりカード',
		),
		array(
			'title' => '『ストームエメラルダ』の当たりカード',
			'desc'  => 'レアリティ別の封入率と買取相場',
			'url'   => 'https://torecamap.co.jp/column/m3/',
			'pack'  => 'ストームエメラルダ',
			'tag'   => '当たりカード',
		),
		array(
			'title' => '『アビスアイ』の当たりカード',
			'desc'  => 'レアリティ別の封入率と買取相場',
			'url'   => 'https://torecamap.co.jp/column/m3a/',
			'pack'  => 'アビスアイ',
			'tag'   => '当たりカード',
		),
		array(
			'title' => '『MEGAドリームex』の当たりカード',
			'desc'  => 'レアリティ別の封入率と買取相場',
			'url'   => 'https://torecamap.co.jp/column/m2a/',
			'pack'  => 'メガドリームex',
			'tag'   => '当たりカード',
		),
		array(
			'title' => '『インフェルノX』の当たりカード',
			'desc'  => 'レアリティ別の封入率と買取相場',
			'url'   => 'https://torecamap.co.jp/column/infernox/',
			'pack'  => 'インフェルノX',
			'tag'   => '当たりカード',
		),
		array(
			'title' => '『メガシンフォニア』の当たりカード',
			'desc'  => 'レアリティ別の封入率と買取相場',
			'url'   => 'https://torecamap.co.jp/column/m1s/',
			'pack'  => 'メガシンフォニア',
			'tag'   => '当たりカード',
		),
		array(
			'title' => '『メガブレイブ』の当たりカード',
			'desc'  => 'レアリティ別の封入率と買取相場',
			'url'   => 'https://torecamap.co.jp/column/m1l/',
			'pack'  => 'メガブレイブ',
			'tag'   => '当たりカード',
		),
		array(
			'title' => '『テラスタルフェスex』の当たりカード',
			'desc'  => 'レアリティ別の封入率と買取相場',
			'url'   => 'https://torecamap.co.jp/column/sv8a/',
			'pack'  => 'テラスタルフェスex',
			'tag'   => '当たりカード',
		),
		array(
			'title' => '『シャイニートレジャーex』の当たりカード',
			'desc'  => 'レアリティ別の封入率と買取相場',
			'url'   => 'https://torecamap.co.jp/column/sv4a/',
			'pack'  => 'シャイニートレジャーex',
			'tag'   => '当たりカード',
		),
		array(
			'title' => '『ポケモンカード151』の当たりカード',
			'desc'  => 'レアリティ別の封入率と買取相場',
			'url'   => 'https://torecamap.co.jp/column/pokemoncard151/',
			'pack'  => 'ポケモンカード151',
			'tag'   => '当たりカード',
		),
	);
}

/**
 * おすすめ記事のセクションを描く。
 *
 * @param int    $limit    出す件数。0 で全件。
 * @param string $pack_name 指定すると、そのパックの記事を先頭に出す（card_box の名前）。
 * @param string $heading  見出し。
 */
function oripa_recommended_articles_html( $limit = 6, $pack_name = '', $heading = 'あわせて読みたい' ) {
	$items = oripa_recommended_articles();
	if ( ! $items ) {
		return '';
	}

	if ( $pack_name ) {
		// そのパックの記事を先頭へ。読んでいる人が今いちばん知りたい記事のため。
		usort(
			$items,
			function ( $a, $b ) use ( $pack_name ) {
				return ( $b['pack'] === $pack_name ? 1 : 0 ) - ( $a['pack'] === $pack_name ? 1 : 0 );
			}
		);
	}
	if ( $limit > 0 ) {
		$items = array_slice( $items, 0, $limit );
	}

	ob_start();
	?>
	<section class="section reco-section">
		<div class="section-heading">
			<span class="bar"></span>
			<h2><?php echo esc_html( $heading ); ?></h2>
			<span class="reco-source">提供：トレカの地図</span>
		</div>
		<div class="reco-grid">
			<?php foreach ( $items as $item ) : ?>
				<a class="reco-card" href="<?php echo esc_url( $item['url'] ); ?>" target="_blank" rel="noopener">
					<span class="reco-tag"><?php echo esc_html( $item['tag'] ); ?></span>
					<span class="reco-title"><?php echo esc_html( $item['title'] ); ?></span>
					<span class="reco-desc"><?php echo esc_html( $item['desc'] ); ?></span>
					<span class="reco-go">読む<span class="reco-ext" aria-hidden="true">↗</span></span>
				</a>
			<?php endforeach; ?>
		</div>
		<p class="footer-note reco-note">外部サイト「トレカの地図」の記事です。新しいタブで開きます。</p>
	</section>
	<?php
	return ob_get_clean();
}

<?php
/**
 * 情報の正確性について。静的プロトタイプ pages/trust.html を移植。
 * 統計値はサーバー側で算出。
 *
 * @package oripa-market
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$stats = oripa_trust_stats();
?>
<main class="wrap legal">
	<?php echo oripa_breadcrumb( array( array( 'label' => '情報の正確性について' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<h1>情報の正確性について</h1>
	<p>オリパマーケットは「速報性」より「正確性」を優先します。掲載する抽選・予約情報は、以下のプロセスを経てから公開しています。</p>

	<div class="trust-stats" style="margin:20px 0;background:var(--header);border-radius:var(--radius-lg);padding:6px;">
		<div class="stat"><div class="stat-num" style="color:#fff;"><?php echo (int) $stats['lotteries']; ?><span class="unit">件</span></div><div class="stat-label">検証済み掲載件数</div></div>
		<div class="stat"><div class="stat-num" style="color:#fff;"><?php echo (int) $stats['verifiedShops']; ?><span class="unit">店舗</span></div><div class="stat-label">確認済み店舗数</div></div>
		<div class="stat"><div class="stat-num" style="color:#fff;"><?php echo (int) $stats['totalShops']; ?><span class="unit">店舗</span></div><div class="stat-label">監視対象店舗数</div></div>
		<div class="stat"><div class="stat-num" style="color:#fff;">0<span class="unit">件</span></div><div class="stat-label">未確認情報の掲載</div></div>
	</div>

	<div class="process-timeline">
		<div class="process-step">
			<div class="num">1</div>
			<div><h3>一次収集（AI）</h3><p>各店舗・公式サイト・SNSの発表をAIが定期的に巡回し、抽選・予約情報の候補を収集します。</p></div>
		</div>
		<div class="process-step">
			<div class="num">2</div>
			<div><h3>公式情報との突合確認</h3><p>収集した情報を、店舗の公式ページ・公式SNSの一次情報と突き合わせて確認します。確認が取れないものは掲載しません。</p></div>
		</div>
		<div class="process-step">
			<div class="num">3</div>
			<div><h3>人による最終チェック</h3><p>応募条件（会員登録・本人確認の要否など）や締切日時の表記に誤りがないか、人の目で最終確認してから掲載します。</p></div>
		</div>
		<div class="process-step">
			<div class="num">4</div>
			<div><h3>掲載後も定期的に再確認</h3><p>掲載後も一定間隔で情報を再確認し、締切変更・受付終了・条件変更を検知した場合は速やかに反映します。カード表示の「◯分前に確認」は、この再確認のタイミングを示しています。</p></div>
		</div>
	</div>

	<h2>掲載しない情報</h2>
	<ul>
		<li>公式情報との突合が取れなかった未確認情報</li>
		<li>出典が不明確な非公式の噂・転載情報</li>
		<li>実施主体が確認できない抽選</li>
	</ul>

	<h2>誤り・変更を見つけたときは</h2>
	<p>掲載情報に誤りや変更を見つけた場合は、<a href="<?php echo esc_url( home_url( '/faq/' ) ); ?>">よくある質問</a>ページのお問い合わせ導線よりご連絡ください。確認のうえ速やかに修正します。</p>

	<p class="footer-note">当サイトは抽選の実施主体ではありません。最終的な応募条件は必ず各店舗の公式ページでご確認ください。</p>
</main>
<?php
get_footer();

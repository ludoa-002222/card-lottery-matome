<?php
/**
 * 抽選締切カレンダー。静的プロトタイプ pages/calendar.html を移植。
 * カレンダー描画・日別ドリルダウンは app.js(initCalendar)。
 *
 * @package oripa-market
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<main class="wrap">
	<?php echo oripa_breadcrumb( array( array( 'label' => '抽選締切カレンダー' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<div class="page-title"><h1>抽選締切カレンダー</h1></div>
	<p class="footer-note">日付をタップすると、その日に締め切られる抽選をチェックできます。<br>選んだ日はカレンダー上で色が変わります。</p>

	<section class="section" style="padding-top:0;">
		<div class="cal-nav">
			<button class="btn ghost" id="prev">＜ 前月</button>
			<h2 id="cal-title"></h2>
			<button class="btn ghost" id="next">次月 ＞</button>
		</div>
		<div class="cal-grid" id="cal-dow"></div>
		<div class="cal-grid" id="cal-grid"></div>
	</section>

	<section class="section" id="day-section" hidden>
		<div class="section-heading">
			<span class="bar"></span>
			<h2 id="day-title"></h2>
			<span class="day-count" id="day-count"></span>
			<button type="button" class="btn ghost day-close" id="day-close">閉じる</button>
		</div>

		<!-- 締切が集中する日は数十件になるため、絞り込めるようにする。
		     件数が3件以下の日では邪魔になるので JS 側で隠す。 -->
		<div class="day-tools" id="day-tools" hidden>
			<input type="search" id="day-q" class="day-q" placeholder="商品名・店舗名でしぼり込む" autocomplete="off">
			<select id="day-cat" class="day-cat"></select>
		</div>

		<div class="lottery-list" id="day-list"></div>
		<button type="button" class="btn ghost day-more" id="day-more" hidden></button>
	</section>

	<section class="section">
		<div class="section-heading"><span class="bar"></span><h2>よくある質問</h2></div>
		<div class="accordion-item"><details><summary>カレンダーはいつ更新されますか？</summary>
			<p>各店舗の公式発表を確認次第、随時更新しています（AIによる巡回チェックも活用しています）。</p></details></div>
		<div class="accordion-item"><details><summary>締切当日でも応募できますか？</summary>
			<p>締切時刻より前であれば応募可能です。時間ギリギリの応募はシステム混雑にご注意ください。</p></details></div>
	</section>
</main>
<?php
get_footer();

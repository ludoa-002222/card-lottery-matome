<?php
/**
 * フッター。静的プロトタイプの renderFooter() 相当。
 *
 * @package oripa-market
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<footer id="site-footer" class="site-footer">
	<div class="wrap">
		<div class="footer-trust-row">
			<span>🛡️ AI一次チェック＋人による二重確認</span>
			<span>🔒 SSL暗号化通信</span>
			<span>📄 個人情報保護方針を策定</span>
			<span>🕒 平均更新間隔 30分以内</span>
		</div>
		<div class="footer-links">
			<?php foreach ( oripa_footer_link_items() as $item ) : ?>
				<a href="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html( $item['label'] ); ?></a>
			<?php endforeach; ?>
		</div>
		<p class="footer-note">本サイトは情報まとめサイトであり、各抽選の実施主体ではありません。最新の応募条件は必ず各店舗・公式サイトでご確認ください。掲載データはAI一次チェック＋人による確認を経て随時更新しています。</p>
		<p class="footer-note">&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?></p>
	</div>
</footer>
<?php wp_footer(); ?>
</body>
</html>

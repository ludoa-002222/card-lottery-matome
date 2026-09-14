<?php
/**
 * お問い合わせフォーム。
 *
 * 会員登録機能は廃止済みのため wp_mail() で管理者宛メール送信のみ行う
 * シンプルな実装（DB保存やプラグインは使わない）。
 * スパム対策はハニーポット＋nonceのみ（reCAPTCHA等は未導入）。
 *
 * 管理者宛の送信に成功したら、入力されたメールアドレスへ受付確認メールも送る
 * （2026-09-15追加）。確認メールが失敗しても管理者へは届いているので、
 * $success の判定には影響させない（お問い合わせ自体は受け付けているため）。
 *
 * @package oripa-market
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$errors             = array();
$success            = false;
$confirmation_sent  = false;
$values             = array(
	'name'    => '',
	'email'   => '',
	'subject' => 'correction',
	'message' => '',
);

// oripa-market.com のドメインで統一する（SPF/DKIMがこのドメインに設定済みのため）。
// WordPress既定の From（wordpress@ドメイン・表示名 "WordPress"）のままだと
// 受信側にそっけなく見えるので、サイト名を名乗る。
$from_header = 'From: オリパマーケット <noreply@' . wp_parse_url( home_url(), PHP_URL_HOST ) . '>';

if ( isset( $_POST['oripa_contact_submit'] ) ) {
	if ( ! isset( $_POST['oripa_contact_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['oripa_contact_nonce'] ) ), 'oripa_contact' ) ) {
		$errors[] = '確認に失敗しました。ページを再読み込みしてもう一度お試しください。';
	} elseif ( ! empty( $_POST['website'] ) ) {
		// ハニーポット。人間には見えない項目が埋まっていればbotとみなし、
		// 何もせず成功したふりをして送信ループを止めさせる。
		$success = true;
	} else {
		$values['name']    = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$values['email']   = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$values['subject'] = isset( $_POST['subject'] ) ? sanitize_key( wp_unslash( $_POST['subject'] ) ) : 'correction';
		$values['message'] = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';

		if ( '' === $values['name'] ) {
			$errors[] = 'お名前を入力してください。';
		}
		if ( '' === $values['email'] || ! is_email( $values['email'] ) ) {
			$errors[] = '正しいメールアドレスを入力してください。';
		}
		if ( '' === trim( $values['message'] ) ) {
			$errors[] = 'お問い合わせ内容を入力してください。';
		}

		if ( ! $errors ) {
			$subject_labels = array(
				'correction' => '掲載情報の誤り・修正依頼',
				'shop'       => '店舗・運営者様からのご連絡',
				'other'      => 'その他のお問い合わせ',
			);
			$subject_label = isset( $subject_labels[ $values['subject'] ] ) ? $subject_labels[ $values['subject'] ] : $subject_labels['other'];

			$sent = wp_mail(
				get_option( 'admin_email' ),
				'[オリパマーケット] お問い合わせ: ' . $subject_label,
				"オリパマーケットのお問い合わせフォームより送信されました。\n\n"
					. "種別: {$subject_label}\n"
					. "お名前: {$values['name']}\n"
					. "メールアドレス: {$values['email']}\n\n"
					. "お問い合わせ内容:\n{$values['message']}\n",
				array(
					$from_header,
					'Reply-To: ' . $values['name'] . ' <' . $values['email'] . '>',
				)
			);

			if ( $sent ) {
				$success = true;

				// 受付確認メール（ユーザー宛）。失敗しても管理者へは届いているので
				// エラー扱いにはせず、$confirmation_sent で完了メッセージの文言だけ変える。
				$confirmation_sent = wp_mail(
					$values['email'],
					'[オリパマーケット] お問い合わせを受け付けました',
					"{$values['name']} 様\n\n"
						. "この度はオリパマーケットへお問い合わせいただき、誠にありがとうございます。\n"
						. "以下の内容で受け付けました。内容を確認のうえ、担当より順次ご連絡いたします。\n\n"
						. "----------------------------------------\n"
						. "種別: {$subject_label}\n"
						. "お問い合わせ内容:\n{$values['message']}\n"
						. "----------------------------------------\n\n"
						. "※このメールは送信専用アドレスから配信しています。ご返信いただいても対応できません。\n"
						. "追加のご連絡は、お手数ですが改めてお問い合わせフォームよりお願いいたします。\n\n"
						. "オリパマーケット " . home_url( '/' ) . "\n",
					array( $from_header )
				);

				$values = array(
					'name'    => '',
					'email'   => '',
					'subject' => 'correction',
					'message' => '',
				);
			} else {
				$errors[] = '送信に失敗しました。お手数ですが時間をおいて再度お試しください。';
			}
		}
	}
}
?>
<main class="wrap legal">
	<?php echo oripa_breadcrumb( array( array( 'label' => 'お問い合わせ' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<h1>お問い合わせ</h1>
	<p>掲載情報の誤り・修正依頼や、その他のお問い合わせは以下のフォームからご連絡ください。内容を確認のうえ、担当より順次対応いたします。</p>

	<?php if ( $success ) : ?>
		<div class="form-notice is-success">
			お問い合わせを受け付けました。内容を確認のうえ、必要に応じてご連絡いたします。
			<?php if ( $confirmation_sent ) : ?>
				ご入力いただいたメールアドレスに受付確認メールをお送りしました。
			<?php endif; ?>
		</div>
	<?php else : ?>
		<?php if ( $errors ) : ?>
			<div class="form-notice is-error">
				<ul>
					<?php foreach ( $errors as $error ) : ?>
						<li><?php echo esc_html( $error ); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>

		<form class="form-box contact-box" method="post" action="<?php echo esc_url( get_permalink() ); ?>">
			<?php wp_nonce_field( 'oripa_contact', 'oripa_contact_nonce' ); ?>
			<div class="hp-field" aria-hidden="true">
				<label for="contact-website">ウェブサイト</label>
				<input type="text" id="contact-website" name="website" tabindex="-1" autocomplete="off">
			</div>

			<label for="contact-name">お名前<span class="req">必須</span></label>
			<input type="text" id="contact-name" name="name" value="<?php echo esc_attr( $values['name'] ); ?>" required>

			<label for="contact-email">メールアドレス<span class="req">必須</span></label>
			<input type="email" id="contact-email" name="email" value="<?php echo esc_attr( $values['email'] ); ?>" required>

			<label for="contact-subject">お問い合わせ種別</label>
			<select id="contact-subject" name="subject">
				<option value="correction" <?php selected( $values['subject'], 'correction' ); ?>>掲載情報の誤り・修正依頼</option>
				<option value="shop" <?php selected( $values['subject'], 'shop' ); ?>>店舗・運営者様からのご連絡</option>
				<option value="other" <?php selected( $values['subject'], 'other' ); ?>>その他のお問い合わせ</option>
			</select>

			<label for="contact-message">お問い合わせ内容<span class="req">必須</span></label>
			<textarea id="contact-message" name="message" rows="7" required><?php echo esc_textarea( $values['message'] ); ?></textarea>

			<button type="submit" name="oripa_contact_submit" value="1" class="btn primary">送信する</button>
		</form>
	<?php endif; ?>

	<p class="footer-note">当サイトは抽選の実施主体ではありません。抽選への応募・当落・返金に関するお問い合わせは、各店舗の公式ページへご連絡ください。</p>
</main>
<?php
get_footer();

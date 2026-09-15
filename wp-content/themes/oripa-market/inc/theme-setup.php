<?php
/**
 * テーマ基本セットアップ。
 *
 * @package oripa-market
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'after_setup_theme',
	function () {
		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'automatic-feed-links' );
		add_theme_support(
			'html5',
			array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' )
		);
		register_nav_menus(
			array(
				'primary' => 'ヘッダーナビ',
				'footer'  => 'フッターリンク',
			)
		);
		load_theme_textdomain( 'oripa-market', ORIPA_THEME_DIR . '/languages' );
	}
);

/**
 * 固定ページの slug から使うテンプレートを解決するためのヘルパ。
 * page-{slug}.php があれば WordPress が自動採用するので追加処理は不要だが、
 * サイト初期化時に必要な固定ページを作るための slug 一覧をここで定義しておく。
 */
function oripa_required_pages() {
	return array(
		'online'   => 'オンライン抽選まとめ',
		'store'    => '店頭抽選まとめ',
		'calendar' => '抽選締切カレンダー',
		'trust'    => '情報の正確性について',
		'about'    => '運営者について',
		'faq'      => 'よくある質問',
		'company'  => '会社概要',
		'terms'    => '利用規約',
		'privacy'  => 'プライバシーポリシー',
	);
}

/**
 * ページごとのmeta description。
 *
 * SEOプラグインを入れない方針のため、ここで簡易に出し分ける。
 */
function oripa_meta_description() {
	if ( is_front_page() ) {
		return 'ポケモンカード・ワンピースカード・遊戯王OCGなどトレーディングカードの抽選販売・予約情報をまとめて掲載。AIによる一次収集と人による確認を経た情報のみを掲載しています。';
	}
	if ( is_singular( 'column' ) ) {
		if ( has_excerpt() ) {
			return wp_strip_all_tags( get_the_excerpt() );
		}
		return wp_strip_all_tags( wp_trim_words( get_the_content(), 60, '…' ) );
	}
	if ( is_singular( 'shop' ) ) {
		return get_the_title() . 'のトレカ抽選・予約情報。応募条件や締切をオリパマーケットが確認のうえ掲載しています。';
	}
	if ( is_post_type_archive( 'lottery' ) || is_tax( 'card_category' ) || is_tax( 'card_box' ) ) {
		$label = ( is_tax( 'card_category' ) || is_tax( 'card_box' ) ) ? single_term_title( '', false ) . 'の' : '';
		return $label . 'トレカ抽選・予約情報を締切が近い順にまとめています。オンライン・店頭どちらの抽選も掲載。';
	}
	if ( is_post_type_archive( 'shop' ) ) {
		return 'トレカ抽選を実施している店舗の一覧です。チェーン・エリアから絞り込めます。';
	}
	if ( is_post_type_archive( 'column' ) || is_tax( 'column_category' ) ) {
		return 'トレカの抽選に当たりやすくなるコツ、安く買う・高く売る方法などの攻略コラムまとめ。';
	}
	if ( is_page() ) {
		$slug = get_post_field( 'post_name', get_queried_object_id() );
		$map  = array(
			'online'   => 'オンラインで応募できるトレカ抽選をまとめて掲載しています。',
			'store'    => '店頭で応募できるトレカ抽選をまとめて掲載しています。',
			'calendar' => 'トレカ抽選の締切日をカレンダー形式で確認できます。',
			'trust'    => 'オリパマーケットが掲載情報をどのように検証しているかをご紹介します。',
			'about'    => 'オリパマーケットの運営方針についてご紹介します。',
			'faq'      => 'オリパマーケットに関するよくある質問と回答をまとめています。',
			'company'  => '運営会社である株式会社LUDOAの会社概要です。',
			'terms'    => 'オリパマーケットの利用規約です。',
			'privacy'  => 'オリパマーケットのプライバシーポリシーです。',
			'contact'  => '掲載情報の誤り・修正依頼やその他のお問い合わせは、こちらのフォームより承っています。',
		);
		if ( isset( $map[ $slug ] ) ) {
			return $map[ $slug ];
		}
		if ( has_excerpt() ) {
			return wp_strip_all_tags( get_the_excerpt() );
		}
		return wp_strip_all_tags( wp_trim_words( get_the_content(), 60, '…' ) );
	}
	return '';
}

/**
 * canonical URL。WPコアは is_singular() の場合しか rel_canonical() を
 * 出さないため、トップページ・アーカイブ・タクソノミーの分だけ自前で補う
 * （2026-09-15）。is_singular() なページはコア側の出力と重複するため触らない。
 */
function oripa_canonical_url() {
	if ( is_front_page() ) {
		return home_url( '/' );
	}
	if ( is_post_type_archive() ) {
		return get_post_type_archive_link( get_query_var( 'post_type' ) );
	}
	if ( is_tax() || is_category() || is_tag() ) {
		$term = get_queried_object();
		return $term ? get_term_link( $term ) : '';
	}
	return '';
}

/**
 * meta description / canonical(トップ・アーカイブ分) / OGP / Twitter Card。
 * SEOプラグイン不使用のためテーマ側で最小限出す（2026-09-15）。
 */
add_action(
	'wp_head',
	function () {
		$description = oripa_meta_description();
		$canonical    = oripa_canonical_url();
		$image        = esc_url( ORIPA_THEME_URI . '/assets/img/ogp-default.jpg?ver=' . ORIPA_THEME_VERSION );
		$og_url       = $canonical ? $canonical : ( is_singular() ? get_permalink() : '' );

		if ( $description ) {
			echo '<meta name="description" content="' . esc_attr( $description ) . '">' . "\n";
		}
		if ( $canonical ) {
			echo '<link rel="canonical" href="' . esc_url( $canonical ) . '">' . "\n";
		}

		echo '<meta property="og:site_name" content="' . esc_attr( get_bloginfo( 'name' ) ) . '">' . "\n";
		echo '<meta property="og:type" content="website">' . "\n";
		echo '<meta property="og:locale" content="ja_JP">' . "\n";
		echo '<meta property="og:title" content="' . esc_attr( wp_get_document_title() ) . '">' . "\n";
		if ( $description ) {
			echo '<meta property="og:description" content="' . esc_attr( $description ) . '">' . "\n";
		}
		if ( $og_url ) {
			echo '<meta property="og:url" content="' . esc_url( $og_url ) . '">' . "\n";
		}
		echo '<meta property="og:image" content="' . $image . '">' . "\n";
		echo '<meta property="og:image:width" content="1200">' . "\n";
		echo '<meta property="og:image:height" content="630">' . "\n";

		echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
		echo '<meta name="twitter:title" content="' . esc_attr( wp_get_document_title() ) . '">' . "\n";
		if ( $description ) {
			echo '<meta name="twitter:description" content="' . esc_attr( $description ) . '">' . "\n";
		}
		echo '<meta name="twitter:image" content="' . $image . '">' . "\n";
	},
	1
);

// XML-RPCは使っていないので無効化（総当たり攻撃の踏み台にされる既知の経路）。
add_filter( 'xmlrpc_enabled', '__return_false' );

/**
 * 著者アーカイブを無効化し、/?author=1 等からのユーザー名特定を防ぐ
 * （2026-09-15、セキュリティ点検で発覚。/author/admin/ へリダイレクトされ
 * 管理者ユーザー名が外部から分かってしまっていた）。
 * サイトとして著者ページを使う予定は無いので、常にトップへ逃がす。
 *
 * 優先度0で登録すること。WPコアの redirect_canonical() が同じ
 * template_redirect フックの優先度10に既に登録されており、
 * ?author=1 を /author/admin/ へ正規化リダイレクトして exit してしまうため、
 * それより先に動かないとここへ到達できない（2026-09-15、実機で発覚）。
 */
add_action(
	'template_redirect',
	function () {
		if ( is_author() && ! is_user_logged_in() ) {
			wp_safe_redirect( home_url( '/' ), 301 );
			exit;
		}
	},
	0
);

/**
 * body_class に現在のテンプレート種別を足しておく（CSS フック用）。
 */
add_filter(
	'body_class',
	function ( $classes ) {
		if ( is_singular( 'lottery' ) ) {
			$classes[] = 'tpl-lottery-single';
		}
		if ( is_post_type_archive( 'lottery' ) || is_tax( 'card_category' ) || is_tax( 'card_box' ) ) {
			$classes[] = 'tpl-lottery-archive';
		}
		return $classes;
	}
);

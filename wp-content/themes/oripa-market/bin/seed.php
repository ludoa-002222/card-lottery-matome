<?php
/**
 * サンプルデータ投入スクリプト（WP-CLI）。
 *
 *   wp eval-file wp-content/themes/oripa-market/bin/seed.php
 *   （npm run wp:seed）
 *
 * bin/seed-data/*.json（静的プロトタイプ data/*.json のコピー）を読み込み、
 * CPT・タクソノミー・ACF 値・必要な固定ページを作成する。slug で冪等。
 *
 * @package oripa-market
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	fwrite( STDERR, "WP-CLI 経由で実行してください。\n" );
	exit( 1 );
}

/* ------------------------------------------------------------------ helpers */

function seed_json( $name ) {
	$path = __DIR__ . '/seed-data/' . $name . '.json';
	if ( ! file_exists( $path ) ) {
		WP_CLI::error( "not found: $path" );
	}
	return json_decode( file_get_contents( $path ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
}

function seed_term( $taxonomy, $name, $args = array() ) {
	$slug = isset( $args['slug'] ) ? $args['slug'] : sanitize_title( $name );
	$term = get_term_by( 'slug', $slug, $taxonomy );
	if ( $term ) {
		if ( ! empty( $args['parent'] ) && (int) $term->parent !== (int) $args['parent'] ) {
			wp_update_term( $term->term_id, $taxonomy, array( 'parent' => (int) $args['parent'] ) );
		}
		return (int) $term->term_id;
	}
	$res = wp_insert_term( $name, $taxonomy, $args );
	if ( is_wp_error( $res ) ) {
		WP_CLI::warning( "term '$name' ($taxonomy): " . $res->get_error_message() );
		return 0;
	}
	return (int) $res['term_id'];
}

function seed_find_post( $post_type, $slug ) {
	$q = get_posts(
		array(
			'post_type'      => $post_type,
			'name'           => $slug,
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
		)
	);
	return $q ? (int) $q[0] : 0;
}

function seed_to_wall( $iso, $fmt = 'Y-m-d H:i:s' ) {
	try {
		$dt = new DateTime( $iso );
		$dt->setTimezone( new DateTimeZone( 'Asia/Tokyo' ) );
		return $dt->format( $fmt );
	} catch ( Exception $e ) {
		return gmdate( $fmt );
	}
}

/* -------------------------------------------------------------- site options */

update_option( 'timezone_string', 'Asia/Tokyo' );
update_option( 'blogname', 'オリパマーケット' );
update_option( 'blogdescription', 'トレカ抽選・予約情報まとめ' );
update_option( 'users_can_register', 1 );
update_option( 'default_role', 'subscriber' );
update_option( 'permalink_structure', '/%postname%/' );

WP_CLI::log( 'options set.' );

/* --------------------------------------------------------------- taxonomies */

$categories = seed_json( 'categories' );
$boxes      = seed_json( 'boxes' );
$shops      = seed_json( 'shops' );
$lotteries  = seed_json( 'lotteries' );
$articles   = seed_json( 'articles' );

// card_category + 正式名称 term meta。
$cat_term_ids = array();
foreach ( $categories as $c ) {
	$tid = seed_term( 'card_category', $c['name'], array( 'slug' => $c['slug'] ) );
	if ( $tid ) {
		update_term_meta( $tid, 'oripa_full_name', $c['fullName'] );
		$cat_term_ids[ $c['slug'] ] = $tid;
	}
}

// card_box の親 term（カテゴリ相当）。card_category と同じ slug で別タクソノミー。
$box_parent_ids = array();
foreach ( $categories as $c ) {
	$box_parent_ids[ $c['slug'] ] = seed_term( 'card_box', $c['name'], array( 'slug' => $c['slug'] ) );
}

// card_box の子 term（実際のボックス）。
$box_term_ids = array();
foreach ( $boxes as $b ) {
	$parent = isset( $box_parent_ids[ $b['category'] ] ) ? $box_parent_ids[ $b['category'] ] : 0;
	$box_term_ids[ $b['slug'] ] = seed_term(
		'card_box',
		$b['name'],
		array(
			'slug'   => $b['slug'],
			'parent' => $parent,
		)
	);
}

// column_category（記事分類の名前）。
$colcat_ids = array();
foreach ( array_unique( array_column( $articles, 'category' ) ) as $name ) {
	$colcat_ids[ $name ] = seed_term( 'column_category', $name );
}

// shop_area。
foreach ( array_unique( array_column( $shops, 'area' ) ) as $area ) {
	seed_term( 'shop_area', $area );
}

WP_CLI::log( 'taxonomies seeded.' );

/* -------------------------------------------------------------------- shops */

$shop_id_map = array(); // "shop-01" => 投稿ID
foreach ( $shops as $s ) {
	$slug = $s['id'];
	$pid  = seed_find_post( 'shop', $slug );
	if ( ! $pid ) {
		$pid = wp_insert_post(
			array(
				'post_type'    => 'shop',
				'post_status'  => 'publish',
				'post_title'   => $s['name'],
				'post_name'    => $slug,
				'post_content' => $s['name'] . 'のトレカ抽選・予約情報を掲載しています。',
			)
		);
	}
	if ( is_wp_error( $pid ) || ! $pid ) {
		WP_CLI::warning( "shop {$s['name']} 作成失敗" );
		continue;
	}
	update_post_meta( $pid, 'is_online', ! empty( $s['isOnline'] ) ? 1 : 0 );
	update_post_meta( $pid, 'is_store', ! empty( $s['isStore'] ) ? 1 : 0 );
	wp_set_object_terms( $pid, $s['area'], 'shop_area' );
	$shop_id_map[ $s['id'] ] = (int) $pid;
}
WP_CLI::log( 'shops: ' . count( $shop_id_map ) );

/* ----------------------------------------------------------------- lotteries */

$made = 0;
foreach ( $lotteries as $l ) {
	$slug     = $l['id'];
	$shop_pid = isset( $shop_id_map[ $l['shopId'] ] ) ? $shop_id_map[ $l['shopId'] ] : 0;
	$shop_nm  = $shop_pid ? get_the_title( $shop_pid ) : '店舗';
	$box_nm   = isset( $box_term_ids[ $l['box'] ] ) ? get_term( $box_term_ids[ $l['box'] ] )->name : $l['box'];

	$pid = seed_find_post( 'lottery', $slug );
	if ( ! $pid ) {
		$pid = wp_insert_post(
			array(
				'post_type'    => 'lottery',
				'post_status'  => 'publish',
				'post_title'   => "{$shop_nm}｜{$box_nm} 抽選（第{$l['roundNo']}回）",
				'post_name'    => $slug,
				'post_content' => "{$shop_nm}が実施する「{$box_nm}」の抽選販売です。応募締切・応募条件は本ページ上部の情報をご確認のうえ、必ず店舗の公式ページで最新情報をご確認ください。",
			)
		);
	}
	if ( is_wp_error( $pid ) || ! $pid ) {
		WP_CLI::warning( "lottery {$slug} 作成失敗" );
		continue;
	}

	wp_set_object_terms( $pid, array( $l['category'] ), 'card_category' );
	$box_terms = array();
	if ( isset( $box_parent_ids[ $l['category'] ] ) ) {
		$box_terms[] = (int) $box_parent_ids[ $l['category'] ];
	}
	if ( isset( $box_term_ids[ $l['box'] ] ) ) {
		$box_terms[] = (int) $box_term_ids[ $l['box'] ];
	}
	wp_set_object_terms( $pid, $box_terms, 'card_box' );

	update_post_meta( $pid, 'method', $l['method'] );
	update_post_meta( $pid, 'shop', $shop_pid );
	update_post_meta( $pid, 'deadline', seed_to_wall( $l['deadline'] ) );
	update_post_meta( $pid, 'member_required', ! empty( $l['memberRequired'] ) ? 1 : 0 );
	update_post_meta( $pid, 'id_required', ! empty( $l['idRequired'] ) ? 1 : 0 );
	update_post_meta( $pid, 'round_no', (int) $l['roundNo'] );
	update_post_meta( $pid, 'round_total', (int) $l['roundTotal'] );
	update_post_meta( $pid, 'last_checked', seed_to_wall( $l['updatedAt'] ) );

	// ACF の内部管理キー（_field）も張っておくと GUI 表示が安定する。
	foreach ( array(
		'method'          => 'field_lottery_method',
		'shop'            => 'field_lottery_shop',
		'deadline'        => 'field_lottery_deadline',
		'member_required' => 'field_lottery_member_required',
		'id_required'     => 'field_lottery_id_required',
		'round_no'        => 'field_lottery_round_no',
		'round_total'     => 'field_lottery_round_total',
		'last_checked'    => 'field_lottery_last_checked',
	) as $k => $fk ) {
		update_post_meta( $pid, '_' . $k, $fk );
	}

	++$made;
}
WP_CLI::log( "lotteries: $made" );

/* ------------------------------------------------------------------- columns */

global $wpdb;
foreach ( $articles as $a ) {
	$pid = seed_find_post( 'column', $a['slug'] );
	$content = implode(
		"\n\n",
		array_map(
			function ( $p ) {
				return $p;
			},
			$a['body']
		)
	);
	$postarr = array(
		'post_type'    => 'column',
		'post_status'  => 'publish',
		'post_title'   => $a['title'],
		'post_name'    => $a['slug'],
		'post_excerpt' => $a['excerpt'],
		'post_content' => $content,
		'post_date'    => seed_to_wall( $a['updatedAt'] ),
	);
	if ( $pid ) {
		$postarr['ID'] = $pid;
		wp_update_post( $postarr );
	} else {
		$pid = wp_insert_post( $postarr );
	}
	if ( is_wp_error( $pid ) || ! $pid ) {
		WP_CLI::warning( "column {$a['slug']} 作成失敗" );
		continue;
	}
	update_post_meta( $pid, 'read_min', (int) $a['readMin'] );
	update_post_meta( $pid, '_read_min', 'field_column_read_min' );
	wp_set_object_terms( $pid, array( $a['category'] ), 'column_category' );

	// 更新日時を updatedAt に合わせる（一覧の並び・「更新 M/D」表示用）。
	$wall = seed_to_wall( $a['updatedAt'] );
	$gmt  = get_gmt_from_date( $wall );
	$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->posts,
		array(
			'post_modified'     => $wall,
			'post_modified_gmt' => $gmt,
		),
		array( 'ID' => $pid )
	);
	clean_post_cache( $pid );
}
WP_CLI::log( 'columns: ' . count( $articles ) );

/* -------------------------------------------------------------------- pages */

$legal_content = array(
	'about'   => "<div class=\"trust-badges\" style=\"margin:18px 0 26px;\">\n<div class=\"trust-badge\"><span class=\"ico\">🛡️</span><div><h3>二重チェック体制</h3><p>AIによる一次収集と、人による公式情報との突合確認を組み合わせています。</p></div></div>\n<div class=\"trust-badge\"><span class=\"ico\">🎯</span><div><h3>正確性を最優先</h3><p>速報性より正確性を重視し、確認が取れた情報のみ掲載します。</p></div></div>\n</div>\n<p>「オリパマーケット」は、ポケモンカード・ワンピースカード・遊戯王OCGなどトレーディングカードの抽選販売・予約情報をまとめる情報サイトです。詳しい検証プロセスは<a href=\"/trust/\">情報の正確性について</a>をご覧ください。</p>\n<h2>掲載方針</h2>\n<ul>\n<li>各店舗・公式サイトで発表された情報をもとに掲載しています</li>\n<li>受付期間・応募条件は随時変更される可能性があるため、応募前に必ず公式ページをご確認ください</li>\n<li>当サイトは抽選の実施主体ではなく、実施主体は掲載各店舗となります</li>\n<li>データ更新にはAIによる一次チェックと人による確認を組み合わせ、鮮度と正確性の両立に努めています</li>\n</ul>\n<h2>お問い合わせ</h2>\n<p>掲載情報の誤りや修正依頼は、<a href=\"/faq/\">よくある質問</a>ページもしくはお問い合わせフォームよりご連絡ください。</p>",
	'faq'     => "<div class=\"accordion-item\"><details><summary>掲載されている抽選情報は正確ですか？</summary>\n<p>各店舗・公式サイトの発表をもとに掲載していますが、内容は予告なく変更される場合があります。応募前に必ず公式ページをご確認ください。</p></details></div>\n<div class=\"accordion-item\"><details><summary>会員登録すると何ができますか？</summary>\n<p>抽選締切カレンダーの全件表示や、お気に入り店舗の新着通知などをご利用いただけます。</p></details></div>\n<div class=\"accordion-item\"><details><summary>抽選に外れた場合の返金はありますか？</summary>\n<p>当サイトは抽選の実施主体ではないため、返金対応は行っておりません。各店舗の規約をご確認ください。</p></details></div>\n<div class=\"accordion-item\"><details><summary>情報の誤りを見つけました。どこに連絡すればよいですか？</summary>\n<p><a href=\"/about/\">運営者について</a>ページよりお問い合わせください。確認のうえ速やかに修正いたします。</p></details></div>\n<div class=\"accordion-item\"><details><summary>新しい抽選情報はどれくらいの頻度で追加されますか？</summary>\n<p>毎日複数回、AIによる巡回チェックと人による確認を組み合わせて更新しています。各ページの「最終更新」表示をご確認ください。</p></details></div>",
	'company' => "<p class=\"footer-note\" style=\"margin-bottom:16px;\">※本ページはプレースホルダーです。正式な会社情報をご提供いただき次第、反映します。</p>\n<table class=\"shop-table\"><tbody>\n<tr><th style=\"width:160px;\">会社名</th><td>［会社名を記入］</td></tr>\n<tr><th>所在地</th><td>［所在地を記入］</td></tr>\n<tr><th>代表者</th><td>［代表者名を記入］</td></tr>\n<tr><th>設立</th><td>［設立年月を記入］</td></tr>\n<tr><th>事業内容</th><td>［事業内容を記入］</td></tr>\n<tr><th>お問い合わせ</th><td>［連絡先を記入］</td></tr>\n</tbody></table>",
	'terms'   => "<p>この利用規約（以下「本規約」）は、「オリパマーケット」（以下「当サイト」）の利用条件を定めるものです。</p>\n<h2>第1条（適用）</h2><p>本規約は、当サイトの利用に関わる一切の関係に適用されるものとします。</p>\n<h2>第2条（情報の正確性）</h2><p>当サイトは各店舗・公式サイトの発表内容をもとに情報を掲載していますが、内容の正確性・最新性を保証するものではありません。応募前に必ず各店舗の公式ページをご確認ください。</p>\n<h2>第3条（免責事項）</h2><p>当サイトの情報を利用したことにより生じたいかなる損害についても、当サイトは一切の責任を負いません。当サイトは抽選の実施主体ではありません。</p>\n<h2>第4条（禁止事項）</h2><p>当サイトのコンテンツを無断で複製・転載する行為、当サイトの運営を妨害する行為を禁止します。</p>\n<h2>第5条（規約の変更）</h2><p>当サイトは、必要と判断した場合には本規約を予告なく変更できるものとします。</p>",
	'privacy' => "<p>「オリパマーケット」（以下「当サイト」）は、ユーザーの個人情報を以下の方針に基づき取り扱います。</p>\n<h2>1. 取得する情報</h2><p>会員登録時にメールアドレス等をお預かりする場合があります。また、アクセス解析のためCookie等を利用する場合があります。</p>\n<h2>2. 利用目的</h2><p>取得した情報は、サービス提供・改善、お問い合わせ対応、新着情報のご案内のために利用します。</p>\n<h2>3. 第三者提供</h2><p>法令に基づく場合を除き、ご本人の同意なく第三者に個人情報を提供することはありません。</p>\n<h2>4. お問い合わせ</h2><p>個人情報の開示・訂正・削除のご請求は、<a href=\"/about/\">運営者について</a>ページの窓口までご連絡ください。</p>",
);

$pages = array(
	'online'   => 'オンライン抽選まとめ',
	'store'    => '店頭抽選まとめ',
	'calendar' => '抽選締切カレンダー',
	'trust'    => '情報の正確性について',
	'about'    => '運営者について',
	'faq'      => 'よくある質問',
	'company'  => '会社概要',
	'terms'    => '利用規約',
	'privacy'  => 'プライバシーポリシー',
	'mypage'   => 'マイページ',
	'register' => '無料会員登録',
);

foreach ( $pages as $slug => $title ) {
	$existing = get_page_by_path( $slug );
	$content  = isset( $legal_content[ $slug ] ) ? $legal_content[ $slug ] : '';
	if ( $existing ) {
		if ( $content && ! $existing->post_content ) {
			wp_update_post(
				array(
					'ID'           => $existing->ID,
					'post_content' => $content,
				)
			);
		}
		continue;
	}
	wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => $title,
			'post_name'    => $slug,
			'post_content' => $content,
		)
	);
}
WP_CLI::log( 'pages: ' . count( $pages ) );

/* ------------------------------------------------------------------- finish */

flush_rewrite_rules( false );
WP_CLI::success( 'seed 完了。/ を開いて確認してください。' );

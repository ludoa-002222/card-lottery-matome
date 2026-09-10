<?php
/**
 * テンプレート用ヘルパー（サーバー側）。
 *
 * 一覧カードや締切バッジの描画はフロント JS（common.js）に寄せる方針のため、
 * ここでは主にヘッダー/フッター/見出し・SEO・パンくずを組み立てる。
 *
 * @package oripa-market
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 現在ページのテンプレ識別子を返す（app.js が参照する data-page 値）。
 */
function oripa_page_key() {
	if ( is_front_page() ) {
		return 'home';
	}
	if ( is_singular( 'lottery' ) ) {
		return 'lottery-single';
	}
	if ( is_tax( 'card_category' ) ) {
		return 'category';
	}
	if ( is_tax( 'card_box' ) ) {
		return 'box';
	}
	if ( is_post_type_archive( 'lottery' ) ) {
		return 'lottery-archive';
	}
	if ( is_singular( 'column' ) ) {
		return 'article';
	}
	if ( is_post_type_archive( 'column' ) || is_tax( 'column_category' ) ) {
		return 'guide';
	}
	if ( is_post_type_archive( 'shop' ) ) {
		return 'shop';
	}
	if ( is_page() ) {
		$slug = get_post_field( 'post_name', get_queried_object_id() );
		return 'page-' . $slug;
	}
	return 'generic';
}

/**
 * パンくずリスト。$trail は [ ['label'=>..,'url'=>..], ... ]。
 */
function oripa_breadcrumb( $trail = array() ) {
	$items   = array_merge(
		array(
			array(
				'label' => 'ホーム',
				'url'   => home_url( '/' ),
			),
		),
		$trail
	);
	$last    = count( $items ) - 1;
	$out     = '<nav class="breadcrumb">';
	foreach ( $items as $i => $item ) {
		if ( $i > 0 ) {
			$out .= ' &gt; ';
		}
		if ( $i === $last || empty( $item['url'] ) ) {
			$out .= '<span>' . esc_html( $item['label'] ) . '</span>';
		} else {
			$out .= '<a href="' . esc_url( $item['url'] ) . '">' . esc_html( $item['label'] ) . '</a>';
		}
	}
	$out .= '</nav>';
	return $out;
}

/**
 * card_category term の正式名称。
 */
function oripa_category_full_name( $term ) {
	if ( ! $term instanceof WP_Term ) {
		return '';
	}
	$full = get_term_meta( $term->term_id, 'oripa_full_name', true );
	return $full ? $full : $term->name;
}

/**
 * ヘッダーナビ項目（renderHeader 相当）。
 */
function oripa_header_nav_items() {
	return array(
		array(
			'label' => '締切カレンダー',
			'url'   => home_url( '/calendar/' ),
		),
		array(
			'label' => '新商品カレンダー',
			'url'   => home_url( '/release/' ),
		),
		array(
			'label' => '攻略ガイド',
			'url'   => home_url( '/guide/' ),
		),
		array(
			'label' => '攻略コラム',
			'url'   => get_post_type_archive_link( 'column' ),
		),
		array(
			'label' => '店舗一覧',
			'url'   => get_post_type_archive_link( 'shop' ),
		),
		array(
			'label' => '情報の正確性について',
			'url'   => home_url( '/trust/' ),
		),
	);
}

/**
 * フッターリンク項目（renderFooter 相当）。
 */
function oripa_footer_link_items() {
	return array(
		array(
			'label' => '抽選締切カレンダー',
			'url'   => home_url( '/calendar/' ),
		),
		array(
			'label' => 'トレカ攻略コラム',
			'url'   => get_post_type_archive_link( 'column' ),
		),
		array(
			'label' => '情報の正確性について',
			'url'   => home_url( '/trust/' ),
		),
		array(
			'label' => '運営者について',
			'url'   => home_url( '/about/' ),
		),
		array(
			'label' => 'よくある質問',
			'url'   => home_url( '/faq/' ),
		),
		array(
			'label' => '全店舗一覧',
			'url'   => get_post_type_archive_link( 'shop' ),
		),
		array(
			'label' => '会社概要',
			'url'   => home_url( '/company/' ),
		),
		array(
			'label' => '利用規約',
			'url'   => home_url( '/terms/' ),
		),
		array(
			'label' => 'プライバシーポリシー',
			'url'   => home_url( '/privacy/' ),
		),
	);
}

/**
 * 記事サムネイル（common.js の articleThumbHtml の PHP 版）。
 *
 * マスコット・ジャンルアイコン・箱画像は使わない（記事の中身と関係がなく、
 * 一覧に並べると全部同じ絵に見えるため）。記事タイトルそのものを図版にする。
 *
 * @param string $category カテゴリ名（配色に使う）
 * @param string $cls      付与するclass
 * @param string $title    記事タイトル
 */
/**
 * カテゴリごとの図案（サムネイル右側）。
 *
 * JS版 assets/js/common.js の categoryMotif() と**同じ絵を返すこと**。
 * 一覧はJSが、記事ページはPHPが描くので、片方だけ直すと見た目がズレる。
 *
 * @param string $category カテゴリ名。
 * @param string $fg       前景色。
 * @return string SVGの断片。
 */
function oripa_category_motif( $category, $fg ) {
	$f = esc_attr( $fg );
	switch ( $category ) {
		case '応募方法':
			return '<rect x="112" y="34" width="30" height="42" rx="4" fill="' . $f . '" opacity=".30"/>'
				. '<rect x="123" y="28" width="30" height="42" rx="4" fill="' . $f . '" opacity=".55"/>'
				. '<rect x="134" y="22" width="30" height="42" rx="4" fill="' . $f . '"/>'
				. '<path d="M142 42 l5 6 l11 -12" stroke="#fff" stroke-width="3" fill="none" stroke-linecap="round" stroke-linejoin="round"/>';
		case '安く入手':
			return '<rect x="116" y="60" width="14" height="18" rx="2" fill="' . $f . '" opacity=".35"/>'
				. '<rect x="137" y="46" width="14" height="32" rx="2" fill="' . $f . '" opacity=".6"/>'
				. '<rect x="158" y="30" width="14" height="48" rx="2" fill="' . $f . '"/>';
		case '高く売る':
			return '<path d="M114 70 L131 54 L144 63 L168 34" stroke="' . $f . '" stroke-width="3.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/>'
				. '<circle cx="168" cy="34" r="4.5" fill="' . $f . '"/>';
		case '大会レポート':
			return '<rect x="116" y="56" width="16" height="22" rx="2" fill="' . $f . '" opacity=".4"/>'
				. '<rect x="138" y="42" width="16" height="36" rx="2" fill="' . $f . '"/>'
				. '<rect x="160" y="62" width="16" height="16" rx="2" fill="' . $f . '" opacity=".55"/>'
				. '<circle cx="146" cy="31" r="6.5" fill="' . $f . '"/>';
		case 'デッキ解説':
			return '<g transform="rotate(-14 144 52)"><rect x="112" y="32" width="26" height="38" rx="3" fill="' . $f . '" opacity=".35"/></g>'
				. '<rect x="131" y="28" width="26" height="42" rx="3" fill="' . $f . '" opacity=".6"/>'
				. '<g transform="rotate(14 152 52)"><rect x="150" y="32" width="26" height="38" rx="3" fill="' . $f . '"/></g>';
		case '初心者ガイド':
			return '<path d="M114 36 Q144 28 144 34 L144 72 Q144 66 114 74 Z" fill="' . $f . '" opacity=".45"/>'
				. '<path d="M174 36 Q144 28 144 34 L144 72 Q144 66 174 74 Z" fill="' . $f . '"/>';
		default:
			return '<circle cx="146" cy="52" r="26" fill="' . $f . '" opacity=".25"/>';
	}
}

function articleThumbHtml_php( $category, $cls = 'article-detail-hero', $title = '' ) {
	$tones = array(
		'安く入手'     => array( '#e8f0ff', '#2f6fed' ),
		'高く売る'     => array( '#e7f8ec', '#16a34a' ),
		'応募方法'     => array( '#f1e9fe', '#7c3aed' ),
		'デッキ解説'   => array( '#fff2e0', '#e07a1f' ),
		'大会レポート' => array( '#ffe9ef', '#d63864' ),
		'初心者ガイド' => array( '#e6f7f8', '#0e9aa7' ),
	);
	$pair = isset( $tones[ $category ] ) ? $tones[ $category ] : array( '#e8f0ff', '#2f6fed' );
	$tone = $pair[0];
	$fg   = $pair[1];

	// 【用途で縦横比を変える・2026-09-11】
	// 記事ページのヒーローはCSSで 21/9、一覧のカードは 16/10。
	// どちらも 200×120（1.67:1）のviewBoxを slice で表示していたため、
	// **ヒーローでは上下が切られてバッジと最終行が欠けていた**。
	// JS版 assets/js/common.js の articleThumbHtml() と同じ値を使うこと。
	$hero      = ( false !== strpos( $cls, 'hero' ) );
	$w         = $hero ? 210 : 200;
	$h         = $hero ? 90 : 120;
	// 行の右端 = 14 + per_line × font_size。これが図案の左端(142)を越えると重なる。
	// 2行では「…」で切れてタイトルが読めなかったため3行にした。
	// ヒーローは横に広いので行数を増やし、文字を小さくして省略を減らす。
	// 表示幅700pxに対しviewBoxは210なので3.3倍に拡大される。11pxでも実質36px相当。
	// 半角の実幅は全角の0.55前後。mb_strwidth は0.5として数えるので、
	// 折り返し幅を少し狭めに取って、半角の多い行がはみ出さないようにする。
	$per_line  = $hero ? 11.5 : 10;
	$max_lines = $hero ? 4 : 3;
	$font_size = $hero ? 10.5 : 12.5;

	// 【】は記号だけ外して中身は残す。トレカ記事では【アブソルガルーラ】のように
	// デッキ名そのものが【】で書かれており、中身を消すと何の記事か分からなくなる（2026-09-11）。
	$core = trim( preg_replace( '/\s+/u', ' ', preg_replace( '/[【】]/u', ' ', (string) $title ) ) );
	if ( '' === $core ) {
		$core = (string) $title;
	}
	// 【副題は落とす・2026-09-11】
	// 「｜」で主題と副題を分けているタイトルがある。両方入れると3行に収まらず、
	// **主題の途中で「…」になって何の記事か分からなくなる。**
	// ただし主題が短すぎるときは切らない。
	$head = trim( preg_split( '/[｜|]/u', $core )[0] );
	if ( mb_strlen( $head, 'UTF-8' ) >= 8 ) {
		$core = $head;
	} else {
		$core = trim( preg_replace( '/\s+/u', ' ', preg_replace( '/[｜|]/u', ' ', $core ) ) );
	}
	// 【末尾の年月はサムネイルから落とす・2026-09-11】
	// 「…上位9枚 2026年9月」のように末尾まで入れると1〜2文字あふれて「…」で切れ、
	// 肝心の商品名まで読めなくなる。年月は記事ページの見出しに出るので要らない。
	$core = trim( preg_replace( '/\s*20\d{2}年\s*\d{1,2}月\s*$/u', '', $core ) );

	// 【文字数ではなく幅で折る】
	// 全角と半角では幅が倍ちがう。文字数で折ると半角の多い行だけ短くなり、
	// あふれたぶんが「…」になっていた。全角を1、半角を0.55として数える。
	// JS版 assets/js/common.js の thumbTitleLines() と同じ計算にすること。
	// 【変数名に注意・2026-09-11】
	// ここを $w にしていたため、viewBoxの幅を入れた $w を上書きしてしまい、
	// **viewBox が "0 0 2 90" になってSVGが極端に拡大表示された。**
	// 行の幅は $line_w、viewBoxの幅は $w と分ける。
	$lines  = array();
	$buf    = '';
	$line_w = 0;
	$chars  = preg_split( '//u', $core, -1, PREG_SPLIT_NO_EMPTY );
	$rest   = 0;
	foreach ( $chars as $idx => $ch ) {
		// mb_strwidth は全角を2、半角を1で返す。半分にして「全角1文字ぶん」を単位にする。
		// 以前は正規表現で半角を判定していたが、/u 修飾子との組み合わせで
		// 期待どおりに動かず、折り返しが効いていなかった（2026-09-11）。
		$cw = mb_strwidth( $ch, 'UTF-8' ) / 2;
		if ( $line_w + $cw > $per_line && '' !== $buf ) {
			$lines[] = $buf;
			if ( count( $lines ) >= $max_lines ) {
				$rest = count( $chars ) - $idx;
				break;
			}
			$buf    = '';
			$line_w = 0;
		}
		$buf    .= $ch;
		$line_w += $cw;
	}
	if ( '' !== $buf && count( $lines ) < $max_lines ) {
		$lines[] = $buf;
		$rest    = 0;
	}
	// 入りきらなかったぶんがある場合だけ、最後の行を「…」で締める
	if ( $rest > 0 && $lines ) {
		$last           = count( $lines ) - 1;
		$lines[ $last ] = mb_substr( $lines[ $last ], 0, mb_strlen( $lines[ $last ], 'UTF-8' ) - 1, 'UTF-8' ) . '…';
	}
	// 最後の1〜2文字だけが次の行に落ちると読みにくいので、前の行にくっつける。
	// ただし**くっつけた結果が幅を超えるなら、そのまま別行にする。**
	// 無条件に結合していたため1行が13文字になり、図案に重なっていた（2026-09-11）。
	if ( count( $lines ) > 1 && mb_strlen( end( $lines ), 'UTF-8' ) <= 2 ) {
		$tail   = end( $lines );
		$prev   = $lines[ count( $lines ) - 2 ];
		$merged = $prev . $tail;
		if ( mb_strwidth( $merged, 'UTF-8' ) / 2 <= $per_line ) {
			array_pop( $lines );
			$lines[ count( $lines ) - 1 ] = $merged;
		}
	}

	// 【バッジの下から始める・2026-09-11】
	// 行数に応じて上へ伸ばすとバッジ(y=12〜28)に文字が重なっていた。
	// **上端を固定して下へ伸ばす。** 文字の上端 = start_y - font_size = 33.5 > 28。
	$start_y  = $hero ? 44 : 68 - ( count( $lines ) - 1 ) * 9;
	$line_gap = $hero ? 12 : $font_size + 4;
	$halo_x  = $hero ? 172 : 163;
	$halo_y  = $hero ? 44 : 58;
	// 図案は中心(144,52)付近に描かれているので、移動と縮小で位置を合わせる
	$motif_t = $hero ? 'translate(60,-4) scale(.72)' : 'translate(16,4) scale(.86)';

	$label   = $category ? $category : 'コラム';
	// バッジの幅は文字数から見積もる（日本語1文字ぶんを約7pxとして左右に余白）
	$badge_w = max( 38, mb_strlen( $label, 'UTF-8' ) * 7 + 14 );
	$gid     = 'art-g-php-' . wp_rand( 1000, 9999 );
	ob_start();
	?>
	<svg class="<?php echo esc_attr( $cls ); ?>" viewBox="0 0 <?php echo (int) $w; ?> <?php echo (int) $h; ?>" preserveAspectRatio="xMidYMid slice" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="<?php echo esc_attr( $title ); ?>">
		<defs><linearGradient id="<?php echo esc_attr( $gid ); ?>" x1="0" y1="0" x2="1" y2="1">
			<stop offset="0%" stop-color="<?php echo esc_attr( $tone ); ?>"/><stop offset="100%" stop-color="#ffffff"/>
		</linearGradient></defs>
		<rect width="<?php echo (int) $w; ?>" height="<?php echo (int) $h; ?>" fill="url(#<?php echo esc_attr( $gid ); ?>)"/>
		<circle cx="<?php echo (int) $halo_x; ?>" cy="<?php echo (int) $halo_y; ?>" r="40" fill="<?php echo esc_attr( $fg ); ?>" opacity=".10"/>
		<g transform="<?php echo esc_attr( $motif_t ); ?>"><?php echo oripa_category_motif( $category, $fg ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></g>
		<rect x="0" y="0" width="5" height="<?php echo (int) $h; ?>" fill="<?php echo esc_attr( $fg ); ?>"/>
		<rect x="14" y="12" width="<?php echo (int) $badge_w; ?>" height="16" rx="8" fill="<?php echo esc_attr( $fg ); ?>"/>
		<text x="<?php echo (int) ( 14 + $badge_w / 2 ); ?>" y="23.5" font-size="8.5" font-weight="700" fill="#ffffff" text-anchor="middle"><?php echo esc_html( $label ); ?></text>
		<text font-size="<?php echo esc_attr( $font_size ); ?>" font-weight="700" fill="#1c2536">
			<?php foreach ( $lines as $i => $line ) : ?>
				<tspan x="14" y="<?php echo esc_attr( $start_y + $i * $line_gap ); ?>"><?php echo esc_html( $line ); ?></tspan>
			<?php endforeach; ?>
		</text>
		<?php if ( ! $hero ) : ?>
		<text x="14" y="<?php echo (int) ( $h - 9 ); ?>" font-size="7" fill="#8a93a6">oripa-market.com</text>
		<?php endif; ?>
	</svg>
	<?php
	return ob_get_clean();
}

/**
 * 抽選件数などの簡易統計（トップ / trust ページの信頼ダッシュボード用）。
 */
function oripa_trust_stats() {
	$lottery_count = (int) wp_count_posts( 'lottery' )->publish;
	$shop_ids      = array();
	$q             = get_posts(
		array(
			'post_type'      => 'lottery',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		)
	);
	foreach ( $q as $id ) {
		$sid = (int) get_post_meta( $id, 'shop', true );
		if ( $sid ) {
			$shop_ids[ $sid ] = true;
		}
	}
	return array(
		'lotteries'     => $lottery_count,
		'verifiedShops' => count( $shop_ids ),
		'totalShops'    => (int) wp_count_posts( 'shop' )->publish,
	);
}

/**
 * ガイドハブ用：受付中の抽選が多い店舗を数える。
 *
 * 「受付中」は締切が現在時刻より後のもの。締切が入っていない抽選は数えない
 * （終わったのか続いているのか判断できないため、数字に混ぜない）。
 *
 * @param int $limit 返す件数
 * @return array{name:string,count:int,url:string}[]
 */
function oripa_guide_active_shops( $limit = 12 ) {
	$now   = current_time( 'mysql' );
	$posts = get_posts(
		array(
			'post_type'      => 'lottery',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		)
	);
	$counts = array();
	foreach ( $posts as $id ) {
		$deadline = get_post_meta( $id, 'deadline', true );
		if ( ! $deadline || $deadline <= $now ) {
			continue;
		}
		$shop_id = (int) get_post_meta( $id, 'shop', true );
		if ( ! $shop_id ) {
			continue;
		}
		$counts[ $shop_id ] = isset( $counts[ $shop_id ] ) ? $counts[ $shop_id ] + 1 : 1;
	}
	arsort( $counts );
	$out = array();
	foreach ( array_slice( $counts, 0, $limit, true ) as $shop_id => $count ) {
		$title = get_the_title( $shop_id );
		if ( ! $title ) {
			continue;
		}
		$out[] = array(
			'name'  => $title,
			'count' => $count,
			'url'   => get_permalink( $shop_id ),
		);
	}
	return $out;
}

/**
 * ガイドハブ用：受付中の抽選が多いパック（card_box）を数える。
 *
 * @param int $limit 返す件数
 * @return array{name:string,count:int,url:string}[]
 */
function oripa_guide_active_boxes( $limit = 12 ) {
	$now   = current_time( 'mysql' );
	$posts = get_posts(
		array(
			'post_type'      => 'lottery',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		)
	);
	$counts = array();
	foreach ( $posts as $id ) {
		$deadline = get_post_meta( $id, 'deadline', true );
		if ( ! $deadline || $deadline <= $now ) {
			continue;
		}
		$terms = wp_get_post_terms( $id, 'card_box' );
		if ( is_wp_error( $terms ) ) {
			continue;
		}
		foreach ( $terms as $t ) {
			// 親（ジャンル名の器）は数えない。実際のパックだけを対象にする。
			if ( 0 === (int) $t->parent ) {
				continue;
			}
			$counts[ $t->term_id ] = isset( $counts[ $t->term_id ] ) ? $counts[ $t->term_id ] + 1 : 1;
		}
	}
	arsort( $counts );
	$out = array();
	foreach ( array_slice( $counts, 0, $limit, true ) as $term_id => $count ) {
		$term = get_term( $term_id );
		if ( ! $term || is_wp_error( $term ) ) {
			continue;
		}
		$out[] = array(
			'name'  => $term->name,
			'count' => $count,
			'url'   => get_term_link( $term ),
		);
	}
	return $out;
}

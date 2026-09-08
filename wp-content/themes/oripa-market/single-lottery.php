<?php
/**
 * 抽選 詳細ページ（廃止・2026-09-09）。
 *
 * 店舗名・ボックス名・締切・応募条件などの情報はすべて一覧（ギャラリー）の
 * カード側に統合したため、個別詳細ページは廃止した。
 * 既存の共有リンク・検索エンジンのインデックス等でこのURLに来た場合は、
 * 一覧ページへ301リダイレクトする（404にはしない）。
 *
 * @package oripa-market
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

wp_safe_redirect( get_post_type_archive_link( 'lottery' ), 301 );
exit;

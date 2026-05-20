<?php
/**
 * テーマのブートストラップ。
 *
 * @package Theme
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
	exit;
}

/** @var string 翻訳用テキストドメイン（ディレクトリ名とは独立） */
define('THEME_GETTEXT_DOMAIN', 'site-theme');

/** @var string スタイル・スクリプトのクエリバスターなど */
define('THEME_VERSION', '1.0.0');

if (!defined('THEME_BRAND_DEFAULT')) {
	define('THEME_BRAND_DEFAULT', 'yuremono works');
}

require_once get_template_directory() . '/inc/fallback-menu.php';
require_once get_template_directory() . '/inc/acf-page-templates.php';

/**
 * ヘッダー／フッター等の表示名（投稿タイトルとは独立）。
 *
 * `THEME_BRAND_DEFAULT` またはフィルター `theme_brand` で上書き。
 */
function theme_brand(): string {
	return (string) apply_filters('theme_brand', THEME_BRAND_DEFAULT);
}

/**
 * body にレイアウトスコープ用クラスを付与（プラグイン CSS との競合を減らす）。
 *
 * @param array<int, string> $classes Existing body classes.
 * @return array<int, string>
 */
function theme_add_layout_body_class( array $classes ): array {
	$classes[] = 'root';
	return $classes;
}
add_filter('body_class', 'theme_add_layout_body_class');

/**
 * メインメニューのリンクにクラスを付与（スタイルは単一クラスセレクタに統一するため）。
 *
 * @param array<string, string> $atts 属性.
 * @param WP_Post               $item メニュー項目.
 * @param stdClass              $args wp_nav_menu の引数.
 * @param int                   $depth 階層.
 * @return array<string, string>
 */
function theme_primary_nav_link_attributes( array $atts, $item, $args, int $depth ): array {
	if ( isset( $args->theme_location ) && 'primary' === $args->theme_location ) {
		$atts['class'] = trim( ( $atts['class'] ?? '' ) . ' primary_nav_link' );
	}
	return $atts;
}
add_filter( 'nav_menu_link_attributes', 'theme_primary_nav_link_attributes', 10, 4 );

/**
 * メインメニューの li にクラスを付与。
 *
 * @param array<int, string> $classes クラス一覧.
 * @param WP_Post            $item メニュー項目.
 * @param stdClass           $args wp_nav_menu の引数.
 * @param int                $depth 階層.
 * @return array<int, string>
 */
function theme_primary_nav_menu_css_class( array $classes, $item, $args, int $depth ): array {
	if ( isset( $args->theme_location ) && 'primary' === $args->theme_location ) {
		$classes[] = 'primary_nav_item';
	}
	return $classes;
}
add_filter( 'nav_menu_css_class', 'theme_primary_nav_menu_css_class', 10, 4 );

/**
 * Theme supports and menus.
 */
function theme_setup(): void {
	load_theme_textdomain(THEME_GETTEXT_DOMAIN, get_template_directory() . '/languages');

	add_theme_support('title-tag');
	add_theme_support('post-thumbnails');
	add_theme_support(
		'html5',
		array(
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
			'style',
			'script',
		)
	);

	register_nav_menus(
		array(
			'primary' => __('メインメニュー', THEME_GETTEXT_DOMAIN),
		)
	);
}
add_action('after_setup_theme', 'theme_setup');

/**
 * Enqueue front-end stylesheet.
 */
function theme_enqueue_assets(): void {
	wp_enqueue_style(
		'theme-main-css',
		get_template_directory_uri() . '/assets/theme.css',
		array(),
		THEME_VERSION
	);

	if ( is_front_page() ) {
		wp_enqueue_style(
			'theme-portfolio-tailwind-compat',
			get_template_directory_uri() . '/assets/portfolio-tailwind-compat.css',
			array( 'theme-main-css' ),
			THEME_VERSION
		);
		wp_enqueue_script(
			'theme-portfolio-front',
			get_template_directory_uri() . '/assets/portfolio.js',
			array(),
			THEME_VERSION,
			true
		);
	}
}
add_action('wp_enqueue_scripts', 'theme_enqueue_assets');

/**
 * CPT: news（お知らせ）。
 */
function theme_register_news(): void {
	register_post_type(
		'news',
		array(
			'labels'              => array(
				'name'               => __('お知らせ', THEME_GETTEXT_DOMAIN),
				'singular_name'      => __('お知らせ', THEME_GETTEXT_DOMAIN),
				'add_new_item'       => __('お知らせを追加', THEME_GETTEXT_DOMAIN),
				'edit_item'          => __('お知らせを編集', THEME_GETTEXT_DOMAIN),
				'view_item'          => __('お知らせを表示', THEME_GETTEXT_DOMAIN),
				'search_items'       => __('お知らせを検索', THEME_GETTEXT_DOMAIN),
				'not_found'          => __('お知らせは見つかりませんでした', THEME_GETTEXT_DOMAIN),
				'not_found_in_trash' => __('ゴミ箱にお知らせはありません', THEME_GETTEXT_DOMAIN),
				'all_items'          => __('お知らせ一覧', THEME_GETTEXT_DOMAIN),
			),
			'public'              => true,
			'show_in_rest'        => true,
			'has_archive'         => true,
			'rewrite'             => array('slug' => 'news'),
			'menu_position'       => 6,
			'menu_icon'           => 'dashicons-megaphone',
			'supports'            => array('title', 'editor', 'thumbnail', 'excerpt'),
		)
	);
}
add_action('init', 'theme_register_news');

/**
 * Front page ID for shared footer/header strings.
 *
 * 「最新の投稿」をホームにしているときは page_on_front が DB に残っていても
 * 参照しない（古い固定ページの ACF が読まれてレイアウトが崩れるのを防ぐ）。
 */
function theme_front_page_id(): int {
	if ((string) get_option('show_on_front') !== 'page') {
		return 0;
	}
	$pid = (int) get_option('page_on_front');
	return $pid > 0 ? $pid : 0;
}

/**
 * フロント固定ページの ACF 用キー一覧（新名を先に、）。
 *
 * @return array<int, string>
 */
function theme_front_meta_field_keys( string $canonical_name ): array {
	static $legacy = array(
		'tagline'                => 'tagline',
		'hero_kicker'            => 'hero_kicker',
		'hero_title'             => 'hero_title',
		'hero_lead'              => 'hero_lead',
		'hero_image'             => 'hero_image',
		'services_heading'       => 'services_heading',
		'service_1_title'        => 'svc1_title',
		'service_1_body'         => 'svc1_body',
		'service_1_image'        => 'svc1_image',
		'service_2_title'        => 'svc2_title',
		'service_2_body'         => 'svc2_body',
		'service_2_image'        => 'svc2_image',
		'service_3_title'        => 'svc3_title',
		'service_3_body'         => 'svc3_body',
		'service_3_image'        => 'svc3_image',
		'highlight_show'         => 'highlight_show',
		'highlight_heading'      => 'highlight_heading',
		'highlight_body'         => 'highlight_body',
		'highlight_image'        => 'highlight_image',
		'footer_contact_show'    => 'footer_show',
		'footer_contact_heading' => 'footer_heading',
		'footer_contact_body'    => 'footer_body',
		'footer_phone'           => 'footer_phone',
		'footer_email'           => 'footer_email',
		'cta_strip_show'         => 'cta_secondary_show',
		'cta_strip_heading'      => 'cta_secondary_title',
		'cta_strip_body'         => 'cta_secondary_text',
		'cta_strip_button_label' => 'cta_secondary_btn_label',
		'cta_strip_button_url'   => 'cta_secondary_btn_url',
		'posts_count'            => 'news_preview_count',
	);

	$keys = array( $canonical_name );
	if ( isset( $legacy[ $canonical_name ] ) ) {
		$keys[] = $legacy[ $canonical_name ];
	}
	return $keys;
}

/**
 * ACF の値が「未設定」かどうか（次のエイリアスキーを試す判定用）。
 *
 * @param mixed $value Raw field value.
 */
function theme_acf_value_absent( $value ): bool {
	if ( null === $value || '' === $value || false === $value ) {
		return true;
	}
	if ( is_array( $value ) && array() === $value ) {
		return true;
	}
	return false;
}

/**
 * 固定フロントページの ACF フィールドを読む（新キー優先、）。
 *
 * @param mixed $default Fallback when empty / missing front page.
 *
 * @return mixed
 */
function theme_front_meta( string $field_name, $default = '' ) {
	$pid = theme_front_page_id();
	if ( ! $pid || ! function_exists( 'get_field' ) ) {
		return $default;
	}

	foreach ( theme_front_meta_field_keys( $field_name ) as $key ) {
		$value = get_field( $key, $pid );
		if ( ! theme_acf_value_absent( $value ) ) {
			return $value;
		}
	}

	return $default;
}

/**
 * 現在編集中の固定ページの ACF フィールドを読む（テンプレート専用グループ向け）。
 *
 * @param int    $post_id Post ID.
 * @param string $field_name Field name (meta key).
 * @param mixed  $default Fallback when empty or ACF inactive.
 *
 * @return mixed
 */
function theme_page_meta( int $post_id, string $field_name, $default = '' ) {
	if ( $post_id < 1 || ! function_exists( 'get_field' ) ) {
		return $default;
	}
	$value = get_field( $field_name, $post_id );
	if ( theme_acf_value_absent( $value ) ) {
		return $default;
	}
	return $value;
}

/**
 * Resolve image field or theme asset fallback URL.
 *
 * @param mixed  $field    ACF image field value.
 * @param string $fallback Relative path under theme directory (e.g. assets/hero.jpg).
 */
function theme_image_url( $field, string $fallback ): string {
	if (is_array($field) && !empty($field['url'])) {
		return esc_url((string) $field['url']);
	}
	if (is_string($field) && $field !== '') {
		return esc_url($field);
	}
	return esc_url(get_template_directory_uri() . '/' . ltrim($fallback, '/'));
}

/**
 * Local ACF field groups (no manual UI creation).
 *
 * `key` は既存 DB のフィールドグループ参照のため変更しない。
 */
function theme_register_acf(): void {
	if (!function_exists('acf_add_local_field_group')) {
		return;
	}

	$td = THEME_GETTEXT_DOMAIN;

	acf_add_local_field_group(
		array(
			'key'                   => 'group_pc_front_page',
			'title'                 => __('フロントページ設定', $td),
			'fields'                => array(
				array(
					'key'     => 'field_pc_intro',
					'label'   => __('について', $td),
					'name'    => '',
					'type'    => 'message',
					'message' => __('このグループはテーマが自動登録します。プラグイン側でフィールドを追加作成する必要はありません。', $td),
				),
				array(
					'key'           => 'field_pc_tagline',
					'label'         => __('サイトタグライン（ヘッダー上の帯）', $td),
					'name'          => 'tagline',
					'type'          => 'text',
					'default_value' => '設計提案│箕面・大阪北部│用途別ワークショップ同行',
				),
				array(
					'key'           => 'field_pc_hero_kicker',
					'label'         => __('ヒーロー・キッカー', $td),
					'name'          => 'hero_kicker',
					'type'          => 'text',
					'default_value' => '箕面・大阪北部｜社寺・集合・福祉の建築設計',
				),
				array(
					'key'           => 'field_pc_hero_title',
					'label'         => __('ヒーロー見出し', $td),
					'name'          => 'hero_title',
					'type'          => 'text',
					'default_value' => '坂ノ上設計｜社寺・集合・福祉で「場」を編む',
				),
				array(
					'key'           => 'field_pc_hero_lead',
					'label'         => __('ヒーローリード', $td),
					'name'          => 'hero_lead',
					'type'          => 'textarea',
					'rows'          => 4,
					'default_value' => "箕面から大阪北部へ、プロセスデザインと構法設計を両輪に据えます。\n現調から監理まで伴走し、制度・構造・運用まで一枚岩でご提案します。",
				),
				array(
					'key'           => 'field_pc_hero_image',
					'label'         => __('ヒーロー画像', $td),
					'name'          => 'hero_image',
					'type'          => 'image',
					'return_format' => 'array',
					'preview_size'  => 'medium',
				),
				array(
					'key'    => 'field_pc_svc_header',
					'label'  => __('サービス見出し', $td),
					'name'   => '',
					'type'   => 'tab',
				),
				array(
					'key'           => 'field_pc_services_heading',
					'label'         => __('サービスブロック見出し', $td),
					'name'          => 'services_heading',
					'type'          => 'text',
					'default_value' => '領域別サービス',
				),
				array(
					'key'           => 'field_pc_svc1_title',
					'label'         => __('サービス1・見出し', $td),
					'name'          => 'service_1_title',
					'type'          => 'text',
					'default_value' => '社寺・伝統的木造',
				),
				array(
					'key'           => 'field_pc_svc1_body',
					'label'         => __('サービス1・本文', $td),
					'name'          => 'service_1_body',
					'type'          => 'textarea',
					'rows'          => 4,
					'default_value' => "本尊・札所動線・地域行事まで見据えた平面組み替え。\n耐震・メンテ計画までワンストップでご提案します。",
				),
				array(
					'key'           => 'field_pc_svc1_image',
					'label'         => __('サービス1・画像', $td),
					'name'          => 'service_1_image',
					'type'          => 'image',
					'return_format' => 'array',
				),
				array(
					'key'           => 'field_pc_svc2_title',
					'label'         => __('サービス2・見出し', $td),
					'name'          => 'service_2_title',
					'type'          => 'text',
					'default_value' => '集合・福祉・中小プロジェクト',
				),
				array(
					'key'           => 'field_pc_svc2_body',
					'label'         => __('サービス2・本文', $td),
					'name'          => 'service_2_body',
					'type'          => 'textarea',
					'rows'          => 4,
					'default_value' => "長寿命仕様・運営動線・ユニバーサルデザインを前提にした計画。\n行政調整やJV連携にも対応します。",
				),
				array(
					'key'           => 'field_pc_svc2_image',
					'label'         => __('サービス2・画像', $td),
					'name'          => 'service_2_image',
					'type'          => 'image',
					'return_format' => 'array',
				),
				array(
					'key'           => 'field_pc_svc3_title',
					'label'         => __('サービス3・見出し', $td),
					'name'          => 'service_3_title',
					'type'          => 'text',
					'default_value' => 'リノベ・構造調査',
				),
				array(
					'key'           => 'field_pc_svc3_body',
					'label'         => __('サービス3・本文', $td),
					'name'          => 'service_3_body',
					'type'          => 'textarea',
					'rows'          => 4,
					'default_value' => "耐震診断から狭小地の増築まで局所的な構法見直し。\n竣工後の保全サイクル設計まで支援します。",
				),
				array(
					'key'           => 'field_pc_svc3_image',
					'label'         => __('サービス3・画像', $td),
					'name'          => 'service_3_image',
					'type'          => 'image',
					'return_format' => 'array',
				),
				array(
					'key'    => 'field_pc_highlight_tab',
					'label'  => __('サービス下ブロック', $td),
					'name'   => '',
					'type'   => 'tab',
				),
				array(
					'key'           => 'field_pc_highlight_show',
					'label'         => __('サービス下ブロックを表示', $td),
					'name'          => 'highlight_show',
					'type'          => 'true_false',
					'default_value' => 1,
					'ui'            => 1,
				),
				array(
					'key'           => 'field_pc_highlight_heading',
					'label'         => __('サービス下・見出し', $td),
					'name'          => 'highlight_heading',
					'type'          => 'text',
					'default_value' => '設計へのスタンス',
				),
				array(
					'key'           => 'field_pc_highlight_body',
					'label'         => __('サービス下・本文', $td),
					'name'          => 'highlight_body',
					'type'          => 'textarea',
					'rows'          => 5,
					'default_value' => "用途と現場条件を踏まえ、法規・構造・運用のバランスを見通してから図面化します。\nワークショップや合意形成の場にも同席し、一段深い提案ができるよう伴走します。",
				),
				array(
					'key'           => 'field_pc_highlight_image',
					'label'         => __('サービス下・画像', $td),
					'name'          => 'highlight_image',
					'type'          => 'image',
					'return_format' => 'array',
					'preview_size'  => 'medium',
				),
				array(
					'key'    => 'field_pc_greeting_tab',
					'label'  => __('代表メッセージ・ご挨拶', $td),
					'name'   => '',
					'type'   => 'tab',
				),
				array(
					'key'           => 'field_pc_greeting_show',
					'label'         => __('代表メッセージを表示', $td),
					'name'          => 'greeting_show',
					'type'          => 'true_false',
					'default_value' => 1,
					'ui'            => 1,
				),
				array(
					'key'           => 'field_pc_greeting_heading',
					'label'         => __('代表メッセージ・見出し', $td),
					'name'          => 'greeting_heading',
					'type'          => 'text',
					'default_value' => 'ごあいさつ',
				),
				array(
					'key'           => 'field_pc_greeting_body',
					'label'         => __('代表メッセージ・本文', $td),
					'name'          => 'greeting_body',
					'type'          => 'textarea',
					'rows'          => 6,
					'default_value' => "私ども坂ノ上設計は、社寺から集合・福祉施設まで、地域に根差した計画や耐震・長寿命の観点を大切にしています。\n現場での対話と図面上の論理、その両面からお役に立てるよう支援いたします。",
				),
				array(
					'key'           => 'field_pc_greeting_image',
					'label'         => __('代表メッセージ・画像（任意）', $td),
					'name'          => 'greeting_image',
					'type'          => 'image',
					'return_format' => 'array',
					'preview_size'  => 'medium',
				),
				array(
					'key'    => 'field_pc_secondary_cta_tab',
					'label'  => __('CTA帯', $td),
					'name'   => '',
					'type'   => 'tab',
				),
				array(
					'key'           => 'field_pc_secondary_cta_show',
					'label'         => __('CTA帯を表示', $td),
					'name'          => 'cta_strip_show',
					'type'          => 'true_false',
					'default_value' => 1,
					'ui'            => 1,
				),
				array(
					'key'           => 'field_pc_secondary_cta_title',
					'label'         => __('CTA帯・見出し', $td),
					'name'          => 'cta_strip_heading',
					'type'          => 'text',
					'default_value' => 'まずはお気軽にご相談ください',
				),
				array(
					'key'           => 'field_pc_secondary_cta_text',
					'label'         => __('CTA帯・本文', $td),
					'name'          => 'cta_strip_body',
					'type'          => 'textarea',
					'rows'          => 3,
					'default_value' => '用途やスケジュールが固まっていなくても構いません。オンライン面談・現地確認の調整など、運用しやすい形でご案内します。',
				),
				array(
					'key'           => 'field_pc_secondary_cta_btn_label',
					'label'         => __('CTA帯・ボタン文言', $td),
					'name'          => 'cta_strip_button_label',
					'type'          => 'text',
					'default_value' => 'お問い合わせページへ',
				),
				array(
					'key'           => 'field_pc_secondary_cta_btn_url',
					'label'         => __('CTA帯・ボタンリンク先 URL', $td),
					'name'          => 'cta_strip_button_url',
					'type'          => 'url',
					'default_value' => '',
				),
				array(
					'key'    => 'field_pc_stats_tab',
					'label'  => __('実績・数字', $td),
					'name'   => '',
					'type'   => 'tab',
				),
				array(
					'key'           => 'field_pc_stats_show',
					'label'         => __('実績・数字ブロックを表示', $td),
					'name'          => 'stats_show',
					'type'          => 'true_false',
					'default_value' => 1,
					'ui'            => 1,
				),
				array(
					'key'           => 'field_pc_stat_1_label',
					'label'         => __('指標1・ラベル', $td),
					'name'          => 'stat_1_label',
					'type'          => 'text',
					'default_value' => '設立',
				),
				array(
					'key'           => 'field_pc_stat_1_value',
					'label'         => __('指標1・値', $td),
					'name'          => 'stat_1_value',
					'type'          => 'text',
					'default_value' => '2006年',
				),
				array(
					'key'           => 'field_pc_stat_2_label',
					'label'         => __('指標2・ラベル', $td),
					'name'          => 'stat_2_label',
					'type'          => 'text',
					'default_value' => '設計監理実績（累計・例）',
				),
				array(
					'key'           => 'field_pc_stat_2_value',
					'label'         => __('指標2・値', $td),
					'name'          => 'stat_2_value',
					'type'          => 'text',
					'default_value' => '120件超',
				),
				array(
					'key'           => 'field_pc_stat_3_label',
					'label'         => __('指標3・ラベル', $td),
					'name'          => 'stat_3_label',
					'type'          => 'text',
					'default_value' => '対応エリア（例）',
				),
				array(
					'key'           => 'field_pc_stat_3_value',
					'label'         => __('指標3・値', $td),
					'name'          => 'stat_3_value',
					'type'          => 'text',
					'default_value' => '大阪府北部・阪神間',
				),
				array(
					'key'    => 'field_pc_news_preview_tab',
					'label'  => __('お知らせプレビュー', $td),
					'name'   => '',
					'type'   => 'tab',
				),
				array(
					'key'           => 'field_pc_news_preview_show',
					'label'         => __('お知らせプレビューを表示', $td),
					'name'          => 'news_preview_show',
					'type'          => 'true_false',
					'default_value' => 1,
					'ui'            => 1,
				),
				array(
					'key'           => 'field_pc_news_preview_heading',
					'label'         => __('お知らせ・見出し', $td),
					'name'          => 'news_preview_heading',
					'type'          => 'text',
					'default_value' => 'お知らせ',
				),
				array(
					'key'           => 'field_pc_news_preview_count',
					'label'         => __('表示件数', $td),
					'name'          => 'posts_count',
					'type'          => 'number',
					'min'           => 1,
					'max'           => 10,
					'default_value' => 3,
					'step'          => 1,
				),
				array(
					'key'    => 'field_pc_footer_tab',
					'label'  => __('フッター連絡先', $td),
					'name'   => '',
					'type'   => 'tab',
				),
				array(
					'key'           => 'field_pc_footer_show',
					'label'         => __('連絡先ブロックを表示', $td),
					'name'          => 'footer_contact_show',
					'type'          => 'true_false',
					'default_value' => 1,
					'ui'            => 1,
				),
				array(
					'key'           => 'field_pc_footer_heading',
					'label'         => __('連絡先見出し', $td),
					'name'          => 'footer_contact_heading',
					'type'          => 'text',
					'default_value' => 'お問い合わせ・所在地',
				),
				array(
					'key'           => 'field_pc_footer_body',
					'label'         => __('所在地・備考（複数行）', $td),
					'name'          => 'footer_contact_body',
					'type'          => 'textarea',
					'rows'          => 4,
					'default_value' => "〒562-0001 大阪府箕面市小野原東 4-12-1 坂ノ上ビル 3F\n平日 9:30–18:30｜見学会・オンライン相談は事前予約制です",
				),
				array(
					'key'           => 'field_pc_footer_phone',
					'label'         => __('電話番号', $td),
					'name'          => 'footer_phone',
					'type'          => 'text',
					'default_value' => '072-736-2840',
				),
				array(
					'key'           => 'field_pc_footer_email',
					'label'         => __('メールアドレス', $td),
					'name'          => 'footer_email',
					'type'          => 'email',
					'default_value' => 'info@sakanoue-sekkei.example',
				),
			),
			'location'              => array(
				array(
					array(
						'param'    => 'page_type',
						'operator' => '==',
						'value'    => 'front_page',
					),
				),
			),
			'position'              => 'acf_after_title',
			'style'                 => 'default',
			'label_placement'       => 'top',
			'instruction_placement' => 'label',
			'active'                => true,
		)
	);

	if ( function_exists( 'theme_register_acf_page_template_groups' ) ) {
		theme_register_acf_page_template_groups();
	}
}
add_action('acf/init', 'theme_register_acf');

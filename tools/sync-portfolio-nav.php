<?php
/**
 * Sync the portfolio header/footer nav menus in a Local WP install.
 *
 * Run from WordPress bootstrap, for example:
 * php -r 'require "./wp-load.php"; require "./wp-content/themes/0520portfolio-wp/tools/sync-portfolio-nav.php";'
 *
 * @package Theme
 */

declare(strict_types=1);

$config_file   = __DIR__ . '/local-wp-load.path';
$file_wp_load  = '';
if ( is_readable( $config_file ) ) {
	$raw = (string) file_get_contents( $config_file );
	foreach ( preg_split( "/\r\n|\n|\r/", $raw ) as $line ) {
		$line = trim( $line );
		if ( $line === '' || str_starts_with( $line, '#' ) ) {
			continue;
		}
		$file_wp_load = str_starts_with( $line, '~' ) ? ( getenv( 'HOME' ) ?: '' ) . substr( $line, 1 ) : $line;
		break;
	}
}

$env_wp  = getenv( 'WP_LOAD_PATH' );
$wp_load = ( is_string( $env_wp ) && $env_wp !== '' ) ? $env_wp : $file_wp_load;

if ( ! defined( 'ABSPATH' ) ) {
	if ( $wp_load === '' || ! is_readable( $wp_load ) ) {
		fwrite( STDERR, "wp-load.php が見つかりません。WP_LOAD_PATH を指定してください。\n" );
		exit( 1 );
	}

	require $wp_load;
}

wp_set_current_user( 1 );

/**
 * Ensure a named menu exists and return its ID.
 */
function theme_sync_nav_menu_id( string $menu_name ): int {
	$menu = wp_get_nav_menu_object( $menu_name );
	if ( $menu instanceof WP_Term ) {
		return (int) $menu->term_id;
	}

	$created = wp_create_nav_menu( $menu_name );
	if ( is_wp_error( $created ) || ! $created ) {
		fwrite( STDERR, "メニューを作成できませんでした: {$menu_name}\n" );
		exit( 1 );
	}

	return (int) $created;
}

/**
 * Remove all items from a menu.
 */
function theme_sync_nav_menu_clear( int $menu_id ): void {
	foreach ( (array) wp_get_nav_menu_items( $menu_id ) as $item ) {
		if ( ! $item instanceof WP_Post ) {
			continue;
		}

		wp_delete_post( (int) $item->ID, true );
	}
}

/**
 * Add a custom menu item.
 *
 * @param int   $menu_id Menu ID.
 * @param array{
 *     title: string,
 *     url: string,
 *     target?: string,
 *     rel?: string,
 *     parent?: int
 * } $item Item definition.
 * @return int Menu item ID.
 */
function theme_sync_nav_menu_add_item( int $menu_id, array $item ): int {
	$args = array(
		'menu-item-title'  => $item['title'],
		'menu-item-url'    => $item['url'],
		'menu-item-status' => 'publish',
		'menu-item-type'   => 'custom',
	);

	if ( ! empty( $item['target'] ) ) {
		$args['menu-item-target'] = $item['target'];
	}

	if ( ! empty( $item['rel'] ) ) {
		$args['menu-item-xfn'] = $item['rel'];
	}

	if ( ! empty( $item['parent'] ) ) {
		$args['menu-item-parent-id'] = (int) $item['parent'];
	}

	$item_id = wp_update_nav_menu_item( $menu_id, 0, $args );
	if ( is_wp_error( $item_id ) || ! $item_id ) {
		fwrite( STDERR, "メニュー項目を追加できませんでした: {$item['title']}\n" );
		exit( 1 );
	}

	return (int) $item_id;
}

/**
 * Sync a menu location with a fixed tree.
 *
 * @param string $location Menu location.
 * @param string $menu_name Menu name.
 * @param array<int, array<string, mixed>> $items Menu tree.
 */
function theme_sync_nav_menu_tree( string $location, string $menu_name, array $items ): void {
	$menu_id = theme_sync_nav_menu_id( $menu_name );
	theme_sync_nav_menu_clear( $menu_id );

	$stack = array();
	foreach ( $items as $item ) {
		$parent_id = isset( $item['parent_key'] ) ? (int) ( $stack[ (string) $item['parent_key'] ] ?? 0 ) : 0;

		$item_id = theme_sync_nav_menu_add_item(
			$menu_id,
			array(
				'title'  => (string) $item['title'],
				'url'    => (string) $item['url'],
				'target' => (string) ( $item['target'] ?? '' ),
				'rel'    => (string) ( $item['rel'] ?? '' ),
				'parent' => $parent_id,
			)
		);

		if ( isset( $item['key'] ) ) {
			$stack[ (string) $item['key'] ] = $item_id;
		}
	}

	$locations = get_nav_menu_locations();
	$locations[ $location ] = $menu_id;
	set_theme_mod( 'nav_menu_locations', $locations );
}

$home = home_url( '/' );

$primary_items = array(
	array(
		'key'   => 'home',
		'title' => 'HOME',
		'url'   => $home,
	),
	array(
		'key'   => 'bunmyaku',
		'title' => '文脈',
		'url'   => home_url( '/bunmyaku' ),
	),
	array(
		'key'   => 'repositories',
		'title' => 'Repositories',
		'url'   => '#',
	),
	array(
		'parent_key' => 'repositories',
		'title'      => 'Portfolio',
		'url'        => 'https://github.com/yuremono/portfolio',
		'target'     => '_blank',
		'rel'        => 'noopener noreferrer',
	),
	array(
		'parent_key' => 'repositories',
		'title'      => 'Portfolio-wp',
		'url'        => 'https://github.com/yuremono/portfolio-wp',
		'target'     => '_blank',
		'rel'        => 'noopener noreferrer',
	),
	array(
		'parent_key' => 'repositories',
		'title'      => 'BurnYourOwnStyle',
		'url'        => 'https://github.com/yuremono/BurnYourOwnStyle/tree/react',
		'target'     => '_blank',
		'rel'        => 'noopener noreferrer',
	),
	array(
		'parent_key' => 'repositories',
		'title'      => 'AgentDrivenCMS',
		'url'        => 'https://github.com/yuremono/agent-driven-CMS',
		'target'     => '_blank',
		'rel'        => 'noopener noreferrer',
	),
	array(
		'parent_key' => 'repositories',
		'title'      => 'AgentRelay',
		'url'        => 'https://github.com/yuremono/agent-relay',
		'target'     => '_blank',
		'rel'        => 'noopener noreferrer',
	),
	array(
		'parent_key' => 'repositories',
		'title'      => 'CreativeDemos',
		'url'        => 'https://github.com/yuremono/creative-demos',
		'target'     => '_blank',
		'rel'        => 'noopener noreferrer',
	),
	array(
		'parent_key' => 'repositories',
		'title'      => 'ChatCanban',
		'url'        => 'https://github.com/yuremono/chatKanban',
		'target'     => '_blank',
		'rel'        => 'noopener noreferrer',
	),
	array(
		'parent_key' => 'repositories',
		'title'      => 'NextJsCMS',
		'url'        => 'https://github.com/yuremono/portfolio',
		'target'     => '_blank',
		'rel'        => 'noopener noreferrer',
	),
	array(
		'key'   => 'pages',
		'title' => 'Pages',
		'url'   => '#',
	),
	array(
		'parent_key' => 'pages',
		'title'      => 'BurnYourOwnStyle',
		'url'        => home_url( '/preview' ),
	),
	array(
		'parent_key' => 'pages',
		'title'      => '文脈',
		'url'        => home_url( '/bunmyaku' ),
	),
	array(
		'parent_key' => 'pages',
		'title'      => 'Donut(ADCMS)',
		'url'        => home_url( '/donut' ),
	),
	array(
		'parent_key' => 'pages',
		'title'      => 'RandomGenerator',
		'url'        => home_url( '/rects' ),
	),
	array(
		'parent_key' => 'pages',
		'title'      => 'ShuffleDivide',
		'url'        => home_url( '/shuffleDivide' ),
	),
	array(
		'parent_key' => 'pages',
		'title'      => 'Glitch',
		'url'        => home_url( '/glitch' ),
	),
	array(
		'parent_key' => 'pages',
		'title'      => 'GridCarousel',
		'url'        => home_url( '/grid-carousel' ),
	),
	array(
		'parent_key' => 'pages',
		'title'      => 'BBox',
		'url'        => home_url( '/bbox' ),
	),
	array(
		'parent_key' => 'pages',
		'title'      => 'Activity',
		'url'        => home_url( '/activity' ),
	),
	array(
		'parent_key' => 'pages',
		'title'      => 'ChatCanban',
		'url'        => 'https://chat-kanban.vercel.app/',
		'target'     => '_blank',
		'rel'        => 'noopener noreferrer',
	),
	array(
		'parent_key' => 'pages',
		'title'      => 'NextJsCMS',
		'url'        => 'https://cms0505.vercel.app/',
		'target'     => '_blank',
		'rel'        => 'noopener noreferrer',
	),
);

$footer_items = array(
	array(
		'title' => 'BurnYourOwnStyle',
		'url'   => home_url( '/preview' ),
	),
	array(
		'title' => '文脈',
		'url'   => home_url( '/bunmyaku' ),
	),
	array(
		'title' => 'Donut(ADCMS)',
		'url'   => home_url( '/donut' ),
	),
	array(
		'title' => 'RandomGenerator',
		'url'   => home_url( '/rects' ),
	),
	array(
		'title' => 'ShuffleDivide',
		'url'   => home_url( '/shuffleDivide' ),
	),
	array(
		'title' => 'Glitch',
		'url'   => home_url( '/glitch' ),
	),
	array(
		'title' => 'GridCarousel',
		'url'   => home_url( '/grid-carousel' ),
	),
	array(
		'title' => 'BBox',
		'url'   => home_url( '/bbox' ),
	),
	array(
		'title' => 'Activity',
		'url'   => home_url( '/activity' ),
	),
	array(
		'title'  => 'ChatCanban',
		'url'    => 'https://chat-kanban.vercel.app/',
		'target' => '_blank',
		'rel'    => 'noopener noreferrer',
	),
	array(
		'title'  => 'NextJsCMS',
		'url'    => 'https://cms0505.vercel.app/',
		'target' => '_blank',
		'rel'    => 'noopener noreferrer',
	),
);

theme_sync_nav_menu_tree( 'primary', 'Primary', $primary_items );
theme_sync_nav_menu_tree( 'footer', 'Footer', $footer_items );

echo "Primary / footer menus synced.\n";

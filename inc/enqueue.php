<?php
/**
 * Front-end asset enqueue.
 *
 * @package Theme
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue front-end stylesheet and scripts.
 */
function theme_enqueue_assets(): void {
	wp_enqueue_style(
		'theme-main-css',
		get_template_directory_uri() . '/assets/theme.css',
		array(),
		theme_asset_version( 'assets/theme.css' )
	);

	if ( is_front_page() ) {
		wp_enqueue_style(
			'theme-tailwind',
			get_template_directory_uri() . '/assets/tailwind.css',
			array( 'theme-main-css' ),
			theme_asset_version( 'assets/tailwind.css' )
		);

		wp_enqueue_script(
			'theme-mindmap-runtime',
			get_template_directory_uri() . '/assets/mindmap-runtime.js',
			array(),
			theme_asset_version( 'assets/mindmap-runtime.js' ),
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);
		wp_enqueue_script_module(
			'theme-header-cylinder-logo',
			get_template_directory_uri() . '/assets/header-cylinder-logo.js',
			array(),
			theme_asset_version( 'assets/header-cylinder-logo.js' )
		);
		wp_enqueue_script(
			'theme-portfolio-front',
			get_template_directory_uri() . '/assets/portfolio.js',
			array( 'theme-mindmap-runtime' ),
			theme_asset_version( 'assets/portfolio.js' ),
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);
		wp_enqueue_script(
			'theme-repulsion-lists',
			get_template_directory_uri() . '/assets/repulsion-lists.js',
			array( 'theme-portfolio-front' ),
			theme_asset_version( 'assets/repulsion-lists.js' ),
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);
		wp_enqueue_script(
			'theme-bunmyaku-teaser',
			get_template_directory_uri() . '/assets/bunmyaku-teaser.js',
			array( 'theme-portfolio-front' ),
			theme_asset_version( 'assets/bunmyaku-teaser.js' ),
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);
	}

	wp_enqueue_script(
		'theme-site-transition',
		get_template_directory_uri() . '/assets/site-transition.js',
		array(),
		theme_asset_version( 'assets/site-transition.js' ),
		array(
			'strategy'  => 'defer',
			'in_footer' => true,
		)
	);
}
add_action( 'wp_enqueue_scripts', 'theme_enqueue_assets' );

<?php
/**
 * Theme setup and nav filters.
 *
 * @package Theme
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add layout scope class to body.
 *
 * @param array<int, string> $classes Existing body classes.
 * @return array<int, string>
 */
function theme_add_layout_body_class( array $classes ): array {
	$classes[] = 'root';
	$classes[] = 'SiteTransitionPending';
	return $classes;
}
add_filter( 'body_class', 'theme_add_layout_body_class' );

/**
 * Render the shared opening / transition overlay.
 */
function theme_render_site_transition_overlay(): void {
	$brand = theme_brand();
	$brand = trim( $brand );
	if ( '' === $brand ) {
		$brand = THEME_BRAND_DEFAULT;
	}

	$label = preg_replace( '/\s+/', "\n", $brand, 1 );
	if ( ! is_string( $label ) || '' === $label ) {
		$label = THEME_BRAND_DEFAULT;
	}
	?>
	<div class="InitialLoading PageTransitionOverlay InitialLoadingLayer" data-site-transition-overlay hidden aria-hidden="true">
		<p class="InitialLoadingTextProbe mmPin" data-site-transition-probe aria-hidden="true"><?php echo esc_html( $label ); ?></p>
		<canvas class="PageTransitionCanvas" data-site-transition-canvas aria-hidden="true"></canvas>
	</div>
	<?php
}
add_action( 'wp_body_open', 'theme_render_site_transition_overlay' );

/**
 * Hide the page contents until the transition overlay has taken over.
 */
function theme_render_site_transition_noscript_style(): void {
	?>
	<noscript>
		<style>
			body.SiteTransitionPending > :not([data-site-transition-overlay]) {
				visibility: visible !important;
			}

			body.SiteTransitionPending > [data-site-transition-overlay] {
				display: none !important;
			}
		</style>
	</noscript>
	<?php
}
add_action( 'wp_footer', 'theme_render_site_transition_noscript_style', 1 );

/**
 * メインメニューのリンクにクラスを付与。
 *
 * @param array<string, string> $atts   Attributes.
 * @param WP_Post               $item    Menu item data object.
 * @param stdClass              $args    Menu arguments.
 * @param int                   $depth   Depth of menu item.
 * @return array<string, string>
 */
function theme_primary_nav_link_attributes( array $atts, $item, $args, int $depth ): array {
	unset( $item, $depth );

	if ( isset( $args->theme_location ) && 'primary' === $args->theme_location ) {
		$atts['class'] = trim( ( $atts['class'] ?? '' ) . ' primary_nav_link' );
	}
	if ( isset( $args->theme_location ) && 'footer' === $args->theme_location ) {
		$atts['class'] = trim( ( $atts['class'] ?? '' ) );
	}
	return $atts;
}
add_filter( 'nav_menu_link_attributes', 'theme_primary_nav_link_attributes', 10, 4 );

/**
 * メインメニューの li にクラスを付与。
 *
 * @param array<int, string> $classes クラス一覧.
 * @param WP_Post            $item    Menu item data object.
 * @param stdClass           $args    Menu arguments.
 * @param int                $depth   Depth of menu item.
 * @return array<int, string>
 */
function theme_primary_nav_menu_css_class( array $classes, $item, $args, int $depth ): array {
	unset( $item, $depth );

	if ( isset( $args->theme_location ) && 'primary' === $args->theme_location ) {
		$classes[] = 'primary_nav_item';
	}
	return $classes;
}
add_filter( 'nav_menu_css_class', 'theme_primary_nav_menu_css_class', 10, 4 );

if ( ! class_exists( 'Theme_Header_Nav_Walker' ) ) {
	// phpcs:disable Universal.Files.SeparateFunctionsFromOO.Mixed
	/**
	 * Header menu walker preserving Header/Nav/Drop markup.
	 */
	class Theme_Header_Nav_Walker extends Walker_Nav_Menu {
		/**
		 * Current top-level dropdown ID.
		 *
		 * @var string
		 */
		private string $dropdown_id = '';

		/**
		 * Current top-level dropdown label.
		 *
		 * @var string
		 */
		private string $dropdown_label = '';

		/**
		 * Starts the submenu list.
		 *
		 * @param string   $output Used to append additional content.
		 * @param int      $depth  Depth of menu item.
		 * @param stdClass $args   Menu arguments.
		 */
		public function start_lvl( &$output, $depth = 0, $args = null ) {
			if ( 0 !== $depth || '' === $this->dropdown_id ) {
				return;
			}

			$output .= '<ul id="' . esc_attr( $this->dropdown_id ) . '" class="DropUl" popover="auto" aria-label="' . esc_attr( $this->dropdown_label ) . '">';
		}

		/**
		 * Ends the submenu list.
		 *
		 * @param string   $output Used to append additional content.
		 * @param int      $depth  Depth of menu item.
		 * @param stdClass $args   Menu arguments.
		 */
		public function end_lvl( &$output, $depth = 0, $args = null ) {
			if ( 0 !== $depth ) {
				return;
			}

			$output           .= '</ul>';
			$this->dropdown_id = '';
		}

		/**
		 * Starts a menu item.
		 *
		 * @param string  $output Used to append additional content.
		 * @param WP_Post $item   Menu item data object.
		 * @param int     $depth  Depth of menu item.
		 * @param object  $args   Menu arguments.
		 * @param int     $id     Current item ID.
		 */
		public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
			$title        = apply_filters( 'the_title', $item->title, $item->ID );
			$has_children = in_array( 'menu-item-has-children', (array) $item->classes, true );

			if ( 0 === $depth && $has_children ) {
				$this->dropdown_id    = 'HeaderMenu-' . (int) $item->ID . '-cylinder';
				$this->dropdown_label = wp_strip_all_tags( $title );

				$output .= '<li class="NavLi NavDrop">';
				$output .= '<button type="button" class="DropA DropToggle" popovertarget="' . esc_attr( $this->dropdown_id ) . '">';
				$output .= esc_html( $title );
				$output .= theme_phosphor_icon(
					'caret-down',
					array(
						'class' => 'DropIcon',
						'size'  => 20,
					)
				); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				$output .= '</button>';
				$output .= '<button type="button" class="DropBtn DropToggle" popovertarget="' . esc_attr( $this->dropdown_id ) . '" aria-label="' . esc_attr(
					sprintf(
						/* translators: %s: Menu item title. */
						__( 'Toggle %s submenu', THEME_GETTEXT_DOMAIN ),
						wp_strip_all_tags( $title )
					)
				) . '"></button>';
				return;
			}

			$li_class = 0 === $depth ? 'NavLi' : 'DropLi';
			$output  .= '<li class="' . esc_attr( $li_class ) . '">';
			$output  .= $this->build_menu_link( $item, $title, 0 < $depth );
		}

		/**
		 * Ends a menu item.
		 *
		 * @param string  $output Used to append additional content.
		 * @param WP_Post $item   Menu item data object.
		 * @param int     $depth  Depth of menu item.
		 * @param object  $args   Menu arguments.
		 */
		public function end_el( &$output, $item, $depth = 0, $args = null ) {
			$output .= '</li>';
		}

		/**
		 * Builds a menu anchor.
		 *
		 * @param WP_Post $item          Menu item data object.
		 * @param string  $title         Menu item title.
		 * @param bool    $external_icon Whether to append external icon.
		 * @return string
		 */
		private function build_menu_link( WP_Post $item, string $title, bool $external_icon ): string {
			$atts = array(
				'href'   => ! empty( $item->url ) ? $item->url : '#',
				'target' => $item->target,
				'rel'    => $item->xfn,
			);

			if ( '_blank' === $item->target && empty( $atts['rel'] ) ) {
				$atts['rel'] = 'noopener noreferrer';
			}

			$output  = '<a' . $this->build_link_attributes( $atts ) . '>';
			$output .= esc_html( $title );
			if ( $external_icon && '_blank' === $item->target ) {
				$output .= theme_phosphor_icon( 'arrow-square-out', array( 'size' => 16 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			$output .= '</a>';

			return $output;
		}

		/**
		 * Builds escaped anchor attributes.
		 *
		 * @param array<string, string> $atts Link attributes.
		 * @return string
		 */
		private function build_link_attributes( array $atts ): string {
			$output = '';

			foreach ( $atts as $name => $value ) {
				if ( '' === $value ) {
					continue;
				}

				$escaped = 'href' === $name ? esc_url( $value ) : esc_attr( $value );
				$output .= ' ' . $name . '="' . $escaped . '"';
			}

			return $output;
		}
	}
	// phpcs:enable Universal.Files.SeparateFunctionsFromOO.Mixed
}

/**
 * Theme supports and menus.
 */
function theme_setup(): void {
	load_theme_textdomain( THEME_GETTEXT_DOMAIN, get_template_directory() . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
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
			'primary' => __( 'メインメニュー', THEME_GETTEXT_DOMAIN ),
			'footer'  => __( 'フッターメニュー', THEME_GETTEXT_DOMAIN ),
		)
	);
}
add_action( 'after_setup_theme', 'theme_setup' );

/**
 * Header fallback menu (chrome.php 既定リンク).
 *
 * @param array<string, mixed> $args Fallback menu arguments.
 */
function theme_header_nav_fallback_menu( $args = array() ): void {
	unset( $args );

	echo '<ul class="NavUl">';
	echo '<li class="NavLi"><a href="' . esc_url( home_url( '/' ) ) . '">HOME</a></li>';
	echo '<li class="NavLi [font-family:--Ship]"><a href="' . esc_url( home_url( '/bunmyaku' ) ) . '">文脈</a></li>';
	echo '<li class="NavLi NavDrop">';
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '<button type="button" class="DropA DropToggle" popovertarget="HeaderRepositoriesMenu-cylinder">Repositories' . theme_phosphor_icon(
		'caret-down',
		array(
			'class' => 'DropIcon',
			'size'  => 20,
		)
	) . '</button>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '<button type="button" class="DropBtn DropToggle" popovertarget="HeaderRepositoriesMenu-cylinder" aria-label="Toggle repositories submenu"></button>';
	echo '<ul id="HeaderRepositoriesMenu-cylinder" class="DropUl" popover="auto" aria-label="Repositories">';
	echo '<li class="DropLi"><a href="https://github.com/yuremono/portfolio" target="_blank" rel="noopener noreferrer">Portfolio' . theme_phosphor_icon( 'arrow-square-out', array( 'size' => 16 ) ) . '</a></li>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '<li class="DropLi"><a href="https://github.com/yuremono/portfolio-wp" target="_blank" rel="noopener noreferrer">Portfolio-wp' . theme_phosphor_icon( 'arrow-square-out', array( 'size' => 16 ) ) . '</a></li>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '<li class="DropLi"><a href="https://github.com/yuremono/BurnYourOwnStyle/tree/react" target="_blank" rel="noopener noreferrer">BurnYourOwnStyle' . theme_phosphor_icon( 'arrow-square-out', array( 'size' => 16 ) ) . '</a></li>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '<li class="DropLi"><a href="https://github.com/yuremono/agent-driven-CMS" target="_blank" rel="noopener noreferrer">AgentDrivenCMS' . theme_phosphor_icon( 'arrow-square-out', array( 'size' => 16 ) ) . '</a></li>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '<li class="DropLi"><a href="https://github.com/yuremono/agent-relay" target="_blank" rel="noopener noreferrer">AgentRelay' . theme_phosphor_icon( 'arrow-square-out', array( 'size' => 16 ) ) . '</a></li>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '<li class="DropLi"><a href="https://github.com/yuremono/creative-demos" target="_blank" rel="noopener noreferrer">CreativeDemos' . theme_phosphor_icon( 'arrow-square-out', array( 'size' => 16 ) ) . '</a></li>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '<li class="DropLi"><a href="https://github.com/yuremono/chatKanban" target="_blank" rel="noopener noreferrer">ChatCanban' . theme_phosphor_icon( 'arrow-square-out', array( 'size' => 16 ) ) . '</a></li>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '<li class="DropLi"><a href="https://github.com/yuremono/portfolio" target="_blank" rel="noopener noreferrer">NextJsCMS' . theme_phosphor_icon( 'arrow-square-out', array( 'size' => 16 ) ) . '</a></li>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '</ul>';
	echo '</li>';
	echo '<li class="NavLi NavDrop">';
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '<button type="button" class="DropA DropToggle" popovertarget="HeaderPagesMenu-cylinder">Pages' . theme_phosphor_icon(
		'caret-down',
		array(
			'class' => 'DropIcon',
			'size'  => 20,
		)
	) . '</button>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '<button type="button" class="DropBtn DropToggle" popovertarget="HeaderPagesMenu-cylinder" aria-label="Toggle pages submenu"></button>';
	echo '<ul id="HeaderPagesMenu-cylinder" class="DropUl" popover="auto" aria-label="Pages">';
	echo '<li class="DropLi"><a href="' . esc_url( home_url( '/preview' ) ) . '">BurnYourOwnStyle</a></li>';
	echo '<li class="DropLi [font-family:--Ship]"><a href="' . esc_url( home_url( '/bunmyaku' ) ) . '">文脈</a></li>';
	echo '<li class="DropLi"><a href="' . esc_url( home_url( '/donut' ) ) . '">Donut<small>(ADCMS)</small></a></li>';
	echo '<li class="DropLi"><a href="' . esc_url( home_url( '/rects' ) ) . '">RandomGenerator</a></li>';
	echo '<li class="DropLi"><a href="' . esc_url( home_url( '/shuffleDivide' ) ) . '">ShuffleDivide</a></li>';
	echo '<li class="DropLi"><a href="' . esc_url( home_url( '/glitch' ) ) . '">Glitch</a></li>';
	echo '<li class="DropLi"><a href="' . esc_url( home_url( '/grid-carousel' ) ) . '">GridCarousel</a></li>';
	echo '<li class="DropLi"><a href="' . esc_url( home_url( '/bbox' ) ) . '">BBox</a></li>';
	echo '<li class="DropLi"><a href="' . esc_url( home_url( '/activity' ) ) . '">Activity</a></li>';
	echo '<li class="DropLi"><a href="https://chat-kanban.vercel.app/" target="_blank" rel="noopener noreferrer">ChatCanban' . theme_phosphor_icon( 'arrow-square-out', array( 'size' => 16 ) ) . '</a></li>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '<li class="DropLi"><a href="https://cms0505.vercel.app/" target="_blank" rel="noopener noreferrer">NextJsCMS' . theme_phosphor_icon( 'arrow-square-out', array( 'size' => 16 ) ) . '</a></li>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '</ul>';
	echo '</li>';
	echo '</ul>';
}

/**
 * Portfolio footer fallback menu.
 *
 * @param array<string, mixed> $args Fallback menu arguments.
 */
function theme_portfolio_footer_fallback_menu( $args = array() ): void {
	unset( $args );

	$links = array(
		array(
			'url'   => home_url( '/preview' ),
			'label' => 'BurnYourOwnStyle',
		),
		array(
			'url'   => home_url( '/bunmyaku' ),
			'label' => '文脈',
			'class' => '[font-family:--Ship]',
		),
		array(
			'url'   => home_url( '/donut' ),
			'label' => 'Donut<small>(ADCMS)</small>',
		),
		array(
			'url'   => home_url( '/rects' ),
			'label' => 'RandomGenerator',
		),
		array(
			'url'   => home_url( '/shuffleDivide' ),
			'label' => 'ShuffleDivide',
		),
		array(
			'url'   => home_url( '/glitch' ),
			'label' => 'Glitch',
		),
		array(
			'url'   => home_url( '/grid-carousel' ),
			'label' => 'GridCarousel',
		),
		array(
			'url'   => home_url( '/bbox' ),
			'label' => 'BBox',
		),
		array(
			'url'   => home_url( '/activity' ),
			'label' => 'Activity',
		),
	);
	echo '<ul class="flex flex-wrap gap md:justify-center mt-6">';
	foreach ( $links as $link ) {
		$class = isset( $link['class'] ) ? ' class="' . esc_attr( $link['class'] ) . '"' : '';
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<li' . $class . '><a href="' . esc_url( $link['url'] ) . '">' . wp_kses_post( $link['label'] ) . '</a></li>';
	}
	echo '</ul>';
}

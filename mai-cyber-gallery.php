<?php
/**
 * Mai Cyber Gallery.
 *
 * Plugin Name:       Mai Cyber Gallery
 * Description:       Advanced masonry, tiled mosaic, content slider, and lightbox galleries powered by Mai Engine.
 * Version:           0.7.1
 * Requires at least: 6.5
 * Tested up to:      7.0
 * Requires PHP:      8.3
 * Requires Plugins:  mai-engine
 * Text Domain:       mai-cyber-gallery
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package MaiCyberGallery
 */

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

/**
 * Main Mai Cyber Gallery plugin class.
 *
 * @since 0.1.0
 */
final class Mai_Cyber_Gallery_Plugin {
	/**
	 * Plugin instance.
	 *
	 * @var Mai_Cyber_Gallery_Plugin|null
	 */
	private static ?self $instance = null;

	/**
	 * Get the plugin instance.
	 *
	 * @since 0.1.0
	 *
	 * @return Mai_Cyber_Gallery_Plugin
	 */
	public static function instance(): self {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			self::$instance->setup_constants();
			self::$instance->hooks();
		}

		return self::$instance;
	}

	/**
	 * Prevent cloning.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function __clone(): void {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cloning is not allowed.', 'mai-cyber-gallery' ), '0.1.0' );
	}

	/**
	 * Prevent unserializing.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function __wakeup(): void {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Unserializing is not allowed.', 'mai-cyber-gallery' ), '0.1.0' );
	}

	/**
	 * Define plugin constants.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	private function setup_constants(): void {
		define( 'MAI_CYBER_GALLERY_VERSION', '0.7.1' );
		define( 'MAI_CYBER_GALLERY_MIN_ENGINE_VERSION', '2.40.0' );
		define( 'MAI_CYBER_GALLERY_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
		define( 'MAI_CYBER_GALLERY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
		define( 'MAI_CYBER_GALLERY_PLUGIN_FILE', __FILE__ );
		define( 'MAI_CYBER_GALLERY_BASENAME', plugin_basename( __FILE__ ) );
	}

	/**
	 * Include plugin files.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	private function includes(): void {
		require_once MAI_CYBER_GALLERY_PLUGIN_DIR . 'classes/class-gallery.php';
		require_once MAI_CYBER_GALLERY_PLUGIN_DIR . 'includes/functions.php';
	}

	/**
	 * Register plugin hooks.
	 *
	 * Mai Engine's runtime API is available after setup_theme at priority 0.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	private function hooks(): void {
		add_action( 'after_setup_theme', [ $this, 'run' ] );
	}

	/**
	 * Start the add-on after Mai Engine has loaded.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function run(): void {
		if ( ! $this->dependencies_met() ) {
			add_action( 'admin_notices', [ $this, 'admin_notice' ] );
			return;
		}

		$this->includes();

		add_action( 'init', [ $this, 'register_assets' ], 0 );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_detected_block_assets' ] );

		require_once MAI_CYBER_GALLERY_PLUGIN_DIR . 'blocks/mai-cyber-gallery/block.php';
	}

	/**
	 * Check the Mai Engine dependency and APIs used by this add-on.
	 *
	 * @since 0.1.0
	 *
	 * @return bool
	 */
	private function dependencies_met(): bool {
		if ( ! class_exists( 'Mai_Engine' ) || ! function_exists( 'mai_get_version' ) ) {
			return false;
		}

		if ( version_compare( mai_get_version(), MAI_CYBER_GALLERY_MIN_ENGINE_VERSION, '<' ) ) {
			return false;
		}

		$functions = [
			'genesis_attr',
			'genesis_markup',
			'mai_add_classes',
			'mai_format_acf_color_value',
			'mai_get_breakpoint',
			'mai_get_color_css',
			'mai_get_colors',
			'mai_get_breakpoint_columns',
			'mai_get_columns_atts',
			'mai_get_columns_defaults',
			'mai_get_columns_sanitized',
			'mai_get_acf_request',
			'mai_get_image_orientation_choices',
			'mai_get_image_size_choices',
			'mai_get_index',
			'mai_get_radio_color_choices',
			'mai_get_svg_icon',
			'mai_get_unit_value',
			'mai_has_image_orientiation',
			'mai_is_valid_size',
			'mai_prepare_legacy_color_field',
			'mai_sanitize_bool',
		];

		foreach ( $functions as $function ) {
			if ( ! function_exists( $function ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Register front-end and editor assets.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function register_assets(): void {
		$debug       = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG;
		$suffix      = $debug ? '' : '.min';
		$style_file  = "assets/css/mai-cyber-gallery{$suffix}.css";
		$script_file = "assets/js/mai-cyber-gallery{$suffix}.js";
		$style_path  = MAI_CYBER_GALLERY_PLUGIN_DIR . $style_file;
		$script_path = MAI_CYBER_GALLERY_PLUGIN_DIR . $script_file;
		$icon_atts   = [
			'aria-hidden' => 'true',
			'focusable'   => 'false',
		];

		wp_register_style(
			'mai-cyber-gallery',
			MAI_CYBER_GALLERY_PLUGIN_URL . $style_file,
			[],
			$this->asset_version( $style_path )
		);
		wp_style_add_data( 'mai-cyber-gallery', 'path', $style_path );

		wp_register_script(
			'mai-cyber-gallery',
			MAI_CYBER_GALLERY_PLUGIN_URL . $script_file,
			[],
			$this->asset_version( $script_path ),
			false
		);
		wp_localize_script(
			'mai-cyber-gallery',
			'MaiCyberGallerySettings',
			[
				'dialogLabel'       => __( 'Image viewer', 'mai-cyber-gallery' ),
				'close'             => __( 'Close image viewer', 'mai-cyber-gallery' ),
				'next'              => __( 'Next image', 'mai-cyber-gallery' ),
				'previous'          => __( 'Previous image', 'mai-cyber-gallery' ),
				'pauseAutoplay'     => __( 'Pause automatic slides', 'mai-cyber-gallery' ),
				'startAutoplay'     => __( 'Start automatic slides', 'mai-cyber-gallery' ),
				'pauseFilmstrip'    => __( 'Pause automatic gallery movement', 'mai-cyber-gallery' ),
				'startFilmstrip'    => __( 'Start automatic gallery movement', 'mai-cyber-gallery' ),
				'filmstripPosition' => __( 'Image %1$d of %2$d', 'mai-cyber-gallery' ),
				'position'          => __( '%1$d of %2$d', 'mai-cyber-gallery' ),
				'status'            => __( '%1$s, %2$s', 'mai-cyber-gallery' ),
				'slide'             => __( 'Slide %1$d of %2$d', 'mai-cyber-gallery' ),
				'slideTitle'        => __( 'Slide %1$d of %2$d: %3$s', 'mai-cyber-gallery' ),
				'icons'             => [
					'close'    => mai_get_svg_icon( 'times', 'solid', $icon_atts ),
					'previous' => mai_get_svg_icon( 'angle-left', 'solid', $icon_atts ),
					'next'     => mai_get_svg_icon( 'angle-right', 'solid', $icon_atts ),
				],
			]
		);
	}

	/**
	 * Load block assets in the document head when queried content uses the block.
	 *
	 * Script-driven layouts should initialize before their CSS fallback can paint.
	 * The renderer still enqueues on demand for galleries created through the PHP API.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function enqueue_detected_block_assets(): void {
		global $wp_query;

		$posts        = [];
		$found_block  = false;
		$needs_script = false;

		if ( $wp_query instanceof WP_Query && is_array( $wp_query->posts ) ) {
			$posts = $wp_query->posts;
		}

		$queried = get_queried_object();
		if ( $queried instanceof WP_Post ) {
			array_unshift( $posts, $queried );
		}

		foreach ( $posts as $post ) {
			if ( ! $post instanceof WP_Post || ! has_block( 'acf/mai-cyber-gallery', $post ) ) {
				continue;
			}

			$found_block = true;

			if ( $this->blocks_need_script( parse_blocks( $post->post_content ) ) ) {
				$needs_script = true;
				break;
			}
		}

		if ( ! $found_block ) {
			return;
		}

		wp_enqueue_style( 'mai-cyber-gallery' );

		if ( $needs_script ) {
			wp_enqueue_script( 'mai-cyber-gallery' );
		}
	}

	/**
	 * Determine whether parsed Cyber blocks need progressive enhancement.
	 *
	 * @since 0.1.0
	 *
	 * @param list<array<string, mixed>> $blocks Parsed WordPress blocks.
	 *
	 * @return bool
	 */
	private function blocks_need_script( array $blocks ): bool {
		foreach ( (array) $blocks as $block ) {
			if ( ! is_array( $block ) ) {
				continue;
			}

			if ( 'acf/mai-cyber-gallery' === ( $block['blockName'] ?? '' ) ) {
				$data   = isset( $block['attrs']['data'] ) && is_array( $block['attrs']['data'] )
					? $block['attrs']['data']
					: [];
				$layout = ! empty( $data['layout'] ) && is_scalar( $data['layout'] )
					? sanitize_key( (string) $data['layout'] )
					: 'masonry';
				$click  = ! empty( $data['click_action'] ) && is_scalar( $data['click_action'] )
					? sanitize_key( (string) $data['click_action'] )
					: 'lightbox';

				if (
					'lightbox' === $click
					|| in_array( $layout, [ 'masonry', 'justified', 'tiled', 'filmstrip', 'slider' ], true )
				) {
					return true;
				}
			}

			if ( ! empty( $block['innerBlocks'] ) && $this->blocks_need_script( $block['innerBlocks'] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get a deterministic asset version.
	 *
	 * @since 0.1.0
	 *
	 * @param string $path Absolute asset path.
	 *
	 * @return string
	 */
	private function asset_version( string $path ): string {
		$modified = file_exists( $path ) ? (string) filemtime( $path ) : '0';
		return MAI_CYBER_GALLERY_VERSION . '.' . $modified;
	}

	/**
	 * Display a useful dependency notice.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function admin_notice(): void {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		$message = sprintf(
			/* translators: %s: Minimum Mai Engine version. */
			esc_html__( 'Mai Cyber Gallery requires Mai Engine %s or later with an active Mai theme.', 'mai-cyber-gallery' ),
			esc_html( MAI_CYBER_GALLERY_MIN_ENGINE_VERSION )
		);

		printf( '<div class="notice notice-warning"><p>%s</p></div>', esc_html( $message ) );
	}
}

/**
 * Get the main plugin instance.
 *
 * @since 0.1.0
 *
 * @return Mai_Cyber_Gallery_Plugin
 */
function mai_cyber_gallery_plugin(): Mai_Cyber_Gallery_Plugin {
	return Mai_Cyber_Gallery_Plugin::instance();
}

// Get Mai Cyber Gallery running.
mai_cyber_gallery_plugin();

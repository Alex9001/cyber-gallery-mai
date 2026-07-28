<?php
/**
 * Cyber Gallery for Mai Theme.
 *
 * Plugin Name:       Cyber Gallery for Mai Theme
 * Description:       Advanced masonry, tiled mosaic, content slider, and lightbox galleries powered by Mai Engine.
 * Version:           0.9.0
 * Requires at least: 6.5
 * Tested up to:      7.0
 * Requires PHP:      8.3
 * Requires Plugins:  mai-engine
 * Author:            CYBER BRAND
 * Author URI:        https://cyberbrand.net/
 * Text Domain:       mai-cyber-gallery
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package MaiCyberGallery
 * @copyright 2026 Aleksandr Oreshkin
 * @license GPL-2.0-or-later
 */

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

/**
 * Main Cyber Gallery for Mai Theme plugin class.
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
		define( 'MAI_CYBER_GALLERY_VERSION', '0.9.0' );
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
		add_filter( 'litespeed_optm_js_defer_exc', [ $this, 'litespeed_js_excludes' ] );
		add_filter( 'litespeed_optm_gm_js_exc', [ $this, 'litespeed_js_excludes' ] );
		add_filter( 'litespeed_ucss_whitelist', [ $this, 'litespeed_ucss_whitelist' ] );
		add_action( 'edit_attachment', [ $this, 'purge_attachment_cache' ] );
		add_action( 'delete_attachment', [ $this, 'purge_attachment_cache' ] );
		add_action( 'added_post_meta', [ $this, 'purge_attachment_meta_cache' ], 10, 4 );
		add_action( 'updated_post_meta', [ $this, 'purge_attachment_meta_cache' ], 10, 4 );
		add_action( 'deleted_post_meta', [ $this, 'purge_deleted_attachment_meta_cache' ], 10, 4 );

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
		$debug              = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG;
		$suffix             = $debug ? '' : '.min';
		$style_file         = "assets/css/mai-cyber-gallery{$suffix}.css";
		$editor_style_file  = "assets/css/mai-cyber-gallery-editor{$suffix}.css";
		$script_file        = "assets/js/mai-cyber-gallery{$suffix}.js";
		$packing_file       = "assets/js/mai-cyber-gallery-packing{$suffix}.js";
		$lightbox_file      = "assets/js/mai-cyber-gallery-lightbox{$suffix}.js";
		$editor_script_file = "assets/js/mai-cyber-gallery-editor{$suffix}.js";
		$style_path         = MAI_CYBER_GALLERY_PLUGIN_DIR . $style_file;
		$editor_style_path  = MAI_CYBER_GALLERY_PLUGIN_DIR . $editor_style_file;
		$script_path        = MAI_CYBER_GALLERY_PLUGIN_DIR . $script_file;
		$packing_path       = MAI_CYBER_GALLERY_PLUGIN_DIR . $packing_file;
		$lightbox_path      = MAI_CYBER_GALLERY_PLUGIN_DIR . $lightbox_file;
		$editor_script_path = MAI_CYBER_GALLERY_PLUGIN_DIR . $editor_script_file;
		$icon_atts          = [
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

		wp_register_style(
			'mai-cyber-gallery-editor',
			MAI_CYBER_GALLERY_PLUGIN_URL . $editor_style_file,
			[ 'mai-cyber-gallery' ],
			$this->asset_version( $editor_style_path )
		);
		wp_style_add_data( 'mai-cyber-gallery-editor', 'path', $editor_style_path );

		wp_register_script(
			'mai-cyber-gallery',
			MAI_CYBER_GALLERY_PLUGIN_URL . $script_file,
			[],
			$this->asset_version( $script_path ),
			[
				'in_footer' => false,
				'strategy'  => 'defer',
			]
		);
		wp_localize_script(
			'mai-cyber-gallery',
			'MaiCyberGallerySettings',
			[
				'pauseAutoplay'     => __( 'Pause automatic slides', 'mai-cyber-gallery' ),
				'startAutoplay'     => __( 'Start automatic slides', 'mai-cyber-gallery' ),
				'pauseFilmstrip'    => __( 'Pause automatic gallery movement', 'mai-cyber-gallery' ),
				'startFilmstrip'    => __( 'Start automatic gallery movement', 'mai-cyber-gallery' ),
				'filmstripPosition' => __( 'Image %1$d of %2$d', 'mai-cyber-gallery' ),
				'slide'             => __( 'Slide %1$d of %2$d', 'mai-cyber-gallery' ),
				'slideTitle'        => __( 'Slide %1$d of %2$d: %3$s', 'mai-cyber-gallery' ),
			]
		);

		wp_register_script(
			'mai-cyber-gallery-packing',
			MAI_CYBER_GALLERY_PLUGIN_URL . $packing_file,
			[],
			$this->asset_version( $packing_path ),
			[
				'in_footer' => false,
				'strategy'  => 'defer',
			]
		);

		wp_register_script(
			'mai-cyber-gallery-lightbox',
			MAI_CYBER_GALLERY_PLUGIN_URL . $lightbox_file,
			[],
			$this->asset_version( $lightbox_path ),
			[
				'in_footer' => false,
				'strategy'  => 'defer',
			]
		);
		wp_localize_script(
			'mai-cyber-gallery-lightbox',
			'MaiCyberGalleryLightboxSettings',
			[
				'dialogLabel' => __( 'Image viewer', 'mai-cyber-gallery' ),
				'close'       => __( 'Close image viewer', 'mai-cyber-gallery' ),
				'next'        => __( 'Next image', 'mai-cyber-gallery' ),
				'previous'    => __( 'Previous image', 'mai-cyber-gallery' ),
				'position'    => __( '%1$d of %2$d', 'mai-cyber-gallery' ),
				'status'      => __( '%1$s, %2$s', 'mai-cyber-gallery' ),
				'icons'       => [
					'close'    => mai_get_svg_icon( 'times', 'solid', $icon_atts ),
					'previous' => mai_get_svg_icon( 'angle-left', 'solid', $icon_atts ),
					'next'     => mai_get_svg_icon( 'angle-right', 'solid', $icon_atts ),
				],
			]
		);

		wp_register_script(
			'mai-cyber-gallery-editor',
			MAI_CYBER_GALLERY_PLUGIN_URL . $editor_script_file,
			[
				'mai-cyber-gallery',
				'mai-cyber-gallery-packing',
				'acf-input',
				'wp-blocks',
				'wp-hooks',
				'wp-data',
			],
			$this->asset_version( $editor_script_path ),
			true
		);
	}

	/**
	 * Load block assets early when queried content uses the block.
	 *
	 * Front-end scripts use the native defer strategy. The renderer still
	 * enqueues on demand for galleries created through the PHP API.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function enqueue_detected_block_assets(): void {
		global $wp_query;

		$posts       = [];
		$found_block = false;
		$asset_needs = [
			'packing'  => false,
			'carousel' => false,
			'lightbox' => false,
		];

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
			$asset_needs = $this->get_block_asset_needs(
				parse_blocks( $post->post_content ),
				$asset_needs
			);

			if ( ! in_array( false, $asset_needs, true ) ) {
				break;
			}
		}

		if ( ! $found_block ) {
			return;
		}

		wp_enqueue_style( 'mai-cyber-gallery' );

		if ( $asset_needs['packing'] ) {
			wp_enqueue_script( 'mai-cyber-gallery-packing' );
		}

		if ( $asset_needs['carousel'] ) {
			wp_enqueue_script( 'mai-cyber-gallery' );
		}

		if ( $asset_needs['lightbox'] ) {
			wp_enqueue_script( 'mai-cyber-gallery-lightbox' );
		}
	}

	/**
	 * Determine which progressive-enhancement assets parsed Cyber blocks need.
	 *
	 * @since 0.1.0
	 *
	 * @param list<array<string, mixed>> $blocks Parsed WordPress blocks.
	 * @param array{packing: bool, carousel: bool, lightbox: bool} $needs Existing requirements.
	 *
	 * @return array{packing: bool, carousel: bool, lightbox: bool}
	 */
	private function get_block_asset_needs(
		array $blocks,
		array $needs = [
			'packing'  => false,
			'carousel' => false,
			'lightbox' => false,
		]
	): array {
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

				if ( 'lightbox' === $click ) {
					$needs['lightbox'] = true;
				}

				if ( in_array( $layout, [ 'masonry', 'justified', 'tiled' ], true ) ) {
					$needs['packing'] = true;
				}

				if ( in_array( $layout, [ 'filmstrip', 'slider' ], true ) ) {
					$needs['carousel'] = true;
				}
			}

			if ( ! empty( $block['innerBlocks'] ) ) {
				$needs = $this->get_block_asset_needs( $block['innerBlocks'], $needs );
			}

			if ( ! in_array( false, $needs, true ) ) {
				break;
			}
		}

		return $needs;
	}

	/**
	 * Keep the gallery's natively deferred scripts out of LiteSpeed's
	 * interaction-delayed and Guest Mode rewrites.
	 *
	 * @since 0.8.0
	 *
	 * @param mixed $excludes Existing exclusion list.
	 *
	 * @return list<string>
	 */
	public function litespeed_js_excludes( $excludes ): array {
		if ( is_string( $excludes ) ) {
			$excludes = preg_split( '/\R+/', $excludes ) ?: [];
		} elseif ( ! is_array( $excludes ) ) {
			$excludes = [];
		}

		$excludes[] = '/mai-cyber-gallery/assets/js/';

		return array_values( array_unique( array_filter( $excludes, 'is_string' ) ) );
	}

	/**
	 * Preserve selectors that appear only after gallery JavaScript runs.
	 *
	 * @since 0.8.0
	 *
	 * @param mixed $selectors Existing LiteSpeed UCSS selector allowlist.
	 *
	 * @return list<string>
	 */
	public function litespeed_ucss_whitelist( $selectors ): array {
		if ( is_string( $selectors ) ) {
			$selectors = preg_split( '/\R+/', $selectors ) ?: [];
		} elseif ( ! is_array( $selectors ) ) {
			$selectors = [];
		}

		$selectors = array_merge(
			$selectors,
			[
				'.is-cyber-masonry-ready',
				'.is-cyber-justified-ready',
				'.is-cyber-tiled-ready',
				'.is-cyber-filmstrip-ready',
				'.is-cyber-filmstrip-scrollable',
				'.is-cyber-filmstrip-dragging',
				'.is-cyber-filmstrip-gliding',
				'.is-cyber-filmstrip-settling',
				'.is-cyber-slider-ready',
				'.is-cyber-slide-active',
				'.is-cyber-slider-transitioning',
				'.mai-cyber-gallery-slider-transition-layer',
				'.is-cyber-slide-outgoing',
				'.is-cyber-slide-incoming',
				'.mai-cyber-gallery-lightbox-open',
				'.mai-cyber-gallery-dialog',
				'.mai-cyber-gallery-dialog-inner',
				'.mai-cyber-gallery-dialog-stage',
				'.mai-cyber-gallery-dialog-image',
				'.mai-cyber-gallery-dialog-meta',
				'.mai-cyber-gallery-dialog-caption',
				'.mai-cyber-gallery-dialog-position',
				'.mai-cyber-gallery-dialog-status',
				'.mai-cyber-gallery-dialog-button',
				'.mai-cyber-gallery-dialog-close',
				'.mai-cyber-gallery-dialog-previous',
				'.mai-cyber-gallery-dialog-next',
			]
		);

		return array_values( array_unique( array_filter( $selectors, 'is_string' ) ) );
	}

	/**
	 * Purge pages tagged with a changed attachment.
	 *
	 * @since 0.8.0
	 *
	 * @param int $attachment_id Attachment ID.
	 *
	 * @return void
	 */
	public function purge_attachment_cache( int $attachment_id ): void {
		if ( 0 < $attachment_id ) {
			do_action( 'litespeed_purge', $this->attachment_cache_tag( $attachment_id ) );
		}
	}

	/**
	 * Purge gallery pages when rendered attachment data changes directly.
	 *
	 * @since 0.8.0
	 *
	 * @param int|array<int, int> $meta_id       Metadata row ID. An array is
	 *                                           accepted defensively during
	 *                                           plugin-updater cleanup.
	 * @param int                 $attachment_id Attachment ID.
	 * @param string              $meta_key      Metadata key.
	 * @param mixed               $meta_value    Metadata value.
	 *
	 * @return void
	 */
	public function purge_attachment_meta_cache(
		int|array $meta_id,
		int $attachment_id,
		string $meta_key,
		$meta_value
	): void {
		unset( $meta_id, $meta_value );
		$this->maybe_purge_attachment_meta_cache( $attachment_id, $meta_key );
	}

	/**
	 * Purge gallery pages after attachment metadata is deleted.
	 *
	 * WordPress passes a list of metadata IDs to deleted_post_meta, unlike the
	 * single metadata ID passed to added_post_meta and updated_post_meta.
	 *
	 * @since 0.8.0
	 *
	 * @param array<int, int> $meta_ids      Deleted metadata row IDs.
	 * @param int             $attachment_id Attachment ID.
	 * @param string          $meta_key      Metadata key.
	 * @param mixed           $meta_value    Metadata value.
	 *
	 * @return void
	 */
	public function purge_deleted_attachment_meta_cache(
		array $meta_ids,
		int $attachment_id,
		string $meta_key,
		$meta_value
	): void {
		unset( $meta_ids, $meta_value );
		$this->maybe_purge_attachment_meta_cache( $attachment_id, $meta_key );
	}

	/**
	 * Purge attachment-tagged pages for metadata used in gallery output.
	 *
	 * @since 0.8.0
	 *
	 * @param int    $attachment_id Attachment ID.
	 * @param string $meta_key      Metadata key.
	 *
	 * @return void
	 */
	private function maybe_purge_attachment_meta_cache(
		int $attachment_id,
		string $meta_key
	): void {
		if (
			in_array( $meta_key, [ '_wp_attachment_image_alt', '_wp_attachment_metadata' ], true )
			&& 'attachment' === get_post_type( $attachment_id )
		) {
			$this->purge_attachment_cache( $attachment_id );
		}
	}

	/**
	 * Build the shared cache tag for an attachment.
	 *
	 * @since 0.8.0
	 *
	 * @param int $attachment_id Attachment ID.
	 *
	 * @return string
	 */
	public function attachment_cache_tag( int $attachment_id ): string {
		return 'MCG.A.' . absint( $attachment_id );
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
			esc_html__( 'Cyber Gallery for Mai Theme requires Mai Engine %s or later with an active Mai theme.', 'mai-cyber-gallery' ),
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

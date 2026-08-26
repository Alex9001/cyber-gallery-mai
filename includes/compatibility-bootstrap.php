<?php
/**
 * Early compatibility aliases for Cyber Gallery for Mai Theme 1.x bootstrap integrations.
 *
 * This file is loaded only after every active plugin file has loaded and the
 * canonical bootstrap has confirmed that the former plugin runtime is absent.
 * It deliberately has no Mai Engine or theme dependency.
 *
 * @package CyberGalleryMai
 * @since 2.0.0
 */

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

if ( ! class_exists( 'Mai_Cyber_Gallery_Plugin', false ) ) {
	class_alias( Cyber_Gallery_Mai_Plugin::class, 'Mai_Cyber_Gallery_Plugin' );
}

// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- Required 1.x compatibility alias.
defined( 'MAI_CYBER_GALLERY_VERSION' ) || define( 'MAI_CYBER_GALLERY_VERSION', CYBER_GALLERY_MAI_VERSION );
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- Required 1.x compatibility alias.
defined( 'MAI_CYBER_GALLERY_MIN_ENGINE_VERSION' ) || define( 'MAI_CYBER_GALLERY_MIN_ENGINE_VERSION', CYBER_GALLERY_MAI_MIN_ENGINE_VERSION );
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- Required 1.x compatibility alias.
defined( 'MAI_CYBER_GALLERY_PLUGIN_FILE' ) || define( 'MAI_CYBER_GALLERY_PLUGIN_FILE', CYBER_GALLERY_MAI_PLUGIN_FILE );
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- Required 1.x compatibility alias.
defined( 'MAI_CYBER_GALLERY_PLUGIN_DIR' ) || define( 'MAI_CYBER_GALLERY_PLUGIN_DIR', CYBER_GALLERY_MAI_PLUGIN_DIR );
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- Required 1.x compatibility alias.
defined( 'MAI_CYBER_GALLERY_PLUGIN_URL' ) || define( 'MAI_CYBER_GALLERY_PLUGIN_URL', CYBER_GALLERY_MAI_PLUGIN_URL );
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- Required 1.x compatibility alias.
defined( 'MAI_CYBER_GALLERY_BASENAME' ) || define( 'MAI_CYBER_GALLERY_BASENAME', CYBER_GALLERY_MAI_BASENAME );

if ( ! function_exists( 'mai_cyber_gallery_plugin' ) ) {
	/**
	 * Get the plugin instance using the 1.x function name.
	 *
	 * @since 0.1.0
	 * @deprecated 2.0.0 Use cyber_gallery_mai_plugin().
	 *
	 * @return Cyber_Gallery_Mai_Plugin
	 */
	function mai_cyber_gallery_plugin(): Cyber_Gallery_Mai_Plugin {
		return cyber_gallery_mai_plugin();
	}
}

<?php
/**
 * Cyber Gallery for Mai Theme.
 *
 * Plugin Name:       Cyber Gallery for Mai Theme
 * Description:       Advanced galleries with masonry, tiled mosaic, content slider, lightbox, and Before / After comparison layouts powered by Mai Engine.
 * Version:           2.0.0
 * Requires at least: 7.1
 * Tested up to:      7.1
 * Requires PHP:      8.3
 * Requires Plugins:  mai-engine
 * Update URI:         https://github.com/Alex9001/cyber-gallery-mai
 * Author:            CYBER BRAND
 * Author URI:        https://cyberbrand.net/
 * Text Domain:       cyber-gallery-mai
 * Domain Path:       /languages
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package CyberGalleryMai
 * @copyright 2026 Aleksandr Oreshkin
 * @license GPL-2.0-or-later
 */

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

define( 'CYBER_GALLERY_MAI_PLUGIN_FILE', __FILE__ );
define( 'CYBER_GALLERY_MAI_VERSION', '2.0.0' );
define( 'CYBER_GALLERY_MAI_MIN_ENGINE_VERSION', '2.40.0' );
define( 'CYBER_GALLERY_MAI_PLUGIN_DIR', plugin_dir_path( CYBER_GALLERY_MAI_PLUGIN_FILE ) );
define( 'CYBER_GALLERY_MAI_PLUGIN_URL', plugin_dir_url( CYBER_GALLERY_MAI_PLUGIN_FILE ) );
define( 'CYBER_GALLERY_MAI_BASENAME', plugin_basename( CYBER_GALLERY_MAI_PLUGIN_FILE ) );

require_once __DIR__ . '/classes/class-cyber-gallery-mai-plugin.php';

/**
 * Get the main plugin instance.
 *
 * @since 2.0.0
 *
 * @return Cyber_Gallery_Mai_Plugin
 */
function cyber_gallery_mai_plugin(): Cyber_Gallery_Mai_Plugin {
	return Cyber_Gallery_Mai_Plugin::instance();
}

/**
 * Replace the former plugin basename when the canonical plugin is activated.
 *
 * WordPress treats a changed directory/main-file pair as a different plugin.
 * Silently deactivating the former basename prevents both runtimes from being
 * loaded together after a manual upgrade. Saved content is not rewritten.
 *
 * @since 2.0.0
 *
 * @param bool $network_wide Whether the plugin is being network activated.
 *
 * @return void
 */
function cyber_gallery_mai_activate( bool $network_wide ): void {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';

	deactivate_plugins( 'mai-cyber-gallery/mai-cyber-gallery.php', true, $network_wide );
}
register_activation_hook( CYBER_GALLERY_MAI_PLUGIN_FILE, 'cyber_gallery_mai_activate' );

/**
 * Start the canonical runtime after every active plugin has loaded.
 *
 * Delaying startup avoids symbol collisions if the former plugin directory is
 * still active. The normal activation path deactivates that basename first;
 * this guard handles manually edited active-plugin options safely.
 *
 * @since 2.0.0
 *
 * @return void
 */
function cyber_gallery_mai_boot(): void {
	static $notice_registered = false;

	if ( ! cyber_gallery_mai_plugin()->boot() && ! $notice_registered ) {
		add_action( 'admin_notices', 'cyber_gallery_mai_legacy_conflict_notice' );
		$notice_registered = true;
	}
}
add_action( 'plugins_loaded', 'cyber_gallery_mai_boot', PHP_INT_MIN );

/**
 * Explain why the canonical runtime is paused when both plugin basenames load.
 *
 * @since 2.0.0
 *
 * @return void
 */
function cyber_gallery_mai_legacy_conflict_notice(): void {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	$message = esc_html__(
		'Cyber Gallery for Mai Theme detected the former Mai Cyber Gallery plugin still active. Deactivate the former plugin, then reactivate Cyber Gallery for Mai Theme; existing galleries will remain available.',
		'cyber-gallery-mai'
	);

	printf( '<div class="notice notice-error"><p>%s</p></div>', esc_html( $message ) );
}

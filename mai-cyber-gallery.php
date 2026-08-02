<?php
/**
 * Cyber Gallery for Mai Theme.
 *
 * Plugin Name:       Cyber Gallery for Mai Theme
 * Description:       Advanced galleries with masonry, tiled mosaic, content slider, lightbox, and Before / After comparison layouts powered by Mai Engine.
 * Version:           1.0.0
 * Requires at least: 6.5
 * Tested up to:      7.0
 * Requires PHP:      8.3
 * Requires Plugins:  mai-engine
 * Update URI:         https://github.com/Alex9001/mai-cyber-gallery
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

define( 'MAI_CYBER_GALLERY_PLUGIN_FILE', __FILE__ );

require_once __DIR__ . '/classes/class-mai-cyber-gallery-plugin.php';

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

// Get Cyber Gallery for Mai Theme running.
mai_cyber_gallery_plugin();

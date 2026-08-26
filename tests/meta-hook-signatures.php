<?php
/**
 * Regression coverage for WordPress metadata hook argument shapes.
 *
 * @package CyberGalleryMai
 */

declare( strict_types=1 );

define( 'ABSPATH', __DIR__ . '/' );

$cyber_gallery_mai_test_hooks  = array();
$cyber_gallery_mai_test_purges = array();

/**
 * Minimal WordPress action registration test double.
 *
 * @param string   $hook          Hook name.
 * @param callable $callback      Callback.
 * @param int      $priority      Priority.
 * @param int      $accepted_args Accepted arguments.
 *
 * @return void
 */
function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ): void {
	global $cyber_gallery_mai_test_hooks;

	$cyber_gallery_mai_test_hooks[ $hook ][] = array(
		'callback'      => $callback,
		'priority'      => $priority,
		'accepted_args' => $accepted_args,
	);
}

/**
 * Ignore activation-hook registration in the standalone test.
 *
 * @return void
 */
function register_activation_hook(): void {}

/**
 * Minimal WordPress filter registration test double.
 *
 * @param string   $hook          Hook name.
 * @param callable $callback      Callback.
 * @param int      $priority      Priority.
 * @param int      $accepted_args Accepted arguments.
 *
 * @return void
 */
function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ): void {
	add_action( $hook, $callback, $priority, $accepted_args );
}

/**
 * Capture LiteSpeed purge actions.
 *
 * @param string $hook Hook name.
 * @param mixed  ...$args Arguments.
 *
 * @return void
 */
function do_action( $hook, ...$args ): void {
	global $cyber_gallery_mai_test_purges;

	if ( 'litespeed_purge' === $hook ) {
		$cyber_gallery_mai_test_purges[] = $args[0] ?? null;
	}
}

/**
 * Return a plugin directory path.
 *
 * @param string $file Plugin file.
 *
 * @return string
 */
function plugin_dir_path( $file ): string {
	return dirname( $file ) . '/';
}

/**
 * Return a placeholder plugin URL.
 *
 * @param string $file Plugin file.
 *
 * @return string
 */
function plugin_dir_url( $file ): string {
	unset( $file );
	return 'https://example.test/wp-content/plugins/cyber-gallery-mai/';
}

/**
 * Return a plugin basename.
 *
 * @param string $file Plugin file.
 *
 * @return string
 */
function plugin_basename( $file ): string {
	return basename( dirname( $file ) ) . '/' . basename( $file );
}

/**
 * Return the attachment post type.
 *
 * @param int $post_id Post ID.
 *
 * @return string
 */
function get_post_type( $post_id ): string {
	unset( $post_id );
	return 'attachment';
}

/**
 * Minimal absolute-integer implementation.
 *
 * @param mixed $value Candidate.
 *
 * @return int
 */
function absint( $value ): int {
	return abs( (int) $value );
}

require dirname( __DIR__ ) . '/cyber-gallery-mai.php';

$plugin                             = cyber_gallery_mai_plugin();
$cyber_gallery_mai_test_plugin_root = dirname( __DIR__ ) . '/';
$source                             = file_get_contents( $cyber_gallery_mai_test_plugin_root . 'classes/class-cyber-gallery-mai-plugin.php' );

if (
	1 !== count( $cyber_gallery_mai_test_hooks['plugins_loaded'] ?? array() )
	|| PHP_INT_MIN !== ( $cyber_gallery_mai_test_hooks['plugins_loaded'][0]['priority'] ?? null )
	|| isset( $cyber_gallery_mai_test_hooks['after_setup_theme'] )
) {
	throw new RuntimeException( 'The canonical singleton must remain inert until the earliest plugins_loaded bootstrap.' );
}

cyber_gallery_mai_boot();
cyber_gallery_mai_boot();

if (
	1 !== count( $cyber_gallery_mai_test_hooks['after_setup_theme'] ?? array() )
	|| 1 !== ( $cyber_gallery_mai_test_hooks['after_setup_theme'][0]['priority'] ?? null )
	|| ! class_exists( 'Mai_Cyber_Gallery_Plugin', false )
	|| ! function_exists( 'mai_cyber_gallery_plugin' )
	|| mai_cyber_gallery_plugin() !== $plugin
	|| ! defined( 'MAI_CYBER_GALLERY_VERSION' )
	|| CYBER_GALLERY_MAI_VERSION !== MAI_CYBER_GALLERY_VERSION
) {
	throw new RuntimeException( 'Conflict-safe bootstrap aliases were not loaded once before normal callbacks.' );
}

$bootstrap_reflection = new ReflectionClass( $plugin );
$legacy_detector      = $bootstrap_reflection->getMethod( 'has_external_legacy_runtime' );

if ( $legacy_detector->invoke( $plugin ) ) {
	throw new RuntimeException( 'The defensive legacy detector mistook canonical compatibility aliases for an external runtime.' );
}

if (
	CYBER_GALLERY_MAI_PLUGIN_FILE !== $cyber_gallery_mai_test_plugin_root . 'cyber-gallery-mai.php'
	|| CYBER_GALLERY_MAI_PLUGIN_DIR !== $cyber_gallery_mai_test_plugin_root
) {
	throw new RuntimeException( 'Bootstrap constants must resolve to the plugin root, not the classes directory.' );
}

if (
	false === $source
	|| ! str_contains(
		$source,
		"add_action( 'deleted_post_meta', array( \$this, 'purge_deleted_attachment_meta_cache' )"
	)
) {
	throw new RuntimeException( 'deleted_post_meta must use its array-aware callback.' );
}

$plugin->purge_attachment_meta_cache(
	101,
	123,
	'_wp_attachment_image_alt',
	'Alt text'
);
$plugin->purge_attachment_meta_cache(
	array( 102 ),
	123,
	'_wp_attachment_image_alt',
	'Updated alt text'
);
$plugin->purge_deleted_attachment_meta_cache(
	array( 101 ),
	123,
	'_wp_attachment_metadata',
	array()
);

if (
	array(
		'CGM.A.123',
		'MCG.A.123',
		'CGM.A.123',
		'MCG.A.123',
		'CGM.A.123',
		'MCG.A.123',
	) !== $cyber_gallery_mai_test_purges
) {
	throw new RuntimeException( 'Metadata callbacks did not emit the expected cache tags.' );
}

echo "Verified add/update and delete metadata hook signatures.\n";

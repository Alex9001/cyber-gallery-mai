<?php
/**
 * Regression coverage for WordPress metadata hook argument shapes.
 *
 * @package MaiCyberGallery
 */

declare( strict_types=1 );

define( 'ABSPATH', __DIR__ . '/' );

$mai_cyber_gallery_test_hooks  = array();
$mai_cyber_gallery_test_purges = array();

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
	global $mai_cyber_gallery_test_hooks;

	$mai_cyber_gallery_test_hooks[ $hook ] = array(
		'callback'      => $callback,
		'priority'      => $priority,
		'accepted_args' => $accepted_args,
	);
}

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
	global $mai_cyber_gallery_test_purges;

	if ( 'litespeed_purge' === $hook ) {
		$mai_cyber_gallery_test_purges[] = $args[0] ?? null;
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
	return 'https://example.test/wp-content/plugins/mai-cyber-gallery/';
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

require dirname( __DIR__ ) . '/mai-cyber-gallery.php';

$plugin                             = mai_cyber_gallery_plugin();
$mai_cyber_gallery_test_plugin_root = dirname( __DIR__ ) . '/';
$source                             = file_get_contents( $mai_cyber_gallery_test_plugin_root . 'classes/class-mai-cyber-gallery-plugin.php' );

if (
	MAI_CYBER_GALLERY_PLUGIN_FILE !== $mai_cyber_gallery_test_plugin_root . 'mai-cyber-gallery.php'
	|| MAI_CYBER_GALLERY_PLUGIN_DIR !== $mai_cyber_gallery_test_plugin_root
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

if ( array( 'MCG.A.123', 'MCG.A.123', 'MCG.A.123' ) !== $mai_cyber_gallery_test_purges ) {
	throw new RuntimeException( 'Metadata callbacks did not emit the expected cache tags.' );
}

echo "Verified add/update and delete metadata hook signatures.\n";

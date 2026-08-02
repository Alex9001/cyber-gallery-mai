<?php
/**
 * Regression coverage for parsed-block asset detection.
 *
 * @package MaiCyberGallery
 */

declare( strict_types=1 );

define( 'ABSPATH', __DIR__ . '/' );

/**
 * Minimal post test double.
 */
class WP_Post {
	/**
	 * Serialized parsed blocks for the test parser.
	 *
	 * @var string
	 */
	public string $post_content;

	/**
	 * Construct a reusable block.
	 *
	 * @param array<int, array<string, mixed>> $blocks Parsed block structures.
	 */
	public function __construct( array $blocks ) {
		$this->post_content = (string) wp_json_encode( $blocks );
	}
}

/**
 * Minimal query test double.
 */
class WP_Query {
	/**
	 * Posts returned by the query.
	 *
	 * @var list<WP_Post>
	 */
	public array $posts;

	/**
	 * Construct a query fixture.
	 *
	 * @param array<int,WP_Post> $posts Query posts.
	 */
	public function __construct( array $posts ) {
		$this->posts = $posts;
	}
}

$mai_cyber_gallery_test_posts              = array();
$mai_cyber_gallery_test_styles             = array();
$mai_cyber_gallery_test_scripts            = array();
$mai_cyber_gallery_test_registered_scripts = array();

/**
 * Minimal WordPress action registration test double.
 *
 * @return void
 */
function add_action(): void {}

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
 * @return string
 */
function plugin_dir_url(): string {
	return 'https://example.test/wp-content/plugins/mai-cyber-gallery/';
}

/**
 * Return the plugin basename.
 *
 * @param string $file Plugin file.
 *
 * @return string
 */
function plugin_basename( $file ): string {
	return basename( dirname( $file ) ) . '/' . basename( $file );
}

/**
 * Minimal JSON encoder used by reusable block fixtures.
 *
 * @param mixed $value Value to encode.
 *
 * @return string|false
 */
function wp_json_encode( $value ) {
	return json_encode( $value );
}

/**
 * Decode reusable content into parsed block structures.
 *
 * @param string $content Encoded blocks.
 *
 * @return array<int, array<string, mixed>>
 */
function parse_blocks( $content ): array {
	$blocks = json_decode( $content, true );
	return is_array( $blocks ) ? $blocks : array();
}

/**
 * Fetch a reusable post fixture.
 *
 * @param int $post_id Post ID.
 *
 * @return WP_Post|null
 */
function get_post( $post_id ): ?WP_Post {
	global $mai_cyber_gallery_test_posts;
	return $mai_cyber_gallery_test_posts[ $post_id ] ?? null;
}

/**
 * Return no additional queried object.
 *
 * @return null
 */
function get_queried_object() {
	return null;
}

/**
 * Detect a serialized block marker in a post fixture.
 *
 * @param string  $block_name Block name.
 * @param WP_Post $post       Post fixture.
 *
 * @return bool
 */
function has_block( $block_name, $post ): bool {
	$encoded_name = str_replace( '/', '\/', $block_name );
	return str_contains( $post->post_content, sprintf( '"blockName":"%s"', $encoded_name ) );
}

/**
 * Capture an enqueued style.
 *
 * @param string $handle Style handle.
 *
 * @return void
 */
function wp_enqueue_style( $handle ): void {
	global $mai_cyber_gallery_test_styles;
	$mai_cyber_gallery_test_styles[] = $handle;
}

/**
 * Capture an enqueued script.
 *
 * @param string $handle Script handle.
 *
 * @return void
 */
function wp_enqueue_script( $handle ): void {
	global $mai_cyber_gallery_test_scripts;
	$mai_cyber_gallery_test_scripts[] = $handle;
}

/**
 * Ignore style registration details in this script-focused test.
 *
 * @return void
 */
function wp_register_style(): void {}

/**
 * Ignore style metadata in this script-focused test.
 *
 * @return void
 */
function wp_style_add_data(): void {}

/**
 * Capture registered script placement.
 *
 * @param string                   $handle Script handle.
 * @param string                   $src Script source.
 * @param array<int,string>        $deps Script dependencies.
 * @param string|false|null        $version Script version.
 * @param array<string,mixed>|bool $args Registration arguments.
 *
 * @return void
 */
function wp_register_script( $handle, $src, $deps = array(), $version = false, $args = array() ): void {
	global $mai_cyber_gallery_test_registered_scripts;
	unset( $src, $deps, $version );

	$mai_cyber_gallery_test_registered_scripts[ $handle ] = $args;
}

/**
 * Ignore localized strings after registration.
 *
 * @return void
 */
function wp_localize_script(): void {}

/**
 * Return untranslated test strings.
 *
 * @param string $text Text.
 *
 * @return string
 */
function __( $text ): string {
	return $text;
}

/**
 * Return placeholder icon markup.
 *
 * @return string
 */
function mai_get_svg_icon(): string {
	return '<svg></svg>';
}

/**
 * Minimal key sanitizer.
 *
 * @param mixed $key Candidate key.
 *
 * @return string
 */
function sanitize_key( $key ): string {
	return preg_replace( '/[^a-z0-9_-]/', '', strtolower( (string) $key ) ) ?? '';
}

/**
 * Minimal absolute-integer implementation.
 *
 * @param mixed $value Candidate value.
 *
 * @return int
 */
function absint( $value ): int {
	return abs( (int) $value );
}

require dirname( __DIR__ ) . '/mai-cyber-gallery.php';

$reflection = new ReflectionClass( Mai_Cyber_Gallery_Plugin::class );
$plugin     = mai_cyber_gallery_plugin();
$method     = $reflection->getMethod( 'get_block_asset_needs' );

$method->setAccessible( true );
$plugin->register_assets();

foreach (
	array(
		'mai-cyber-gallery',
		'mai-cyber-gallery-packing',
		'mai-cyber-gallery-lightbox',
		'mai-cyber-gallery-comparison',
	) as $runtime_handle
) {
	$registration = $mai_cyber_gallery_test_registered_scripts[ $runtime_handle ] ?? null;

	if ( ! is_array( $registration ) || empty( $registration['in_footer'] ) ) {
		throw new RuntimeException( sprintf( '%s was not registered for safe late footer output.', $runtime_handle ) );
	}
}

/**
 * Invoke the private detector.
 *
 * @param ReflectionMethod                 $method Detector method.
 * @param Mai_Cyber_Gallery_Plugin         $plugin Plugin instance.
 * @param array<int, array<string, mixed>> $blocks Parsed blocks.
 *
 * @return array{gallery: bool, packing: bool, carousel: bool, lightbox: bool, comparison: bool}
 */
function cyber_detect_assets( $method, $plugin, array $blocks ): array {
	return $method->invoke( $plugin, $blocks );
}

$gallery_block = static function ( string $layout, string $click = 'none' ): array {
	return array(
		'blockName'   => 'acf/mai-cyber-gallery',
		'attrs'       => array(
			'data' => array(
				'layout'       => $layout,
				'click_action' => $click,
			),
		),
		'innerBlocks' => array(),
	);
};

$plain_grid = cyber_detect_assets( $method, $plugin, array( $gallery_block( 'grid' ) ) );
if (
	! $plain_grid['gallery']
	|| $plain_grid['packing']
	|| $plain_grid['carousel']
	|| $plain_grid['lightbox']
	|| $plain_grid['comparison']
) {
	throw new RuntimeException( 'A plain Grid did not request base gallery CSS alone.' );
}

$wp_query = new WP_Query( array( new WP_Post( array( $gallery_block( 'grid' ) ) ) ) );
$plugin->enqueue_detected_block_assets();
if (
	array( 'mai-cyber-gallery' ) !== $mai_cyber_gallery_test_styles
	|| array() !== $mai_cyber_gallery_test_scripts
) {
	throw new RuntimeException( 'A plain Grid did not enqueue base CSS without behavior scripts.' );
}

$slider = cyber_detect_assets( $method, $plugin, array( $gallery_block( 'slider', 'lightbox' ) ) );
if (
	! $slider['gallery']
	|| ! $slider['carousel']
	|| $slider['lightbox']
	|| $slider['packing']
	|| $slider['comparison']
) {
	throw new RuntimeException( 'Slider asset detection did not suppress its inapplicable lightbox.' );
}

$comparison = cyber_detect_assets( $method, $plugin, array( $gallery_block( 'comparison', 'lightbox' ) ) );
if (
	! $comparison['gallery']
	|| ! $comparison['comparison']
	|| $comparison['lightbox']
	|| $comparison['packing']
	|| $comparison['carousel']
) {
	throw new RuntimeException( 'Comparison asset detection was not strict and layout-specific.' );
}

$combined = cyber_detect_assets(
	$method,
	$plugin,
	array(
		$gallery_block( 'masonry', 'lightbox' ),
		$gallery_block( 'filmstrip' ),
	)
);
if (
	! $combined['gallery']
	|| ! $combined['packing']
	|| ! $combined['carousel']
	|| ! $combined['lightbox']
	|| $combined['comparison']
) {
	throw new RuntimeException( 'Packing, carousel, and lightbox requirements were not accumulated.' );
}

$nested = cyber_detect_assets(
	$method,
	$plugin,
	array(
		array(
			'blockName'   => 'core/group',
			'attrs'       => array(),
			'innerBlocks' => array( $gallery_block( 'tiled' ) ),
		),
	)
);
if ( ! $nested['gallery'] || ! $nested['packing'] ) {
	throw new RuntimeException( 'Nested innerBlocks were not scanned recursively.' );
}

$mai_cyber_gallery_test_posts[101] = new WP_Post(
	array(
		array(
			'blockName'   => 'core/block',
			'attrs'       => array( 'ref' => 102 ),
			'innerBlocks' => array(),
		),
		$gallery_block( 'grid' ),
	)
);
$mai_cyber_gallery_test_posts[102] = new WP_Post(
	array(
		array(
			'blockName'   => 'core/block',
			'attrs'       => array( 'ref' => 101 ),
			'innerBlocks' => array(),
		),
		$gallery_block( 'comparison' ),
	)
);

$reusable = cyber_detect_assets(
	$method,
	$plugin,
	array(
		array(
			'blockName'   => 'core/block',
			'attrs'       => array( 'ref' => 101 ),
			'innerBlocks' => array(),
		),
	)
);
if ( ! $reusable['gallery'] || ! $reusable['comparison'] ) {
	throw new RuntimeException( 'Reusable block references were not followed with cycle protection.' );
}

echo "Verified base, enhanced, nested, and reusable-block asset detection.\n";

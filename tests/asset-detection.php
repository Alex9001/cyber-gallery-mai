<?php
/**
 * Regression coverage for parsed-block asset detection.
 *
 * @package CyberGalleryMai
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

$cyber_gallery_mai_test_posts              = array();
$cyber_gallery_mai_test_styles             = array();
$cyber_gallery_mai_test_scripts            = array();
$cyber_gallery_mai_test_registered_styles  = array();
$cyber_gallery_mai_test_registered_scripts = array();
$cyber_gallery_mai_test_localized_scripts  = array();

/**
 * Minimal WordPress action registration test double.
 *
 * @return void
 */
function add_action(): void {}

/**
 * Ignore activation-hook registration in the standalone test.
 *
 * @return void
 */
function register_activation_hook(): void {}

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
	return 'https://example.test/wp-content/plugins/cyber-gallery-mai/';
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
	global $cyber_gallery_mai_test_posts;
	return $cyber_gallery_mai_test_posts[ $post_id ] ?? null;
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
	global $cyber_gallery_mai_test_styles;
	$cyber_gallery_mai_test_styles[] = $handle;
}

/**
 * Capture an enqueued script.
 *
 * @param string $handle Script handle.
 *
 * @return void
 */
function wp_enqueue_script( $handle ): void {
	global $cyber_gallery_mai_test_scripts;
	$cyber_gallery_mai_test_scripts[] = $handle;
}

/**
 * Capture registered style aliases and dependencies.
 *
 * @param string            $handle  Style handle.
 * @param string|false      $src     Style source.
 * @param array<int,string> $deps    Style dependencies.
 * @param string|false|null $version Style version.
 *
 * @return void
 */
function wp_register_style( $handle, $src, $deps = array(), $version = false ): void {
	global $cyber_gallery_mai_test_registered_styles;
	unset( $version );

	$cyber_gallery_mai_test_registered_styles[ $handle ] = array(
		'src'  => $src,
		'deps' => $deps,
	);
}

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
	global $cyber_gallery_mai_test_registered_scripts;
	unset( $version );

	$cyber_gallery_mai_test_registered_scripts[ $handle ] = array(
		'src'  => $src,
		'deps' => $deps,
		'args' => $args,
	);
}

/**
 * Capture localized JavaScript object names.
 *
 * @param string $handle      Script handle.
 * @param string $object_name JavaScript object name.
 *
 * @return void
 */
function wp_localize_script( $handle, $object_name ): void {
	global $cyber_gallery_mai_test_localized_scripts;

	$cyber_gallery_mai_test_localized_scripts[ $handle ] = $object_name;
}

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

require dirname( __DIR__ ) . '/cyber-gallery-mai.php';

$reflection = new ReflectionClass( Cyber_Gallery_Mai_Plugin::class );
$plugin     = cyber_gallery_mai_plugin();
$method     = $reflection->getMethod( 'get_block_asset_needs' );

$plugin->register_assets();

foreach (
	array(
		'cyber-gallery-mai',
		'cyber-gallery-mai-packing',
		'cyber-gallery-mai-lightbox',
		'cyber-gallery-mai-comparison',
	) as $runtime_handle
) {
	$registration = $cyber_gallery_mai_test_registered_scripts[ $runtime_handle ] ?? null;

	if ( ! is_array( $registration ) || empty( $registration['args']['in_footer'] ) ) {
		throw new RuntimeException( sprintf( '%s was not registered for safe late footer output.', $runtime_handle ) );
	}
}

foreach (
	array(
		'mai-cyber-gallery'            => 'cyber-gallery-mai',
		'mai-cyber-gallery-packing'    => 'cyber-gallery-mai-packing',
		'mai-cyber-gallery-lightbox'   => 'cyber-gallery-mai-lightbox',
		'mai-cyber-gallery-comparison' => 'cyber-gallery-mai-comparison',
		'mai-cyber-gallery-editor'     => 'cyber-gallery-mai-editor',
	) as $legacy_handle => $canonical_handle
) {
	$registration = $cyber_gallery_mai_test_registered_scripts[ $legacy_handle ] ?? null;

	if (
		! is_array( $registration )
		|| false !== $registration['src']
		|| array( $canonical_handle ) !== $registration['deps']
	) {
		throw new RuntimeException( sprintf( '%s did not remain a dependency-only 1.x alias.', $legacy_handle ) );
	}
}

if (
	array( 'cyber-gallery-mai' ) !== ( $cyber_gallery_mai_test_registered_styles['mai-cyber-gallery']['deps'] ?? null )
	|| array( 'cyber-gallery-mai-editor' ) !== ( $cyber_gallery_mai_test_registered_styles['mai-cyber-gallery-editor']['deps'] ?? null )
) {
	throw new RuntimeException( 'The 1.x style handles did not remain dependency-only aliases.' );
}

if (
	'CyberGalleryMaiSettings' !== ( $cyber_gallery_mai_test_localized_scripts['cyber-gallery-mai'] ?? null )
	|| 'CyberGalleryMaiComparisonSettings' !== ( $cyber_gallery_mai_test_localized_scripts['cyber-gallery-mai-comparison'] ?? null )
	|| 'CyberGalleryMaiLightboxSettings' !== ( $cyber_gallery_mai_test_localized_scripts['cyber-gallery-mai-lightbox'] ?? null )
) {
	throw new RuntimeException( 'Canonical JavaScript settings globals were not localized.' );
}

/**
 * Invoke the private detector.
 *
 * @param ReflectionMethod                 $method Detector method.
 * @param Cyber_Gallery_Mai_Plugin         $plugin Plugin instance.
 * @param array<int, array<string, mixed>> $blocks Parsed blocks.
 *
 * @return array{gallery: bool, packing: bool, carousel: bool, lightbox: bool, comparison: bool}
 */
function cyber_detect_assets( $method, $plugin, array $blocks ): array {
	return $method->invoke( $plugin, $blocks );
}

$gallery_block = static function (
	string $layout,
	string $click = 'none',
	string $block_name = 'acf/cyber-gallery-mai'
): array {
	return array(
		'blockName'   => $block_name,
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

$legacy_grid = cyber_detect_assets(
	$method,
	$plugin,
	array( $gallery_block( 'grid', 'none', 'acf/mai-cyber-gallery' ) )
);
if ( $legacy_grid !== $plain_grid ) {
	throw new RuntimeException( 'The hidden 1.x block alias did not retain canonical asset detection.' );
}

$wp_query = new WP_Query( array( new WP_Post( array( $gallery_block( 'grid' ) ) ) ) );
$plugin->enqueue_detected_block_assets();
if (
	array( 'cyber-gallery-mai' ) !== $cyber_gallery_mai_test_styles
	|| array() !== $cyber_gallery_mai_test_scripts
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

$cyber_gallery_mai_test_posts[101] = new WP_Post(
	array(
		array(
			'blockName'   => 'core/block',
			'attrs'       => array( 'ref' => 102 ),
			'innerBlocks' => array(),
		),
		$gallery_block( 'grid' ),
	)
);
$cyber_gallery_mai_test_posts[102] = new WP_Post(
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

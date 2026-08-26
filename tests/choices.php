<?php
/**
 * Regression coverage for filtered choices and filtered gallery arguments.
 *
 * @package CyberGalleryMai
 */

declare( strict_types=1 );

define( 'ABSPATH', __DIR__ . '/' );

$cyber_gallery_mai_test_filters            = array();
$cyber_gallery_mai_test_breakpoint_request = array();

/**
 * Apply a configured test filter.
 *
 * @param string $hook Hook name.
 * @param mixed  $value Original value.
 * @param mixed  ...$args Additional filter arguments.
 *
 * @return mixed
 */
function apply_filters( $hook, $value, ...$args ) {
	global $cyber_gallery_mai_test_filters;

	if ( ! array_key_exists( $hook, $cyber_gallery_mai_test_filters ) ) {
		return $value;
	}

	$filtered = $cyber_gallery_mai_test_filters[ $hook ];

	return is_callable( $filtered ) ? $filtered( $value, ...$args ) : $filtered;
}

/**
 * Return untranslated HTML test text.
 *
 * @param mixed  $text   Source text.
 * @param string $domain Text domain.
 *
 * @return string
 */
function esc_html__( $text, $domain = 'default' ): string {
	unset( $domain );
	return (string) $text;
}

/**
 * Sanitize a key for the standalone test environment.
 *
 * @param mixed $value Candidate key.
 *
 * @return string
 */
function sanitize_key( $value ): string {
	return preg_replace( '/[^a-z0-9_-]/', '', strtolower( (string) $value ) ) ?? '';
}

/**
 * Sanitize a single-line test value.
 *
 * @param mixed $value Candidate text.
 *
 * @return string
 */
function sanitize_text_field( $value ): string {
	return trim( strip_tags( (string) $value ) );
}

/**
 * Sanitize a multiline test value.
 *
 * @param mixed $value Candidate text.
 *
 * @return string
 */
function sanitize_textarea_field( $value ): string {
	return sanitize_text_field( $value );
}

/**
 * Sanitize a test title.
 *
 * @param mixed $value Candidate title.
 *
 * @return string
 */
function sanitize_title( $value ): string {
	return sanitize_key( $value );
}

/**
 * Sanitize a CSS class for the standalone test environment.
 *
 * @param mixed $value Candidate class.
 *
 * @return string
 */
function sanitize_html_class( $value ): string {
	return preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $value ) ?? '';
}

/**
 * Sanitize a hexadecimal color for the standalone test environment.
 *
 * @param mixed $value Candidate color.
 *
 * @return string|null
 */
function sanitize_hex_color( $value ) {
	return preg_match( '/^#[0-9a-f]{6}$/i', (string) $value ) ? strtolower( (string) $value ) : null;
}

/**
 * Sanitize a URL for storage in the standalone test environment.
 *
 * @param mixed $value Candidate URL.
 *
 * @return string
 */
function esc_url_raw( $value ): string {
	return filter_var( (string) $value, FILTER_VALIDATE_URL ) ? (string) $value : '';
}

/**
 * Return the absolute integer representation of a test value.
 *
 * @param mixed $value Candidate value.
 *
 * @return int
 */
function absint( $value ): int {
	return abs( (int) $value );
}

/**
 * Merge test arguments with defaults.
 *
 * @param array<string,mixed> $args     Supplied arguments.
 * @param array<string,mixed> $defaults Default arguments.
 *
 * @return array<string,mixed>
 */
function wp_parse_args( $args, $defaults = array() ): array {
	return array_merge( (array) $defaults, (array) $args );
}

/**
 * Sanitize a boolean using the Mai Engine test contract.
 *
 * @param mixed $value Candidate value.
 *
 * @return bool
 */
function mai_sanitize_bool( $value ): bool {
	return filter_var( $value, FILTER_VALIDATE_BOOLEAN );
}

/**
 * Determine whether a test size is supported by Mai Engine.
 *
 * @param mixed $value Candidate size.
 *
 * @return bool
 */
function mai_is_valid_size( $value ): bool {
	return in_array( $value, array( '', 'sm', 'md', 'lg' ), true );
}

/**
 * Determine whether a test image orientation exists.
 *
 * @param mixed $orientation Candidate orientation.
 *
 * @return bool
 */
function mai_has_image_orientiation( $orientation ): bool {
	return in_array( $orientation, array( 'landscape', 'portrait', 'square' ), true );
}

/**
 * Return a stable test breakpoint.
 *
 * @param mixed $breakpoint Requested breakpoint.
 *
 * @return int
 */
function mai_get_breakpoint( $breakpoint ): int {
	unset( $breakpoint );
	return 1200;
}

/**
 * Return responsive defaults through Mai Engine's public column contract.
 *
 * @param array<string,mixed> $args Column arguments.
 *
 * @return array<string,int>
 */
function mai_get_breakpoint_columns( $args ): array {
	global $cyber_gallery_mai_test_breakpoint_request;
	$cyber_gallery_mai_test_breakpoint_request = $args;

	return array(
		'lg' => 3,
		'md' => 1,
		'sm' => 1,
		'xs' => 1,
	);
}

/**
 * Return representative Mai Engine colors.
 *
 * @return array<string,string>
 */
function mai_get_colors(): array {
	return array( 'black' => '#000000' );
}

/**
 * Return representative registered image sizes.
 *
 * @return array<string,array<string,bool>>
 */
function wp_get_registered_image_subsizes(): array {
	return array(
		'large'        => array( 'crop' => false ),
		'landscape-sm' => array( 'crop' => true ),
	);
}

require dirname( __DIR__ ) . '/includes/functions.php';
require dirname( __DIR__ ) . '/classes/class-cyber-gallery-mai.php';

$cyber_gallery_mai_test_filters['cyber_gallery_mai_layouts'] = array(
	' Custom Layout ' => ' <b>Custom Layout</b> ',
	'no-label'        => '<script></script>',
	'bad-value'       => array( 'Not scalar' ),
	'???'             => 'No slug',
);

$normalized = cyber_gallery_mai_get_layout_choices();

if ( array( 'customlayout' => 'Custom Layout' ) !== $normalized ) {
	throw new RuntimeException( 'Filtered choices were not normalized to usable slug/label pairs.' );
}

if (
	'customlayout' !== cyber_gallery_mai_get_choice_default( $normalized, 'masonry' )
	|| 'customlayout' !== cyber_gallery_mai_get_choice_default( $normalized, 'masonry', 'customlayout' )
) {
	throw new RuntimeException( 'Choice defaults did not fall back deterministically for a single custom choice.' );
}

$cyber_gallery_mai_test_filters['cyber_gallery_mai_layouts'] = false;

if ( ! isset( cyber_gallery_mai_get_layout_choices()['masonry'] ) ) {
	throw new RuntimeException( 'A malformed filter did not restore the core choice list.' );
}

$cyber_gallery_mai_test_filters = array(
	'cyber_gallery_mai_layouts' => array( 'canonical-layout' => 'Canonical Layout' ),
	'mai_cyber_gallery_layouts' => array( 'legacy-layout' => 'Legacy Layout' ),
);

if ( array( 'legacy-layout' => 'Legacy Layout' ) !== cyber_gallery_mai_get_layout_choices() ) {
	throw new RuntimeException( 'The 1.x layout filter alias did not run after the canonical filter.' );
}

$distinct = array(
	'arrows' => 'Arrows',
	'dots'   => 'Circles',
	'none'   => 'None',
);

if (
	'dots' !== cyber_gallery_mai_get_choice_default( $distinct, 'dots', 'arrows' )
	|| 'dots' !== cyber_gallery_mai_get_choice_default( $distinct, 'arrows', 'arrows' )
) {
	throw new RuntimeException( 'Choice defaults did not select a registered alternative.' );
}

$cyber_gallery_mai_test_filters['cyber_gallery_mai_slider_navigation_choices'] = array(
	'pager' => 'Unsupported Pager',
);

$navigation_fallback = cyber_gallery_mai_get_slider_navigation_choices();
if (
	! isset( $navigation_fallback['arrows'], $navigation_fallback['dots'], $navigation_fallback['counter'], $navigation_fallback['none'] )
	|| isset( $navigation_fallback['pager'] )
) {
	throw new RuntimeException( 'Unsupported navigation choices did not restore the implemented core controls.' );
}

$cyber_gallery_mai_test_filters = array(
	'cyber_gallery_mai_layouts'                         => array( 'custom-layout' => 'Custom Layout' ),
	'cyber_gallery_mai_arrow_styles'                    => array( 'custom-arrow' => 'Custom Arrow' ),
	'cyber_gallery_mai_button_style_choices'            => array( 'custom-button' => 'Custom Button' ),
	'cyber_gallery_mai_slider_navigation_choices'       => array( 'counter' => 'Renamed Counter' ),
	'cyber_gallery_mai_slider_transition_choices'       => array( 'dissolve' => 'Dissolve' ),
	'cyber_gallery_mai_slider_transition_speed_choices' => array( 'deliberate' => 'Deliberate' ),
	'cyber_gallery_mai_slider_navigation_hover_choices' => array( 'pulse' => 'Pulse' ),
	'cyber_gallery_mai_slider_overlay_style_choices'    => array( 'wash' => 'Wash' ),
	'cyber_gallery_mai_args'                            => static function ( $args ): array {
		unset( $args );

		// Deliberately omit almost every required key and supply stale core
		// choices. The constructor must restore defaults before sanitizing them
		// against the replacement choice lists above.
		return array(
			'align'                       => 'unexpected',
			'layout'                      => 'masonry',
			'lightbox_arrow_style'        => 'circle',
			'filmstrip_arrow_style'       => 'circle',
			'slider_navigation'           => 'arrows',
			'slider_navigation_secondary' => 'dots',
			'slider_arrow_style'          => 'circle',
			'slider_transition'           => 'native',
			'slider_transition_speed'     => 'normal',
			'slider_navigation_hover'     => 'color',
			'slider_overlay_style'        => 'none',
		);
	},
);

$gallery    = new Cyber_Gallery_Mai();
$reflection = new ReflectionClass( $gallery );
$property   = $reflection->getProperty( 'args' );
$args       = $property->getValue( $gallery );

$expected = array(
	'align'                       => '',
	'layout'                      => 'custom-layout',
	'lightbox_arrow_style'        => 'custom-arrow',
	'filmstrip_arrow_style'       => 'custom-arrow',
	'slider_navigation'           => 'counter',
	'slider_navigation_secondary' => 'counter',
	'slider_arrow_style'          => 'custom-arrow',
	'slider_transition'           => 'dissolve',
	'slider_transition_speed'     => 'deliberate',
	'slider_navigation_hover'     => 'pulse',
	'slider_overlay_style'        => 'wash',
);

foreach ( $expected as $key => $value ) {
	if ( $value !== $args[ $key ] ) {
		throw new RuntimeException( sprintf( 'Filtered runtime default failed for %s.', $key ) );
	}
}

if (
	3 !== $args['columns']
	|| 1 !== $args['columns_md']
	|| 1 !== $args['columns_sm']
	|| 1 !== $args['columns_xs']
	|| 'xl' !== $args['column_gap']
	|| 'xl' !== $args['row_gap']
	|| empty( $cyber_gallery_mai_test_breakpoint_request['columns_responsive'] )
) {
	throw new RuntimeException( 'Public Mai breakpoint defaults or stable gap defaults were not retained.' );
}

$cyber_gallery_mai_test_filters = array();
$malformed_links_gallery        = new Cyber_Gallery_Mai(
	array(
		'images_links' => array(
			array(
				'image' => 17,
				'link'  => array(
					'url'    => array( 'not-a-string' ),
					'title'  => new stdClass(),
					'target' => array( '_blank' ),
				),
			),
			array(
				'image' => new stdClass(),
				'link'  => array( 'url' => 'https://example.test/skipped' ),
			),
			array(
				'image' => '23',
				'link'  => array(
					'url'    => 'https://example.test/kept',
					'title'  => 23,
					'target' => '_blank',
				),
			),
		),
	)
);
$malformed_args                 = $property->getValue( $malformed_links_gallery );

if (
	2 !== count( $malformed_args['images_links'] )
	|| '' !== $malformed_args['images_links'][0]['link']['url']
	|| '' !== $malformed_args['images_links'][0]['link']['title']
	|| '' !== $malformed_args['images_links'][0]['link']['target']
	|| 'https://example.test/kept' !== $malformed_args['images_links'][1]['link']['url']
	|| '23' !== $malformed_args['images_links'][1]['link']['title']
	|| '_blank' !== $malformed_args['images_links'][1]['link']['target']
) {
	throw new RuntimeException( 'Malformed linked-image rows were not safely normalized.' );
}

$items_method   = ( new ReflectionClass( $malformed_links_gallery ) )->getMethod( 'sanitize_filtered_items' );
$filtered_items = $items_method->invoke(
	$malformed_links_gallery,
	array(
		array(
			'id'     => new stdClass(),
			'image'  => '<img src="https://example.test/image.jpg" alt="">',
			'width'  => new stdClass(),
			'height' => array( 800 ),
			'link'   => array(
				'url'    => array( 'not-a-string' ),
				'title'  => new stdClass(),
				'target' => array( '_blank' ),
			),
		),
	)
);

if (
	1 !== count( $filtered_items )
	|| 0 !== $filtered_items[0]['id']
	|| 0 !== $filtered_items[0]['width']
	|| 0 !== $filtered_items[0]['height']
	|| '' !== $filtered_items[0]['link']['url']
	|| '' !== $filtered_items[0]['link']['title']
	|| '' !== $filtered_items[0]['link']['target']
) {
	throw new RuntimeException( 'Malformed item-filter identifiers, dimensions, or links were not safely normalized.' );
}

echo "Verified normalized choices, public Mai column defaults, and malformed link safety.\n";

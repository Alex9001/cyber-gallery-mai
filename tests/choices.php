<?php
/**
 * Regression coverage for filtered choices and filtered gallery arguments.
 *
 * @package MaiCyberGallery
 */

declare( strict_types=1 );

define( 'ABSPATH', __DIR__ . '/' );

$mai_cyber_gallery_test_filters = array();

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
	global $mai_cyber_gallery_test_filters;

	if ( ! array_key_exists( $hook, $mai_cyber_gallery_test_filters ) ) {
		return $value;
	}

	$filtered = $mai_cyber_gallery_test_filters[ $hook ];

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
 * Return representative Mai Engine column defaults.
 *
 * @return array<string,mixed>
 */
function mai_get_columns_defaults(): array {
	return array(
		'columns'                => 3,
		'columns_responsive'     => false,
		'columns_md'             => 3,
		'columns_sm'             => 2,
		'columns_xs'             => 1,
		'align_columns'          => '',
		'align_columns_vertical' => '',
		'column_gap'             => 'md',
		'row_gap'                => 'md',
		'margin_top'             => '',
		'margin_bottom'          => '',
	);
}

/**
 * Return supplied column arguments as sanitized test data.
 *
 * @param mixed $args Supplied column arguments.
 *
 * @return array<string,mixed>
 */
function mai_get_columns_sanitized( $args ): array {
	return (array) $args;
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
require dirname( __DIR__ ) . '/classes/class-mai-cyber-gallery.php';

$mai_cyber_gallery_test_filters['mai_cyber_gallery_layouts'] = array(
	' Custom Layout ' => ' <b>Custom Layout</b> ',
	'no-label'        => '<script></script>',
	'bad-value'       => array( 'Not scalar' ),
	'???'             => 'No slug',
);

$normalized = mai_cyber_gallery_get_layout_choices();

if ( array( 'customlayout' => 'Custom Layout' ) !== $normalized ) {
	throw new RuntimeException( 'Filtered choices were not normalized to usable slug/label pairs.' );
}

if (
	'customlayout' !== mai_cyber_gallery_get_choice_default( $normalized, 'masonry' )
	|| 'customlayout' !== mai_cyber_gallery_get_choice_default( $normalized, 'masonry', 'customlayout' )
) {
	throw new RuntimeException( 'Choice defaults did not fall back deterministically for a single custom choice.' );
}

$mai_cyber_gallery_test_filters['mai_cyber_gallery_layouts'] = false;

if ( ! isset( mai_cyber_gallery_get_layout_choices()['masonry'] ) ) {
	throw new RuntimeException( 'A malformed filter did not restore the core choice list.' );
}

$distinct = array(
	'arrows' => 'Arrows',
	'dots'   => 'Circles',
	'none'   => 'None',
);

if (
	'dots' !== mai_cyber_gallery_get_choice_default( $distinct, 'dots', 'arrows' )
	|| 'dots' !== mai_cyber_gallery_get_choice_default( $distinct, 'arrows', 'arrows' )
) {
	throw new RuntimeException( 'Choice defaults did not select a registered alternative.' );
}

$mai_cyber_gallery_test_filters['mai_cyber_gallery_slider_navigation_choices'] = array(
	'pager' => 'Unsupported Pager',
);

$navigation_fallback = mai_cyber_gallery_get_slider_navigation_choices();
if (
	! isset( $navigation_fallback['arrows'], $navigation_fallback['dots'], $navigation_fallback['counter'], $navigation_fallback['none'] )
	|| isset( $navigation_fallback['pager'] )
) {
	throw new RuntimeException( 'Unsupported navigation choices did not restore the implemented core controls.' );
}

$mai_cyber_gallery_test_filters = array(
	'mai_cyber_gallery_layouts'                         => array( 'custom-layout' => 'Custom Layout' ),
	'mai_cyber_gallery_arrow_styles'                    => array( 'custom-arrow' => 'Custom Arrow' ),
	'mai_cyber_gallery_button_style_choices'            => array( 'custom-button' => 'Custom Button' ),
	'mai_cyber_gallery_slider_navigation_choices'       => array( 'counter' => 'Renamed Counter' ),
	'mai_cyber_gallery_slider_transition_choices'       => array( 'dissolve' => 'Dissolve' ),
	'mai_cyber_gallery_slider_transition_speed_choices' => array( 'deliberate' => 'Deliberate' ),
	'mai_cyber_gallery_slider_navigation_hover_choices' => array( 'pulse' => 'Pulse' ),
	'mai_cyber_gallery_slider_overlay_style_choices'    => array( 'wash' => 'Wash' ),
	'mai_cyber_gallery_args'                            => static function ( $args ): array {
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

$gallery    = new Mai_Cyber_Gallery();
$reflection = new ReflectionClass( $gallery );
$property   = $reflection->getProperty( 'args' );
$property->setAccessible( true );
$args = $property->getValue( $gallery );

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

echo "Verified normalized choices and filtered runtime defaults.\n";

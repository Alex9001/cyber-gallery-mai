<?php
/**
 * Regression coverage for filtered choices and filtered gallery arguments.
 *
 * @package MaiCyberGallery
 */

declare( strict_types=1 );

define( 'ABSPATH', __DIR__ . '/' );

$mai_cyber_gallery_test_filters = [];

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

function esc_html__( $text, $domain = 'default' ): string {
	unset( $domain );
	return (string) $text;
}

function sanitize_key( $value ): string {
	return preg_replace( '/[^a-z0-9_-]/', '', strtolower( (string) $value ) ) ?? '';
}

function sanitize_text_field( $value ): string {
	return trim( strip_tags( (string) $value ) );
}

function sanitize_textarea_field( $value ): string {
	return sanitize_text_field( $value );
}

function sanitize_title( $value ): string {
	return sanitize_key( $value );
}

function sanitize_html_class( $value ): string {
	return preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $value ) ?? '';
}

function sanitize_hex_color( $value ) {
	return preg_match( '/^#[0-9a-f]{6}$/i', (string) $value ) ? strtolower( (string) $value ) : null;
}

function esc_url_raw( $value ): string {
	return filter_var( (string) $value, FILTER_VALIDATE_URL ) ? (string) $value : '';
}

function absint( $value ): int {
	return abs( (int) $value );
}

function wp_parse_args( $args, $defaults = [] ): array {
	return array_merge( (array) $defaults, (array) $args );
}

function mai_sanitize_bool( $value ): bool {
	return filter_var( $value, FILTER_VALIDATE_BOOLEAN );
}

function mai_is_valid_size( $value ): bool {
	return in_array( $value, [ '', 'sm', 'md', 'lg' ], true );
}

function mai_has_image_orientiation( $orientation ): bool {
	return in_array( $orientation, [ 'landscape', 'portrait', 'square' ], true );
}

function mai_get_breakpoint( $breakpoint ): int {
	unset( $breakpoint );
	return 1200;
}

function mai_get_columns_defaults(): array {
	return [
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
	];
}

function mai_get_columns_sanitized( $args ): array {
	return (array) $args;
}

function mai_get_colors(): array {
	return [ 'black' => '#000000' ];
}

function wp_get_registered_image_subsizes(): array {
	return [
		'large'        => [ 'crop' => false ],
		'landscape-sm' => [ 'crop' => true ],
	];
}

require dirname( __DIR__ ) . '/includes/functions.php';
require dirname( __DIR__ ) . '/classes/class-gallery.php';

$mai_cyber_gallery_test_filters['mai_cyber_gallery_layouts'] = [
	' Custom Layout ' => ' <b>Custom Layout</b> ',
	'no-label'        => '<script></script>',
	'bad-value'       => [ 'Not scalar' ],
	'???'             => 'No slug',
];

$normalized = mai_cyber_gallery_get_layout_choices();

if ( [ 'customlayout' => 'Custom Layout' ] !== $normalized ) {
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

$distinct = [
	'arrows' => 'Arrows',
	'dots'   => 'Circles',
	'none'   => 'None',
];

if (
	'dots' !== mai_cyber_gallery_get_choice_default( $distinct, 'dots', 'arrows' )
	|| 'dots' !== mai_cyber_gallery_get_choice_default( $distinct, 'arrows', 'arrows' )
) {
	throw new RuntimeException( 'Choice defaults did not select a registered alternative.' );
}

$mai_cyber_gallery_test_filters['mai_cyber_gallery_slider_navigation_choices'] = [
	'pager' => 'Unsupported Pager',
];

$navigation_fallback = mai_cyber_gallery_get_slider_navigation_choices();
if (
	! isset( $navigation_fallback['arrows'], $navigation_fallback['dots'], $navigation_fallback['counter'], $navigation_fallback['none'] )
	|| isset( $navigation_fallback['pager'] )
) {
	throw new RuntimeException( 'Unsupported navigation choices did not restore the implemented core controls.' );
}

$mai_cyber_gallery_test_filters = [
	'mai_cyber_gallery_layouts'                          => [ 'custom-layout' => 'Custom Layout' ],
	'mai_cyber_gallery_arrow_styles'                     => [ 'custom-arrow' => 'Custom Arrow' ],
	'mai_cyber_gallery_button_style_choices'             => [ 'custom-button' => 'Custom Button' ],
	'mai_cyber_gallery_slider_navigation_choices'        => [ 'counter' => 'Renamed Counter' ],
	'mai_cyber_gallery_slider_transition_choices'        => [ 'dissolve' => 'Dissolve' ],
	'mai_cyber_gallery_slider_transition_speed_choices'  => [ 'deliberate' => 'Deliberate' ],
	'mai_cyber_gallery_slider_navigation_hover_choices'  => [ 'pulse' => 'Pulse' ],
	'mai_cyber_gallery_slider_overlay_style_choices'     => [ 'wash' => 'Wash' ],
	'mai_cyber_gallery_args'                             => static function ( $args ): array {
		unset( $args );

		// Deliberately omit almost every required key and supply stale core
		// choices. The constructor must restore defaults before sanitizing them
		// against the replacement choice lists above.
		return [
			'align'                        => 'unexpected',
			'layout'                       => 'masonry',
			'lightbox_arrow_style'         => 'circle',
			'filmstrip_arrow_style'        => 'circle',
			'slider_navigation'            => 'arrows',
			'slider_navigation_secondary'  => 'dots',
			'slider_arrow_style'           => 'circle',
			'slider_transition'            => 'native',
			'slider_transition_speed'      => 'normal',
			'slider_navigation_hover'      => 'color',
			'slider_overlay_style'         => 'none',
		];
	},
];

$gallery    = new Mai_Cyber_Gallery();
$reflection = new ReflectionClass( $gallery );
$property   = $reflection->getProperty( 'args' );
$property->setAccessible( true );
$args = $property->getValue( $gallery );

$expected = [
	'align'                        => '',
	'layout'                       => 'custom-layout',
	'lightbox_arrow_style'         => 'custom-arrow',
	'filmstrip_arrow_style'        => 'custom-arrow',
	'slider_navigation'            => 'counter',
	'slider_navigation_secondary'  => 'counter',
	'slider_arrow_style'           => 'custom-arrow',
	'slider_transition'            => 'dissolve',
	'slider_transition_speed'      => 'deliberate',
	'slider_navigation_hover'      => 'pulse',
	'slider_overlay_style'         => 'wash',
];

foreach ( $expected as $key => $value ) {
	if ( $value !== $args[ $key ] ) {
		throw new RuntimeException( sprintf( 'Filtered runtime default failed for %s.', $key ) );
	}
}

echo "Verified normalized choices and filtered runtime defaults.\n";

<?php
/**
 * Regression coverage for layout-aware ACF validation and field conditions.
 *
 * @package MaiCyberGallery
 */

define( 'ABSPATH', __DIR__ );

$mai_cyber_gallery_test_filters = [];
$mai_cyber_gallery_test_groups  = [];
$mai_cyber_gallery_test_values  = [];
$mai_cyber_gallery_test_current = '';

/**
 * Minimal WordPress and ACF test doubles used while loading the block file.
 */
function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ): void {
}

function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ): void {
	global $mai_cyber_gallery_test_filters;

	$mai_cyber_gallery_test_filters[ $hook ][] = [
		'callback'      => $callback,
		'priority'      => $priority,
		'accepted_args' => $accepted_args,
	];
}

function acf_add_local_field_group( $group ): void {
	global $mai_cyber_gallery_test_groups;

	$mai_cyber_gallery_test_groups[] = $group;
}

function get_field( $name, $post_id = false, $format_value = true ) {
	global $mai_cyber_gallery_test_current, $mai_cyber_gallery_test_values;

	$post_id = $post_id ? (string) $post_id : $mai_cyber_gallery_test_current;

	return $mai_cyber_gallery_test_values[ $post_id ][ $name ] ?? null;
}

function sanitize_key( $value ): string {
	return preg_replace( '/[^a-z0-9_-]/', '', strtolower( (string) $value ) );
}

function sanitize_text_field( $value ): string {
	return trim( strip_tags( (string) $value ) );
}

function esc_html__( $text, $domain = 'default' ): string {
	return (string) $text;
}

function esc_url( $url ): string {
	return (string) $url;
}

function absint( $value ): int {
	return abs( (int) $value );
}

function mai_get_breakpoint( $breakpoint ): int {
	return 1200;
}

function mai_get_image_orientation_choices(): array {
	return [ 'landscape' => 'Landscape' ];
}

function mai_get_image_size_choices(): array {
	return [ 'large' => 'Large' ];
}

function mai_get_radio_color_choices(): array {
	return [ '' => 'Default' ];
}

function mai_cyber_gallery_get_layout_choices(): array {
	return [
		'grid'       => 'Grid',
		'masonry'    => 'Masonry',
		'justified'  => 'Justified',
		'tiled'      => 'Tiled Mosaic',
		'mosaic'     => 'Editorial Mosaic',
		'square'     => 'Square',
		'circle'     => 'Circle',
		'comparison' => 'Before / After',
		'filmstrip'  => 'Filmstrip',
		'slider'     => 'Slider',
	];
}

function mai_cyber_gallery_get_arrow_style_choices(): array {
	return [ 'circle' => 'Circle' ];
}

function mai_cyber_gallery_get_slider_navigation_choices(): array {
	return [
		'none'   => 'None',
		'arrows' => 'Arrows',
		'dots'   => 'Circles',
	];
}

function mai_cyber_gallery_get_slider_transition_choices(): array {
	return [ 'slide' => 'Slide' ];
}

function mai_cyber_gallery_get_slider_transition_speed_choices(): array {
	return [ 'normal' => 'Normal' ];
}

function mai_cyber_gallery_get_slider_navigation_hover_choices(): array {
	return [ 'none' => 'None' ];
}

function mai_cyber_gallery_get_slider_overlay_style_choices(): array {
	return [ 'none' => 'None' ];
}

function mai_cyber_gallery_get_button_style_choices(): array {
	return [ 'button' => 'Button' ];
}

function mai_cyber_gallery_get_choice_default( array $choices, $preferred, $exclude = '' ): string {
	$preferred = (string) $preferred;
	$exclude   = (string) $exclude;

	if ( isset( $choices[ $preferred ] ) && $preferred !== $exclude ) {
		return $preferred;
	}

	foreach ( array_keys( $choices ) as $choice ) {
		if ( $choice !== $exclude ) {
			return (string) $choice;
		}
	}

	return $exclude;
}

function mai_cyber_gallery_get_default_button_style(): string {
	return 'button';
}

require dirname( __DIR__ ) . '/blocks/mai-cyber-gallery/block.php';

/**
 * Fail this standalone test with a useful message.
 *
 * @param bool   $condition Assertion result.
 * @param string $message   Failure message.
 *
 * @return void
 */
function mai_cyber_gallery_test_assert( bool $condition, string $message ): void {
	if ( $condition ) {
		return;
	}

	fwrite( STDERR, "Validation regression failed: {$message}\n" );
	exit( 1 );
}

/**
 * Locate one field recursively by key.
 *
 * @param array<int,array<string,mixed>> $fields Fields.
 * @param string                         $key    Field key.
 *
 * @return array<string,mixed>|null
 */
function mai_cyber_gallery_test_find_field( array $fields, string $key ): ?array {
	foreach ( $fields as $field ) {
		if ( isset( $field['key'] ) && $key === $field['key'] ) {
			return $field;
		}

		if ( ! empty( $field['sub_fields'] ) && is_array( $field['sub_fields'] ) ) {
			$match = mai_cyber_gallery_test_find_field( $field['sub_fields'], $key );
			if ( $match ) {
				return $match;
			}
		}
	}

	return null;
}

/**
 * Return layout equality values from an ACF OR-condition array.
 *
 * @param mixed $condition Conditional logic.
 *
 * @return list<string>
 */
function mai_cyber_gallery_test_condition_layouts( $condition ): array {
	$layouts = [];

	foreach ( (array) $condition as $group ) {
		foreach ( (array) $group as $rule ) {
			if (
				is_array( $rule )
				&& 'mai_cyber_gallery_layout' === ( $rule['field'] ?? '' )
				&& '==' === ( $rule['operator'] ?? '' )
				&& is_string( $rule['value'] ?? null )
			) {
				$layouts[] = $rule['value'];
			}
		}
	}

	sort( $layouts );
	return $layouts;
}

mai_cyber_gallery_test_assert(
	'block_abc-123' === mai_cyber_gallery_get_validation_block_id(
		'acf-block_abc-123[mai_cyber_gallery_slides][0][mai_cyber_gallery_slide_image]'
	),
	'block IDs must be parsed from nested ACF inputs'
);
mai_cyber_gallery_test_assert(
	false === mai_cyber_gallery_get_validation_block_id( 'acf-block/unsafe[mai_cyber_gallery_layout]' ),
	'unsafe block IDs must not be used as ACF post IDs'
);
mai_cyber_gallery_test_assert(
	false === mai_cyber_gallery_get_validation_block_id( 'acf[mai_cyber_gallery_layout]' ),
	'ordinary ACF inputs must use the current validation context'
);

$mai_cyber_gallery_test_values = [
	'block-grid' => [
		'layout'               => 'grid',
		'filmstrip_image_mode' => 'natural',
	],
	'block-comparison' => [
		'layout'               => 'comparison',
		'filmstrip_image_mode' => 'natural',
	],
	'block-slider' => [
		'layout'               => 'slider',
		'filmstrip_image_mode' => 'natural',
	],
	'block-filmstrip-natural' => [
		'layout'               => 'filmstrip',
		'filmstrip_image_mode' => 'natural',
	],
	'block-filmstrip-logos' => [
		'layout'               => 'filmstrip',
		'filmstrip_image_mode' => 'logo-contain',
	],
	'block-current' => [
		'layout' => 'grid',
	],
];
$mai_cyber_gallery_test_current = 'block-current';

$comparison_field = [ 'key' => 'mai_cyber_gallery_comparison_before_image' ];
$slide_field      = [ 'key' => 'mai_cyber_gallery_slide_image' ];
$button_field     = [ 'key' => 'mai_cyber_gallery_slide_button_link' ];
$logo_field       = [ 'key' => 'mai_cyber_gallery_filmstrip_logo_override_image' ];

mai_cyber_gallery_test_assert(
	true === mai_cyber_gallery_validate_conditional_required_value(
		false,
		'',
		$comparison_field,
		'acf-block-grid[mai_cyber_gallery_comparison_before_image]'
	),
	'inactive Comparison images must not invalidate an ordinary gallery'
);
mai_cyber_gallery_test_assert(
	false === mai_cyber_gallery_validate_conditional_required_value(
		false,
		'',
		$comparison_field,
		'acf-block-comparison[mai_cyber_gallery_comparison_before_image]'
	),
	'active Comparison images must remain required'
);
mai_cyber_gallery_test_assert(
	false === mai_cyber_gallery_validate_conditional_required_value(
		false,
		'',
		$slide_field,
		'acf-block-slider[mai_cyber_gallery_slides][0][mai_cyber_gallery_slide_image]'
	),
	'active Slider images must remain required'
);
mai_cyber_gallery_test_assert(
	true === mai_cyber_gallery_validate_conditional_required_value(
		false,
		'',
		$slide_field,
		'acf-block-comparison[mai_cyber_gallery_slides][0][mai_cyber_gallery_slide_image]'
	),
	'inactive Slider rows must not invalidate another layout'
);
mai_cyber_gallery_test_assert(
	true === mai_cyber_gallery_validate_slide_button_link(
		false,
		[],
		$button_field,
		'acf-block-grid[mai_cyber_gallery_slides][0][mai_cyber_gallery_slide_buttons][0][mai_cyber_gallery_slide_button_link]'
	),
	'inactive Slider CTA rows must bypass required and title validation'
);
mai_cyber_gallery_test_assert(
	false === mai_cyber_gallery_validate_slide_button_link(
		false,
		[],
		$button_field,
		'acf-block-slider[mai_cyber_gallery_slides][0][mai_cyber_gallery_slide_buttons][0][mai_cyber_gallery_slide_button_link]'
	),
	'active empty Slider CTA links must retain ACF required errors'
);
mai_cyber_gallery_test_assert(
	'Add link text to use as the button label.' === mai_cyber_gallery_validate_slide_button_link(
		true,
		[ 'url' => 'https://example.com', 'title' => '' ],
		$button_field,
		'acf-block-slider[mai_cyber_gallery_slides][0][mai_cyber_gallery_slide_buttons][0][mai_cyber_gallery_slide_button_link]'
	),
	'active Slider CTA links must retain visible-label validation'
);
mai_cyber_gallery_test_assert(
	true === mai_cyber_gallery_validate_slide_button_link(
		true,
		[ 'url' => 'https://example.com', 'title' => 'Learn more' ],
		$button_field,
		'acf-block-slider[mai_cyber_gallery_slides][0][mai_cyber_gallery_slide_buttons][0][mai_cyber_gallery_slide_button_link]'
	),
	'complete active Slider CTA links must validate'
);
mai_cyber_gallery_test_assert(
	true === mai_cyber_gallery_validate_conditional_required_value(
		false,
		'',
		$logo_field,
		'acf-block-filmstrip-natural[mai_cyber_gallery_filmstrip_logo_overrides][0][mai_cyber_gallery_filmstrip_logo_override_image]'
	),
	'logo overrides must be dormant outside Logo / Contain mode'
);
mai_cyber_gallery_test_assert(
	false === mai_cyber_gallery_validate_conditional_required_value(
		false,
		'',
		$logo_field,
		'acf-block-filmstrip-logos[mai_cyber_gallery_filmstrip_logo_overrides][0][mai_cyber_gallery_filmstrip_logo_override_image]'
	),
	'logo override images must remain required in Logo / Contain mode'
);

foreach (
	[
		'mai_cyber_gallery_comparison_before_image',
		'mai_cyber_gallery_comparison_after_image',
		'mai_cyber_gallery_slide_image',
		'mai_cyber_gallery_filmstrip_logo_override_image',
	] as $field_key
) {
	$hook = "acf/validate_value/key={$field_key}";
	mai_cyber_gallery_test_assert(
		isset( $mai_cyber_gallery_test_filters[ $hook ][0] )
		&& 'mai_cyber_gallery_validate_conditional_required_value' === $mai_cyber_gallery_test_filters[ $hook ][0]['callback']
		&& 4 === $mai_cyber_gallery_test_filters[ $hook ][0]['accepted_args'],
		"{$field_key} must register layout-aware validation"
	);
}

$column_field = mai_cyber_gallery_prepare_columns_clone_field(
	[
		'key'               => 'mai_columns_md',
		'conditional_logic' => [
			[
				[
					'field'    => 'mai_columns_responsive',
					'operator' => '==',
					'value'    => '1',
				],
			],
		],
	],
	[ 'key' => 'mai_cyber_gallery_columns_clone' ]
);
mai_cyber_gallery_test_assert(
	3 === count( $column_field['conditional_logic'][0] ?? [] )
	&& 'mai_columns_responsive' === ( $column_field['conditional_logic'][0][0]['field'] ?? '' )
	&& 'justified' === ( $column_field['conditional_logic'][0][1]['value'] ?? '' )
	&& 'mosaic' === ( $column_field['conditional_logic'][0][2]['value'] ?? '' ),
	'cloned responsive fields must retain Engine conditions while excluding layouts that calculate their own columns'
);

$alignment_field = mai_cyber_gallery_prepare_columns_clone_field(
	[
		'key'               => 'mai_align_columns',
		'conditional_logic' => [],
	],
	[ 'key' => 'mai_cyber_gallery_columns_clone' ]
);
$alignment_exclusions = array_map(
	static fn( array $rule ): string => (string) ( $rule['value'] ?? '' ),
	$alignment_field['conditional_logic'][0] ?? []
);
sort( $alignment_exclusions );

mai_cyber_gallery_test_assert(
	[ 'filmstrip', 'justified', 'masonry', 'mosaic', 'tiled' ] === $alignment_exclusions,
	'column alignment must be hidden for layouts whose packing or rail owns alignment'
);

$gap_field = [
	'key'               => 'mai_column_gap',
	'conditional_logic' => [],
];
mai_cyber_gallery_test_assert(
	$gap_field === mai_cyber_gallery_prepare_columns_clone_field(
		$gap_field,
		[ 'key' => 'mai_cyber_gallery_columns_clone' ]
	),
	'Mai Engine gap fields must remain available for Justified and Editorial Mosaic'
);
mai_cyber_gallery_test_assert(
	isset( $mai_cyber_gallery_test_filters['acf/clone_field'][0] )
	&& 'mai_cyber_gallery_prepare_columns_clone_field' === $mai_cyber_gallery_test_filters['acf/clone_field'][0]['callback']
	&& 20 === $mai_cyber_gallery_test_filters['acf/clone_field'][0]['priority']
	&& 2 === $mai_cyber_gallery_test_filters['acf/clone_field'][0]['accepted_args'],
	'the Mai Engine clone customization must be registered after ACF prepares cloned fields'
);

mai_cyber_gallery_register_field_group();
mai_cyber_gallery_test_assert( 1 === count( $mai_cyber_gallery_test_groups ), 'one field group must register' );

$fields = $mai_cyber_gallery_test_groups[0]['fields'];
foreach (
	[
		'mai_cyber_gallery_comparison_before_image',
		'mai_cyber_gallery_comparison_after_image',
		'mai_cyber_gallery_slide_image',
		'mai_cyber_gallery_slide_button_link',
		'mai_cyber_gallery_filmstrip_logo_override_image',
	] as $field_key
) {
	$field = mai_cyber_gallery_test_find_field( $fields, $field_key );
	mai_cyber_gallery_test_assert(
		is_array( $field ) && 1 === ( $field['required'] ?? 0 ),
		"{$field_key} must remain required in its active context"
	);
}

$columns = mai_cyber_gallery_test_find_field( $fields, 'mai_cyber_gallery_columns_clone' );
mai_cyber_gallery_test_assert(
	[
		'circle',
		'filmstrip',
		'grid',
		'justified',
		'masonry',
		'mosaic',
		'square',
		'tiled',
	] === mai_cyber_gallery_test_condition_layouts( $columns['conditional_logic'] ?? [] ),
	'the single Engine clone must cover every layout that uses columns or gaps'
);
mai_cyber_gallery_test_assert(
	1 === count( array_keys( $fields, $columns, true ) )
	&& count( $columns['clone'] ?? [] ) === count( array_unique( $columns['clone'] ?? [] ) )
	&& null === mai_cyber_gallery_test_find_field( $fields, 'mai_cyber_gallery_gaps_clone' ),
	'Engine spacing fields must have one canonical clone parent and one serialized value'
);

$aria_label = mai_cyber_gallery_test_find_field( $fields, 'mai_cyber_gallery_aria_label' );
mai_cyber_gallery_test_assert(
	is_array( $aria_label )
	&& 'text' === ( $aria_label['type'] ?? '' )
	&& '' === ( $aria_label['default_value'] ?? null )
	&& [ 'comparison', 'filmstrip', 'slider' ] === mai_cyber_gallery_test_condition_layouts( $aria_label['conditional_logic'] ?? [] ),
	'enhanced region layouts must expose an optional accessible label with a legacy-safe empty default'
);

$slider_title_tag = mai_cyber_gallery_test_find_field( $fields, 'mai_cyber_gallery_slider_title_tag' );
mai_cyber_gallery_test_assert(
	is_array( $slider_title_tag )
	&& 'select' === ( $slider_title_tag['type'] ?? '' )
	&& 'h2' === ( $slider_title_tag['default_value'] ?? '' )
	&& [ 'h2', 'h3', 'h4', 'h5', 'h6', 'p' ] === array_keys( $slider_title_tag['choices'] ?? [] )
	&& [ 'slider' ] === mai_cyber_gallery_test_condition_layouts( $slider_title_tag['conditional_logic'] ?? [] ),
	'Slider title semantics must offer h2-h6 and paragraph while preserving the former h2 default'
);

$desaturate = mai_cyber_gallery_test_find_field( $fields, 'mai_cyber_gallery_hover_desaturate_amount' );
$ordinary_desaturate_rules = $desaturate['conditional_logic'][0] ?? [];
$comparison_excluded       = false;
foreach ( $ordinary_desaturate_rules as $rule ) {
	if (
		'mai_cyber_gallery_layout' === ( $rule['field'] ?? '' )
		&& '!=' === ( $rule['operator'] ?? '' )
		&& 'comparison' === ( $rule['value'] ?? '' )
	) {
		$comparison_excluded = true;
	}
}
mai_cyber_gallery_test_assert(
	$comparison_excluded,
	'Comparison must not reveal a stale Desaturate Amount setting'
);

echo "Layout-aware ACF validation regression checks passed.\n";

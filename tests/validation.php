<?php
/**
 * Regression coverage for layout-aware ACF validation and field conditions.
 *
 * @package MaiCyberGallery
 */

define( 'ABSPATH', __DIR__ );

$mai_cyber_gallery_test_filters = array();
$mai_cyber_gallery_test_groups  = array();
$mai_cyber_gallery_test_values  = array();
$mai_cyber_gallery_test_current = '';

/**
 * Minimal WordPress and ACF test doubles used while loading the block file.
 *
 * @param string   $hook          Hook name.
 * @param callable $callback      Hook callback.
 * @param int      $priority      Hook priority.
 * @param int      $accepted_args Accepted callback arguments.
 *
 * @return void
 */
function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ): void {
}

/**
 * Capture a filter registration for later assertions.
 *
 * @param string   $hook          Hook name.
 * @param callable $callback      Hook callback.
 * @param int      $priority      Hook priority.
 * @param int      $accepted_args Accepted callback arguments.
 *
 * @return void
 */
function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ): void {
	global $mai_cyber_gallery_test_filters;

	$mai_cyber_gallery_test_filters[ $hook ][] = array(
		'callback'      => $callback,
		'priority'      => $priority,
		'accepted_args' => $accepted_args,
	);
}

/**
 * Capture an ACF field group for later assertions.
 *
 * @param array<string,mixed> $group Field-group configuration.
 *
 * @return void
 */
function acf_add_local_field_group( $group ): void {
	global $mai_cyber_gallery_test_groups;

	$mai_cyber_gallery_test_groups[] = $group;
}

/**
 * Return one configured ACF test value.
 *
 * @param string     $name         Field name.
 * @param string|int $post_id      Post or block identifier.
 * @param bool       $format_value Whether to format the value.
 *
 * @return mixed
 */
function get_field( $name, $post_id = false, $format_value = true ) {
	global $mai_cyber_gallery_test_current, $mai_cyber_gallery_test_values;
	unset( $format_value );

	$post_id = $post_id ? (string) $post_id : $mai_cyber_gallery_test_current;

	return $mai_cyber_gallery_test_values[ $post_id ][ $name ] ?? null;
}

/**
 * Sanitize a key for the standalone test environment.
 *
 * @param mixed $value Candidate key.
 *
 * @return string
 */
function sanitize_key( $value ): string {
	return preg_replace( '/[^a-z0-9_-]/', '', strtolower( (string) $value ) );
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
 * Return a URL unchanged in the standalone test environment.
 *
 * @param mixed $url Candidate URL.
 *
 * @return string
 */
function esc_url( $url ): string {
	return (string) $url;
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
 * Return representative Mai Engine image orientations.
 *
 * @return array<string,string>
 */
function mai_get_image_orientation_choices(): array {
	return array( 'landscape' => 'Landscape' );
}

/**
 * Return representative Mai Engine image sizes.
 *
 * @return array<string,string>
 */
function mai_get_image_size_choices(): array {
	return array( 'large' => 'Large' );
}

/**
 * Return representative Mai Engine color choices.
 *
 * @return array<string,string>
 */
function mai_get_radio_color_choices(): array {
	return array( '' => 'Default' );
}

/**
 * Return all gallery layouts needed by validation fixtures.
 *
 * @return array<string,string>
 */
function mai_cyber_gallery_get_layout_choices(): array {
	return array(
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
	);
}

/**
 * Return representative gallery arrow styles.
 *
 * @return array<string,string>
 */
function mai_cyber_gallery_get_arrow_style_choices(): array {
	return array( 'circle' => 'Circle' );
}

/**
 * Return representative slider navigation choices.
 *
 * @return array<string,string>
 */
function mai_cyber_gallery_get_slider_navigation_choices(): array {
	return array(
		'none'   => 'None',
		'arrows' => 'Arrows',
		'dots'   => 'Circles',
	);
}

/**
 * Return representative slider transitions.
 *
 * @return array<string,string>
 */
function mai_cyber_gallery_get_slider_transition_choices(): array {
	return array( 'slide' => 'Slide' );
}

/**
 * Return representative slider transition speeds.
 *
 * @return array<string,string>
 */
function mai_cyber_gallery_get_slider_transition_speed_choices(): array {
	return array( 'normal' => 'Normal' );
}

/**
 * Return representative slider navigation hover choices.
 *
 * @return array<string,string>
 */
function mai_cyber_gallery_get_slider_navigation_hover_choices(): array {
	return array( 'none' => 'None' );
}

/**
 * Return representative slider overlay choices.
 *
 * @return array<string,string>
 */
function mai_cyber_gallery_get_slider_overlay_style_choices(): array {
	return array( 'none' => 'None' );
}

/**
 * Return representative button styles.
 *
 * @return array<string,string>
 */
function mai_cyber_gallery_get_button_style_choices(): array {
	return array( 'button' => 'Button' );
}

/**
 * Resolve a deterministic default from the test choice set.
 *
 * @param array<string,string> $choices   Available choices.
 * @param mixed                $preferred Preferred key.
 * @param mixed                $exclude   Excluded key.
 *
 * @return string
 */
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

/**
 * Return the default test button style.
 *
 * @return string
 */
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
	$layouts = array();

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

$mai_cyber_gallery_test_values  = array(
	'block-grid'              => array(
		'layout'               => 'grid',
		'filmstrip_image_mode' => 'natural',
	),
	'block-comparison'        => array(
		'layout'               => 'comparison',
		'filmstrip_image_mode' => 'natural',
	),
	'block-slider'            => array(
		'layout'               => 'slider',
		'filmstrip_image_mode' => 'natural',
	),
	'block-filmstrip-natural' => array(
		'layout'               => 'filmstrip',
		'filmstrip_image_mode' => 'natural',
	),
	'block-filmstrip-logos'   => array(
		'layout'               => 'filmstrip',
		'filmstrip_image_mode' => 'logo-contain',
	),
	'block-current'           => array(
		'layout' => 'grid',
	),
);
$mai_cyber_gallery_test_current = 'block-current';

$comparison_field = array( 'key' => 'mai_cyber_gallery_comparison_before_image' );
$slide_field      = array( 'key' => 'mai_cyber_gallery_slide_image' );
$button_field     = array( 'key' => 'mai_cyber_gallery_slide_button_link' );
$logo_field       = array( 'key' => 'mai_cyber_gallery_filmstrip_logo_override_image' );

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
		array(),
		$button_field,
		'acf-block-grid[mai_cyber_gallery_slides][0][mai_cyber_gallery_slide_buttons][0][mai_cyber_gallery_slide_button_link]'
	),
	'inactive Slider CTA rows must bypass required and title validation'
);
mai_cyber_gallery_test_assert(
	false === mai_cyber_gallery_validate_slide_button_link(
		false,
		array(),
		$button_field,
		'acf-block-slider[mai_cyber_gallery_slides][0][mai_cyber_gallery_slide_buttons][0][mai_cyber_gallery_slide_button_link]'
	),
	'active empty Slider CTA links must retain ACF required errors'
);
mai_cyber_gallery_test_assert(
	'Add link text to use as the button label.' === mai_cyber_gallery_validate_slide_button_link(
		true,
		array(
			'url'   => 'https://example.com',
			'title' => '',
		),
		$button_field,
		'acf-block-slider[mai_cyber_gallery_slides][0][mai_cyber_gallery_slide_buttons][0][mai_cyber_gallery_slide_button_link]'
	),
	'active Slider CTA links must retain visible-label validation'
);
mai_cyber_gallery_test_assert(
	true === mai_cyber_gallery_validate_slide_button_link(
		true,
		array(
			'url'   => 'https://example.com',
			'title' => 'Learn more',
		),
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
	array(
		'mai_cyber_gallery_comparison_before_image',
		'mai_cyber_gallery_comparison_after_image',
		'mai_cyber_gallery_slide_image',
		'mai_cyber_gallery_filmstrip_logo_override_image',
	) as $field_key
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
	array(
		'key'               => 'mai_columns_md',
		'conditional_logic' => array(
			array(
				array(
					'field'    => 'mai_columns_responsive',
					'operator' => '==',
					'value'    => '1',
				),
			),
		),
	),
	array( 'key' => 'mai_cyber_gallery_columns_clone' )
);
mai_cyber_gallery_test_assert(
	3 === count( $column_field['conditional_logic'][0] ?? array() )
	&& 'mai_columns_responsive' === ( $column_field['conditional_logic'][0][0]['field'] ?? '' )
	&& 'justified' === ( $column_field['conditional_logic'][0][1]['value'] ?? '' )
	&& 'mosaic' === ( $column_field['conditional_logic'][0][2]['value'] ?? '' ),
	'cloned responsive fields must retain Engine conditions while excluding layouts that calculate their own columns'
);

$alignment_field      = mai_cyber_gallery_prepare_columns_clone_field(
	array(
		'key'               => 'mai_align_columns',
		'conditional_logic' => array(),
	),
	array( 'key' => 'mai_cyber_gallery_columns_clone' )
);
$alignment_exclusions = array_map(
	static fn( array $rule ): string => (string) ( $rule['value'] ?? '' ),
	$alignment_field['conditional_logic'][0] ?? array()
);
sort( $alignment_exclusions );

mai_cyber_gallery_test_assert(
	array( 'filmstrip', 'justified', 'masonry', 'mosaic', 'tiled' ) === $alignment_exclusions,
	'column alignment must be hidden for layouts whose packing or rail owns alignment'
);

$gap_field = array(
	'key'               => 'mai_column_gap',
	'conditional_logic' => array(),
);
mai_cyber_gallery_test_assert(
	mai_cyber_gallery_prepare_columns_clone_field(
		$gap_field,
		array( 'key' => 'mai_cyber_gallery_columns_clone' )
	) === $gap_field,
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
	array(
		'mai_cyber_gallery_comparison_before_image',
		'mai_cyber_gallery_comparison_after_image',
		'mai_cyber_gallery_slide_image',
		'mai_cyber_gallery_slide_button_link',
		'mai_cyber_gallery_filmstrip_logo_override_image',
	) as $field_key
) {
	$field = mai_cyber_gallery_test_find_field( $fields, $field_key );
	mai_cyber_gallery_test_assert(
		is_array( $field ) && 1 === ( $field['required'] ?? 0 ),
		"{$field_key} must remain required in its active context"
	);
}

$columns = mai_cyber_gallery_test_find_field( $fields, 'mai_cyber_gallery_columns_clone' );
mai_cyber_gallery_test_assert(
	array(
		'circle',
		'filmstrip',
		'grid',
		'justified',
		'masonry',
		'mosaic',
		'square',
		'tiled',
	) === mai_cyber_gallery_test_condition_layouts( $columns['conditional_logic'] ?? array() ),
	'the single Engine clone must cover every layout that uses columns or gaps'
);
mai_cyber_gallery_test_assert(
	1 === count( array_keys( $fields, $columns, true ) )
	&& count( $columns['clone'] ?? array() ) === count( array_unique( $columns['clone'] ?? array() ) )
	&& null === mai_cyber_gallery_test_find_field( $fields, 'mai_cyber_gallery_gaps_clone' ),
	'Engine spacing fields must have one canonical clone parent and one serialized value'
);

$aria_label = mai_cyber_gallery_test_find_field( $fields, 'mai_cyber_gallery_aria_label' );
mai_cyber_gallery_test_assert(
	is_array( $aria_label )
	&& 'text' === ( $aria_label['type'] ?? '' )
	&& '' === ( $aria_label['default_value'] ?? null )
	&& array( 'comparison', 'filmstrip', 'slider' ) === mai_cyber_gallery_test_condition_layouts( $aria_label['conditional_logic'] ?? array() ),
	'enhanced region layouts must expose an optional accessible label with a legacy-safe empty default'
);

$slider_title_tag = mai_cyber_gallery_test_find_field( $fields, 'mai_cyber_gallery_slider_title_tag' );
mai_cyber_gallery_test_assert(
	is_array( $slider_title_tag )
	&& 'select' === ( $slider_title_tag['type'] ?? '' )
	&& 'h2' === ( $slider_title_tag['default_value'] ?? '' )
	&& array( 'h2', 'h3', 'h4', 'h5', 'h6', 'p' ) === array_keys( $slider_title_tag['choices'] ?? array() )
	&& array( 'slider' ) === mai_cyber_gallery_test_condition_layouts( $slider_title_tag['conditional_logic'] ?? array() ),
	'Slider title semantics must offer h2-h6 and paragraph while preserving the former h2 default'
);

$desaturate                = mai_cyber_gallery_test_find_field( $fields, 'mai_cyber_gallery_hover_desaturate_amount' );
$ordinary_desaturate_rules = $desaturate['conditional_logic'][0] ?? array();
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

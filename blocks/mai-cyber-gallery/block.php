<?php
/**
 * Cyber Gallery for Mai Theme block registration and fields.
 *
 * @package MaiCyberGallery
 */

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

add_action( 'acf/init', 'mai_cyber_gallery_register_block' );

foreach (
	[
		'mai_cyber_gallery_slider_navigation_main',
		'mai_cyber_gallery_slider_navigation_border',
		'mai_cyber_gallery_slider_navigation_inner',
		'mai_cyber_gallery_slider_navigation_main_hover',
		'mai_cyber_gallery_slider_navigation_border_hover',
		'mai_cyber_gallery_slider_navigation_inner_hover',
		'mai_cyber_gallery_slider_overlay_color',
	] as $mai_cyber_gallery_color_field_key
) {
	add_filter(
		"acf/prepare_field/key={$mai_cyber_gallery_color_field_key}",
		'mai_prepare_legacy_color_field'
	);
	add_filter(
		"acf/format_value/key={$mai_cyber_gallery_color_field_key}",
		'mai_format_acf_color_value',
		10,
		3
	);
}
unset( $mai_cyber_gallery_color_field_key );

add_filter(
	'acf/prepare_field/key=mai_cyber_gallery_slider_navigation',
	'mai_cyber_gallery_prepare_slider_navigation_field'
);
add_filter(
	'acf/load_value/key=mai_cyber_gallery_slider_navigation_secondary',
	'mai_cyber_gallery_load_slider_navigation_secondary',
	10,
	3
);
foreach (
	[
		'mai_cyber_gallery_filmstrip_logo_padding_horizontal',
		'mai_cyber_gallery_filmstrip_logo_padding_vertical',
	] as $mai_cyber_gallery_logo_padding_field_key
) {
	add_filter(
		"acf/load_value/key={$mai_cyber_gallery_logo_padding_field_key}",
		'mai_cyber_gallery_load_logo_padding_axis',
		10,
		3
	);
}
unset( $mai_cyber_gallery_logo_padding_field_key );
add_filter(
	'attachment_fields_to_edit',
	'mai_cyber_gallery_add_logo_scale_attachment_field',
	10,
	2
);
add_action(
	'acf/render_field/key=mai_cyber_gallery_image',
	'mai_cyber_gallery_render_linked_image_logo_scale_field',
	10,
	0
);
add_action(
	'acf/render_field/key=mai_cyber_gallery_images',
	'mai_cyber_gallery_render_gallery_logo_scale_support',
	10,
	0
);

/**
 * Add a block-local logo-size control to this block's ACF Gallery sidebar.
 *
 * The inputs intentionally have no names. Editor JavaScript mirrors their
 * values into the existing filmstrip_logo_overrides repeater, so WordPress
 * never treats them as global attachment metadata.
 *
 * @since 0.7.1
 *
 * @param array<string,array<string,mixed>> $fields Attachment fields.
 * @param WP_Post                           $post   Attachment post.
 *
 * @return array<string,array<string,mixed>>
 */
function mai_cyber_gallery_add_logo_scale_attachment_field( $fields, $post ): array {
	if (
		! wp_doing_ajax()
		|| 'acf/fields/gallery/get_attachment' !== sanitize_text_field( (string) mai_get_acf_request( 'action' ) )
		|| 'mai_cyber_gallery_images' !== sanitize_key( (string) mai_get_acf_request( 'field_key' ) )
		|| ! $post instanceof WP_Post
		|| 'attachment' !== $post->post_type
		|| ! wp_attachment_is_image( $post )
	) {
		return $fields;
	}

	$fields['mai_cyber_gallery_logo_scale'] = [
		'tr' => sprintf(
			'<tr class="compat-field-mai-cyber-gallery-logo-scale mai-cyber-gallery-logo-scale-control" data-cyber-logo-scale-control data-attachment-id="%1$d" hidden>
				<th scope="row">%2$s</th>
				<td>
					<div class="mai-cyber-gallery-logo-scale-fields">
						<label class="mai-cyber-gallery-logo-scale-toggle">
							<input type="checkbox" value="1" data-cyber-logo-scale-enabled>
							<span>%3$s</span>
						</label>
						<div class="mai-cyber-gallery-logo-scale-range" data-cyber-logo-scale-range hidden>
							<input type="range" min="50" max="200" step="5" value="100" aria-label="%4$s" data-cyber-logo-scale-input>
							<output data-cyber-logo-scale-output>%5$s</output>
						</div>
						<p class="description">%6$s</p>
					</div>
				</td>
			</tr>',
			absint( $post->ID ),
			esc_html__( 'Logo Size', 'mai-cyber-gallery' ),
			esc_html__( 'Use a custom size for this image', 'mai-cyber-gallery' ),
			esc_attr__( 'Logo size percentage', 'mai-cyber-gallery' ),
			esc_html__( '100%', 'mai-cyber-gallery' ),
			esc_html__( 'Applies only to this gallery.', 'mai-cyber-gallery' )
		),
	];

	return $fields;
}

/**
 * Render the block-local logo-size control for a Linked Images row.
 *
 * Its unnamed inputs are progressive editor controls only. The existing
 * filmstrip_logo_overrides repeater remains the canonical saved field.
 *
 * @since 0.7.1
 *
 * @return void
 */
function mai_cyber_gallery_render_linked_image_logo_scale_field(): void {
	?>
	<div class="mai-cyber-gallery-logo-scale-control mai-cyber-gallery-logo-scale-control-inline" data-cyber-logo-scale-control data-cyber-logo-scale-linked hidden>
		<p class="mai-cyber-gallery-logo-scale-heading">
			<strong><?php esc_html_e( 'Logo Size', 'mai-cyber-gallery' ); ?></strong>
		</p>
		<div class="mai-cyber-gallery-logo-scale-fields">
			<label class="mai-cyber-gallery-logo-scale-toggle">
				<input type="checkbox" value="1" data-cyber-logo-scale-enabled>
				<span><?php esc_html_e( 'Use a custom size for this image', 'mai-cyber-gallery' ); ?></span>
			</label>
			<div class="mai-cyber-gallery-logo-scale-range" data-cyber-logo-scale-range hidden>
				<input type="range" min="50" max="200" step="5" value="100" aria-label="<?php esc_attr_e( 'Logo size percentage', 'mai-cyber-gallery' ); ?>" data-cyber-logo-scale-input>
				<output data-cyber-logo-scale-output><?php esc_html_e( '100%', 'mai-cyber-gallery' ); ?></output>
			</div>
			<p class="description"><?php esc_html_e( 'Applies only to this gallery.', 'mai-cyber-gallery' ); ?></p>
		</div>
	</div>
	<?php
}

/**
 * Render a feature-detection marker beside the ACF Gallery field.
 *
 * Editor JavaScript requires this server-rendered marker before replacing the
 * canonical repeater UI with attachment-side controls.
 *
 * @since 0.7.1
 *
 * @return void
 */
function mai_cyber_gallery_render_gallery_logo_scale_support(): void {
	echo '<span data-cyber-logo-scale-gallery-support hidden></span>';
}

/**
 * Preserve intentionally atomic navigation saved before the second selector.
 *
 * A missing primary value means the block used the former Arrows + Circles
 * default. A stored atomic primary means its single-control choice was
 * intentional, while the former aggregate maps to canonical Circles here.
 *
 * @since 0.6.0
 *
 * @param mixed               $value   Loaded/default field value.
 * @param int|string          $post_id ACF post or block ID.
 * @param array<string,mixed> $field   ACF field.
 *
 * @return mixed
 */
function mai_cyber_gallery_load_slider_navigation_secondary( $value, $post_id, $field ) {
	$meta = acf_get_meta( $post_id );

	if (
		is_array( $meta )
		&& ! array_key_exists( 'slider_navigation_secondary', $meta )
		&& array_key_exists( 'slider_navigation', $meta )
	) {
		return 'arrows-dots' === $meta['slider_navigation'] ? 'dots' : 'none';
	}

	return $value;
}

/**
 * Use the former shared logo padding for either new axis until it is saved.
 *
 * @since 0.8.3
 *
 * @param mixed               $value   Loaded/default field value.
 * @param int|string          $post_id ACF post or block ID.
 * @param array<string,mixed> $field   ACF field.
 *
 * @return mixed
 */
function mai_cyber_gallery_load_logo_padding_axis( $value, $post_id, $field ) {
	$meta = acf_get_meta( $post_id );
	$name = isset( $field['name'] ) ? (string) $field['name'] : '';

	if (
		$name
		&& is_array( $meta )
		&& ! array_key_exists( $name, $meta )
		&& array_key_exists( 'filmstrip_logo_padding', $meta )
	) {
		return $meta['filmstrip_logo_padding'];
	}

	return $value;
}

/**
 * Present the former combined choice as canonical Primary Arrows.
 *
 * Secondary Navigation defaults to Circles, so the editor shows the same
 * combination and writes the two canonical fields when the block is resaved.
 *
 * @since 0.6.0
 *
 * @param array<string,mixed> $field ACF field.
 *
 * @return array<string,mixed>
 */
function mai_cyber_gallery_prepare_slider_navigation_field( $field ): array {
	if ( isset( $field['value'] ) && 'arrows-dots' === $field['value'] ) {
		$field['value'] = 'arrows';
	}

	return $field;
}

/**
 * Register the block from metadata.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_cyber_gallery_register_block(): void {
	register_block_type( __DIR__ . '/block.json' );
}

/**
 * Render the dynamic ACF block.
 *
 * @since 0.1.0
 *
 * @param array<string, mixed> $block      Block settings and attributes.
 * @param string               $content    Inner block content.
 * @param bool                 $is_preview Whether this is an editor preview.
 * @param int                  $post_id    Current post ID.
 *
 * @return void
 */
function mai_cyber_gallery_render_block( $block, $content = '', $is_preview = false, $post_id = 0 ): void {
	$instance = mai_get_index( 'mai-cyber-gallery-block' ) + 1;
	$block_id = isset( $block['id'] ) ? sanitize_html_class( $block['id'] ) : 'block';
	$block_data = isset( $block['data'] ) && is_array( $block['data'] )
		? $block['data']
		: [];
	$layout                      = get_field( 'layout' );
	$hover                       = 'slider' === $layout ? get_field( 'slider_hover' ) : get_field( 'hover' );
	$slider_navigation           = get_field( 'slider_navigation' );
	$slider_navigation_secondary = get_field( 'slider_navigation_secondary' );
	$filmstrip_controls          = array_key_exists( 'filmstrip_controls', $block_data )
		? get_field( 'filmstrip_controls' )
		: true;
	$filmstrip_arrows           = array_key_exists( 'filmstrip_arrows', $block_data )
		? get_field( 'filmstrip_arrows' )
		: false;
	$legacy_logo_padding        = array_key_exists( 'filmstrip_logo_padding', $block_data )
		? $block_data['filmstrip_logo_padding']
		: 24;
	$logo_padding_horizontal    = array_key_exists( 'filmstrip_logo_padding_horizontal', $block_data )
		? get_field( 'filmstrip_logo_padding_horizontal' )
		: $legacy_logo_padding;
	$logo_padding_vertical      = array_key_exists( 'filmstrip_logo_padding_vertical', $block_data )
		? get_field( 'filmstrip_logo_padding_vertical' )
		: $legacy_logo_padding;
	$slider_autoplay_control     = array_key_exists( 'slider_autoplay_control', $block_data )
		? get_field( 'slider_autoplay_control' )
		: true;
	$slider_title_tag           = array_key_exists( 'slider_title_tag', $block_data )
		? get_field( 'slider_title_tag' )
		: 'h2';
	$aria_label                 = array_key_exists( 'aria_label', $block_data )
		? get_field( 'aria_label' )
		: '';
	$comparison_before_label    = array_key_exists( 'comparison_before_label', $block_data )
		? get_field( 'comparison_before_label' )
		: esc_html__( 'Before', 'mai-cyber-gallery' );
	$comparison_after_label     = array_key_exists( 'comparison_after_label', $block_data )
		? get_field( 'comparison_after_label' )
		: esc_html__( 'After', 'mai-cyber-gallery' );
	$comparison_position        = array_key_exists( 'comparison_position', $block_data )
		? get_field( 'comparison_position' )
		: 50;

	// Preserve explicit pre-0.6.0 single-navigation choices. A block with no
	// stored primary value used the former Arrows + Circles default, while an
	// explicitly stored atomic primary was intentionally a single control.
	if (
		! array_key_exists( 'slider_navigation_secondary', $block_data )
		&& array_key_exists( 'slider_navigation', $block_data )
		&& 'arrows-dots' !== $slider_navigation
	) {
		$slider_navigation_secondary = 'none';
	}

	// Identical ACF blocks can share a content-derived block ID. Keep front-end
	// output deterministic by occurrence, and editor AJAX previews globally unique.
	$block_id .= $is_preview
		? '-' . substr( wp_generate_uuid4(), 0, 8 )
		: '-' . $instance;

	$args = [
		'preview'                => $is_preview,
		'id'                     => 'mai-cyber-gallery-' . $block_id,
		'align'                  => isset( $block['align'] ) ? $block['align'] : '',
		'anchor'                 => isset( $block['anchor'] ) ? $block['anchor'] : '',
		'class'                  => isset( $block['className'] ) ? $block['className'] : '',
		'aria_label'             => $aria_label,
		'layout'                 => $layout,
		'click_action'           => get_field( 'click_action' ),
		'lightbox_arrow_style'   => get_field( 'lightbox_arrow_style' ),
		'images'                 => get_field( 'images' ),
		'images_links'           => get_field( 'images_links' ),
		'slides'                 => get_field( 'slides' ),
		'comparison_before_image' => get_field( 'comparison_before_image' ),
		'comparison_after_image'  => get_field( 'comparison_after_image' ),
		'comparison_before_label' => $comparison_before_label,
		'comparison_after_label'  => $comparison_after_label,
		'comparison_position'     => $comparison_position,
		'image_orientation'      => get_field( 'image_orientation' ),
		'image_size'             => get_field( 'image_size' ),
		'image_radius'           => get_field( 'image_radius' ),
		'captions'               => get_field( 'captions' ),
		'shadow'                 => get_field( 'shadow' ),
		'hover'                  => $hover,
		'hover_desaturate_amount' => get_field( 'hover_desaturate_amount' ),
		'masonry_flow'           => get_field( 'masonry_flow' ),
		'justified_row_height'   => get_field( 'justified_row_height' ),
		'justified_last_row'     => get_field( 'justified_last_row' ),
		'tiled_pattern'          => get_field( 'tiled_pattern' ),
		'mosaic_feature_side'    => get_field( 'mosaic_feature_side' ),
		'mosaic_alternate'       => get_field( 'mosaic_alternate' ),
		'mosaic_items_after_feature' => get_field( 'mosaic_items_after_feature' ),
		'mosaic_odd_alignment'   => get_field( 'mosaic_odd_alignment' ),
		'mosaic_row_height'      => get_field( 'mosaic_row_height' ),
		'filmstrip_autoplay'          => get_field( 'filmstrip_autoplay' ),
		'filmstrip_autoplay_interval' => get_field( 'filmstrip_autoplay_interval' ),
		'filmstrip_autoplay_items'    => get_field( 'filmstrip_autoplay_items' ),
		'filmstrip_pause_on_hover'    => get_field( 'filmstrip_pause_on_hover' ),
		'filmstrip_controls'          => $filmstrip_controls,
		'filmstrip_arrows'            => $filmstrip_arrows,
		'filmstrip_arrow_position'    => get_field( 'filmstrip_arrow_position' ),
		'filmstrip_arrow_style'       => get_field( 'filmstrip_arrow_style' ),
		'filmstrip_image_mode'        => get_field( 'filmstrip_image_mode' ),
		'filmstrip_height'            => get_field( 'filmstrip_height' ),
		'filmstrip_height_mobile'     => get_field( 'filmstrip_height_mobile' ),
		'filmstrip_logo_padding'      => $legacy_logo_padding,
		'filmstrip_logo_padding_horizontal' => $logo_padding_horizontal,
		'filmstrip_logo_padding_vertical'   => $logo_padding_vertical,
		'filmstrip_logo_overrides'    => get_field( 'filmstrip_logo_overrides' ),
		'slider_navigation'           => $slider_navigation,
		'slider_navigation_secondary' => $slider_navigation_secondary,
		'slider_navigation_position' => get_field( 'slider_navigation_position' ),
		'slider_dots_position'        => get_field( 'slider_dots_position' ),
		'slider_arrow_style'          => get_field( 'slider_arrow_style' ),
		'slider_transition'           => get_field( 'slider_transition' ),
		'slider_transition_speed' => get_field( 'slider_transition_speed' ),
		'slider_autoplay'         => get_field( 'slider_autoplay' ),
		'slider_autoplay_control' => $slider_autoplay_control,
		'slider_autoplay_interval' => get_field( 'slider_autoplay_interval' ),
		'slider_pause_on_hover'    => get_field( 'slider_pause_on_hover' ),
		'slider_navigation_hover' => get_field( 'slider_navigation_hover' ),
		'slider_navigation_main'  => get_field( 'slider_navigation_main' ),
		'slider_navigation_border' => get_field( 'slider_navigation_border' ),
		'slider_navigation_inner'  => get_field( 'slider_navigation_inner' ),
		'slider_navigation_main_hover' => get_field( 'slider_navigation_main_hover' ),
		'slider_navigation_border_hover' => get_field( 'slider_navigation_border_hover' ),
		'slider_navigation_inner_hover' => get_field( 'slider_navigation_inner_hover' ),
		'slider_overlay_style'        => get_field( 'slider_overlay_style' ),
		'slider_overlay_color'        => get_field( 'slider_overlay_color' ),
		'slider_overlay_opacity'      => get_field( 'slider_overlay_opacity' ),
		'slider_width_mode'           => get_field( 'slider_width_mode' ),
		'slider_width'                => get_field( 'slider_width' ),
		'slider_height'               => get_field( 'slider_height' ),
		'slider_height_mobile'        => get_field( 'slider_height_mobile' ),
		'slider_image_fit'            => get_field( 'slider_image_fit' ),
		'slider_title_tag'            => $slider_title_tag,
		'columns'                => get_field( 'columns' ),
		'columns_responsive'     => get_field( 'columns_responsive' ),
		'columns_md'             => get_field( 'columns_md' ),
		'columns_sm'             => get_field( 'columns_sm' ),
		'columns_xs'             => get_field( 'columns_xs' ),
		'align_columns'          => get_field( 'align_columns' ),
		'align_columns_vertical' => get_field( 'align_columns_vertical' ),
		'column_gap'             => get_field( 'column_gap' ),
		'row_gap'                => get_field( 'row_gap' ),
		'margin_top'             => get_field( 'margin_top' ),
		'margin_right'           => get_field( 'margin_right' ),
		'margin_bottom'          => get_field( 'margin_bottom' ),
		'margin_left'            => get_field( 'margin_left' ),
	];

	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Gallery handles escaping.
	echo mai_get_cyber_gallery( $args );
}

add_action( 'acf/init', 'mai_cyber_gallery_register_field_group', 12 );
add_filter( 'acf/clone_field', 'mai_cyber_gallery_prepare_columns_clone_field', 20, 2 );
add_filter( 'acf/validate_value/key=mai_cyber_gallery_slide_button_link', 'mai_cyber_gallery_validate_slide_button_link', 10, 4 );
foreach (
	[
		'mai_cyber_gallery_comparison_before_image',
		'mai_cyber_gallery_comparison_after_image',
		'mai_cyber_gallery_slide_image',
		'mai_cyber_gallery_filmstrip_logo_override_image',
	] as $mai_cyber_gallery_conditional_required_field_key
) {
	add_filter(
		"acf/validate_value/key={$mai_cyber_gallery_conditional_required_field_key}",
		'mai_cyber_gallery_validate_conditional_required_value',
		10,
		4
	);
}
unset( $mai_cyber_gallery_conditional_required_field_key );

/**
 * Hide cloned column controls where only Mai Engine's gap fields apply.
 *
 * Keeping one clone avoids rendering the same Engine gap fields under two ACF
 * parents, which could serialize competing values. Existing Engine conditions
 * (such as responsive-column toggles) are retained and combined with the
 * gallery-layout rules.
 *
 * @since 1.0.0
 *
 * @param array<string,mixed> $field       Field being cloned.
 * @param array<string,mixed> $clone_field Clone field settings.
 *
 * @return array<string,mixed>
 */
function mai_cyber_gallery_prepare_columns_clone_field( $field, $clone_field ): array {
	if (
		'mai_cyber_gallery_columns_clone' !== ( $clone_field['key'] ?? '' )
		|| ! in_array(
			$field['key'] ?? '',
			[
				'mai_columns',
				'mai_columns_responsive',
				'mai_columns_md',
				'mai_columns_sm',
				'mai_columns_xs',
				'mai_align_columns',
				'mai_align_columns_vertical',
			],
			true
		)
	) {
		return $field;
	}

	$excluded_layouts = [ 'justified', 'mosaic' ];

	if (
		in_array(
			$field['key'] ?? '',
			[ 'mai_align_columns', 'mai_align_columns_vertical' ],
			true
		)
	) {
		$excluded_layouts = array_merge(
			$excluded_layouts,
			[ 'masonry', 'tiled', 'filmstrip' ]
		);
	}

	$layout_rules = array_map(
		static fn( string $layout ): array => [
			'field'    => 'mai_cyber_gallery_layout',
			'operator' => '!=',
			'value'    => $layout,
		],
		$excluded_layouts
	);
	$conditions   = isset( $field['conditional_logic'] ) && is_array( $field['conditional_logic'] )
		? $field['conditional_logic']
		: [];

	if ( ! $conditions ) {
		$field['conditional_logic'] = [ $layout_rules ];
		return $field;
	}

	$field['conditional_logic'] = [];
	foreach ( $conditions as $condition ) {
		if ( is_array( $condition ) ) {
			$field['conditional_logic'][] = array_merge( $condition, $layout_rules );
		}
	}

	if ( ! $field['conditional_logic'] ) {
		$field['conditional_logic'] = [ $layout_rules ];
	}

	return $field;
}

/**
 * Get the ACF block ID that owns a field being validated.
 *
 * ACF block-v3 validation prefixes every input with `acf-{$block_id}`. Using
 * that ID instead of ACF's mutable global "current" meta context keeps
 * validation deterministic when one request contains multiple gallery blocks.
 *
 * @since 1.0.0
 *
 * @param mixed $input ACF input name.
 *
 * @return string|false
 */
function mai_cyber_gallery_get_validation_block_id( $input ) {
	if ( ! is_string( $input ) || ! str_starts_with( $input, 'acf-' ) ) {
		return false;
	}

	$separator = strpos( $input, '[' );
	if ( false === $separator || 4 >= $separator ) {
		return false;
	}

	$block_id = substr( $input, 4, $separator - 4 );

	return preg_match( '/^[A-Za-z0-9_-]+$/D', $block_id ) ? $block_id : false;
}

/**
 * Read an unformatted field from the block currently being validated.
 *
 * ACF calls `acf_setup_meta()` before validating block-v3 request data, so the
 * submitted values are available through the normal field API under the block
 * ID parsed from the input name.
 *
 * @since 1.0.0
 *
 * @param string $name  Field name.
 * @param mixed  $input ACF input name.
 *
 * @return string
 */
function mai_cyber_gallery_get_validation_context_value( string $name, $input ): string {
	if ( ! function_exists( 'get_field' ) ) {
		return '';
	}

	$block_id = mai_cyber_gallery_get_validation_block_id( $input );
	$value    = get_field( $name, $block_id ? $block_id : false, false );

	return is_scalar( $value ) ? sanitize_key( (string) $value ) : '';
}

/**
 * Check whether a conditionally required field is active for this block.
 *
 * The editor intentionally serializes hidden Cyber fields so values survive a
 * layout switch. ACF does not apply conditional logic during validation, so
 * requirements must be scoped to the submitted layout on the server.
 *
 * @since 1.0.0
 *
 * @param array<string,mixed> $field ACF field settings.
 * @param mixed               $input ACF input name.
 *
 * @return bool
 */
function mai_cyber_gallery_is_required_field_active( array $field, $input ): bool {
	$key    = isset( $field['key'] ) && is_scalar( $field['key'] ) ? (string) $field['key'] : '';
	$layout = mai_cyber_gallery_get_validation_context_value( 'layout', $input );

	if (
		in_array(
			$key,
			[
				'mai_cyber_gallery_comparison_before_image',
				'mai_cyber_gallery_comparison_after_image',
			],
			true
		)
	) {
		return 'comparison' === $layout;
	}

	if (
		in_array(
			$key,
			[
				'mai_cyber_gallery_slide_image',
				'mai_cyber_gallery_slide_button_link',
			],
			true
		)
	) {
		return 'slider' === $layout;
	}

	if ( 'mai_cyber_gallery_filmstrip_logo_override_image' === $key ) {
		return 'filmstrip' === $layout
			&& 'logo-contain' === mai_cyber_gallery_get_validation_context_value( 'filmstrip_image_mode', $input );
	}

	return true;
}

/**
 * Ignore required errors for fields preserved from an inactive layout.
 *
 * Active fields retain the validation result produced by ACF and other
 * validators. Inactive fields return true because their saved value is dormant
 * configuration, not part of the gallery currently being authored.
 *
 * @since 1.0.0
 *
 * @param bool|string          $valid Whether the current value is valid.
 * @param mixed                $value Submitted field value.
 * @param array<string,mixed>  $field ACF field settings.
 * @param mixed                $input ACF input name.
 *
 * @return bool|string
 */
function mai_cyber_gallery_validate_conditional_required_value( $valid, $value, $field, $input ) {
	return mai_cyber_gallery_is_required_field_active( (array) $field, $input )
		? $valid
		: true;
}

/**
 * Require visible text for Slider CTA links.
 *
 * ACF's Link field only validates its URL, while the link title is the CTA's
 * visible label in this block.
 *
 * @since 0.3.0
 *
 * @param bool|string          $valid Whether the current value is valid.
 * @param mixed                $value Submitted Link field value.
 * @param array<string, mixed> $field ACF field settings.
 * @param string               $input Input name.
 *
 * @return bool|string
 */
function mai_cyber_gallery_validate_slide_button_link( $valid, $value, $field, $input ) {
	if ( ! mai_cyber_gallery_is_required_field_active( (array) $field, $input ) ) {
		return true;
	}

	if ( true !== $valid ) {
		return $valid;
	}

	$title = is_array( $value ) && isset( $value['title'] ) && is_scalar( $value['title'] )
		? sanitize_text_field( (string) $value['title'] )
		: '';

	return '' !== $title
		? $valid
		: esc_html__( 'Add link text to use as the button label.', 'mai-cyber-gallery' );
}

/**
 * Register the local ACF field group after Mai Engine's clone fields.
 *
 * @since 0.1.0
 *
 * @return void
 */
function mai_cyber_gallery_register_field_group(): void {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	$orientations              = (array) mai_get_image_orientation_choices();
	$image_sizes               = (array) mai_get_image_size_choices();
	$slider_width              = max( 0, min( 2400, absint( mai_get_breakpoint( 'xl' ) ) ) );
	$layout_choices            = mai_cyber_gallery_get_layout_choices();
	$arrow_style_choices       = mai_cyber_gallery_get_arrow_style_choices();
	$button_style_choices      = mai_cyber_gallery_get_button_style_choices();
	$slider_navigation_choices = mai_cyber_gallery_get_slider_navigation_choices();
	$slider_transition_choices = mai_cyber_gallery_get_slider_transition_choices();
	$slider_speed_choices      = mai_cyber_gallery_get_slider_transition_speed_choices();
	$slider_hover_choices      = mai_cyber_gallery_get_slider_navigation_hover_choices();
	$slider_overlay_choices    = mai_cyber_gallery_get_slider_overlay_style_choices();
	$slider_navigation_default = mai_cyber_gallery_get_choice_default( $slider_navigation_choices, 'arrows' );
	$slider_navigation_secondary_default = mai_cyber_gallery_get_choice_default(
		$slider_navigation_choices,
		'dots',
		$slider_navigation_default
	);
	$hover_choices             = [
		'none'       => esc_html__( 'None', 'mai-cyber-gallery' ),
		'zoom'       => esc_html__( 'Zoom', 'mai-cyber-gallery' ),
		'lift'       => esc_html__( 'Lift', 'mai-cyber-gallery' ),
		'fade'       => esc_html__( 'Fade', 'mai-cyber-gallery' ),
		'desaturate' => esc_html__( 'Desaturate', 'mai-cyber-gallery' ),
	];

	if ( ! isset( $image_sizes['full'] ) ) {
		$image_sizes = [ 'full' => esc_html__( 'Full Size', 'mai-cyber-gallery' ) ] + $image_sizes;
	}

	$slider_condition = [
		[
			[
				'field'    => 'mai_cyber_gallery_layout',
				'operator' => '==',
				'value'    => 'slider',
			],
		],
	];
	$comparison_condition = [
		[
			[
				'field'    => 'mai_cyber_gallery_layout',
				'operator' => '==',
				'value'    => 'comparison',
			],
		],
	];
	$masonry_condition = [
		[
			[
				'field'    => 'mai_cyber_gallery_layout',
				'operator' => '==',
				'value'    => 'masonry',
			],
		],
	];
	$justified_condition = [
		[
			[
				'field'    => 'mai_cyber_gallery_layout',
				'operator' => '==',
				'value'    => 'justified',
			],
		],
	];
	$tiled_condition = [
		[
			[
				'field'    => 'mai_cyber_gallery_layout',
				'operator' => '==',
				'value'    => 'tiled',
			],
		],
	];
	$mosaic_condition = [
		[
			[
				'field'    => 'mai_cyber_gallery_layout',
				'operator' => '==',
				'value'    => 'mosaic',
			],
		],
	];
	$columns_condition = [];
	foreach ( [ 'grid', 'masonry', 'tiled', 'square', 'circle', 'filmstrip' ] as $layout ) {
		$columns_condition[] = [
			[
				'field'    => 'mai_cyber_gallery_layout',
				'operator' => '==',
				'value'    => $layout,
			],
		];
	}
	$columns_and_gaps_condition = array_merge( $columns_condition, $justified_condition, $mosaic_condition );
	$filmstrip_condition = [
		[
			[
				'field'    => 'mai_cyber_gallery_layout',
				'operator' => '==',
				'value'    => 'filmstrip',
			],
		],
	];
	$region_condition = array_merge( $filmstrip_condition, $slider_condition, $comparison_condition );
	$filmstrip_autoplay_condition = [
		[
			[
				'field'    => 'mai_cyber_gallery_layout',
				'operator' => '==',
				'value'    => 'filmstrip',
			],
			[
				'field'    => 'mai_cyber_gallery_filmstrip_autoplay',
				'operator' => '==',
				'value'    => '1',
			],
		],
	];
	$filmstrip_controls_enabled_condition = [
		[
			[
				'field'    => 'mai_cyber_gallery_layout',
				'operator' => '==',
				'value'    => 'filmstrip',
			],
			[
				'field'    => 'mai_cyber_gallery_filmstrip_controls',
				'operator' => '==',
				'value'    => '1',
			],
		],
	];
	$filmstrip_visible_autoplay_condition = [
		[
			[
				'field'    => 'mai_cyber_gallery_layout',
				'operator' => '==',
				'value'    => 'filmstrip',
			],
			[
				'field'    => 'mai_cyber_gallery_filmstrip_controls',
				'operator' => '==',
				'value'    => '1',
			],
			[
				'field'    => 'mai_cyber_gallery_filmstrip_autoplay',
				'operator' => '==',
				'value'    => '1',
			],
		],
	];
	$filmstrip_arrows_condition = [
		[
			[
				'field'    => 'mai_cyber_gallery_layout',
				'operator' => '==',
				'value'    => 'filmstrip',
			],
			[
				'field'    => 'mai_cyber_gallery_filmstrip_controls',
				'operator' => '==',
				'value'    => '1',
			],
			[
				'field'    => 'mai_cyber_gallery_filmstrip_arrows',
				'operator' => '==',
				'value'    => '1',
			],
		],
	];
	$filmstrip_controls_condition = array_merge(
		$filmstrip_arrows_condition,
		$filmstrip_visible_autoplay_condition
	);
	$filmstrip_uniform_condition = [
		[
			[
				'field'    => 'mai_cyber_gallery_layout',
				'operator' => '==',
				'value'    => 'filmstrip',
			],
			[
				'field'    => 'mai_cyber_gallery_filmstrip_image_mode',
				'operator' => '!=',
				'value'    => 'natural',
			],
		],
	];
	$filmstrip_logo_condition = [
		[
			[
				'field'    => 'mai_cyber_gallery_layout',
				'operator' => '==',
				'value'    => 'filmstrip',
			],
			[
				'field'    => 'mai_cyber_gallery_filmstrip_image_mode',
				'operator' => '==',
				'value'    => 'logo-contain',
			],
		],
	];
	$slider_transition_effect_condition = [
		[
			[
				'field'    => 'mai_cyber_gallery_layout',
				'operator' => '==',
				'value'    => 'slider',
			],
			[
				'field'    => 'mai_cyber_gallery_slider_transition',
				'operator' => '!=',
				'value'    => 'native',
			],
		],
	];
	$slider_autoplay_condition = [
		[
			[
				'field'    => 'mai_cyber_gallery_layout',
				'operator' => '==',
				'value'    => 'slider',
			],
			[
				'field'    => 'mai_cyber_gallery_slider_autoplay',
				'operator' => '==',
				'value'    => '1',
			],
		],
	];
	$slider_autoplay_control_condition = [
		[
			[
				'field'    => 'mai_cyber_gallery_layout',
				'operator' => '==',
				'value'    => 'slider',
			],
			[
				'field'    => 'mai_cyber_gallery_slider_autoplay',
				'operator' => '==',
				'value'    => '1',
			],
			[
				'field'    => 'mai_cyber_gallery_slider_autoplay_control',
				'operator' => '==',
				'value'    => '1',
			],
		],
	];
	$slider_navigation_fields = [
		'mai_cyber_gallery_slider_navigation',
		'mai_cyber_gallery_slider_navigation_secondary',
	];
	$slider_navigation_condition             = [];
	$slider_navigation_interactive_condition = [];
	$slider_arrows_condition                 = [];
	$slider_dots_overlay_condition           = [];

	foreach ( $slider_navigation_fields as $navigation_field ) {
		$slider_navigation_condition[] = [
			[
				'field'    => 'mai_cyber_gallery_layout',
				'operator' => '==',
				'value'    => 'slider',
			],
			[
				'field'    => $navigation_field,
				'operator' => '!=',
				'value'    => 'none',
			],
		];

		foreach ( [ 'arrows', 'dots' ] as $navigation ) {
			$slider_navigation_interactive_condition[] = [
				[
					'field'    => 'mai_cyber_gallery_layout',
					'operator' => '==',
					'value'    => 'slider',
				],
				[
					'field'    => $navigation_field,
					'operator' => '==',
					'value'    => $navigation,
				],
			];
		}

		$slider_arrows_condition[] = [
			[
				'field'    => 'mai_cyber_gallery_layout',
				'operator' => '==',
				'value'    => 'slider',
			],
			[
				'field'    => $navigation_field,
				'operator' => '==',
				'value'    => 'arrows',
			],
		];
		$slider_dots_overlay_condition[] = [
			[
				'field'    => 'mai_cyber_gallery_layout',
				'operator' => '==',
				'value'    => 'slider',
			],
			[
				'field'    => $navigation_field,
				'operator' => '==',
				'value'    => 'dots',
			],
			[
				'field'    => 'mai_cyber_gallery_slider_navigation_position',
				'operator' => '==',
				'value'    => 'overlay',
			],
		];
	}
	$slider_navigation_interactive_condition[] = [
		[
			'field'    => 'mai_cyber_gallery_layout',
			'operator' => '==',
			'value'    => 'slider',
		],
		[
			'field'    => 'mai_cyber_gallery_slider_navigation',
			'operator' => '==',
			'value'    => 'arrows-dots',
		],
	];
	$slider_arrows_condition[] = [
		[
			'field'    => 'mai_cyber_gallery_layout',
			'operator' => '==',
			'value'    => 'slider',
		],
		[
			'field'    => 'mai_cyber_gallery_slider_navigation',
			'operator' => '==',
			'value'    => 'arrows-dots',
		],
	];
	$slider_dots_overlay_condition[] = [
		[
			'field'    => 'mai_cyber_gallery_layout',
			'operator' => '==',
			'value'    => 'slider',
		],
		[
			'field'    => 'mai_cyber_gallery_slider_navigation',
			'operator' => '==',
			'value'    => 'arrows-dots',
		],
		[
			'field'    => 'mai_cyber_gallery_slider_navigation_position',
			'operator' => '==',
			'value'    => 'overlay',
		],
	];
	$slider_navigation_condition[] = [
		[
			'field'    => 'mai_cyber_gallery_layout',
			'operator' => '==',
			'value'    => 'slider',
		],
		[
			'field'    => 'mai_cyber_gallery_slider_autoplay',
			'operator' => '==',
			'value'    => '1',
		],
		[
			'field'    => 'mai_cyber_gallery_slider_autoplay_control',
			'operator' => '==',
			'value'    => '1',
		],
	];
	$slider_navigation_interactive_condition = array_merge(
		$slider_navigation_interactive_condition,
		$slider_autoplay_control_condition
	);
	$navigation_color_condition       = array_merge( $slider_navigation_condition, $filmstrip_controls_condition );
	$navigation_interactive_condition = array_merge( $slider_navigation_interactive_condition, $filmstrip_controls_condition );
	$slider_overlay_condition = [
		[
			[
				'field'    => 'mai_cyber_gallery_layout',
				'operator' => '!=',
				'value'    => 'comparison',
			],
			[
				'field'    => 'mai_cyber_gallery_slider_overlay_style',
				'operator' => '!=',
				'value'    => 'none',
			],
		],
	];

	/**
	 * Build a Mai palette radio field and its conditional custom picker.
	 *
	 * @param string                              $key               Unique field key suffix.
	 * @param string                              $label             Visible field label.
	 * @param string                              $name              Stored field name.
	 * @param string                              $default           Default palette slug.
	 * @param array<int,array<int,array<string,string>>> $conditional_logic ACF conditional rules.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	$make_color_fields = static function ( $key, $label, $name, $default, $conditional_logic ) {
		$choices      = mai_get_radio_color_choices();
		$field_key    = 'mai_cyber_gallery_' . $key;
		$custom_logic = [];

		if ( $default && isset( $choices[''] ) ) {
			$choices[''] = esc_html__( 'Default', 'mai-cyber-gallery' );
		}

		foreach ( $conditional_logic as $group ) {
			$group[]       = [
				'field'    => $field_key,
				'operator' => '==',
				'value'    => 'custom',
			];
			$custom_logic[] = $group;
		}

		return [
			[
				'key'               => $field_key,
					'label'             => $label,
					'name'              => $name,
					'type'              => 'radio',
					'choices'           => $choices,
				'default_value'     => $default,
				'wrapper'           => [
					'class' => 'mai-block-colors',
				],
				'conditional_logic' => $conditional_logic,
			],
			[
				'key'               => $field_key . '_custom',
				'label'             => sprintf(
					/* translators: %s: Color setting label. */
					esc_html__( 'Custom %s', 'mai-cyber-gallery' ),
					$label
				),
				'name'              => $name . '_custom',
				'type'              => 'color_picker',
				'return_format'     => 'string',
				'conditional_logic' => $custom_logic,
			],
		];
	};

	$slider_navigation_color_fields = [];
	foreach (
		[
			[ 'slider_navigation_main', esc_html__( 'Main Color', 'mai-cyber-gallery' ), 'slider_navigation_main', '', $navigation_color_condition ],
			[ 'slider_navigation_border', esc_html__( 'Border Color', 'mai-cyber-gallery' ), 'slider_navigation_border', '', $navigation_color_condition ],
			[ 'slider_navigation_inner', esc_html__( 'Inner Color', 'mai-cyber-gallery' ), 'slider_navigation_inner', '', $navigation_color_condition ],
			[ 'slider_navigation_main_hover', esc_html__( 'Main Hover Color', 'mai-cyber-gallery' ), 'slider_navigation_main_hover', '', $navigation_interactive_condition ],
			[ 'slider_navigation_border_hover', esc_html__( 'Border Hover Color', 'mai-cyber-gallery' ), 'slider_navigation_border_hover', '', $navigation_interactive_condition ],
			[ 'slider_navigation_inner_hover', esc_html__( 'Inner Hover Color', 'mai-cyber-gallery' ), 'slider_navigation_inner_hover', '', $navigation_interactive_condition ],
		] as $color_field
	) {
		$slider_navigation_color_fields = array_merge(
			$slider_navigation_color_fields,
			$make_color_fields( ...$color_field )
		);
	}

	$slider_overlay_color_fields = $make_color_fields(
		'slider_overlay_color',
		esc_html__( 'Overlay Color', 'mai-cyber-gallery' ),
		'slider_overlay_color',
		'black',
		$slider_overlay_condition
	);

	$credit_link = sprintf(
		'<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
		esc_url( 'https://cyberbrand.net/' ),
		esc_html__( 'CYBER BRAND: Custom Web Development →', 'mai-cyber-gallery' )
	);

	/* translators: %s: Linked CYBER BRAND credit and service description. */
	$credit_message = sprintf( esc_html__( 'Built by %s', 'mai-cyber-gallery' ), $credit_link );

	acf_add_local_field_group(
		[
			'key'    => 'mai_cyber_gallery_field_group',
			'title'  => esc_html__( 'Cyber Gallery for Mai Theme', 'mai-cyber-gallery' ),
			'fields' => [
				[
					'key'       => 'mai_cyber_gallery_credit',
					'label'     => '',
					'type'      => 'message',
					'message'   => $credit_message,
					'new_lines' => '',
					'esc_html'  => 0,
					'wrapper'   => [
						'class' => 'mai-cyber-gallery-credit',
					],
				],
				[
					'key'       => 'mai_cyber_gallery_images_tab',
					'label'     => esc_html__( 'Images', 'mai-cyber-gallery' ),
					'type'      => 'tab',
					'placement' => 'top',
				],
				[
					'key'               => 'mai_cyber_gallery_click_action',
					'label'             => esc_html__( 'Click Action', 'mai-cyber-gallery' ),
					'name'              => 'click_action',
					'type'              => 'select',
					'default_value'     => 'lightbox',
					'choices'           => [
						'none'       => esc_html__( 'None', 'mai-cyber-gallery' ),
						'lightbox'   => esc_html__( 'Lightbox', 'mai-cyber-gallery' ),
						'media'      => esc_html__( 'Media File', 'mai-cyber-gallery' ),
						'attachment' => esc_html__( 'Attachment Page', 'mai-cyber-gallery' ),
						'custom'     => esc_html__( 'Custom Links', 'mai-cyber-gallery' ),
					],
					'ui'                => 1,
					'conditional_logic' => [
						[
							[
								'field'    => 'mai_cyber_gallery_layout',
								'operator' => '!=',
								'value'    => 'slider',
							],
							[
								'field'    => 'mai_cyber_gallery_layout',
								'operator' => '!=',
								'value'    => 'comparison',
							],
						],
					],
				],
				[
					'key'               => 'mai_cyber_gallery_comparison_before_image',
					'label'             => esc_html__( 'Before Image', 'mai-cyber-gallery' ),
					'name'              => 'comparison_before_image',
					'type'              => 'image',
					'required'          => 1,
					'return_format'     => 'id',
					'preview_size'      => 'medium',
					'library'           => 'all',
					'conditional_logic' => $comparison_condition,
				],
				[
					'key'               => 'mai_cyber_gallery_comparison_after_image',
					'label'             => esc_html__( 'After Image', 'mai-cyber-gallery' ),
					'name'              => 'comparison_after_image',
					'type'              => 'image',
					'required'          => 1,
					'return_format'     => 'id',
					'preview_size'      => 'medium',
					'library'           => 'all',
					'conditional_logic' => $comparison_condition,
				],
				[
					'key'               => 'mai_cyber_gallery_comparison_before_label',
					'label'             => esc_html__( 'Before Label', 'mai-cyber-gallery' ),
					'instructions'      => esc_html__( 'Leave blank to hide this badge.', 'mai-cyber-gallery' ),
					'name'              => 'comparison_before_label',
					'type'              => 'text',
					'default_value'     => esc_html__( 'Before', 'mai-cyber-gallery' ),
					'conditional_logic' => $comparison_condition,
				],
				[
					'key'               => 'mai_cyber_gallery_comparison_after_label',
					'label'             => esc_html__( 'After Label', 'mai-cyber-gallery' ),
					'instructions'      => esc_html__( 'Leave blank to hide this badge.', 'mai-cyber-gallery' ),
					'name'              => 'comparison_after_label',
					'type'              => 'text',
					'default_value'     => esc_html__( 'After', 'mai-cyber-gallery' ),
					'conditional_logic' => $comparison_condition,
				],
				[
					'key'               => 'mai_cyber_gallery_comparison_position',
					'label'             => esc_html__( 'Starting Position', 'mai-cyber-gallery' ),
					'instructions'      => esc_html__( 'Percentage of the Before image visible from the inline start edge.', 'mai-cyber-gallery' ),
					'name'              => 'comparison_position',
					'type'              => 'range',
					'default_value'     => 50,
					'min'               => 0,
					'max'               => 100,
					'step'              => 1,
					'append'            => '%',
					'conditional_logic' => $comparison_condition,
				],
				[
					'key'          => 'mai_cyber_gallery_slides',
					'label'        => esc_html__( 'Slides', 'mai-cyber-gallery' ),
					'instructions' => esc_html__( 'Add an image and optional content for each slide. Buttons use the site-wide Mai Engine button styles.', 'mai-cyber-gallery' ),
					'name'         => 'slides',
					'type'         => 'repeater',
					'layout'       => 'block',
					'min'          => 0,
					'collapsed'    => 'mai_cyber_gallery_slide_title',
					'button_label' => esc_html__( 'Add Slide', 'mai-cyber-gallery' ),
					'sub_fields'   => [
						[
							'key'             => 'mai_cyber_gallery_slide_image',
							'label'           => esc_html__( 'Image', 'mai-cyber-gallery' ),
							'name'            => 'image',
							'type'            => 'image',
							'required'        => 1,
							'return_format'   => 'id',
							'preview_size'    => 'thumbnail',
							'library'         => 'all',
							'parent_repeater' => 'mai_cyber_gallery_slides',
						],
						[
							'key'             => 'mai_cyber_gallery_slide_title',
							'label'           => esc_html__( 'Title', 'mai-cyber-gallery' ),
							'name'            => 'title',
							'type'            => 'text',
							'parent_repeater' => 'mai_cyber_gallery_slides',
						],
						[
							'key'             => 'mai_cyber_gallery_slide_subtext',
							'label'           => esc_html__( 'Subtext', 'mai-cyber-gallery' ),
							'name'            => 'subtext',
							'type'            => 'textarea',
							'rows'            => 3,
							'new_lines'       => '',
							'parent_repeater' => 'mai_cyber_gallery_slides',
						],
						[
							'key'             => 'mai_cyber_gallery_slide_align',
							'label'           => esc_html__( 'Horizontal Alignment', 'mai-cyber-gallery' ),
							'name'            => 'align',
							'type'            => 'button_group',
							'default_value'   => 'start',
							'choices'         => [
								'start'  => esc_html__( 'Start', 'mai-cyber-gallery' ),
								'center' => esc_html__( 'Center', 'mai-cyber-gallery' ),
								'end'    => esc_html__( 'End', 'mai-cyber-gallery' ),
							],
							'wrapper'         => [
								'class' => 'mai-acf-button-group',
							],
							'parent_repeater' => 'mai_cyber_gallery_slides',
						],
						[
							'key'             => 'mai_cyber_gallery_slide_align_vertical',
							'label'           => esc_html__( 'Vertical Alignment', 'mai-cyber-gallery' ),
							'name'            => 'align_vertical',
							'type'            => 'button_group',
							'default_value'   => 'bottom',
							'choices'         => [
								'top'    => esc_html__( 'Top', 'mai-cyber-gallery' ),
								'center' => esc_html__( 'Center', 'mai-cyber-gallery' ),
								'bottom' => esc_html__( 'Bottom', 'mai-cyber-gallery' ),
							],
							'wrapper'         => [
								'class' => 'mai-acf-button-group',
							],
							'parent_repeater' => 'mai_cyber_gallery_slides',
						],
						[
							'key'             => 'mai_cyber_gallery_slide_buttons',
							'label'           => esc_html__( 'CTA Buttons', 'mai-cyber-gallery' ),
							'instructions'    => esc_html__( 'Add up to two calls to action.', 'mai-cyber-gallery' ),
							'name'            => 'buttons',
							'type'            => 'repeater',
							'layout'          => 'block',
							'max'             => 2,
							'button_label'    => esc_html__( 'Add Button', 'mai-cyber-gallery' ),
							'parent_repeater' => 'mai_cyber_gallery_slides',
							'sub_fields'      => [
								[
									'key'             => 'mai_cyber_gallery_slide_button_link',
									'label'           => esc_html__( 'Link', 'mai-cyber-gallery' ),
									'instructions'    => esc_html__( 'The link title is used as the button label.', 'mai-cyber-gallery' ),
									'name'            => 'link',
									'type'            => 'link',
									'required'        => 1,
									'return_format'   => 'array',
									'parent_repeater' => 'mai_cyber_gallery_slide_buttons',
								],
								[
									'key'             => 'mai_cyber_gallery_slide_button_style',
									'label'           => esc_html__( 'Style', 'mai-cyber-gallery' ),
									'name'            => 'style',
									'type'            => 'select',
									'default_value'   => mai_cyber_gallery_get_choice_default( $button_style_choices, 'button' ),
									'choices'         => $button_style_choices,
									'ui'              => 1,
									'parent_repeater' => 'mai_cyber_gallery_slide_buttons',
								],
							],
						],
					],
					'conditional_logic' => [
						[
							[
								'field'    => 'mai_cyber_gallery_layout',
								'operator' => '==',
								'value'    => 'slider',
							],
						],
					],
				],
				[
					'key'           => 'mai_cyber_gallery_images',
					'label'         => esc_html__( 'Images', 'mai-cyber-gallery' ),
					'name'          => 'images',
					'type'          => 'gallery',
					'return_format' => 'id',
					'preview_size'  => 'medium',
					'insert'        => 'append',
					'library'       => 'all',
					'min'           => 0,
					'conditional_logic' => [
						[
							[
								'field'    => 'mai_cyber_gallery_click_action',
								'operator' => '!=',
								'value'    => 'custom',
							],
							[
								'field'    => 'mai_cyber_gallery_layout',
								'operator' => '!=',
								'value'    => 'slider',
							],
							[
								'field'    => 'mai_cyber_gallery_layout',
								'operator' => '!=',
								'value'    => 'comparison',
							],
						],
					],
				],
				[
					'key'          => 'mai_cyber_gallery_images_links',
					'label'        => esc_html__( 'Linked Images', 'mai-cyber-gallery' ),
					'instructions' => esc_html__( 'Add an image and optional link. Link title and target are respected.', 'mai-cyber-gallery' ),
					'name'         => 'images_links',
					'type'         => 'repeater',
					'layout'       => 'block',
					'min'          => 0,
					'collapsed'    => 'mai_cyber_gallery_image',
					'button_label' => esc_html__( 'Add Image', 'mai-cyber-gallery' ),
					'sub_fields'   => [
						[
							'key'             => 'mai_cyber_gallery_image',
							'label'           => esc_html__( 'Image', 'mai-cyber-gallery' ),
							'name'            => 'image',
							'type'            => 'image',
							'return_format'   => 'id',
							'preview_size'    => 'thumbnail',
							'parent_repeater' => 'mai_cyber_gallery_images_links',
						],
						[
							'key'             => 'mai_cyber_gallery_link',
							'label'           => esc_html__( 'Link', 'mai-cyber-gallery' ),
							'name'            => 'link',
							'type'            => 'link',
							'return_format'   => 'array',
							'parent_repeater' => 'mai_cyber_gallery_images_links',
						],
					],
					'conditional_logic' => [
						[
							[
								'field'    => 'mai_cyber_gallery_click_action',
								'operator' => '==',
								'value'    => 'custom',
							],
							[
								'field'    => 'mai_cyber_gallery_layout',
								'operator' => '!=',
								'value'    => 'slider',
							],
							[
								'field'    => 'mai_cyber_gallery_layout',
								'operator' => '!=',
								'value'    => 'comparison',
							],
						],
					],
				],
				[
					'key'           => 'mai_cyber_gallery_image_orientation',
					'label'         => esc_html__( 'Image Orientation', 'mai-cyber-gallery' ),
					'instructions'  => esc_html__( 'Used by Mai Grid, Before / After, and Filmstrip Natural/Uniform Crop modes. Natural-ratio layouts use the selected image size below.', 'mai-cyber-gallery' ),
					'name'          => 'image_orientation',
					'type'          => 'select',
					'default_value' => 'landscape',
					'choices'       => $orientations,
					'conditional_logic' => [
						[
							[
								'field'    => 'mai_cyber_gallery_layout',
								'operator' => '==',
								'value'    => 'grid',
							],
						],
						[
							[
								'field'    => 'mai_cyber_gallery_layout',
								'operator' => '==',
								'value'    => 'filmstrip',
							],
							[
								'field'    => 'mai_cyber_gallery_filmstrip_image_mode',
								'operator' => '!=',
								'value'    => 'logo-contain',
							],
						],
						[
							[
								'field'    => 'mai_cyber_gallery_layout',
								'operator' => '==',
								'value'    => 'comparison',
							],
						],
					],
				],
				[
					'key'           => 'mai_cyber_gallery_image_size',
					'label'         => esc_html__( 'Image Quality / Source Size', 'mai-cyber-gallery' ),
					'instructions'  => esc_html__( 'Choose a source at least as large as its display dimensions. Larger sources look sharper but use more bandwidth. Logo / Contain automatically replaces a cropped preset with Full Size.', 'mai-cyber-gallery' ),
					'name'          => 'image_size',
					'type'          => 'select',
					'default_value' => 'large',
					'choices'       => $image_sizes,
					'ui'            => 1,
					'conditional_logic' => [
						[
							[
								'field'    => 'mai_cyber_gallery_layout',
								'operator' => '!=',
								'value'    => 'grid',
							],
							[
								'field'    => 'mai_cyber_gallery_layout',
								'operator' => '!=',
								'value'    => 'filmstrip',
							],
							[
								'field'    => 'mai_cyber_gallery_layout',
								'operator' => '!=',
								'value'    => 'comparison',
							],
						],
						[
							[
								'field'    => 'mai_cyber_gallery_layout',
								'operator' => '==',
								'value'    => 'comparison',
							],
							[
								'field'    => 'mai_cyber_gallery_image_orientation',
								'operator' => '==',
								'value'    => 'custom',
							],
						],
						[
							[
								'field'    => 'mai_cyber_gallery_layout',
								'operator' => '==',
								'value'    => 'grid',
							],
							[
								'field'    => 'mai_cyber_gallery_image_orientation',
								'operator' => '==',
								'value'    => 'custom',
							],
						],
						[
							[
								'field'    => 'mai_cyber_gallery_layout',
								'operator' => '==',
								'value'    => 'filmstrip',
							],
							[
								'field'    => 'mai_cyber_gallery_image_orientation',
								'operator' => '==',
								'value'    => 'custom',
							],
						],
						[
							[
								'field'    => 'mai_cyber_gallery_layout',
								'operator' => '==',
								'value'    => 'filmstrip',
							],
							[
								'field'    => 'mai_cyber_gallery_filmstrip_image_mode',
								'operator' => '==',
								'value'    => 'logo-contain',
							],
						],
					],
				],
				[
					'key'           => 'mai_cyber_gallery_slider_overlay_style',
					'label'         => esc_html__( 'Image Overlay', 'mai-cyber-gallery' ),
					'name'          => 'slider_overlay_style',
					'type'          => 'select',
					'default_value' => mai_cyber_gallery_get_choice_default( $slider_overlay_choices, 'none' ),
					'choices'       => $slider_overlay_choices,
					'ui'            => 1,
					'conditional_logic' => [
						[
							[
								'field'    => 'mai_cyber_gallery_layout',
								'operator' => '!=',
								'value'    => 'comparison',
							],
						],
					],
				],
				...$slider_overlay_color_fields,
				[
					'key'               => 'mai_cyber_gallery_slider_overlay_opacity',
					'label'             => esc_html__( 'Overlay Opacity', 'mai-cyber-gallery' ),
					'name'              => 'slider_overlay_opacity',
					'type'              => 'range',
					'default_value'     => 35,
					'min'               => 0,
					'max'               => 100,
					'step'              => 1,
					'append'            => '%',
					'conditional_logic' => $slider_overlay_condition,
				],
				[
					'key'           => 'mai_cyber_gallery_captions',
					'label'         => esc_html__( 'Captions', 'mai-cyber-gallery' ),
					'instructions'  => esc_html__( 'Uses the caption saved for each image in the Media Library.', 'mai-cyber-gallery' ),
					'name'          => 'captions',
					'type'          => 'button_group',
					'default_value' => 'below',
					'choices'       => [
						'none'    => esc_html__( 'None', 'mai-cyber-gallery' ),
						'below'   => esc_html__( 'Below', 'mai-cyber-gallery' ),
						'overlay' => esc_html__( 'Overlay', 'mai-cyber-gallery' ),
					],
					'wrapper'       => [
						'class' => 'mai-acf-button-group',
					],
					'conditional_logic' => [
						[
							[
								'field'    => 'mai_cyber_gallery_layout',
								'operator' => '!=',
								'value'    => 'slider',
							],
							[
								'field'    => 'mai_cyber_gallery_layout',
								'operator' => '!=',
								'value'    => 'comparison',
							],
						],
					],
				],
				[
					'key'           => 'mai_cyber_gallery_image_radius',
					'label'         => esc_html__( 'Image Corners', 'mai-cyber-gallery' ),
					'name'          => 'image_radius',
					'type'          => 'button_group',
					'default_value' => 'rounded',
					'choices'       => [
						'square'  => esc_html__( 'Square', 'mai-cyber-gallery' ),
						'rounded' => esc_html__( 'Rounded', 'mai-cyber-gallery' ),
					],
					'wrapper'       => [
						'class' => 'mai-acf-button-group',
					],
					'conditional_logic' => [
						[
							[
								'field'    => 'mai_cyber_gallery_layout',
								'operator' => '!=',
								'value'    => 'circle',
							],
						],
					],
				],
				[
					'key'           => 'mai_cyber_gallery_shadow',
					'name'          => 'shadow',
					'type'          => 'true_false',
					'message'       => esc_html__( 'Add image shadow', 'mai-cyber-gallery' ),
					'default_value' => 0,
				],
				[
					'key'       => 'mai_cyber_gallery_layout_tab',
					'label'     => esc_html__( 'Layout', 'mai-cyber-gallery' ),
					'type'      => 'tab',
					'placement' => 'top',
				],
				[
					'key'           => 'mai_cyber_gallery_layout',
					'label'         => esc_html__( 'Gallery Layout', 'mai-cyber-gallery' ),
					'name'          => 'layout',
					'type'          => 'select',
					'default_value' => mai_cyber_gallery_get_choice_default( $layout_choices, 'masonry' ),
					'choices'       => $layout_choices,
					'ui'            => 1,
				],
				[
					'key'               => 'mai_cyber_gallery_aria_label',
					'label'             => esc_html__( 'Accessible Label', 'mai-cyber-gallery' ),
					'instructions'      => esc_html__( 'Optional name that distinguishes this gallery region from other galleries on the page. Leave blank to use the built-in layout label.', 'mai-cyber-gallery' ),
					'name'              => 'aria_label',
					'type'              => 'text',
					'default_value'     => '',
					'conditional_logic' => $region_condition,
				],
				[
					'key'           => 'mai_cyber_gallery_lightbox_arrow_style',
					'label'         => esc_html__( 'Lightbox Arrow Design', 'mai-cyber-gallery' ),
					'name'          => 'lightbox_arrow_style',
					'type'          => 'select',
					'default_value' => mai_cyber_gallery_get_choice_default( $arrow_style_choices, 'circle' ),
					'choices'       => $arrow_style_choices,
					'ui'            => 1,
					'conditional_logic' => [
						[
							[
								'field'    => 'mai_cyber_gallery_click_action',
								'operator' => '==',
								'value'    => 'lightbox',
							],
							[
								'field'    => 'mai_cyber_gallery_layout',
								'operator' => '!=',
								'value'    => 'slider',
							],
							[
								'field'    => 'mai_cyber_gallery_layout',
								'operator' => '!=',
								'value'    => 'comparison',
							],
						],
					],
				],
				[
					'key'               => 'mai_cyber_gallery_filmstrip_autoplay',
					'label'             => esc_html__( 'Filmstrip Autoplay', 'mai-cyber-gallery' ),
					'name'              => 'filmstrip_autoplay',
					'type'              => 'true_false',
					'message'           => esc_html__( 'Advance the filmstrip automatically', 'mai-cyber-gallery' ),
					'default_value'     => 0,
					'ui'                => 1,
					'conditional_logic' => $filmstrip_condition,
				],
				[
					'key'               => 'mai_cyber_gallery_filmstrip_autoplay_interval',
					'label'             => esc_html__( 'Autoplay Interval', 'mai-cyber-gallery' ),
					'name'              => 'filmstrip_autoplay_interval',
					'type'              => 'number',
					'default_value'     => 5,
					'min'               => 0.1,
					'max'               => 30,
					'step'              => 0.1,
					'append'            => esc_html__( 'seconds', 'mai-cyber-gallery' ),
					'conditional_logic' => $filmstrip_autoplay_condition,
					'wrapper'           => [
						'class' => 'mai-cyber-gallery-number-no-spinner',
					],
				],
				[
					'key'               => 'mai_cyber_gallery_filmstrip_autoplay_items',
					'label'             => esc_html__( 'Items Per Advance', 'mai-cyber-gallery' ),
					'name'              => 'filmstrip_autoplay_items',
					'type'              => 'number',
					'instructions'      => esc_html__( 'Choose how many items autoplay moves at a time.', 'mai-cyber-gallery' ),
					'default_value'     => 1,
					'min'               => 1,
					'step'              => 1,
					'append'            => esc_html__( 'items', 'mai-cyber-gallery' ),
					'conditional_logic' => $filmstrip_autoplay_condition,
					'wrapper'           => [
						'class' => 'mai-cyber-gallery-number-no-spinner',
					],
				],
				[
					'key'               => 'mai_cyber_gallery_filmstrip_pause_on_hover',
					'label'             => esc_html__( 'Pause on Mouse Over', 'mai-cyber-gallery' ),
					'name'              => 'filmstrip_pause_on_hover',
					'type'              => 'true_false',
					'message'           => esc_html__( 'Pause autoplay while the pointer is over the filmstrip', 'mai-cyber-gallery' ),
					'default_value'     => 1,
					'ui'                => 1,
					'conditional_logic' => $filmstrip_autoplay_condition,
				],
				[
					'key'               => 'mai_cyber_gallery_filmstrip_controls',
					'label'             => esc_html__( 'Filmstrip Controls', 'mai-cyber-gallery' ),
					'name'              => 'filmstrip_controls',
					'type'              => 'true_false',
					'instructions'      => esc_html__( 'Autoplay can continue when controls are hidden. Swiping, dragging, scrolling, or focusing the filmstrip pauses it.', 'mai-cyber-gallery' ),
					'message'           => esc_html__( 'Show playback and arrow controls', 'mai-cyber-gallery' ),
					'default_value'     => 1,
					'ui'                => 1,
					'conditional_logic' => $filmstrip_condition,
				],
				[
					'key'               => 'mai_cyber_gallery_filmstrip_arrows',
					'label'             => esc_html__( 'Filmstrip Arrows', 'mai-cyber-gallery' ),
					'name'              => 'filmstrip_arrows',
					'type'              => 'true_false',
					'message'           => esc_html__( 'Show previous and next controls', 'mai-cyber-gallery' ),
					'default_value'     => 0,
					'ui'                => 1,
					'conditional_logic' => $filmstrip_controls_enabled_condition,
				],
				[
					'key'               => 'mai_cyber_gallery_filmstrip_arrow_position',
					'label'             => esc_html__( 'Filmstrip Controls Position', 'mai-cyber-gallery' ),
					'name'              => 'filmstrip_arrow_position',
					'type'              => 'select',
					'default_value'     => 'below-end',
					'choices'           => [
						'overlay'      => esc_html__( 'Overlay', 'mai-cyber-gallery' ),
						'above-start'  => esc_html__( 'Above — Start', 'mai-cyber-gallery' ),
						'above-center' => esc_html__( 'Above — Center', 'mai-cyber-gallery' ),
						'above-end'    => esc_html__( 'Above — End', 'mai-cyber-gallery' ),
						'below-start'  => esc_html__( 'Below — Start', 'mai-cyber-gallery' ),
						'below-center' => esc_html__( 'Below — Center', 'mai-cyber-gallery' ),
						'below-end'    => esc_html__( 'Below — End', 'mai-cyber-gallery' ),
					],
					'ui'                => 1,
					'conditional_logic' => $filmstrip_controls_condition,
				],
				[
					'key'               => 'mai_cyber_gallery_filmstrip_arrow_style',
					'label'             => esc_html__( 'Filmstrip Arrow Design', 'mai-cyber-gallery' ),
					'name'              => 'filmstrip_arrow_style',
					'type'              => 'select',
					'default_value'     => mai_cyber_gallery_get_choice_default( $arrow_style_choices, 'circle' ),
					'choices'           => $arrow_style_choices,
					'ui'                => 1,
					'conditional_logic' => $filmstrip_arrows_condition,
				],
				[
					'key'               => 'mai_cyber_gallery_filmstrip_image_mode',
					'label'             => esc_html__( 'Filmstrip Image Mode', 'mai-cyber-gallery' ),
					'name'              => 'filmstrip_image_mode',
					'type'              => 'select',
					'default_value'     => 'natural',
					'choices'           => [
						'natural'       => esc_html__( 'Natural', 'mai-cyber-gallery' ),
						'uniform-cover' => esc_html__( 'Uniform Crop', 'mai-cyber-gallery' ),
						'logo-contain'  => esc_html__( 'Logo / Contain', 'mai-cyber-gallery' ),
					],
					'ui'                => 1,
					'conditional_logic' => $filmstrip_condition,
				],
				[
					'key'               => 'mai_cyber_gallery_filmstrip_height',
					'label'             => esc_html__( 'Filmstrip Height', 'mai-cyber-gallery' ),
					'instructions'      => esc_html__( 'Desktop image height.', 'mai-cyber-gallery' ),
					'name'              => 'filmstrip_height',
					'type'              => 'number',
					'default_value'     => 320,
					'min'               => 120,
					'max'               => 800,
					'step'              => 10,
					'append'            => 'px',
					'conditional_logic' => $filmstrip_uniform_condition,
					'wrapper'           => [
						'class' => 'mai-cyber-gallery-number-no-spinner',
					],
				],
				[
					'key'               => 'mai_cyber_gallery_filmstrip_height_mobile',
					'label'             => esc_html__( 'Filmstrip Mobile Height', 'mai-cyber-gallery' ),
					'name'              => 'filmstrip_height_mobile',
					'type'              => 'number',
					'default_value'     => 220,
					'min'               => 100,
					'max'               => 600,
					'step'              => 10,
					'append'            => 'px',
					'conditional_logic' => $filmstrip_uniform_condition,
					'wrapper'           => [
						'class' => 'mai-cyber-gallery-number-no-spinner',
					],
				],
				[
					'key'               => 'mai_cyber_gallery_filmstrip_logo_padding_horizontal',
					'label'             => esc_html__( 'Horizontal Logo Padding', 'mai-cyber-gallery' ),
					'instructions'      => esc_html__( 'Optional space on the left and right of every logo.', 'mai-cyber-gallery' ),
					'name'              => 'filmstrip_logo_padding_horizontal',
					'type'              => 'range',
					'default_value'     => 24,
					'min'               => 0,
					'max'               => 120,
					'step'              => 2,
					'append'            => 'px',
					'conditional_logic' => $filmstrip_logo_condition,
				],
				[
					'key'               => 'mai_cyber_gallery_filmstrip_logo_padding_vertical',
					'label'             => esc_html__( 'Vertical Logo Padding', 'mai-cyber-gallery' ),
					'instructions'      => esc_html__( 'Optional space above and below every logo.', 'mai-cyber-gallery' ),
					'name'              => 'filmstrip_logo_padding_vertical',
					'type'              => 'range',
					'default_value'     => 24,
					'min'               => 0,
					'max'               => 120,
					'step'              => 2,
					'append'            => 'px',
					'conditional_logic' => $filmstrip_logo_condition,
				],
				[
					'key'               => 'mai_cyber_gallery_filmstrip_logo_overrides',
					'label'             => esc_html__( 'Logo Size Overrides', 'mai-cyber-gallery' ),
					'instructions'      => esc_html__( 'Fine-tune individual logos relative to the shared logo size.', 'mai-cyber-gallery' ),
					'name'              => 'filmstrip_logo_overrides',
					'type'              => 'repeater',
					'layout'            => 'table',
					'min'               => 0,
					'collapsed'         => 'mai_cyber_gallery_filmstrip_logo_override_image',
					'button_label'      => esc_html__( 'Add Logo Override', 'mai-cyber-gallery' ),
					'conditional_logic' => $filmstrip_logo_condition,
					'wrapper'           => [
						'class' => 'mai-cyber-gallery-logo-overrides-fallback',
					],
					'sub_fields'        => [
						[
							'key'             => 'mai_cyber_gallery_filmstrip_logo_override_image',
							'label'           => esc_html__( 'Image', 'mai-cyber-gallery' ),
							'name'            => 'image',
							'type'            => 'image',
							'required'        => 1,
							'return_format'   => 'id',
							'preview_size'    => 'thumbnail',
							'library'         => 'all',
							'parent_repeater' => 'mai_cyber_gallery_filmstrip_logo_overrides',
						],
						[
							'key'             => 'mai_cyber_gallery_filmstrip_logo_override_scale',
							'label'           => esc_html__( 'Scale', 'mai-cyber-gallery' ),
							'name'            => 'scale',
							'type'            => 'range',
							'default_value'   => 100,
							'min'             => 50,
							'max'             => 200,
							'step'            => 5,
							'append'          => '%',
							'parent_repeater' => 'mai_cyber_gallery_filmstrip_logo_overrides',
						],
					],
				],
				[
					'key'               => 'mai_cyber_gallery_slider_title_tag',
					'label'             => esc_html__( 'Slider Title Element', 'mai-cyber-gallery' ),
					'instructions'      => esc_html__( 'Choose a heading level that fits the surrounding page structure, or Paragraph to keep the title styling without adding a heading.', 'mai-cyber-gallery' ),
					'name'              => 'slider_title_tag',
					'type'              => 'select',
					'default_value'     => 'h2',
					'choices'           => [
						'h2' => esc_html__( 'Heading 2', 'mai-cyber-gallery' ),
						'h3' => esc_html__( 'Heading 3', 'mai-cyber-gallery' ),
						'h4' => esc_html__( 'Heading 4', 'mai-cyber-gallery' ),
						'h5' => esc_html__( 'Heading 5', 'mai-cyber-gallery' ),
						'h6' => esc_html__( 'Heading 6', 'mai-cyber-gallery' ),
						'p'  => esc_html__( 'Paragraph', 'mai-cyber-gallery' ),
					],
					'ui'                => 1,
					'conditional_logic' => $slider_condition,
				],
				[
					'key'           => 'mai_cyber_gallery_slider_navigation',
					'label'         => esc_html__( 'Primary Navigation', 'mai-cyber-gallery' ),
					'instructions'  => esc_html__( 'Choose the first navigation type.', 'mai-cyber-gallery' ),
					'name'          => 'slider_navigation',
					'type'          => 'select',
					'default_value' => $slider_navigation_default,
					'choices'       => $slider_navigation_choices,
					'ui'            => 1,
					'conditional_logic' => [
						[
							[
								'field'    => 'mai_cyber_gallery_layout',
								'operator' => '==',
								'value'    => 'slider',
							],
						],
					],
				],
				[
					'key'           => 'mai_cyber_gallery_slider_navigation_secondary',
					'label'         => esc_html__( 'Secondary Navigation', 'mai-cyber-gallery' ),
					'instructions'  => esc_html__( 'Optionally add another navigation type. Duplicate selections render only once.', 'mai-cyber-gallery' ),
					'name'          => 'slider_navigation_secondary',
					'type'          => 'select',
					'default_value' => $slider_navigation_secondary_default,
					'choices'       => $slider_navigation_choices,
					'ui'            => 1,
					'conditional_logic' => [
						[
							[
								'field'    => 'mai_cyber_gallery_layout',
								'operator' => '==',
								'value'    => 'slider',
							],
						],
					],
				],
				[
					'key'           => 'mai_cyber_gallery_slider_navigation_position',
					'label'         => esc_html__( 'Navigation Position', 'mai-cyber-gallery' ),
					'name'          => 'slider_navigation_position',
					'type'          => 'button_group',
					'default_value' => 'overlay',
					'choices'       => [
						'overlay' => esc_html__( 'Overlay', 'mai-cyber-gallery' ),
						'below'   => esc_html__( 'Below', 'mai-cyber-gallery' ),
					],
					'wrapper'       => [
						'class' => 'mai-acf-button-group',
					],
					'conditional_logic' => $slider_navigation_condition,
				],
				[
					'key'           => 'mai_cyber_gallery_slider_arrow_style',
					'label'         => esc_html__( 'Slider Arrow Design', 'mai-cyber-gallery' ),
					'name'          => 'slider_arrow_style',
					'type'          => 'select',
					'default_value' => mai_cyber_gallery_get_choice_default( $arrow_style_choices, 'circle' ),
					'choices'       => $arrow_style_choices,
					'ui'            => 1,
					'conditional_logic' => $slider_arrows_condition,
				],
				[
					'key'           => 'mai_cyber_gallery_slider_dots_position',
					'label'         => esc_html__( 'Circle Position', 'mai-cyber-gallery' ),
					'instructions'  => esc_html__( 'Choose whether overlay circles sit at the top or bottom of the slide.', 'mai-cyber-gallery' ),
					'name'          => 'slider_dots_position',
					'type'          => 'button_group',
					'default_value' => 'top',
					'choices'       => [
						'top'    => esc_html__( 'Top', 'mai-cyber-gallery' ),
						'bottom' => esc_html__( 'Bottom', 'mai-cyber-gallery' ),
					],
					'wrapper'       => [
						'class' => 'mai-acf-button-group',
					],
					'conditional_logic' => $slider_dots_overlay_condition,
				],
				[
					'key'               => 'mai_cyber_gallery_slider_transition',
					'label'             => esc_html__( 'Slide Transition', 'mai-cyber-gallery' ),
					'name'              => 'slider_transition',
					'type'              => 'select',
					'default_value'     => mai_cyber_gallery_get_choice_default( $slider_transition_choices, 'native' ),
					'choices'           => $slider_transition_choices,
					'ui'                => 1,
					'conditional_logic' => $slider_condition,
				],
				[
					'key'               => 'mai_cyber_gallery_slider_transition_speed',
					'label'             => esc_html__( 'Transition Speed', 'mai-cyber-gallery' ),
					'name'              => 'slider_transition_speed',
					'type'              => 'button_group',
					'default_value'     => mai_cyber_gallery_get_choice_default( $slider_speed_choices, 'normal' ),
					'choices'           => $slider_speed_choices,
					'wrapper'           => [
						'class' => 'mai-acf-button-group',
					],
					'conditional_logic' => $slider_transition_effect_condition,
				],
				[
					'key'               => 'mai_cyber_gallery_slider_autoplay',
					'label'             => esc_html__( 'Auto Slide', 'mai-cyber-gallery' ),
					'instructions'      => esc_html__( 'Automatically advance slides on the front end. Reduced-motion preferences disable autoplay.', 'mai-cyber-gallery' ),
					'name'              => 'slider_autoplay',
					'type'              => 'true_false',
					'default_value'     => 0,
					'ui'                => 1,
					'conditional_logic' => $slider_condition,
				],
				[
					'key'               => 'mai_cyber_gallery_slider_autoplay_interval',
					'label'             => esc_html__( 'Time Between Slides', 'mai-cyber-gallery' ),
					'name'              => 'slider_autoplay_interval',
					'type'              => 'number',
					'default_value'     => 5,
					'min'               => 3,
					'max'               => 30,
					'step'              => 1,
					'append'            => esc_html__( 'seconds', 'mai-cyber-gallery' ),
					'wrapper'           => [
						'class' => 'mai-cyber-gallery-number-no-spinner',
					],
					'conditional_logic' => $slider_autoplay_condition,
				],
				[
					'key'               => 'mai_cyber_gallery_slider_autoplay_control',
					'label'             => esc_html__( 'Autoplay Control', 'mai-cyber-gallery' ),
					'instructions'      => esc_html__( 'Autoplay continues when this control is hidden.', 'mai-cyber-gallery' ),
					'name'              => 'slider_autoplay_control',
					'type'              => 'true_false',
					'message'           => esc_html__( 'Show the Pause / Start button', 'mai-cyber-gallery' ),
					'default_value'     => 1,
					'ui'                => 1,
					'conditional_logic' => $slider_autoplay_condition,
				],
				[
					'key'               => 'mai_cyber_gallery_slider_pause_on_hover',
					'label'             => esc_html__( 'Pause on Mouse Over', 'mai-cyber-gallery' ),
					'instructions'      => esc_html__( 'Pause while a pointer is over the slider. Keyboard focus and hidden tabs always pause autoplay.', 'mai-cyber-gallery' ),
					'name'              => 'slider_pause_on_hover',
					'type'              => 'true_false',
					'default_value'     => 1,
					'ui'                => 1,
					'conditional_logic' => $slider_autoplay_condition,
				],
				[
					'key'               => 'mai_cyber_gallery_slider_navigation_hover',
					'label'             => esc_html__( 'Navigation Hover Effect', 'mai-cyber-gallery' ),
					'name'              => 'slider_navigation_hover',
					'type'              => 'select',
					'default_value'     => mai_cyber_gallery_get_choice_default( $slider_hover_choices, 'color' ),
					'choices'           => $slider_hover_choices,
					'ui'                => 1,
					'conditional_logic' => $navigation_interactive_condition,
				],
				[
					'key'               => 'mai_cyber_gallery_slider_navigation_colors_accordion',
					'label'             => esc_html__( 'Navigation Colors', 'mai-cyber-gallery' ),
					'instructions'      => esc_html__( 'Main controls the surface; Inner controls arrow icons, circle fills, and counter text.', 'mai-cyber-gallery' ),
					'type'              => 'accordion',
					'open'              => 0,
					'multi_expand'      => 1,
					'conditional_logic' => $navigation_color_condition,
				],
				...$slider_navigation_color_fields,
				[
					'key'      => 'mai_cyber_gallery_slider_navigation_colors_accordion_end',
					'label'    => '',
					'type'     => 'accordion',
					'endpoint' => 1,
				],
				[
					'key'           => 'mai_cyber_gallery_slider_width_mode',
					'label'         => esc_html__( 'Slider Width', 'mai-cyber-gallery' ),
					'instructions'  => esc_html__( 'Full Width follows the available block or parent width. Contained uses the maximum width below. Inset adds responsive side spacing.', 'mai-cyber-gallery' ),
					'name'          => 'slider_width_mode',
					'type'          => 'button_group',
					'default_value' => 'full',
					'choices'       => [
						'full'      => esc_html__( 'Full Width', 'mai-cyber-gallery' ),
						'contained' => esc_html__( 'Contained', 'mai-cyber-gallery' ),
						'inset'     => esc_html__( 'Inset', 'mai-cyber-gallery' ),
					],
					'wrapper'       => [
						'class' => 'mai-acf-button-group',
					],
					'conditional_logic' => [
						[
							[
								'field'    => 'mai_cyber_gallery_layout',
								'operator' => '==',
								'value'    => 'slider',
							],
						],
					],
				],
				[
					'key'           => 'mai_cyber_gallery_slider_width',
					'label'         => esc_html__( 'Maximum Slider Width', 'mai-cyber-gallery' ),
					'instructions'  => esc_html__( 'Used by Contained mode. Use 0 to remove the maximum.', 'mai-cyber-gallery' ),
					'name'          => 'slider_width',
					'type'          => 'number',
					'default_value' => $slider_width,
					'min'           => 0,
					'max'           => 2400,
					'step'          => 20,
					'append'        => 'px',
					'wrapper'       => [
						'width' => 33,
						'class' => 'mai-cyber-gallery-slider-dimension mai-cyber-gallery-number-no-spinner',
					],
					'conditional_logic' => [
						[
							[
								'field'    => 'mai_cyber_gallery_layout',
								'operator' => '==',
								'value'    => 'slider',
							],
							[
								'field'    => 'mai_cyber_gallery_slider_width_mode',
								'operator' => '==',
								'value'    => 'contained',
							],
						],
					],
				],
				[
					'key'           => 'mai_cyber_gallery_slider_height',
					'label'         => esc_html__( 'Desktop Height', 'mai-cyber-gallery' ),
					'name'          => 'slider_height',
					'type'          => 'number',
					'default_value' => 560,
					'min'           => 320,
					'max'           => 1200,
					'step'          => 10,
					'append'        => 'px',
					'wrapper'       => [
						'width' => 33,
						'class' => 'mai-cyber-gallery-slider-dimension mai-cyber-gallery-number-no-spinner',
					],
					'conditional_logic' => [
						[
							[
								'field'    => 'mai_cyber_gallery_layout',
								'operator' => '==',
								'value'    => 'slider',
							],
						],
					],
				],
				[
					'key'           => 'mai_cyber_gallery_slider_height_mobile',
					'label'         => esc_html__( 'Mobile Height', 'mai-cyber-gallery' ),
					'name'          => 'slider_height_mobile',
					'type'          => 'number',
					'default_value' => 320,
					'min'           => 280,
					'max'           => 800,
					'step'          => 10,
					'append'        => 'px',
					'wrapper'       => [
						'width' => 33,
						'class' => 'mai-cyber-gallery-slider-dimension mai-cyber-gallery-number-no-spinner',
					],
					'conditional_logic' => [
						[
							[
								'field'    => 'mai_cyber_gallery_layout',
								'operator' => '==',
								'value'    => 'slider',
							],
						],
					],
				],
				[
					'key'           => 'mai_cyber_gallery_slider_image_fit',
					'label'         => esc_html__( 'Slider Image Fit', 'mai-cyber-gallery' ),
					'name'          => 'slider_image_fit',
					'type'          => 'button_group',
					'default_value' => 'cover',
					'choices'       => [
						'cover'   => esc_html__( 'Crop to Fill', 'mai-cyber-gallery' ),
						'contain' => esc_html__( 'Show Full Image', 'mai-cyber-gallery' ),
					],
					'wrapper'       => [
						'class' => 'mai-acf-button-group',
					],
					'conditional_logic' => [
						[
							[
								'field'    => 'mai_cyber_gallery_layout',
								'operator' => '==',
								'value'    => 'slider',
							],
						],
					],
				],
				[
					'key'           => 'mai_cyber_gallery_masonry_flow',
					'label'         => esc_html__( 'Masonry Packing', 'mai-cyber-gallery' ),
					'instructions'  => esc_html__( 'Balanced fills the shortest column. Ordered cycles images through columns for a more regular rhythm.', 'mai-cyber-gallery' ),
					'name'          => 'masonry_flow',
					'type'          => 'select',
					'default_value' => 'balanced',
					'choices'       => [
						'balanced' => esc_html__( 'Balanced / Shortest Column', 'mai-cyber-gallery' ),
						'ordered'  => esc_html__( 'Ordered / Column Rhythm', 'mai-cyber-gallery' ),
					],
					'ui'                => 1,
					'conditional_logic' => $masonry_condition,
				],
				[
					'key'           => 'mai_cyber_gallery_justified_row_height',
					'label'         => esc_html__( 'Target Row Height', 'mai-cyber-gallery' ),
					'instructions'  => esc_html__( 'The layout balances each row around this height.', 'mai-cyber-gallery' ),
					'name'          => 'justified_row_height',
					'type'          => 'number',
					'default_value' => 240,
					'min'           => 100,
					'max'           => 600,
					'step'          => 10,
					'append'        => 'px',
					'conditional_logic' => $justified_condition,
				],
				[
					'key'           => 'mai_cyber_gallery_justified_last_row',
					'label'         => esc_html__( 'Last Row Alignment', 'mai-cyber-gallery' ),
					'instructions'  => esc_html__( 'Positions the final partial row without stretching its images.', 'mai-cyber-gallery' ),
					'name'          => 'justified_last_row',
					'type'          => 'button_group',
					'default_value' => 'start',
					'choices'       => [
						'start'  => esc_html__( 'Start', 'mai-cyber-gallery' ),
						'center' => esc_html__( 'Center', 'mai-cyber-gallery' ),
						'end'    => esc_html__( 'End', 'mai-cyber-gallery' ),
					],
					'wrapper'         => [
						'class' => 'mai-acf-button-group',
					],
					'conditional_logic' => $justified_condition,
				],
				[
					'key'           => 'mai_cyber_gallery_tiled_pattern',
					'label'         => esc_html__( 'Tiled Pattern', 'mai-cyber-gallery' ),
					'instructions'  => esc_html__( 'Organic mixes rows and stacks, Calm favors broad bands, and Dense favors more vertical stacks.', 'mai-cyber-gallery' ),
					'name'          => 'tiled_pattern',
					'type'          => 'select',
					'default_value' => 'organic',
					'choices'       => [
						'organic' => esc_html__( 'Organic Mix', 'mai-cyber-gallery' ),
						'calm'    => esc_html__( 'Calm Bands', 'mai-cyber-gallery' ),
						'dense'   => esc_html__( 'Dense Stacks', 'mai-cyber-gallery' ),
					],
					'ui'                => 1,
					'conditional_logic' => $tiled_condition,
				],
				[
					'key'           => 'mai_cyber_gallery_mosaic_feature_side',
					'label'         => esc_html__( 'First Feature Side', 'mai-cyber-gallery' ),
					'instructions'  => esc_html__( 'Positions the first image in the mosaic and sets the starting side when alternation is enabled.', 'mai-cyber-gallery' ),
					'name'          => 'mosaic_feature_side',
					'type'          => 'button_group',
					'default_value' => 'left',
					'choices'       => [
						'left'  => esc_html__( 'Left', 'mai-cyber-gallery' ),
						'right' => esc_html__( 'Right', 'mai-cyber-gallery' ),
					],
					'wrapper'       => [
						'class' => 'mai-acf-button-group',
					],
					'conditional_logic' => $mosaic_condition,
				],
				[
					'key'           => 'mai_cyber_gallery_mosaic_alternate',
					'name'          => 'mosaic_alternate',
					'type'          => 'true_false',
					'message'       => esc_html__( 'Alternate the feature side for each image group', 'mai-cyber-gallery' ),
					'default_value' => 0,
					'conditional_logic' => $mosaic_condition,
				],
				[
					'key'           => 'mai_cyber_gallery_mosaic_items_after_feature',
					'label'         => esc_html__( 'Images After Feature', 'mai-cyber-gallery' ),
					'instructions'  => esc_html__( 'Sets how many smaller images follow each feature before the next feature group begins.', 'mai-cyber-gallery' ),
					'name'          => 'mosaic_items_after_feature',
					'type'          => 'number',
					'default_value' => 4,
					'min'           => 1,
					'max'           => 8,
					'step'          => 1,
					'append'        => esc_html__( 'images', 'mai-cyber-gallery' ),
					'wrapper'       => [
						'class' => 'mai-cyber-gallery-number-no-spinner',
					],
					'conditional_logic' => $mosaic_condition,
				],
				[
					'key'           => 'mai_cyber_gallery_mosaic_odd_alignment',
					'label'         => esc_html__( 'Odd Remainder', 'mai-cyber-gallery' ),
					'instructions'  => esc_html__( 'Controls the unpaired final small image when a group contains an odd number.', 'mai-cyber-gallery' ),
					'name'          => 'mosaic_odd_alignment',
					'type'          => 'select',
					'default_value' => 'fill',
					'choices'       => [
						'fill'   => esc_html__( 'Fill the Row', 'mai-cyber-gallery' ),
						'start'  => esc_html__( 'Align Start', 'mai-cyber-gallery' ),
						'center' => esc_html__( 'Center', 'mai-cyber-gallery' ),
						'end'    => esc_html__( 'Align End', 'mai-cyber-gallery' ),
					],
					'ui'                => 1,
					'conditional_logic' => $mosaic_condition,
				],
				[
					'key'           => 'mai_cyber_gallery_mosaic_row_height',
					'label'         => esc_html__( 'Mosaic Tile Height', 'mai-cyber-gallery' ),
					'instructions'  => esc_html__( 'Use 0 for the responsive automatic height, or set a fixed row height.', 'mai-cyber-gallery' ),
					'name'          => 'mosaic_row_height',
					'type'          => 'number',
					'default_value' => 0,
					'min'           => 0,
					'max'           => 600,
					'step'          => 10,
					'append'        => 'px',
					'wrapper'       => [
						'class' => 'mai-cyber-gallery-number-no-spinner',
					],
					'conditional_logic' => $mosaic_condition,
				],
				[
					'key'           => 'mai_cyber_gallery_hover',
					'label'         => esc_html__( 'Hover Effect', 'mai-cyber-gallery' ),
					'name'          => 'hover',
					'type'          => 'select',
					'default_value' => 'zoom',
					'choices'       => $hover_choices,
					'conditional_logic' => [
						[
							[
								'field'    => 'mai_cyber_gallery_layout',
								'operator' => '!=',
								'value'    => 'slider',
							],
							[
								'field'    => 'mai_cyber_gallery_layout',
								'operator' => '!=',
								'value'    => 'comparison',
							],
						],
					],
				],
				[
					'key'               => 'mai_cyber_gallery_slider_hover',
					'label'             => esc_html__( 'Hover Effect', 'mai-cyber-gallery' ),
					'name'              => 'slider_hover',
					'type'              => 'select',
					'default_value'     => 'none',
					'choices'           => $hover_choices,
					'conditional_logic' => $slider_condition,
				],
				[
					'key'               => 'mai_cyber_gallery_hover_desaturate_amount',
					'label'             => esc_html__( 'Desaturate Amount', 'mai-cyber-gallery' ),
					'instructions'      => esc_html__( 'Color removed at rest; the image returns to full color on hover.', 'mai-cyber-gallery' ),
					'name'              => 'hover_desaturate_amount',
					'type'              => 'range',
					'default_value'     => 100,
					'min'               => 0,
					'max'               => 100,
					'step'              => 5,
					'append'            => '%',
					'conditional_logic' => [
						[
							[
								'field'    => 'mai_cyber_gallery_layout',
								'operator' => '!=',
								'value'    => 'slider',
							],
							[
								'field'    => 'mai_cyber_gallery_layout',
								'operator' => '!=',
								'value'    => 'comparison',
							],
							[
								'field'    => 'mai_cyber_gallery_hover',
								'operator' => '==',
								'value'    => 'desaturate',
							],
						],
						[
							[
								'field'    => 'mai_cyber_gallery_layout',
								'operator' => '==',
								'value'    => 'slider',
							],
							[
								'field'    => 'mai_cyber_gallery_slider_hover',
								'operator' => '==',
								'value'    => 'desaturate',
							],
						],
					],
				],
				[
					'key'          => 'mai_cyber_gallery_columns_clone',
					'label'        => esc_html__( 'Responsive Columns & Gaps', 'mai-cyber-gallery' ),
					'instructions' => esc_html__( 'Justified and Editorial Mosaic expose gaps only because they calculate their own item arrangement. Tiled Mosaic caps its visual-group column count at four.', 'mai-cyber-gallery' ),
					'name'         => 'columns_clone',
					'type'         => 'clone',
					'display'      => 'group',
					'clone'        => [
						'mai_columns',
						'mai_columns_responsive',
						'mai_columns_md',
						'mai_columns_sm',
						'mai_columns_xs',
						'mai_align_columns',
						'mai_align_columns_vertical',
						'mai_column_gap',
						'mai_row_gap',
					],
					'conditional_logic' => $columns_and_gaps_condition,
				],
				[
					'key'          => 'mai_cyber_gallery_margins_clone',
					'label'        => esc_html__( 'Block Spacing', 'mai-cyber-gallery' ),
					'instructions' => esc_html__( 'Outer margin around the complete gallery. Left and right controls follow below.', 'mai-cyber-gallery' ),
					'name'         => 'margins_clone',
					'type'         => 'clone',
					'display'      => 'group',
					'clone'        => [
						'mai_margin_top',
						'mai_margin_bottom',
					],
				],
				[
					'key'           => 'mai_cyber_gallery_margin_left',
					'label'         => esc_html__( 'Left Margin', 'mai-cyber-gallery' ),
					'name'          => 'margin_left',
					'type'          => 'button_group',
					'default_value' => '',
					'choices'       => [
						''      => esc_html__( 'None', 'mai-cyber-gallery' ),
						'md'    => esc_html__( 'XS', 'mai-cyber-gallery' ),
						'lg'    => esc_html__( 'S', 'mai-cyber-gallery' ),
						'xl'    => esc_html__( 'M', 'mai-cyber-gallery' ),
						'xxl'   => esc_html__( 'L', 'mai-cyber-gallery' ),
						'xxxl'  => esc_html__( 'XL', 'mai-cyber-gallery' ),
						'xxxxl' => esc_html__( '2XL', 'mai-cyber-gallery' ),
					],
					'wrapper'       => [
						'class' => 'mai-acf-button-group mai-acf-button-group-small',
					],
				],
				[
					'key'           => 'mai_cyber_gallery_margin_right',
					'label'         => esc_html__( 'Right Margin', 'mai-cyber-gallery' ),
					'name'          => 'margin_right',
					'type'          => 'button_group',
					'default_value' => '',
					'choices'       => [
						''      => esc_html__( 'None', 'mai-cyber-gallery' ),
						'md'    => esc_html__( 'XS', 'mai-cyber-gallery' ),
						'lg'    => esc_html__( 'S', 'mai-cyber-gallery' ),
						'xl'    => esc_html__( 'M', 'mai-cyber-gallery' ),
						'xxl'   => esc_html__( 'L', 'mai-cyber-gallery' ),
						'xxxl'  => esc_html__( 'XL', 'mai-cyber-gallery' ),
						'xxxxl' => esc_html__( '2XL', 'mai-cyber-gallery' ),
					],
					'wrapper'       => [
						'class' => 'mai-acf-button-group mai-acf-button-group-small',
					],
				],
			],
			'location' => [
				[
					[
						'param'    => 'block',
						'operator' => '==',
						'value'    => 'acf/mai-cyber-gallery',
					],
				],
			],
			'active' => true,
		]
	);
}

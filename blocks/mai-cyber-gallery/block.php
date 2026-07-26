<?php
/**
 * Mai Cyber Gallery block registration and fields.
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
		'layout'                 => $layout,
		'click_action'           => get_field( 'click_action' ),
		'lightbox_arrow_style'   => get_field( 'lightbox_arrow_style' ),
		'images'                 => get_field( 'images' ),
		'images_links'           => get_field( 'images_links' ),
		'slides'                 => get_field( 'slides' ),
		'image_orientation'      => get_field( 'image_orientation' ),
		'image_size'             => get_field( 'image_size' ),
		'image_radius'           => get_field( 'image_radius' ),
		'captions'               => get_field( 'captions' ),
		'shadow'                 => get_field( 'shadow' ),
		'hover'                  => $hover,
		'hover_desaturate_amount' => get_field( 'hover_desaturate_amount' ),
		'justified_row_height'   => get_field( 'justified_row_height' ),
		'mosaic_feature_side'    => get_field( 'mosaic_feature_side' ),
		'mosaic_alternate'       => get_field( 'mosaic_alternate' ),
		'filmstrip_autoplay'          => get_field( 'filmstrip_autoplay' ),
		'filmstrip_autoplay_interval' => get_field( 'filmstrip_autoplay_interval' ),
		'filmstrip_pause_on_hover'    => get_field( 'filmstrip_pause_on_hover' ),
		'filmstrip_arrows'            => get_field( 'filmstrip_arrows' ),
		'filmstrip_arrow_position'    => get_field( 'filmstrip_arrow_position' ),
		'filmstrip_arrow_style'       => get_field( 'filmstrip_arrow_style' ),
		'filmstrip_image_mode'        => get_field( 'filmstrip_image_mode' ),
		'filmstrip_height'            => get_field( 'filmstrip_height' ),
		'filmstrip_height_mobile'     => get_field( 'filmstrip_height_mobile' ),
		'filmstrip_logo_padding'      => get_field( 'filmstrip_logo_padding' ),
		'filmstrip_logo_overrides'    => get_field( 'filmstrip_logo_overrides' ),
		'slider_navigation'           => $slider_navigation,
		'slider_navigation_secondary' => $slider_navigation_secondary,
		'slider_navigation_position' => get_field( 'slider_navigation_position' ),
		'slider_dots_position'        => get_field( 'slider_dots_position' ),
		'slider_arrow_style'          => get_field( 'slider_arrow_style' ),
		'slider_transition'           => get_field( 'slider_transition' ),
		'slider_transition_speed' => get_field( 'slider_transition_speed' ),
		'slider_autoplay'         => get_field( 'slider_autoplay' ),
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
		'margin_bottom'          => get_field( 'margin_bottom' ),
	];

	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Gallery handles escaping.
	echo mai_get_cyber_gallery( $args );
}

add_action( 'acf/init', 'mai_cyber_gallery_register_field_group', 12 );
add_filter( 'acf/validate_value/key=mai_cyber_gallery_slide_button_link', 'mai_cyber_gallery_validate_slide_button_link', 10, 4 );

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

	$orientations = (array) mai_get_image_orientation_choices();
	$image_sizes  = (array) mai_get_image_size_choices();
	$slider_width = max( 0, min( 2400, absint( mai_get_breakpoint( 'xl' ) ) ) );
	$hover_choices = [
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
	$filmstrip_condition = [
		[
			[
				'field'    => 'mai_cyber_gallery_layout',
				'operator' => '==',
				'value'    => 'filmstrip',
			],
		],
	];
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
	$filmstrip_arrows_condition = [
		[
			[
				'field'    => 'mai_cyber_gallery_layout',
				'operator' => '==',
				'value'    => 'filmstrip',
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
		$filmstrip_autoplay_condition
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
	];
	$slider_navigation_interactive_condition = array_merge(
		$slider_navigation_interactive_condition,
		$slider_autoplay_condition
	);
	$navigation_color_condition       = array_merge( $slider_navigation_condition, $filmstrip_controls_condition );
	$navigation_interactive_condition = array_merge( $slider_navigation_interactive_condition, $filmstrip_controls_condition );
	$slider_overlay_condition = [
		[
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

	acf_add_local_field_group(
		[
			'key'    => 'mai_cyber_gallery_field_group',
			'title'  => esc_html__( 'Mai Cyber Gallery', 'mai-cyber-gallery' ),
			'fields' => [
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
						],
					],
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
									'default_value'   => mai_cyber_gallery_get_default_button_style(),
									'choices'         => mai_cyber_gallery_get_button_style_choices(),
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
						],
					],
				],
				[
					'key'           => 'mai_cyber_gallery_image_orientation',
					'label'         => esc_html__( 'Image Orientation', 'mai-cyber-gallery' ),
					'instructions'  => esc_html__( 'Used by Mai Grid and Filmstrip Natural/Uniform Crop modes. Natural-ratio layouts use the selected image size below.', 'mai-cyber-gallery' ),
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
					'default_value' => 'none',
					'choices'       => mai_cyber_gallery_get_slider_overlay_style_choices(),
					'ui'            => 1,
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
					'default_value' => 'masonry',
					'choices'       => mai_cyber_gallery_get_layout_choices(),
					'ui'            => 1,
				],
				[
					'key'           => 'mai_cyber_gallery_lightbox_arrow_style',
					'label'         => esc_html__( 'Lightbox Arrow Design', 'mai-cyber-gallery' ),
					'name'          => 'lightbox_arrow_style',
					'type'          => 'select',
					'default_value' => 'circle',
					'choices'       => mai_cyber_gallery_get_arrow_style_choices(),
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
					'min'               => 3,
					'max'               => 30,
					'step'              => 1,
					'append'            => esc_html__( 'seconds', 'mai-cyber-gallery' ),
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
					'key'               => 'mai_cyber_gallery_filmstrip_arrows',
					'label'             => esc_html__( 'Filmstrip Arrows', 'mai-cyber-gallery' ),
					'name'              => 'filmstrip_arrows',
					'type'              => 'true_false',
					'message'           => esc_html__( 'Show previous and next controls', 'mai-cyber-gallery' ),
					'default_value'     => 1,
					'ui'                => 1,
					'conditional_logic' => $filmstrip_condition,
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
					'default_value'     => 'circle',
					'choices'           => mai_cyber_gallery_get_arrow_style_choices(),
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
					'key'               => 'mai_cyber_gallery_filmstrip_logo_padding',
					'label'             => esc_html__( 'Logo Padding', 'mai-cyber-gallery' ),
					'instructions'      => esc_html__( 'Space around every contained logo.', 'mai-cyber-gallery' ),
					'name'              => 'filmstrip_logo_padding',
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
					'key'           => 'mai_cyber_gallery_slider_navigation',
					'label'         => esc_html__( 'Primary Navigation', 'mai-cyber-gallery' ),
					'instructions'  => esc_html__( 'Choose the first navigation type.', 'mai-cyber-gallery' ),
					'name'          => 'slider_navigation',
					'type'          => 'select',
					'default_value' => 'arrows',
					'choices'       => mai_cyber_gallery_get_slider_navigation_choices(),
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
					'default_value' => 'dots',
					'choices'       => mai_cyber_gallery_get_slider_navigation_choices(),
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
					'default_value' => 'circle',
					'choices'       => mai_cyber_gallery_get_arrow_style_choices(),
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
					'default_value'     => 'native',
					'choices'           => mai_cyber_gallery_get_slider_transition_choices(),
					'ui'                => 1,
					'conditional_logic' => $slider_condition,
				],
				[
					'key'               => 'mai_cyber_gallery_slider_transition_speed',
					'label'             => esc_html__( 'Transition Speed', 'mai-cyber-gallery' ),
					'name'              => 'slider_transition_speed',
					'type'              => 'button_group',
					'default_value'     => 'normal',
					'choices'           => mai_cyber_gallery_get_slider_transition_speed_choices(),
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
					'default_value'     => 'color',
					'choices'           => mai_cyber_gallery_get_slider_navigation_hover_choices(),
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
					'conditional_logic' => [
						[
							[
								'field'    => 'mai_cyber_gallery_layout',
								'operator' => '==',
								'value'    => 'justified',
							],
						],
					],
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
					'conditional_logic' => [
						[
							[
								'field'    => 'mai_cyber_gallery_layout',
								'operator' => '==',
								'value'    => 'mosaic',
							],
						],
					],
				],
				[
					'key'           => 'mai_cyber_gallery_mosaic_alternate',
					'name'          => 'mosaic_alternate',
					'type'          => 'true_false',
					'message'       => esc_html__( 'Alternate the feature side every five images', 'mai-cyber-gallery' ),
					'default_value' => 0,
					'conditional_logic' => [
						[
							[
								'field'    => 'mai_cyber_gallery_layout',
								'operator' => '==',
								'value'    => 'mosaic',
							],
						],
					],
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
					'instructions' => esc_html__( 'Available for non-Slider layouts. Tiled Mosaic uses the column count as a visual-group limit capped at four.', 'mai-cyber-gallery' ),
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
					'conditional_logic' => [
						[
							[
								'field'    => 'mai_cyber_gallery_layout',
								'operator' => '!=',
								'value'    => 'slider',
							],
						],
					],
				],
				[
					'key'          => 'mai_cyber_gallery_margins_clone',
					'label'        => esc_html__( 'Block Spacing', 'mai-cyber-gallery' ),
					'instructions' => esc_html__( 'Top and bottom margin around the complete gallery.', 'mai-cyber-gallery' ),
					'name'         => 'margins_clone',
					'type'         => 'clone',
					'display'      => 'group',
					'clone'        => [
						'mai_margin_top',
						'mai_margin_bottom',
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

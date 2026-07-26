<?php
/**
 * Mai Cyber Gallery block registration and fields.
 *
 * @package MaiCyberGallery
 */

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

add_action( 'acf/init', 'mai_cyber_gallery_register_block' );
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
	static $instance = 0;

	$instance++;
	$block_id = isset( $block['id'] ) ? sanitize_html_class( $block['id'] ) : 'block';

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
		'layout'                 => get_field( 'layout' ),
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
		'hover'                  => get_field( 'hover' ),
		'justified_row_height'   => get_field( 'justified_row_height' ),
		'mosaic_feature_side'    => get_field( 'mosaic_feature_side' ),
		'mosaic_alternate'       => get_field( 'mosaic_alternate' ),
		'slider_navigation'      => get_field( 'slider_navigation' ),
		'slider_navigation_position' => get_field( 'slider_navigation_position' ),
		'slider_dots_position'   => get_field( 'slider_dots_position' ),
		'slider_arrow_style'     => get_field( 'slider_arrow_style' ),
		'slider_width'           => get_field( 'slider_width' ),
		'slider_height'          => get_field( 'slider_height' ),
		'slider_height_mobile'   => get_field( 'slider_height_mobile' ),
		'slider_image_fit'       => get_field( 'slider_image_fit' ),
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

	if ( ! isset( $image_sizes['full'] ) ) {
		$image_sizes = [ 'full' => esc_html__( 'Full Size', 'mai-cyber-gallery' ) ] + $image_sizes;
	}

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
					'min'          => 1,
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
					'min'           => 1,
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
					'min'          => 1,
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
					'instructions'  => esc_html__( 'Used by Mai Grid and Filmstrip. Natural-ratio layouts use the selected image size below.', 'mai-cyber-gallery' ),
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
						],
					],
				],
				[
					'key'           => 'mai_cyber_gallery_image_size',
					'label'         => esc_html__( 'Image Quality / Source Size', 'mai-cyber-gallery' ),
					'instructions'  => esc_html__( 'Choose a source at least as large as its display dimensions. Larger sources look sharper but use more bandwidth; use an uncropped size for natural-ratio layouts.', 'mai-cyber-gallery' ),
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
					],
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
					'key'           => 'mai_cyber_gallery_slider_navigation',
					'label'         => esc_html__( 'Slider Navigation', 'mai-cyber-gallery' ),
					'name'          => 'slider_navigation',
					'type'          => 'select',
					'default_value' => 'arrows-dots',
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
					'conditional_logic' => [
						[
							[
								'field'    => 'mai_cyber_gallery_layout',
								'operator' => '==',
								'value'    => 'slider',
							],
							[
								'field'    => 'mai_cyber_gallery_slider_navigation',
								'operator' => '!=',
								'value'    => 'none',
							],
						],
					],
				],
				[
					'key'           => 'mai_cyber_gallery_slider_arrow_style',
					'label'         => esc_html__( 'Slider Arrow Design', 'mai-cyber-gallery' ),
					'name'          => 'slider_arrow_style',
					'type'          => 'select',
					'default_value' => 'circle',
					'choices'       => mai_cyber_gallery_get_arrow_style_choices(),
					'ui'            => 1,
					'conditional_logic' => [
						[
							[
								'field'    => 'mai_cyber_gallery_layout',
								'operator' => '==',
								'value'    => 'slider',
							],
							[
								'field'    => 'mai_cyber_gallery_slider_navigation',
								'operator' => '==',
								'value'    => 'arrows',
							],
						],
						[
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
						],
					],
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
					'conditional_logic' => [
						[
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
						],
						[
							[
								'field'    => 'mai_cyber_gallery_layout',
								'operator' => '==',
								'value'    => 'slider',
							],
							[
								'field'    => 'mai_cyber_gallery_slider_navigation',
								'operator' => '==',
								'value'    => 'dots',
							],
							[
								'field'    => 'mai_cyber_gallery_slider_navigation_position',
								'operator' => '==',
								'value'    => 'overlay',
							],
						],
					],
				],
				[
					'key'           => 'mai_cyber_gallery_slider_width',
					'label'         => esc_html__( 'Maximum Slider Width', 'mai-cyber-gallery' ),
					'instructions'  => esc_html__( 'Use 0 to fill the available block width.', 'mai-cyber-gallery' ),
					'name'          => 'slider_width',
					'type'          => 'number',
					'default_value' => 1200,
					'min'           => 0,
					'max'           => 2400,
					'step'          => 20,
					'append'        => 'px',
					'wrapper'       => [ 'width' => 33 ],
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
					'key'           => 'mai_cyber_gallery_slider_height',
					'label'         => esc_html__( 'Desktop Height', 'mai-cyber-gallery' ),
					'name'          => 'slider_height',
					'type'          => 'number',
					'default_value' => 560,
					'min'           => 320,
					'max'           => 1200,
					'step'          => 10,
					'append'        => 'px',
					'wrapper'       => [ 'width' => 33 ],
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
					'wrapper'       => [ 'width' => 33 ],
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
					'choices'       => [
						'none' => esc_html__( 'None', 'mai-cyber-gallery' ),
						'zoom' => esc_html__( 'Zoom', 'mai-cyber-gallery' ),
						'lift' => esc_html__( 'Lift', 'mai-cyber-gallery' ),
						'fade' => esc_html__( 'Fade', 'mai-cyber-gallery' ),
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

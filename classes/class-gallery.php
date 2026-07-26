<?php
/**
 * Gallery renderer.
 *
 * @package MaiCyberGallery
 */

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

/**
 * Render a Mai Engine-powered Cyber Gallery.
 *
 * @since 0.1.0
 */
class Mai_Cyber_Gallery {
	/**
	 * Sanitized gallery arguments.
	 *
	 * @var array
	 */
	protected $args;

	/**
	 * WordPress image size used for thumbnails.
	 *
	 * @var string
	 */
	protected $image_size;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param array $args Gallery arguments.
	 */
	public function __construct( $args = [] ) {
		$column_defaults = function_exists( 'mai_get_columns_defaults' )
			? mai_get_columns_defaults()
			: [
				'columns'                => 3,
				'columns_responsive'     => false,
				'columns_md'             => 1,
				'columns_sm'             => 1,
				'columns_xs'             => 1,
				'align_columns'          => '',
				'align_columns_vertical' => '',
				'column_gap'             => 'md',
				'row_gap'                => 'md',
				'margin_top'             => '',
				'margin_bottom'          => '',
			];

		$defaults = array_merge(
			$column_defaults,
			[
				'preview'                    => false,
				'id'                         => '',
				'align'                      => '',
				'anchor'                     => '',
				'class'                      => '',
				'layout'                     => 'masonry',
				'click_action'               => 'lightbox',
				'lightbox_arrow_style'       => 'circle',
				'images'                     => [],
				'images_links'               => [],
				'slides'                     => [],
				'image_orientation'          => 'landscape',
				'image_size'                 => 'large',
				'image_radius'               => 'rounded',
				'captions'                   => 'below',
				'shadow'                     => false,
				'hover'                      => 'zoom',
				'justified_row_height'       => 240,
				'mosaic_feature_side'        => 'left',
				'mosaic_alternate'           => false,
				'slider_navigation'          => 'arrows-dots',
				'slider_navigation_position' => 'overlay',
				'slider_dots_position'       => 'top',
				'slider_arrow_style'         => 'circle',
				'slider_width'               => 1200,
				'slider_height'              => 560,
				'slider_height_mobile'       => 320,
				'slider_image_fit'           => 'cover',
			]
		);

		$args = wp_parse_args( $args, $defaults );

		/**
		 * Filter gallery arguments before they are sanitized.
		 *
		 * @since 0.1.0
		 *
		 * @param array $args Gallery arguments.
		 */
		$args = (array) apply_filters( 'mai_cyber_gallery_args', $args );

		$this->args       = $this->sanitize_args( $args, $defaults );
		$this->image_size = $this->get_image_size();
	}

	/**
	 * Display the gallery.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function render() {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Markup is escaped while built.
		echo $this->get();
	}

	/**
	 * Get gallery markup.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public function get() {
		static $instance = 0;

		$items = $this->get_items();

		if ( ! $items ) {
			return $this->get_empty_preview();
		}

		$instance++;
		$gallery_id = $this->args['anchor']
			? $this->args['anchor']
			: ( $this->args['id'] ? $this->args['id'] : sprintf( 'mai-cyber-gallery-%d', $instance ) );

		$this->enqueue_assets();

		$root_atts = [
			'id'                  => $gallery_id,
			'class'               => sprintf( 'mai-cyber-gallery-wrap mai-cyber-gallery-layout-%s', $this->args['layout'] ),
			'data-cyber-gallery'  => $gallery_id,
			'data-cyber-layout'   => $this->args['layout'],
		];

		$root_classes = [
			'has-captions-' . $this->args['captions'],
			'has-hover-' . $this->args['hover'],
			'has-image-radius-' . $this->args['image_radius'],
		];
		$has_slider_content = false;

		if ( 'slider' === $this->args['layout'] ) {
			foreach ( $items as $item ) {
				if ( $item['slide_title'] || $item['slide_subtext'] || $item['slide_buttons'] ) {
					$has_slider_content = true;
					break;
				}
			}
		}

		if ( $this->args['shadow'] ) {
			$root_classes[] = 'has-image-shadow';
		}

		if ( 'mosaic' === $this->args['layout'] ) {
			$root_classes[] = 'has-mosaic-feature-' . $this->args['mosaic_feature_side'];

			if ( $this->args['mosaic_alternate'] ) {
				$root_classes[] = 'has-mosaic-alternating';
			}
		}

		if ( 'slider' === $this->args['layout'] ) {
			if ( $has_slider_content ) {
				$root_classes[] = 'has-slider-content';
			}

			$root_classes[] = 'has-slider-navigation-' . $this->args['slider_navigation'];
			$root_classes[] = 'has-slider-navigation-' . $this->args['slider_navigation_position'];
			$root_classes[] = 'has-slider-dots-' . $this->args['slider_dots_position'];
			$root_classes[] = 'has-slider-arrows-' . $this->args['slider_arrow_style'];
			$root_classes[] = 'has-slider-fit-' . $this->args['slider_image_fit'];
		}

		$root_atts['class'] = mai_add_classes( $root_classes, $root_atts['class'] );

		if ( $this->args['align'] ) {
			$root_atts['class'] = mai_add_classes( 'align' . $this->args['align'], $root_atts['class'] );
		}

		if ( $this->args['class'] ) {
			$root_atts['class'] = mai_add_classes( $this->args['class'], $root_atts['class'] );
		}

		foreach ( [ 'top', 'bottom' ] as $side ) {
			$key = 'margin_' . $side;

			if ( $this->args[ $key ] && mai_is_valid_size( $this->args[ $key ] ) ) {
				$root_atts['class'] = mai_add_classes(
					sprintf( 'has-%s-margin-%s', $this->args[ $key ], $side ),
					$root_atts['class']
				);
			}
		}

		$root_style = '';

		if ( $this->args['shadow'] ) {
			$root_style .= '--image-filter:var(--drop-shadow);';
		}

		if ( 'slider' === $this->args['layout'] ) {
			$slider_width = $this->args['slider_width']
				? $this->args['slider_width'] . 'px'
				: '100%';

			$root_style .= sprintf(
				'--cyber-slider-max-width:%1$s;--cyber-slider-height:%2$dpx;--cyber-slider-height-mobile:%3$dpx;',
				$slider_width,
				$this->args['slider_height'],
				$this->args['slider_height_mobile']
			);
		}

		if ( $root_style ) {
			$root_atts['style'] = $root_style;
		}

		if ( in_array( $this->args['layout'], [ 'filmstrip', 'slider' ], true ) ) {
			$root_atts['role']                  = 'region';
			$root_atts['aria-label']            = 'slider' === $this->args['layout']
				? esc_html__( 'Content slider', 'mai-cyber-gallery' )
				: esc_html__( 'Image gallery', 'mai-cyber-gallery' );
			$root_atts['aria-roledescription']  = esc_html__( 'carousel', 'mai-cyber-gallery' );
		}

		$params = [
			'args'  => $this->args,
			'items' => $items,
		];

		$html = genesis_markup(
			[
				'open'    => '<div %s>',
				'context' => 'mai-cyber-gallery',
				'echo'    => false,
				'atts'    => $root_atts,
				'params'  => $params,
			]
		);

		$items_atts          = mai_get_columns_atts( [ 'class' => 'mai-cyber-gallery' ], $this->args );
		$items_atts['id']    = $gallery_id . '-items';
		$items_atts['role']  = 'list';
		$items_atts['style'] = isset( $items_atts['style'] ) ? $items_atts['style'] : '';

		$breakpoint_columns = mai_get_breakpoint_columns( $this->args );
		foreach ( $breakpoint_columns as $breakpoint => $columns ) {
			$items_atts['style'] .= sprintf(
				'--cyber-columns-%s:%d;',
				sanitize_key( $breakpoint ),
				max( 1, min( 8, absint( $columns ) ) )
			);
		}

		if ( 'justified' === $this->args['layout'] ) {
			$items_atts['data-cyber-row-height'] = $this->args['justified_row_height'];
		}

		if ( in_array( $this->args['layout'], [ 'filmstrip', 'slider' ], true ) ) {
			// Genesis omits the exact string "0" as a falsey attribute value.
			// "00" is an equivalent valid integer which preserves keyboard focus.
			$items_atts['tabindex'] = '00';
			$items_atts['aria-label'] = 'filmstrip' === $this->args['layout']
				? esc_html__( 'Gallery filmstrip', 'mai-cyber-gallery' )
				: esc_html__( 'Gallery slider', 'mai-cyber-gallery' );
		}

		$html .= genesis_markup(
			[
				'open'    => '<div %s>',
				'context' => 'mai-cyber-gallery-items',
				'echo'    => false,
				'atts'    => $items_atts,
				'params'  => $params,
			]
		);

		$total = count( $items );
		foreach ( $items as $index => $item ) {
			$html .= $this->get_item_markup( $item, $index, $total, $gallery_id );
		}

		$html .= genesis_markup(
			[
				'close'   => '</div>',
				'context' => 'mai-cyber-gallery-items',
				'echo'    => false,
				'params'  => $params,
			]
		);

		if ( 'filmstrip' === $this->args['layout'] && 1 < $total ) {
			$html .= $this->get_filmstrip_controls( $gallery_id );
		}

		if ( 'slider' === $this->args['layout'] && 1 < $total ) {
			$html .= $this->get_slider_controls( $gallery_id, $items );
		}

		$html .= genesis_markup(
			[
				'close'   => '</div>',
				'context' => 'mai-cyber-gallery',
				'echo'    => false,
				'params'  => $params,
			]
		);

		return $html;
	}

	/**
	 * Sanitize gallery arguments.
	 *
	 * @since 0.1.0
	 *
	 * @param array $args     Raw arguments.
	 * @param array $defaults Defaults.
	 *
	 * @return array
	 */
	protected function sanitize_args( $args, $defaults ) {
		$layouts           = array_keys( mai_cyber_gallery_get_layout_choices() );
		$arrow_styles      = array_keys( mai_cyber_gallery_get_arrow_style_choices() );
		$button_styles     = array_keys( mai_cyber_gallery_get_button_style_choices() );
		$slider_navigation = array_keys( mai_cyber_gallery_get_slider_navigation_choices() );

		$args['preview']                    = mai_sanitize_bool( $args['preview'] );
		$args['id']                         = sanitize_title( (string) $args['id'] );
		$args['align']                      = sanitize_html_class( (string) $args['align'] );
		$args['anchor']                     = sanitize_title( (string) $args['anchor'] );
		$args['class']                      = $this->sanitize_classes( $args['class'] );
		$args['layout']                     = sanitize_key( (string) $args['layout'] );
		$args['click_action']               = sanitize_key( (string) $args['click_action'] );
		$args['lightbox_arrow_style']       = sanitize_key( (string) $args['lightbox_arrow_style'] );
		$args['image_radius']               = sanitize_key( (string) $args['image_radius'] );
		$args['captions']                   = sanitize_key( (string) $args['captions'] );
		$args['hover']                      = sanitize_key( (string) $args['hover'] );
		$args['mosaic_feature_side']        = sanitize_key( (string) $args['mosaic_feature_side'] );
		$args['slider_navigation']          = sanitize_key( (string) $args['slider_navigation'] );
		$args['slider_navigation_position'] = sanitize_key( (string) $args['slider_navigation_position'] );
		$args['slider_dots_position']       = sanitize_key( (string) $args['slider_dots_position'] );
		$args['slider_arrow_style']         = sanitize_key( (string) $args['slider_arrow_style'] );
		$args['slider_image_fit']           = sanitize_key( (string) $args['slider_image_fit'] );

		if ( ! in_array( $args['layout'], $layouts, true ) ) {
			$args['layout'] = $defaults['layout'];
		}

		if ( ! in_array( $args['click_action'], [ 'none', 'lightbox', 'media', 'attachment', 'custom' ], true ) ) {
			$args['click_action'] = $defaults['click_action'];
		}

		if ( ! in_array( $args['lightbox_arrow_style'], $arrow_styles, true ) ) {
			$args['lightbox_arrow_style'] = $defaults['lightbox_arrow_style'];
		}

		if ( ! in_array( $args['image_radius'], [ 'square', 'rounded' ], true ) ) {
			$args['image_radius'] = $defaults['image_radius'];
		}

		if ( ! in_array( $args['captions'], [ 'none', 'below', 'overlay' ], true ) ) {
			$args['captions'] = $defaults['captions'];
		}

		if ( ! in_array( $args['hover'], [ 'none', 'zoom', 'lift', 'fade' ], true ) ) {
			$args['hover'] = $defaults['hover'];
		}

		if ( ! in_array( $args['mosaic_feature_side'], [ 'left', 'right' ], true ) ) {
			$args['mosaic_feature_side'] = $defaults['mosaic_feature_side'];
		}

		$args['mosaic_alternate'] = mai_sanitize_bool( $args['mosaic_alternate'] );

		if ( ! in_array( $args['slider_navigation'], $slider_navigation, true ) ) {
			$args['slider_navigation'] = $defaults['slider_navigation'];
		}

		if ( ! in_array( $args['slider_navigation_position'], [ 'overlay', 'below' ], true ) ) {
			$args['slider_navigation_position'] = $defaults['slider_navigation_position'];
		}

		if ( ! in_array( $args['slider_dots_position'], [ 'top', 'bottom' ], true ) ) {
			$args['slider_dots_position'] = $defaults['slider_dots_position'];
		}

		if ( ! in_array( $args['slider_arrow_style'], $arrow_styles, true ) ) {
			$args['slider_arrow_style'] = $defaults['slider_arrow_style'];
		}

		if ( ! in_array( $args['slider_image_fit'], [ 'cover', 'contain' ], true ) ) {
			$args['slider_image_fit'] = $defaults['slider_image_fit'];
		}

		foreach (
			[
				'slider_width'         => [ 0, 2400 ],
				'slider_height'        => [ 320, 1200 ],
				'slider_height_mobile' => [ 280, 800 ],
			] as $key => $range
		) {
			if ( ! is_numeric( $args[ $key ] ) ) {
				$args[ $key ] = $defaults[ $key ];
			}

			$args[ $key ] = max( $range[0], min( $range[1], (int) $args[ $key ] ) );
		}

		$args['images']       = array_values( array_filter( array_map( 'absint', (array) $args['images'] ) ) );
		$args['images_links'] = $this->sanitize_linked_images( $args['images_links'] );
		$args['slides']       = $this->sanitize_slides( $args['slides'], $button_styles );

		$args['image_orientation'] = sanitize_key( (string) $args['image_orientation'] );
		if (
			'custom' !== $args['image_orientation']
			&& ! mai_has_image_orientiation( $args['image_orientation'] )
		) {
			$args['image_orientation'] = $defaults['image_orientation'];
		}

		$args['image_size'] = sanitize_key( (string) $args['image_size'] );
		if ( ! $args['image_size'] ) {
			$args['image_size'] = $defaults['image_size'];
		}

		$args['shadow'] = mai_sanitize_bool( $args['shadow'] );

		if ( ! is_numeric( $args['justified_row_height'] ) || 1 > (int) $args['justified_row_height'] ) {
			$args['justified_row_height'] = $defaults['justified_row_height'];
		}

		$args['justified_row_height'] = absint( $args['justified_row_height'] );
		$args['justified_row_height'] = max( 100, min( 600, $args['justified_row_height'] ) );

		foreach ( [ 'columns', 'columns_md', 'columns_sm', 'columns_xs' ] as $key ) {
			if ( ! is_numeric( $args[ $key ] ) || 1 > (int) $args[ $key ] ) {
				$args[ $key ] = $defaults[ $key ];
			}
		}

		$args = array_merge( $args, mai_get_columns_sanitized( $args ) );

		foreach ( [ 'columns', 'columns_md', 'columns_sm', 'columns_xs' ] as $key ) {
			$args[ $key ] = max( 1, min( 8, absint( $args[ $key ] ) ) );
		}

		if ( 'slider' === $args['layout'] ) {
			$args['click_action']           = 'none';
			$args['captions']               = 'none';
			$args['hover']                  = 'none';
			$args['columns']                = 1;
			$args['columns_responsive']     = false;
			$args['columns_md']             = 1;
			$args['columns_sm']             = 1;
			$args['columns_xs']             = 1;
			$args['align_columns']          = '';
			$args['align_columns_vertical'] = '';
			$args['column_gap']             = '';
			$args['row_gap']                = '';
		}

		return $args;
	}

	/**
	 * Sanitize a space-separated class list token by token.
	 *
	 * @since 0.1.0
	 *
	 * @param mixed $classes Classes.
	 *
	 * @return string
	 */
	protected function sanitize_classes( $classes ) {
		$classes = is_array( $classes ) ? $classes : preg_split( '/\s+/', (string) $classes );
		$classes = array_map( 'sanitize_html_class', array_filter( (array) $classes ) );
		return implode( ' ', array_filter( $classes ) );
	}

	/**
	 * Sanitize canonical Slider slide rows.
	 *
	 * @since 0.3.0
	 *
	 * @param mixed $rows          Slide repeater rows.
	 * @param array $button_styles Allowed CTA style slugs.
	 *
	 * @return array
	 */
	protected function sanitize_slides( $rows, $button_styles ) {
		$slides = [];

		foreach ( (array) $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$image_id = isset( $row['image'] ) && is_scalar( $row['image'] ) && is_numeric( $row['image'] )
				? absint( $row['image'] )
				: 0;
			if ( ! $image_id ) {
				continue;
			}

			$align = isset( $row['align'] ) && is_scalar( $row['align'] )
				? sanitize_key( (string) $row['align'] )
				: 'start';
			if ( ! in_array( $align, [ 'start', 'center', 'end' ], true ) ) {
				$align = 'start';
			}

			$align_vertical = isset( $row['align_vertical'] ) && is_scalar( $row['align_vertical'] )
				? sanitize_key( (string) $row['align_vertical'] )
				: 'bottom';
			if ( ! in_array( $align_vertical, [ 'top', 'center', 'bottom' ], true ) ) {
				$align_vertical = 'bottom';
			}

			$slides[] = [
				'image'          => $image_id,
				'title'          => isset( $row['title'] ) && is_scalar( $row['title'] )
					? sanitize_text_field( (string) $row['title'] )
					: '',
				'subtext'        => isset( $row['subtext'] ) && is_scalar( $row['subtext'] )
					? sanitize_textarea_field( (string) $row['subtext'] )
					: '',
				'align'          => $align,
				'align_vertical' => $align_vertical,
				'buttons'        => $this->sanitize_slide_buttons( $row['buttons'] ?? [], $button_styles ),
			];
		}

		return $slides;
	}

	/**
	 * Sanitize up to two Slider CTA button rows.
	 *
	 * @since 0.3.0
	 *
	 * @param mixed $rows          Button repeater rows.
	 * @param array $button_styles Allowed CTA style slugs.
	 *
	 * @return array
	 */
	protected function sanitize_slide_buttons( $rows, $button_styles = [] ) {
		$button_styles = $button_styles
			? array_values( (array) $button_styles )
			: array_keys( mai_cyber_gallery_get_button_style_choices() );
		$default_style = in_array( 'button', $button_styles, true )
			? 'button'
			: reset( $button_styles );
		$default_style = $default_style ? $default_style : 'button';
		$buttons       = [];

		foreach ( (array) $rows as $button ) {
			if ( 2 <= count( $buttons ) ) {
				break;
			}

			if ( ! is_array( $button ) ) {
				continue;
			}

			$link  = isset( $button['link'] ) && is_array( $button['link'] ) ? $button['link'] : [];
			$url   = isset( $link['url'] ) && is_scalar( $link['url'] )
				? esc_url_raw( (string) $link['url'] )
				: '';
			$title = isset( $link['title'] ) && is_scalar( $link['title'] )
				? sanitize_text_field( (string) $link['title'] )
				: '';

			if ( ! $url || ! $title ) {
				continue;
			}

			$style = isset( $button['style'] ) && is_scalar( $button['style'] )
				? sanitize_key( (string) $button['style'] )
				: $default_style;
			if ( ! in_array( $style, $button_styles, true ) ) {
				$style = $default_style;
			}

			$buttons[] = [
				'link'  => [
					'url'    => $url,
					'title'  => $title,
					'target' => isset( $link['target'] ) && '_blank' === $link['target'] ? '_blank' : '',
				],
				'style' => $style,
			];
		}

		return $buttons;
	}

	/**
	 * Sanitize the custom-linked image repeater.
	 *
	 * Supports both the Cyber `link` array and the original gallery's `url` key
	 * for a smoother developer-facing fork path.
	 *
	 * @since 0.1.0
	 *
	 * @param mixed $rows Repeater rows.
	 *
	 * @return array
	 */
	protected function sanitize_linked_images( $rows ) {
		$sanitized = [];

		foreach ( (array) $rows as $row ) {
			if ( ! is_array( $row ) || empty( $row['image'] ) ) {
				continue;
			}

			$image_id = absint( $row['image'] );
			if ( ! $image_id ) {
				continue;
			}

			$link = isset( $row['link'] ) && is_array( $row['link'] ) ? $row['link'] : [];

			if ( ! $link && ! empty( $row['url'] ) ) {
				$link = [ 'url' => $row['url'] ];
			}

			$target = isset( $link['target'] ) && '_blank' === $link['target'] ? '_blank' : '';

			$sanitized[] = [
				'image' => $image_id,
				'link'  => [
					'url'    => isset( $link['url'] ) ? esc_url_raw( $link['url'] ) : '',
					'title'  => isset( $link['title'] ) ? sanitize_text_field( $link['title'] ) : '',
					'target' => $target,
				],
			];
		}

		return $sanitized;
	}

	/**
	 * Build and validate gallery items.
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	protected function get_items() {
		$rows = [];

		if ( 'slider' === $this->args['layout'] ) {
			foreach ( $this->args['slides'] as $slide ) {
				$rows[] = [
					'image'                => $slide['image'],
					'link'                 => [],
					'slide_title'          => $slide['title'],
					'slide_subtext'        => $slide['subtext'],
					'slide_align'          => $slide['align'],
					'slide_align_vertical' => $slide['align_vertical'],
					'slide_buttons'        => $slide['buttons'],
				];
			}
		} elseif ( 'custom' === $this->args['click_action'] ) {
			$rows = $this->args['images_links'];
		} else {
			foreach ( $this->args['images'] as $image_id ) {
				$rows[] = [
					'image' => $image_id,
					'link'  => [],
				];
			}
		}

		$items = [];

		foreach ( $rows as $row ) {
			$image_id = isset( $row['image'] ) ? absint( $row['image'] ) : 0;
			if ( ! $image_id ) {
				continue;
			}

			$image = wp_get_attachment_image(
				$image_id,
				$this->image_size,
				false,
				[
					'class' => sprintf( 'mai-cyber-gallery-image size-%s', sanitize_html_class( $this->image_size ) ),
				]
			);

			if ( ! $image ) {
				continue;
			}

			$metadata = wp_get_attachment_metadata( $image_id );
			$width    = isset( $metadata['width'] ) ? absint( $metadata['width'] ) : 0;
			$height   = isset( $metadata['height'] ) ? absint( $metadata['height'] ) : 0;

			if ( ! $width || ! $height ) {
				$source = wp_get_attachment_image_src( $image_id, 'full' );
				$width  = $source ? absint( $source[1] ) : 0;
				$height = $source ? absint( $source[2] ) : 0;
			}

			$caption = wp_get_attachment_caption( $image_id );
			$item = [
				'id'                   => $image_id,
				'image'                => $image,
				'caption'              => $caption ? $caption : '',
				'alt'                  => (string) get_post_meta( $image_id, '_wp_attachment_image_alt', true ),
				'title'                => get_the_title( $image_id ),
				'ratio'                => $width && $height ? $width / $height : 1,
				'width'                => $width,
				'height'               => $height,
				'link'                 => isset( $row['link'] ) && is_array( $row['link'] ) ? $row['link'] : [],
				'slide_title'          => isset( $row['slide_title'] ) ? $row['slide_title'] : '',
				'slide_subtext'        => isset( $row['slide_subtext'] ) ? $row['slide_subtext'] : '',
				'slide_align'          => isset( $row['slide_align'] ) ? $row['slide_align'] : 'start',
				'slide_align_vertical' => isset( $row['slide_align_vertical'] ) ? $row['slide_align_vertical'] : 'bottom',
				'slide_buttons'        => isset( $row['slide_buttons'] ) ? $row['slide_buttons'] : [],
			];

			/**
			 * Filter a normalized gallery item before rendering.
			 *
			 * @since 0.1.0
			 *
			 * @param array $item Item data.
			 * @param array $args Gallery arguments.
			 */
			$item = (array) apply_filters( 'mai_cyber_gallery_item', $item, $this->args );

			if ( empty( $item['image'] ) ) {
				continue;
			}

			$items[] = $item;
		}

		/**
		 * Filter all prepared gallery items.
		 *
		 * @since 0.1.0
		 *
		 * @param array $items Prepared items.
		 * @param array $args  Gallery arguments.
		 */
		$items = (array) apply_filters( 'mai_cyber_gallery_items', $items, $this->args );

		$normalized = [];

		foreach ( $items as $item ) {
			if ( ! is_array( $item ) || empty( $item['image'] ) || ! is_string( $item['image'] ) ) {
				continue;
			}

			$item = wp_parse_args(
				$item,
				[
					'id'                   => 0,
					'caption'              => '',
					'alt'                  => '',
					'title'                => '',
					'ratio'                => 1,
					'width'                => 0,
					'height'               => 0,
					'link'                 => [],
					'slide_title'          => '',
					'slide_subtext'        => '',
					'slide_align'          => 'start',
					'slide_align_vertical' => 'bottom',
					'slide_buttons'        => [],
				]
			);

			foreach ( [ 'caption', 'alt', 'title' ] as $key ) {
				$item[ $key ] = is_scalar( $item[ $key ] ) ? (string) $item[ $key ] : '';
			}

			$item['id']          = absint( $item['id'] );
			$item['ratio']       = is_numeric( $item['ratio'] ) ? (float) $item['ratio'] : 1;
			$item['width']       = absint( $item['width'] );
			$item['height']      = absint( $item['height'] );
			$item['link']        = is_array( $item['link'] ) ? $item['link'] : [];
			$item['slide_title'] = is_scalar( $item['slide_title'] )
				? sanitize_text_field( (string) $item['slide_title'] )
				: '';
			$item['slide_subtext'] = is_scalar( $item['slide_subtext'] )
				? sanitize_textarea_field( (string) $item['slide_subtext'] )
				: '';
			$item['slide_align'] = is_scalar( $item['slide_align'] )
				? sanitize_key( (string) $item['slide_align'] )
				: 'start';
			if ( ! in_array( $item['slide_align'], [ 'start', 'center', 'end' ], true ) ) {
				$item['slide_align'] = 'start';
			}
			$item['slide_align_vertical'] = is_scalar( $item['slide_align_vertical'] )
				? sanitize_key( (string) $item['slide_align_vertical'] )
				: 'bottom';
			if ( ! in_array( $item['slide_align_vertical'], [ 'top', 'center', 'bottom' ], true ) ) {
				$item['slide_align_vertical'] = 'bottom';
			}
			$item['slide_buttons'] = $this->sanitize_slide_buttons( $item['slide_buttons'] );
			$normalized[]          = $item;
		}

		// Item position is part of labels, lightbox grouping, and filmstrip state.
		return array_values( $normalized );
	}

	/**
	 * Get one gallery item.
	 *
	 * @since 0.1.0
	 *
	 * @param array  $item       Item data.
	 * @param int    $index      Zero-based item index.
	 * @param int    $total      Total items.
	 * @param string $gallery_id Gallery ID.
	 *
	 * @return string
	 */
	protected function get_item_markup( $item, $index, $total, $gallery_id ) {
		$ratio = ! empty( $item['ratio'] ) ? (float) $item['ratio'] : 1;
		$ratio = max( 0.1, min( 10, $ratio ) );
		$has_slide_content = (
			'slider' === $this->args['layout']
			&& ( $item['slide_title'] || $item['slide_subtext'] || $item['slide_buttons'] )
		);

		$item_atts = [
			'class'            => 'mai-cyber-gallery-item is-column',
			'role'             => 'listitem',
			'data-cyber-ratio' => number_format( $ratio, 6, '.', '' ),
			'style'            => sprintf( '--cyber-ratio:%s;', number_format( $ratio, 6, '.', '' ) ),
		];

		if ( $has_slide_content ) {
			$item_atts['class'] = mai_add_classes(
				[
					'has-slide-content',
					'has-slide-align-' . $item['slide_align'],
					'has-slide-align-vertical-' . $item['slide_align_vertical'],
				],
				$item_atts['class']
			);
		}

		if ( in_array( $this->args['layout'], [ 'filmstrip', 'slider' ], true ) ) {
			$item_atts['aria-roledescription'] = esc_html__( 'slide', 'mai-cyber-gallery' );

			if ( 'slider' === $this->args['layout'] && $item['slide_title'] ) {
				$item_atts['aria-label'] = sprintf(
					/* translators: 1: Current slide number. 2: Total slides. 3: Slide title. */
					esc_html__( '%1$d of %2$d: %3$s', 'mai-cyber-gallery' ),
					$index + 1,
					$total,
					$item['slide_title']
				);
				$item_atts['data-cyber-slide-title'] = $item['slide_title'];
			} else {
				$item_atts['aria-label'] = sprintf(
					/* translators: 1: Current slide number. 2: Total slides. */
					esc_html__( '%1$d of %2$d', 'mai-cyber-gallery' ),
					$index + 1,
					$total
				);
			}
		}

		$link_atts = $this->get_link_attributes( $item, $index, $total, $gallery_id );

		if ( $link_atts ) {
			$frame = sprintf(
				'<a %1$s>%2$s</a>',
				genesis_attr(
					'mai-cyber-gallery-image-link',
					$link_atts,
					[
						'args' => $this->args,
						'item' => $item,
					]
				),
				$item['image']
			);
		} else {
			$frame = sprintf( '<div class="mai-cyber-gallery-image-frame">%s</div>', $item['image'] );
		}

		$caption = '';
		if ( 'none' !== $this->args['captions'] && ! empty( $item['caption'] ) ) {
			$caption = sprintf(
				'<figcaption class="mai-cyber-gallery-image-caption">%s</figcaption>',
				wp_kses_post( $item['caption'] )
			);
		}

		$slide_content = $has_slide_content
			? $this->get_slider_content_markup( $item, $index, $gallery_id )
			: '';
		$media = '<div class="mai-cyber-gallery-media">' . $frame . $slide_content;
		if ( 'overlay' === $this->args['captions'] ) {
			$media .= $caption;
			$caption = '';
		}
		$media .= '</div>';

		$content = $media . $caption;

		return genesis_markup(
			[
				'open'    => '<figure %s>',
				'close'   => '</figure>',
				'context' => 'mai-cyber-gallery-item',
				'content' => $content,
				'echo'    => false,
				'atts'    => $item_atts,
				'params'  => [
					'args'  => $this->args,
					'item'  => $item,
					'index' => $index,
				],
			]
		);
	}

	/**
	 * Get one Slider content overlay.
	 *
	 * @since 0.3.0
	 *
	 * @param array  $item       Normalized gallery item.
	 * @param int    $index      Zero-based item index.
	 * @param string $gallery_id Gallery ID.
	 *
	 * @return string
	 */
	protected function get_slider_content_markup( $item, $index, $gallery_id ) {
		$content = '';

		if ( $item['slide_title'] ) {
			$content .= sprintf(
				'<h2 id="%1$s" class="mai-cyber-gallery-slider-title">%2$s</h2>',
				esc_attr( sanitize_html_class( $gallery_id . '-slide-' . ( $index + 1 ) . '-title' ) ),
				esc_html( $item['slide_title'] )
			);
		}

		if ( $item['slide_subtext'] ) {
			$content .= sprintf(
				'<p class="mai-cyber-gallery-slider-subtext">%s</p>',
				nl2br( esc_html( $item['slide_subtext'] ), false )
			);
		}

		if ( $item['slide_buttons'] ) {
			$buttons = '';

			foreach ( $item['slide_buttons'] as $button ) {
				$link       = $button['link'];
				$classes    = mai_add_classes(
					'mai-cyber-gallery-slider-cta',
					mai_cyber_gallery_get_button_style_classes( $button['style'] )
				);
				$new_window = '_blank' === $link['target']
					? ' target="_blank" rel="noopener"'
					: '';

				$buttons .= sprintf(
					'<a class="%1$s" href="%2$s"%3$s>%4$s</a>',
					esc_attr( $classes ),
					esc_url( $link['url'] ),
					$new_window,
					esc_html( $link['title'] )
				);
			}

			if ( $buttons ) {
				$content .= '<div class="mai-cyber-gallery-slider-actions">' . $buttons . '</div>';
			}
		}

		if ( ! $content ) {
			return '';
		}

		return sprintf(
			'<div class="mai-cyber-gallery-slider-content-layer has-dark-background"><div class="mai-cyber-gallery-slider-content">%s</div></div>',
			$content
		);
	}

	/**
	 * Get link attributes for an item.
	 *
	 * @since 0.1.0
	 *
	 * @param array  $item       Item data.
	 * @param int    $index      Zero-based item index.
	 * @param int    $total      Total items.
	 * @param string $gallery_id Gallery ID.
	 *
	 * @return array
	 */
	protected function get_link_attributes( $item, $index, $total, $gallery_id ) {
		$action = $this->args['click_action'];
		$atts   = [
			'class' => 'mai-cyber-gallery-image-link',
		];

		if ( 'none' === $action ) {
			return [];
		}

		$label = wp_strip_all_tags( (string) $item['caption'] );
		$label = $label ? $label : wp_strip_all_tags( (string) $item['alt'] );
		$label = $label ? $label : wp_strip_all_tags( (string) $item['title'] );
		$label = $label ? $label : sprintf(
			/* translators: %d: Image number. */
			esc_html__( 'Image %d', 'mai-cyber-gallery' ),
			$index + 1
		);

		if ( 'custom' === $action ) {
			if ( empty( $item['link']['url'] ) ) {
				return [];
			}

			$atts['href'] = esc_url( $item['link']['url'] );

			if ( ! empty( $item['link']['title'] ) ) {
				$atts['aria-label'] = sanitize_text_field( $item['link']['title'] );
			} else {
				$atts['aria-label'] = sprintf(
					/* translators: %s: Image description. */
					esc_html__( 'Follow image link: %s', 'mai-cyber-gallery' ),
					$label
				);
			}

			if ( ! empty( $item['link']['target'] ) && '_blank' === $item['link']['target'] ) {
				$atts['target'] = '_blank';
				$atts['rel']    = 'noopener';
			}

			return $atts;
		}

		if ( 'attachment' === $action ) {
			$atts['href'] = esc_url( get_attachment_link( $item['id'] ) );
			$atts['aria-label'] = sprintf(
				/* translators: %s: Image description. */
				esc_html__( 'View image attachment page: %s', 'mai-cyber-gallery' ),
				$label
			);
			return $atts['href'] ? $atts : [];
		}

		$full = wp_get_attachment_image_src( $item['id'], 'full' );
		if ( ! $full ) {
			return [];
		}

		$atts['href'] = esc_url( $full[0] );

		if ( 'lightbox' !== $action ) {
			$atts['aria-label'] = sprintf(
				/* translators: %s: Image description. */
				esc_html__( 'View full-size image: %s', 'mai-cyber-gallery' ),
				$label
			);
			return $atts;
		}

		$atts['class']                  = mai_add_classes( 'mai-cyber-gallery-lightbox', $atts['class'] );
		$atts['data-cyber-lightbox']    = 'true';
		$atts['data-cyber-group']       = $gallery_id;
		$atts['data-cyber-alt']         = wp_strip_all_tags( (string) $item['alt'] );
		$atts['data-cyber-caption']     = wp_strip_all_tags( (string) $item['caption'] );
		$atts['data-cyber-label']       = $label;
		$atts['data-cyber-srcset']      = wp_get_attachment_image_srcset( $item['id'], 'full' );
		$atts['data-cyber-sizes']       = wp_get_attachment_image_sizes( $item['id'], 'full' );
		$atts['data-cyber-width']       = absint( $full[1] );
		$atts['data-cyber-height']      = absint( $full[2] );
		$atts['data-cyber-arrow-style'] = $this->args['lightbox_arrow_style'];
		$atts['aria-haspopup']          = 'dialog';
		$atts['aria-label']             = sprintf(
			/* translators: 1: Current image number. 2: Total images. 3: Image description. */
			esc_html__( 'Open image %1$d of %2$d in viewer: %3$s', 'mai-cyber-gallery' ),
			$index + 1,
			$total,
			$label
		);

		return $atts;
	}

	/**
	 * Get the layout-aware WordPress image size.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public function get_image_size() {
		$image_size = $this->args['image_size'];

		if (
			in_array( $this->args['layout'], [ 'grid', 'filmstrip' ], true )
			&& mai_has_image_orientiation( $this->args['image_orientation'] )
		) {
			$image_size = sprintf( '%s-sm', $this->args['image_orientation'] );
		}

		/**
		 * Filter the image size used by a Cyber Gallery.
		 *
		 * @since 0.1.0
		 *
		 * @param string $image_size WordPress image size.
		 * @param array  $args       Gallery arguments.
		 */
		$image_size = apply_filters( 'mai_cyber_gallery_image_size', $image_size, $this->args );

		return sanitize_key( (string) $image_size );
	}

	/**
	 * Enqueue only the assets required by this gallery.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	protected function enqueue_assets() {
		wp_enqueue_style( 'mai-cyber-gallery' );

		if (
			'lightbox' === $this->args['click_action']
			|| in_array( $this->args['layout'], [ 'masonry', 'justified', 'tiled', 'filmstrip', 'slider' ], true )
		) {
			wp_enqueue_script( 'mai-cyber-gallery' );
		}
	}

	/**
	 * Get editor placeholder markup when the gallery is empty.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	protected function get_empty_preview() {
		if ( ! $this->args['preview'] ) {
			return '';
		}

		$message = 'slider' === $this->args['layout']
			? esc_html__( 'Add slides in the block sidebar settings.', 'mai-cyber-gallery' )
			: esc_html__( 'Add gallery images in the block sidebar settings.', 'mai-cyber-gallery' );

		return sprintf(
			'<p class="mai-cyber-gallery-placeholder"><span class="dashicons dashicons-format-gallery" aria-hidden="true"></span><span>%s</span></p>',
			$message
		);
	}

	/**
	 * Get filmstrip navigation controls.
	 *
	 * @since 0.1.0
	 *
	 * @param string $gallery_id Gallery ID.
	 *
	 * @return string
	 */
	protected function get_filmstrip_controls( $gallery_id ) {
		$previous_icon = function_exists( 'mai_get_svg_icon' )
			? mai_get_svg_icon( 'arrow-left', 'regular' )
			: '&larr;';
		$next_icon = function_exists( 'mai_get_svg_icon' )
			? mai_get_svg_icon( 'arrow-right', 'regular' )
			: '&rarr;';

		return sprintf(
			'<div class="mai-cyber-gallery-controls" role="group" aria-label="%1$s">'
			. '<button class="mai-cyber-gallery-control mai-cyber-gallery-control-previous" type="button" data-cyber-filmstrip-previous aria-controls="%2$s" aria-label="%3$s" disabled><span aria-hidden="true">%4$s</span></button>'
			. '<button class="mai-cyber-gallery-control mai-cyber-gallery-control-next" type="button" data-cyber-filmstrip-next aria-controls="%2$s" aria-label="%5$s"><span aria-hidden="true">%6$s</span></button>'
			. '</div>',
			esc_attr__( 'Gallery navigation', 'mai-cyber-gallery' ),
			esc_attr( $gallery_id . '-items' ),
			esc_attr__( 'Previous images', 'mai-cyber-gallery' ),
			$previous_icon ? $previous_icon : '&larr;',
			esc_attr__( 'Next images', 'mai-cyber-gallery' ),
			$next_icon ? $next_icon : '&rarr;'
		);
	}

	/**
	 * Get slider navigation controls.
	 *
	 * @since 0.2.0
	 *
	 * @param string $gallery_id Gallery ID.
	 * @param array  $items      Normalized slide items.
	 *
	 * @return string
	 */
	protected function get_slider_controls( $gallery_id, $items ) {
		$navigation = $this->args['slider_navigation'];
		$has_arrows = in_array( $navigation, [ 'arrows', 'arrows-dots' ], true );
		$has_dots   = in_array( $navigation, [ 'dots', 'arrows-dots' ], true );
		$track_id   = $gallery_id . '-items';
		$total      = count( $items );
		$controls   = '';

		if ( $has_arrows ) {
			$previous_icon = '<svg aria-hidden="true" viewBox="0 0 24 24" focusable="false"><path d="M15.5 4 7.5 12l8 8 1.5-1.5-6.5-6.5L17 5.5z"></path></svg>';
			$next_icon     = '<svg aria-hidden="true" viewBox="0 0 24 24" focusable="false"><path d="m8.5 20 8-8-8-8L7 5.5l6.5 6.5L7 18.5z"></path></svg>';

			$controls .= sprintf(
				'<button class="mai-cyber-gallery-slider-arrow mai-cyber-gallery-slider-previous" type="button" data-cyber-slider-previous aria-controls="%1$s" aria-label="%2$s" disabled>%3$s</button>'
				. '<button class="mai-cyber-gallery-slider-arrow mai-cyber-gallery-slider-next" type="button" data-cyber-slider-next aria-controls="%1$s" aria-label="%4$s">%5$s</button>',
				esc_attr( $track_id ),
				esc_attr__( 'Previous slide', 'mai-cyber-gallery' ),
				$previous_icon,
				esc_attr__( 'Next slide', 'mai-cyber-gallery' ),
				$next_icon
			);
		}

		if ( $has_dots ) {
			$controls .= sprintf(
				'<div class="mai-cyber-gallery-slider-dots" role="group" aria-label="%s">',
				esc_attr__( 'Choose a slide', 'mai-cyber-gallery' )
			);

			for ( $index = 0; $index < $total; $index++ ) {
				$current = 0 === $index ? ' aria-current="true"' : '';
				$title   = $items[ $index ]['slide_title'];
				$label   = $title
					? sprintf(
						/* translators: 1: Slide number. 2: Slide title. */
						__( 'Go to slide %1$d: %2$s', 'mai-cyber-gallery' ),
						$index + 1,
						$title
					)
					: sprintf(
						/* translators: %d: Slide number. */
						__( 'Go to slide %d', 'mai-cyber-gallery' ),
						$index + 1
					);

				$controls .= sprintf(
					'<button class="mai-cyber-gallery-slider-dot" type="button" data-cyber-slider-dot="%1$d" aria-controls="%2$s" aria-label="%3$s"%4$s><span aria-hidden="true"></span></button>',
					$index,
					esc_attr( $track_id ),
					esc_attr( $label ),
					$current
				);
			}

			$controls .= '</div>';
		}

		if ( 'counter' === $navigation ) {
			$controls .= sprintf(
				'<p class="mai-cyber-gallery-slider-counter" aria-hidden="true"><span data-cyber-slider-current>1</span><span class="mai-cyber-gallery-slider-counter-separator">/</span><span>%d</span></p>',
				$total
			);
		}

		$first_title = $items[0]['slide_title'];
		$status      = $first_title
			? sprintf(
				/* translators: 1: Current slide number. 2: Total slides. 3: Slide title. */
				esc_html__( 'Slide %1$d of %2$d: %3$s', 'mai-cyber-gallery' ),
				1,
				$total,
				$first_title
			)
			: sprintf(
				/* translators: 1: Current slide number. 2: Total slides. */
				esc_html__( 'Slide %1$d of %2$d', 'mai-cyber-gallery' ),
				1,
				$total
			);

		$status_markup = sprintf(
			'<p class="mai-cyber-gallery-slider-status screen-reader-text" data-cyber-slider-status aria-live="polite" aria-atomic="true">%s</p>',
			esc_html( $status )
		);

		if ( 'none' === $navigation ) {
			return $status_markup;
		}

		return sprintf(
			'<div class="mai-cyber-gallery-slider-controls" role="group" aria-label="%1$s">%2$s%3$s</div>',
			esc_attr__( 'Slider navigation', 'mai-cyber-gallery' ),
			$controls,
			$status_markup
		);
	}
}

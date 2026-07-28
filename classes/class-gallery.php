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
 *
 * @phpstan-type CyberGalleryLink array{
 *     url: string,
 *     title: string,
 *     target: ''|'_blank'
 * }
 * @phpstan-type CyberGalleryButton array{
 *     link: CyberGalleryLink,
 *     style: string
 * }
 * @phpstan-type CyberGallerySlide array{
 *     image: positive-int,
 *     title: string,
 *     subtext: string,
 *     align: 'start'|'center'|'end',
 *     align_vertical: 'top'|'center'|'bottom',
 *     buttons: list<CyberGalleryButton>
 * }
 * @phpstan-type CyberGalleryLinkedImage array{
 *     image: positive-int,
 *     link: CyberGalleryLink
 * }
 * @phpstan-type CyberGalleryItem array{
 *     id: int,
 *     image: string,
 *     caption: string,
 *     alt: string,
 *     title: string,
 *     ratio: float,
 *     width: int,
 *     height: int,
 *     link: array<array-key, mixed>,
 *     slide_title: string,
 *     slide_subtext: string,
 *     slide_align: 'start'|'center'|'end',
 *     slide_align_vertical: 'top'|'center'|'bottom',
 *     slide_buttons: list<CyberGalleryButton>,
 *     filmstrip_logo_scale: int
 * }
 * @phpstan-type CyberGalleryArgs array{
 *     preview: bool,
 *     id: string,
 *     align: string,
 *     anchor: string,
 *     class: string,
 *     layout: string,
 *     click_action: 'none'|'lightbox'|'media'|'attachment'|'custom',
 *     lightbox_arrow_style: string,
 *     images: list<positive-int>,
 *     images_links: list<CyberGalleryLinkedImage>,
 *     slides: list<CyberGallerySlide>,
 *     image_orientation: string,
 *     image_size: string,
 *     image_radius: 'square'|'rounded',
 *     captions: 'none'|'below'|'overlay',
 *     shadow: bool,
 *     hover: 'none'|'zoom'|'lift'|'fade'|'desaturate',
 *     hover_desaturate_amount: int,
 *     masonry_flow: 'balanced'|'ordered',
 *     justified_row_height: int,
 *     justified_last_row: 'start'|'center'|'end',
 *     tiled_pattern: 'organic'|'calm'|'dense',
 *     mosaic_feature_side: 'left'|'right',
 *     mosaic_alternate: bool,
 *     mosaic_items_after_feature: int,
 *     mosaic_odd_alignment: 'fill'|'start'|'center'|'end',
 *     mosaic_row_height: int,
 *     filmstrip_autoplay: bool,
 *     filmstrip_autoplay_interval: float,
 *     filmstrip_autoplay_items: int,
 *     filmstrip_pause_on_hover: bool,
 *     filmstrip_controls: bool,
 *     filmstrip_arrows: bool,
 *     filmstrip_arrow_position: 'overlay'|'above-start'|'above-center'|'above-end'|'below-start'|'below-center'|'below-end',
 *     filmstrip_arrow_style: string,
 *     filmstrip_image_mode: 'natural'|'uniform-cover'|'logo-contain',
 *     filmstrip_height: int,
 *     filmstrip_height_mobile: int,
 *     filmstrip_logo_padding: int,
 *     filmstrip_logo_padding_horizontal: int,
 *     filmstrip_logo_padding_vertical: int,
 *     filmstrip_logo_overrides: array<int,int>,
 *     slider_navigation: string,
 *     slider_navigation_secondary: string,
 *     slider_navigation_position: 'overlay'|'below',
 *     slider_dots_position: 'top'|'bottom',
 *     slider_arrow_style: string,
 *     slider_transition: string,
 *     slider_transition_speed: string,
 *     slider_autoplay: bool,
 *     slider_autoplay_control: bool,
 *     slider_autoplay_interval: int,
 *     slider_pause_on_hover: bool,
 *     slider_navigation_hover: string,
 *     slider_navigation_main: string,
 *     slider_navigation_border: string,
 *     slider_navigation_inner: string,
 *     slider_navigation_main_hover: string,
 *     slider_navigation_border_hover: string,
 *     slider_navigation_inner_hover: string,
 *     slider_overlay_style: string,
 *     slider_overlay_color: string,
 *     slider_overlay_opacity: int,
 *     slider_width_mode: 'full'|'contained'|'inset',
 *     slider_width: int,
 *     slider_height: int,
 *     slider_height_mobile: int,
 *     slider_image_fit: 'cover'|'contain',
 *     columns: int,
 *     columns_responsive: bool,
 *     columns_md: int,
 *     columns_sm: int,
 *     columns_xs: int,
 *     align_columns: string,
 *     align_columns_vertical: string,
 *     column_gap: string,
 *     row_gap: string,
 *     margin_top: string,
 *     margin_right: string,
 *     margin_bottom: string,
 *     margin_left: string,
 *     ...
 * }
 */
class Mai_Cyber_Gallery {
	/**
	 * Number of validated items in the current render.
	 *
	 * Kept as render state so the long-standing one-argument
	 * get_filmstrip_controls() extension signature remains compatible.
	 *
	 * @since 0.7.0
	 *
	 * @var int
	 */
	protected int $render_item_count = 0;

	/**
	 * Sanitized gallery arguments.
	 *
	 * @var CyberGalleryArgs
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
	 * @param array<string, mixed> $args Gallery arguments.
	 */
	public function __construct( $args = [] ) {
		$navigation_was_provided = is_array( $args )
			&& array_key_exists( 'slider_navigation', $args );
		$secondary_was_provided = is_array( $args )
			&& array_key_exists( 'slider_navigation_secondary', $args );
		$hover_was_provided = is_array( $args )
			&& array_key_exists( 'hover', $args );
		$provided_navigation = $navigation_was_provided && is_scalar( $args['slider_navigation'] )
			? sanitize_key( (string) $args['slider_navigation'] )
			: '';
		$column_defaults = mai_get_columns_defaults();
		$slider_width    = max( 0, min( 2400, absint( mai_get_breakpoint( 'xl' ) ) ) );

		$defaults = array_merge(
			$column_defaults,
			[
				'preview'                    => false,
				'id'                         => '',
				'align'                      => '',
				'anchor'                     => '',
				'class'                      => '',
				'margin_right'               => '',
				'margin_left'                => '',
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
				'hover_desaturate_amount'    => 100,
				'masonry_flow'               => 'balanced',
				'justified_row_height'       => 240,
				'justified_last_row'         => 'start',
				'tiled_pattern'              => 'organic',
				'mosaic_feature_side'        => 'left',
				'mosaic_alternate'           => false,
				'mosaic_items_after_feature' => 4,
				'mosaic_odd_alignment'       => 'fill',
				'mosaic_row_height'          => 0,
				'filmstrip_autoplay'         => false,
				'filmstrip_autoplay_interval' => 5,
				'filmstrip_autoplay_items'   => 1,
				'filmstrip_pause_on_hover'   => true,
				'filmstrip_controls'         => true,
				'filmstrip_arrows'           => false,
				'filmstrip_arrow_position'   => 'below-end',
				'filmstrip_arrow_style'      => 'circle',
				'filmstrip_image_mode'       => 'natural',
				'filmstrip_height'           => 320,
				'filmstrip_height_mobile'    => 220,
				'filmstrip_logo_padding'     => 24,
				'filmstrip_logo_padding_horizontal' => null,
				'filmstrip_logo_padding_vertical'   => null,
				'filmstrip_logo_overrides'   => [],
				'slider_navigation'            => 'arrows',
				'slider_navigation_secondary'  => 'dots',
				'slider_navigation_position'   => 'overlay',
				'slider_dots_position'         => 'top',
				'slider_arrow_style'           => 'circle',
				'slider_transition'            => 'native',
				'slider_transition_speed'      => 'normal',
				'slider_autoplay'              => false,
				'slider_autoplay_control'      => true,
				'slider_autoplay_interval'     => 5,
				'slider_pause_on_hover'        => true,
				'slider_navigation_hover'      => 'color',
				'slider_navigation_main'       => '',
				'slider_navigation_border'     => '',
				'slider_navigation_inner'      => '',
				'slider_navigation_main_hover' => '',
				'slider_navigation_border_hover' => '',
				'slider_navigation_inner_hover'  => '',
				'slider_overlay_style'           => 'none',
				'slider_overlay_color'           => 'black',
				'slider_overlay_opacity'         => 35,
				'slider_width_mode'              => 'full',
				'slider_width'                   => $slider_width,
				'slider_height'                  => 560,
				'slider_height_mobile'           => 320,
				'slider_image_fit'               => 'cover',
			]
		);

		$args = wp_parse_args( $args, $defaults );

		// Slider hover effects are new in 0.7.0. Keep existing/API Sliders
		// motionless until a hover treatment is deliberately selected.
		if (
			! $hover_was_provided
			&& 'slider' === sanitize_key( (string) $args['layout'] )
		) {
			$args['hover'] = 'none';
		}

		/**
		 * Filter gallery arguments before they are sanitized.
		 *
		 * @since 0.1.0
		 *
		 * @param array<string, mixed> $args Gallery arguments.
		 */
		$args = (array) apply_filters( 'mai_cyber_gallery_args', $args );

		// Before 0.6.0, an explicitly supplied atomic navigation value was the
		// complete navigation choice. Preserve those PHP API calls when the new
		// secondary argument is absent. A filter that supplies a non-default
		// secondary value still takes precedence.
		if (
			$navigation_was_provided
			&& ! $secondary_was_provided
			&& in_array( $provided_navigation, [ 'arrows', 'dots', 'counter', 'none' ], true )
			&& (
				! isset( $args['slider_navigation_secondary'] )
				|| $defaults['slider_navigation_secondary'] === $args['slider_navigation_secondary']
			)
		) {
			$args['slider_navigation_secondary'] = 'none';
		}

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
		$items = $this->get_items();

		if ( ! $items ) {
			return $this->get_empty_preview();
		}

		if ( ! $this->args['preview'] ) {
			$cache_tags = [];

			foreach ( $items as $item ) {
				$cache_tags[] = mai_cyber_gallery_plugin()->attachment_cache_tag( $item['id'] );
			}

			do_action( 'litespeed_tag_add', array_values( array_unique( $cache_tags ) ) );
		}

		$instance   = mai_get_index( 'mai-cyber-gallery' ) + 1;
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
			'has-image-overlay-' . $this->args['slider_overlay_style'],
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

		if ( 'mosaic' === $this->args['layout'] ) {
			$root_classes[] = 'has-mosaic-feature-' . $this->args['mosaic_feature_side'];
			$root_classes[] = 'has-mosaic-odd-' . $this->args['mosaic_odd_alignment'];

			if ( $this->args['mosaic_alternate'] ) {
				$root_classes[] = 'has-mosaic-alternating';
			}
		}

		if ( 'justified' === $this->args['layout'] ) {
			$root_classes[] = 'has-justified-last-row-' . $this->args['justified_last_row'];
		}

		if ( in_array( $this->args['layout'], [ 'filmstrip', 'slider' ], true ) ) {
			$root_classes[] = 'has-navigation-hover-' . $this->args['slider_navigation_hover'];
		}

		if ( 'filmstrip' === $this->args['layout'] ) {
			$root_classes[] = 'has-filmstrip-arrows-' . $this->args['filmstrip_arrow_style'];
			$root_classes[] = 'has-filmstrip-controls-' . $this->args['filmstrip_arrow_position'];
			$root_classes[] = 'has-filmstrip-image-' . $this->args['filmstrip_image_mode'];

			if ( $this->args['filmstrip_autoplay'] && 1 < count( $items ) && ! $this->args['preview'] ) {
				$root_classes[] = 'has-filmstrip-autoplay';
				$root_atts['data-cyber-filmstrip-autoplay'] = (string) ( $this->args['filmstrip_autoplay_interval'] * 1000 );
				$root_atts['data-cyber-filmstrip-autoplay-items'] = (string) $this->args['filmstrip_autoplay_items'];
				$root_atts['data-cyber-filmstrip-pause-hover'] = $this->args['filmstrip_pause_on_hover']
					? 'true'
					: 'false';
			}
		}

		if ( 'slider' === $this->args['layout'] ) {
			if ( $has_slider_content ) {
				$root_classes[] = 'has-slider-content';
			}

			$navigation = array_unique(
				[
					$this->args['slider_navigation'],
					$this->args['slider_navigation_secondary'],
				]
			);
			foreach ( array_diff( $navigation, [ 'none' ] ) as $navigation_type ) {
				$root_classes[] = 'has-slider-navigation-' . $navigation_type;
			}
			if ( in_array( 'arrows', $navigation, true ) && in_array( 'dots', $navigation, true ) ) {
				$root_classes[] = 'has-slider-navigation-arrows-dots';
			}
			$root_classes[] = 'has-slider-navigation-' . $this->args['slider_navigation_position'];
			$root_classes[] = 'has-slider-dots-' . $this->args['slider_dots_position'];
			$root_classes[] = 'has-slider-arrows-' . $this->args['slider_arrow_style'];
			$root_classes[] = 'has-slider-transition-' . $this->args['slider_transition'];
			$root_classes[] = 'has-slider-transition-speed-' . $this->args['slider_transition_speed'];
			$root_classes[] = 'has-slider-navigation-hover-' . $this->args['slider_navigation_hover'];
			$root_classes[] = 'has-slider-overlay-' . $this->args['slider_overlay_style'];
			$root_classes[] = 'has-slider-width-' . $this->args['slider_width_mode'];
			$root_classes[] = 'has-slider-fit-' . $this->args['slider_image_fit'];
			$root_atts['data-cyber-slider-transition'] = $this->args['slider_transition'];

			if ( $this->args['slider_autoplay'] && 1 < count( $items ) && ! $this->args['preview'] ) {
				$root_classes[] = 'has-slider-autoplay';
				$root_atts['data-cyber-slider-autoplay'] = (string) ( $this->args['slider_autoplay_interval'] * 1000 );
				$root_atts['data-cyber-slider-pause-hover'] = $this->args['slider_pause_on_hover']
					? 'true'
					: 'false';
			}
		}

		$root_atts['class'] = mai_add_classes( $root_classes, $root_atts['class'] );

		if ( $this->args['align'] ) {
			$root_atts['class'] = mai_add_classes( 'align' . $this->args['align'], $root_atts['class'] );
		}

		if ( $this->args['class'] ) {
			$root_atts['class'] = mai_add_classes( $this->args['class'], $root_atts['class'] );
		}

		foreach ( [ 'top', 'right', 'bottom', 'left' ] as $side ) {
			$key = 'margin_' . $side;

			if ( $this->args[ $key ] && mai_is_valid_size( $this->args[ $key ] ) ) {
				$root_atts['class'] = mai_add_classes(
					sprintf( 'has-%s-margin-%s', $this->args[ $key ], $side ),
					$root_atts['class']
				);
			}
		}

		$root_style = '';

		if ( 'desaturate' === $this->args['hover'] ) {
			$root_style .= sprintf( '--cyber-desaturate:%d%%;', $this->args['hover_desaturate_amount'] );
		}

		if ( 'mosaic' === $this->args['layout'] && $this->args['mosaic_row_height'] ) {
			$root_style .= sprintf(
				'--cyber-mosaic-row-height-custom:%s;',
				mai_get_unit_value( $this->args['mosaic_row_height'] )
			);
		}

		if ( 'filmstrip' === $this->args['layout'] ) {
			$root_style .= sprintf(
				'--cyber-filmstrip-height:%1$s;--cyber-filmstrip-height-mobile:%2$s;--cyber-filmstrip-logo-padding:%3$s;--cyber-filmstrip-logo-padding-horizontal:%4$s;--cyber-filmstrip-logo-padding-vertical:%5$s;',
				mai_get_unit_value( $this->args['filmstrip_height'] ),
				mai_get_unit_value( $this->args['filmstrip_height_mobile'] ),
				mai_get_unit_value( $this->args['filmstrip_logo_padding'] ),
				mai_get_unit_value( $this->args['filmstrip_logo_padding_horizontal'] ),
				mai_get_unit_value( $this->args['filmstrip_logo_padding_vertical'] )
			);
		}

		if ( in_array( $this->args['layout'], [ 'filmstrip', 'slider' ], true ) ) {
			$navigation_colors = [
				'slider_navigation_main'         => [ '--cyber-navigation-main', '--cyber-slider-nav-main' ],
				'slider_navigation_border'       => [ '--cyber-navigation-border', '--cyber-slider-nav-border' ],
				'slider_navigation_inner'        => [ '--cyber-navigation-inner', '--cyber-slider-nav-inner' ],
				'slider_navigation_main_hover'   => [ '--cyber-navigation-main-hover', '--cyber-slider-nav-main-hover' ],
				'slider_navigation_border_hover' => [ '--cyber-navigation-border-hover', '--cyber-slider-nav-border-hover' ],
				'slider_navigation_inner_hover'  => [ '--cyber-navigation-inner-hover', '--cyber-slider-nav-inner-hover' ],
			];

			foreach ( $navigation_colors as $key => $properties ) {
				if ( ! $this->args[ $key ] ) {
					continue;
				}

				$color = mai_get_color_css( $this->args[ $key ] );
				foreach ( $properties as $property ) {
					$root_style .= sprintf( '%1$s:%2$s;', $property, $color );
				}
			}
		}

		if ( 'none' !== $this->args['slider_overlay_style'] ) {
			$overlay_opacity = rtrim(
				rtrim(
					number_format( $this->args['slider_overlay_opacity'] / 100, 2, '.', '' ),
					'0'
				),
				'.'
			);
			$overlay_opacity = '' === $overlay_opacity ? '0' : $overlay_opacity;
			$overlay_color   = mai_get_color_css( $this->args['slider_overlay_color'] );
			$root_style     .= sprintf(
				'--cyber-image-overlay-color:%1$s;--cyber-image-overlay-opacity:%2$s;',
				$overlay_color,
				$overlay_opacity
			);

			if ( 'slider' === $this->args['layout'] ) {
				$root_style .= sprintf(
					'--cyber-slider-overlay-color:%1$s;--cyber-slider-overlay-opacity:%2$s;',
					$overlay_color,
					$overlay_opacity
				);
			}
		}

		if ( 'slider' === $this->args['layout'] ) {
			$slider_width = $this->args['slider_width']
				? mai_get_unit_value( $this->args['slider_width'] )
				: '100%';

			$root_style .= sprintf(
				'--cyber-slider-max-width:%1$s;--cyber-slider-height:%2$s;--cyber-slider-height-mobile:%3$s;',
				$slider_width,
				mai_get_unit_value( $this->args['slider_height'] ),
				mai_get_unit_value( $this->args['slider_height_mobile'] )
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

		if ( 'masonry' === $this->args['layout'] ) {
			$items_atts['data-cyber-masonry-flow'] = $this->args['masonry_flow'];
		}

		if ( 'tiled' === $this->args['layout'] ) {
			$items_atts['data-cyber-tiled-pattern'] = $this->args['tiled_pattern'];
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

		$total                   = count( $items );
		$this->render_item_count = $total;
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
	 * @param array<string, mixed> $args     Raw arguments.
	 * @param array<string, mixed> $defaults Defaults.
	 *
	 * @return CyberGalleryArgs
	 */
	protected function sanitize_args( $args, $defaults ) {
		$layouts            = array_keys( mai_cyber_gallery_get_layout_choices() );
		$arrow_styles       = array_keys( mai_cyber_gallery_get_arrow_style_choices() );
		$button_styles      = array_keys( mai_cyber_gallery_get_button_style_choices() );
		$slider_navigation  = array_keys( mai_cyber_gallery_get_slider_navigation_choices() );
		$slider_transitions = array_keys( mai_cyber_gallery_get_slider_transition_choices() );
		$slider_speeds      = array_keys( mai_cyber_gallery_get_slider_transition_speed_choices() );
		$slider_hovers      = array_keys( mai_cyber_gallery_get_slider_navigation_hover_choices() );
		$slider_overlays    = array_keys( mai_cyber_gallery_get_slider_overlay_style_choices() );

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
		$args['masonry_flow']               = sanitize_key( (string) $args['masonry_flow'] );
		$args['justified_last_row']         = sanitize_key( (string) $args['justified_last_row'] );
		$args['tiled_pattern']              = sanitize_key( (string) $args['tiled_pattern'] );
		$args['mosaic_feature_side']        = sanitize_key( (string) $args['mosaic_feature_side'] );
		$args['mosaic_odd_alignment']       = sanitize_key( (string) $args['mosaic_odd_alignment'] );
		$args['filmstrip_arrow_position']   = sanitize_key( (string) $args['filmstrip_arrow_position'] );
		$args['filmstrip_arrow_style']      = sanitize_key( (string) $args['filmstrip_arrow_style'] );
		$args['filmstrip_image_mode']       = sanitize_key( (string) $args['filmstrip_image_mode'] );
		$args['slider_navigation']          = sanitize_key( (string) $args['slider_navigation'] );
		$args['slider_navigation_secondary'] = sanitize_key( (string) $args['slider_navigation_secondary'] );
		$args['slider_navigation_position'] = sanitize_key( (string) $args['slider_navigation_position'] );
		$args['slider_dots_position']       = sanitize_key( (string) $args['slider_dots_position'] );
		$args['slider_arrow_style']         = sanitize_key( (string) $args['slider_arrow_style'] );
		$args['slider_transition']          = sanitize_key( (string) $args['slider_transition'] );
		$args['slider_transition_speed']    = sanitize_key( (string) $args['slider_transition_speed'] );
		$args['slider_navigation_hover']    = sanitize_key( (string) $args['slider_navigation_hover'] );
		$args['slider_overlay_style']       = sanitize_key( (string) $args['slider_overlay_style'] );
		$args['slider_width_mode']          = sanitize_key( (string) $args['slider_width_mode'] );
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

		if ( ! in_array( $args['hover'], [ 'none', 'zoom', 'lift', 'fade', 'desaturate' ], true ) ) {
			$args['hover'] = $defaults['hover'];
		}

		if ( ! is_numeric( $args['hover_desaturate_amount'] ) ) {
			$args['hover_desaturate_amount'] = $defaults['hover_desaturate_amount'];
		}
		$args['hover_desaturate_amount'] = max( 0, min( 100, (int) $args['hover_desaturate_amount'] ) );

		if ( ! in_array( $args['masonry_flow'], [ 'balanced', 'ordered' ], true ) ) {
			$args['masonry_flow'] = $defaults['masonry_flow'];
		}

		if ( ! in_array( $args['justified_last_row'], [ 'start', 'center', 'end' ], true ) ) {
			$args['justified_last_row'] = $defaults['justified_last_row'];
		}

		if ( ! in_array( $args['tiled_pattern'], [ 'organic', 'calm', 'dense' ], true ) ) {
			$args['tiled_pattern'] = $defaults['tiled_pattern'];
		}

		if ( ! in_array( $args['mosaic_feature_side'], [ 'left', 'right' ], true ) ) {
			$args['mosaic_feature_side'] = $defaults['mosaic_feature_side'];
		}

		$args['mosaic_alternate'] = mai_sanitize_bool( $args['mosaic_alternate'] );
		$args['mosaic_items_after_feature'] = is_numeric( $args['mosaic_items_after_feature'] )
			? max( 1, min( 8, absint( $args['mosaic_items_after_feature'] ) ) )
			: $defaults['mosaic_items_after_feature'];

		if ( ! in_array( $args['mosaic_odd_alignment'], [ 'fill', 'start', 'center', 'end' ], true ) ) {
			$args['mosaic_odd_alignment'] = $defaults['mosaic_odd_alignment'];
		}

		$args['mosaic_row_height'] = is_numeric( $args['mosaic_row_height'] )
			? max( 0, min( 600, absint( $args['mosaic_row_height'] ) ) )
			: $defaults['mosaic_row_height'];

		foreach ( [ 'margin_right', 'margin_left' ] as $key ) {
			$args[ $key ] = is_scalar( $args[ $key ] )
				? sanitize_html_class( (string) $args[ $key ] )
				: '';

			if ( $args[ $key ] && ! mai_is_valid_size( $args[ $key ] ) ) {
				$args[ $key ] = '';
			}
		}

		$args['filmstrip_autoplay']       = mai_sanitize_bool( $args['filmstrip_autoplay'] );
		$args['filmstrip_pause_on_hover'] = mai_sanitize_bool( $args['filmstrip_pause_on_hover'] );
		$args['filmstrip_controls']       = mai_sanitize_bool( $args['filmstrip_controls'] );
		$args['filmstrip_arrows']         = mai_sanitize_bool( $args['filmstrip_arrows'] );

		if ( ! is_numeric( $args['filmstrip_autoplay_interval'] ) ) {
			$args['filmstrip_autoplay_interval'] = $defaults['filmstrip_autoplay_interval'];
		}
		$args['filmstrip_autoplay_interval'] = max(
			0.1,
			min( 30.0, round( (float) $args['filmstrip_autoplay_interval'], 1 ) )
		);
		$args['filmstrip_autoplay_items'] = is_numeric( $args['filmstrip_autoplay_items'] )
			? max( 1, absint( $args['filmstrip_autoplay_items'] ) )
			: $defaults['filmstrip_autoplay_items'];

		if (
			! in_array(
				$args['filmstrip_arrow_position'],
				[ 'overlay', 'above-start', 'above-center', 'above-end', 'below-start', 'below-center', 'below-end' ],
				true
			)
		) {
			$args['filmstrip_arrow_position'] = $defaults['filmstrip_arrow_position'];
		}

		if ( ! in_array( $args['filmstrip_arrow_style'], $arrow_styles, true ) ) {
			$args['filmstrip_arrow_style'] = $defaults['filmstrip_arrow_style'];
		}

		if ( ! in_array( $args['filmstrip_image_mode'], [ 'natural', 'uniform-cover', 'logo-contain' ], true ) ) {
			$args['filmstrip_image_mode'] = $defaults['filmstrip_image_mode'];
		}

		foreach (
			[
				'filmstrip_height'        => [ 120, 800 ],
				'filmstrip_height_mobile' => [ 100, 600 ],
				'filmstrip_logo_padding'  => [ 0, 120 ],
			] as $key => $range
		) {
			if ( ! is_numeric( $args[ $key ] ) ) {
				$args[ $key ] = $defaults[ $key ];
			}

			$args[ $key ] = max( $range[0], min( $range[1], (int) $args[ $key ] ) );
		}

		foreach (
			[
				'filmstrip_logo_padding_horizontal',
				'filmstrip_logo_padding_vertical',
			] as $key
		) {
			if ( ! is_numeric( $args[ $key ] ) ) {
				$args[ $key ] = $args['filmstrip_logo_padding'];
			}

			$args[ $key ] = max( 0, min( 120, (int) $args[ $key ] ) );
		}

		$args['filmstrip_logo_overrides'] = $this->sanitize_filmstrip_logo_overrides(
			$args['filmstrip_logo_overrides']
		);

		$legacy_navigation = 'arrows-dots' === $args['slider_navigation'];
		if ( $legacy_navigation ) {
			$args['slider_navigation']           = 'arrows';
			$args['slider_navigation_secondary'] = 'dots';
		}

		if ( ! in_array( $args['slider_navigation'], $slider_navigation, true ) ) {
			$args['slider_navigation'] = $legacy_navigation
				? 'arrows'
				: $defaults['slider_navigation'];
		}

		if ( ! in_array( $args['slider_navigation_secondary'], $slider_navigation, true ) ) {
			$args['slider_navigation_secondary'] = $defaults['slider_navigation_secondary'];
		}

		if ( $args['slider_navigation'] === $args['slider_navigation_secondary'] ) {
			$args['slider_navigation_secondary'] = 'none';
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

		if ( ! in_array( $args['slider_transition'], $slider_transitions, true ) ) {
			$args['slider_transition'] = $defaults['slider_transition'];
		}

		if ( ! in_array( $args['slider_transition_speed'], $slider_speeds, true ) ) {
			$args['slider_transition_speed'] = $defaults['slider_transition_speed'];
		}

		$args['slider_autoplay']         = mai_sanitize_bool( $args['slider_autoplay'] );
		$args['slider_autoplay_control'] = mai_sanitize_bool( $args['slider_autoplay_control'] );
		$args['slider_pause_on_hover']   = mai_sanitize_bool( $args['slider_pause_on_hover'] );

		if ( ! is_numeric( $args['slider_autoplay_interval'] ) ) {
			$args['slider_autoplay_interval'] = $defaults['slider_autoplay_interval'];
		}
		$args['slider_autoplay_interval'] = max( 3, min( 30, (int) $args['slider_autoplay_interval'] ) );

		if ( ! in_array( $args['slider_navigation_hover'], $slider_hovers, true ) ) {
			$args['slider_navigation_hover'] = $defaults['slider_navigation_hover'];
		}

		if ( ! in_array( $args['slider_overlay_style'], $slider_overlays, true ) ) {
			$args['slider_overlay_style'] = $defaults['slider_overlay_style'];
		}

		foreach (
			[
				'slider_navigation_main',
				'slider_navigation_border',
				'slider_navigation_inner',
				'slider_navigation_main_hover',
				'slider_navigation_border_hover',
				'slider_navigation_inner_hover',
				'slider_overlay_color',
			] as $key
		) {
			$args[ $key ] = $this->sanitize_color( $args[ $key ], $defaults[ $key ] );
		}

		if ( ! is_numeric( $args['slider_overlay_opacity'] ) ) {
			$args['slider_overlay_opacity'] = $defaults['slider_overlay_opacity'];
		}
		$args['slider_overlay_opacity'] = max( 0, min( 100, (int) $args['slider_overlay_opacity'] ) );

		if ( ! in_array( $args['slider_width_mode'], [ 'full', 'contained', 'inset' ], true ) ) {
			$args['slider_width_mode'] = $defaults['slider_width_mode'];
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

		$args = mai_get_columns_sanitized( $args );

		foreach ( [ 'columns', 'columns_md', 'columns_sm', 'columns_xs' ] as $key ) {
			$args[ $key ] = max( 1, min( 8, absint( $args[ $key ] ) ) );
		}

		if ( 'slider' === $args['layout'] ) {
			$args['click_action']           = 'none';
			$args['captions']               = 'none';
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
	 * Sanitize a Mai palette slug or custom hex color.
	 *
	 * @since 0.5.0
	 *
	 * @param mixed  $color   Raw color value.
	 * @param string $default Trusted fallback value.
	 *
	 * @return string
	 */
	protected function sanitize_color( $color, $default = '' ) {
		$color = is_scalar( $color ) ? trim( (string) $color ) : '';

		if ( '' === $color ) {
			return $default;
		}

		$palette = array_keys( (array) mai_get_colors() );
		if ( in_array( $color, $palette, true ) ) {
			return sanitize_key( $color );
		}

		$hex = sanitize_hex_color( $color );
		return $hex ? $hex : $default;
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
	 * Sanitize Filmstrip per-logo optical scale overrides.
	 *
	 * ACF supplies repeater rows with `image` and `scale` keys. The keyed
	 * `attachment_id => scale` form is also accepted by the PHP API.
	 *
	 * @since 0.7.0
	 *
	 * @param mixed $rows Override rows or an attachment-ID keyed scale map.
	 *
	 * @return array<int,int>
	 */
	protected function sanitize_filmstrip_logo_overrides( $rows ) {
		$overrides = [];

		foreach ( (array) $rows as $key => $row ) {
			$image_id = 0;
			$scale    = 100;

			if ( is_array( $row ) ) {
				$image_id = isset( $row['image'] ) && is_scalar( $row['image'] ) && is_numeric( $row['image'] )
					? absint( $row['image'] )
					: 0;
				$scale = isset( $row['scale'] ) && is_scalar( $row['scale'] ) && is_numeric( $row['scale'] )
					? (int) $row['scale']
					: 100;
			} elseif ( is_numeric( $key ) && is_scalar( $row ) && is_numeric( $row ) ) {
				$image_id = absint( $key );
				$scale    = (int) $row;
			}

			if ( ! $image_id ) {
				continue;
			}

			$overrides[ $image_id ] = max( 50, min( 200, $scale ) );
		}

		return $overrides;
	}

	/**
	 * Sanitize canonical Slider slide rows.
	 *
	 * @since 0.3.0
	 *
	 * @param mixed $rows          Slide repeater rows.
	 * @param list<string> $button_styles Allowed CTA style slugs.
	 *
	 * @return list<CyberGallerySlide>
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
	 * @param list<string> $button_styles Allowed CTA style slugs.
	 *
	 * @return list<CyberGalleryButton>
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
	 * @return list<CyberGalleryLinkedImage>
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
	 * @return list<CyberGalleryItem>
	 */
	protected function get_items() {
		$slide_rows = [];
		$image_rows = [];

		foreach ( $this->args['slides'] as $slide ) {
			$slide_rows[] = [
				'image'                => $slide['image'],
				'link'                 => [],
				'slide_title'          => $slide['title'],
				'slide_subtext'        => $slide['subtext'],
				'slide_align'          => $slide['align'],
				'slide_align_vertical' => $slide['align_vertical'],
				'slide_buttons'        => $slide['buttons'],
			];
		}

		foreach ( $this->args['images'] as $image_id ) {
			$image_rows[] = [
				'image' => $image_id,
				'link'  => [],
			];
		}

		$linked_rows = $this->args['images_links'];

		if ( 'slider' === $this->args['layout'] ) {
			$rows = $slide_rows;
		} elseif ( 'custom' === $this->args['click_action'] ) {
			$rows = $linked_rows ? $linked_rows : ( $image_rows ? $image_rows : $slide_rows );
		} else {
			$rows = $image_rows ? $image_rows : ( $linked_rows ? $linked_rows : $slide_rows );
		}

		$image_ids = array_values(
			array_unique(
				array_filter(
					array_map(
						static function ( $row ): int {
							return is_array( $row ) && isset( $row['image'] )
								? absint( $row['image'] )
								: 0;
						},
						$rows
					)
				)
			)
		);

		if ( $image_ids ) {
			_prime_post_caches( $image_ids, false, true );
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
			$logo_scale = isset( $this->args['filmstrip_logo_overrides'][ $image_id ] )
				? $this->args['filmstrip_logo_overrides'][ $image_id ]
				: 100;
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
				'filmstrip_logo_scale' => $logo_scale,
			];

			/**
			 * Filter a normalized gallery item before rendering.
			 *
			 * @since 0.1.0
			 *
			 * @param CyberGalleryItem $item Item data.
			 * @param CyberGalleryArgs $args Gallery arguments.
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
		 * @param list<CyberGalleryItem> $items Prepared items.
		 * @param CyberGalleryArgs       $args  Gallery arguments.
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
					'filmstrip_logo_scale' => 100,
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
			$item['filmstrip_logo_scale'] = is_numeric( $item['filmstrip_logo_scale'] )
				? max( 50, min( 200, (int) $item['filmstrip_logo_scale'] ) )
				: 100;
			$normalized[] = $item;
		}

		// Items are appended in display order for labels, lightbox grouping, and filmstrip state.
		return $normalized;
	}

	/**
	 * Add source-ordered Editorial Mosaic group metadata to one item.
	 *
	 * Each group contains one feature followed by the configured number of
	 * secondary images. Incomplete final groups use their actual item count so
	 * the CSS can close the shape without reserving blank cells.
	 *
	 * @since 0.9.0
	 *
	 * @param array<string, scalar> $item_atts Item markup attributes.
	 * @param int                   $index     Zero-based item index.
	 * @param int                   $total     Total item count.
	 *
	 * @return array<string, scalar>
	 */
	protected function get_mosaic_item_attributes( $item_atts, $index, $total ) {
		$items_after_feature = $this->args['mosaic_items_after_feature'];
		$group_size          = $items_after_feature + 1;
		$group_index         = intdiv( $index, $group_size );
		$group_position      = $index % $group_size;
		$group_start         = $group_index * $group_size;
		$secondary_count     = min(
			$items_after_feature,
			max( 0, $total - $group_start - 1 )
		);
		$feature_rows        = max( 2, (int) ceil( $secondary_count / 2 ) );

		if ( 0 === $group_position ) {
			$feature_side = $this->args['mosaic_feature_side'];

			if ( $this->args['mosaic_alternate'] && 1 === ( $group_index % 2 ) ) {
				$feature_side = 'left' === $feature_side ? 'right' : 'left';
			}

			$item_atts['class'] = mai_add_classes(
				[
					'is-mosaic-feature',
					'is-mosaic-feature-' . $feature_side,
					0 === $secondary_count ? 'is-mosaic-feature-solo' : '',
				],
				$item_atts['class']
			);
			$item_atts['style'] .= sprintf(
				'--cyber-mosaic-feature-rows:%d;',
				$feature_rows
			);

			return $item_atts;
		}

		$is_odd_remainder = (
			1 === ( $secondary_count % 2 )
			&& $secondary_count === $group_position
		);
		$classes = [ 'is-mosaic-secondary' ];

		if ( 2 >= $secondary_count || $is_odd_remainder ) {
			$classes[] = 'is-mosaic-secondary-span';
		}

		if ( $is_odd_remainder ) {
			$classes[] = 'is-mosaic-odd-remainder';
		}

		if ( 1 === $secondary_count ) {
			$classes[] = 'is-mosaic-secondary-only';
		}

		$item_atts['class'] = mai_add_classes( $classes, $item_atts['class'] );

		return $item_atts;
	}

	/**
	 * Get one gallery item.
	 *
	 * @since 0.1.0
	 *
	 * @param CyberGalleryItem $item       Item data.
	 * @param int              $index      Zero-based item index.
	 * @param int              $total      Total items.
	 * @param string           $gallery_id Gallery ID.
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

		if ( 'mosaic' === $this->args['layout'] ) {
			$item_atts = $this->get_mosaic_item_attributes( $item_atts, $index, $total );
		}

		if (
			'filmstrip' === $this->args['layout']
			&& 'logo-contain' === $this->args['filmstrip_image_mode']
		) {
			$logo_scale = rtrim(
				rtrim(
					number_format( $item['filmstrip_logo_scale'] / 100, 2, '.', '' ),
					'0'
				),
				'.'
			);
			$item_atts['style'] .= sprintf(
				'--cyber-filmstrip-logo-scale:%s;',
				'' === $logo_scale ? '1' : $logo_scale
			);
		}

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
		$media_class = $this->args['shadow']
			? mai_add_classes( 'has-drop-shadow', 'mai-cyber-gallery-media' )
			: 'mai-cyber-gallery-media';
		$media = sprintf( '<div class="%s">', esc_attr( $media_class ) ) . $frame . $slide_content;
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
	 * @param CyberGalleryItem $item       Normalized gallery item.
	 * @param int              $index      Zero-based item index.
	 * @param string           $gallery_id Gallery ID.
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
	 * @param CyberGalleryItem $item       Item data.
	 * @param int              $index      Zero-based item index.
	 * @param int              $total      Total items.
	 * @param string           $gallery_id Gallery ID.
	 *
	 * @return array<string, scalar>
	 */
	protected function get_link_attributes( $item, $index, $total, $gallery_id ) {
		$action = $this->args['click_action'];
		$atts   = [
			'class' => 'mai-cyber-gallery-image-link',
		];

		if ( 'slider' === $this->args['layout'] || 'none' === $action ) {
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
			(
				'grid' === $this->args['layout']
				|| (
					'filmstrip' === $this->args['layout']
					&& 'logo-contain' !== $this->args['filmstrip_image_mode']
				)
			)
			&& mai_has_image_orientiation( $this->args['image_orientation'] )
		) {
			$image_size = sprintf( '%s-sm', $this->args['image_orientation'] );
		}

		/**
		 * Filter the image size used by a Cyber Gallery.
		 *
		 * @since 0.1.0
		 *
		 * @param string           $image_size WordPress image size.
		 * @param CyberGalleryArgs $args       Gallery arguments.
		 */
		$image_size = sanitize_key(
			(string) apply_filters( 'mai_cyber_gallery_image_size', $image_size, $this->args )
		);

		// object-fit cannot restore pixels already removed by a generated hard
		// crop. Logo / Contain therefore falls back to the original source while
		// WordPress still supplies responsive, aspect-compatible srcset entries.
		if (
			'filmstrip' === $this->args['layout']
			&& 'logo-contain' === $this->args['filmstrip_image_mode']
			&& 'full' !== $image_size
		) {
			$sizes = wp_get_registered_image_subsizes();
			if ( ! empty( $sizes[ $image_size ]['crop'] ) ) {
				$image_size = 'full';
			}
		}

		return $image_size;
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

		if ( in_array( $this->args['layout'], [ 'masonry', 'justified', 'tiled' ], true ) ) {
			wp_enqueue_script( 'mai-cyber-gallery-packing' );
		}

		if ( in_array( $this->args['layout'], [ 'filmstrip', 'slider' ], true ) ) {
			wp_enqueue_script( 'mai-cyber-gallery' );
		}

		if ( 'lightbox' === $this->args['click_action'] ) {
			wp_enqueue_script( 'mai-cyber-gallery-lightbox' );
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
			? esc_html__( 'Add slides to begin building this gallery.', 'mai-cyber-gallery' )
			: esc_html__( 'Add images to begin building this gallery.', 'mai-cyber-gallery' );

		return sprintf(
			'<div class="mai-cyber-gallery-placeholder"><span class="dashicons dashicons-format-gallery" aria-hidden="true"></span><span>%1$s</span><button type="button" class="button button-primary" data-cyber-manage-gallery>%2$s</button><span class="screen-reader-text" role="status" data-cyber-manage-gallery-status hidden>%3$s</span></div>',
			$message,
			esc_html__( 'Manage Gallery', 'mai-cyber-gallery' ),
			esc_html__( 'Use Open Expanded Editor in the block settings.', 'mai-cyber-gallery' )
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
		if ( ! $this->args['filmstrip_controls'] ) {
			return '';
		}

		$has_autoplay = $this->args['filmstrip_autoplay'] && ! $this->args['preview'];
		$has_arrows   = $this->args['filmstrip_arrows'];
		$total        = $this->render_item_count;

		if ( ! $has_autoplay && ! $has_arrows ) {
			return '';
		}

		$icon_atts = [
			'aria-hidden' => 'true',
			'focusable'   => 'false',
		];
		$track_id   = $gallery_id . '-items';
		$controls   = '';

		if ( $has_autoplay ) {
			$pause_icon = mai_get_svg_icon( 'pause', 'solid', $icon_atts );
			$play_icon  = mai_get_svg_icon( 'play', 'solid', $icon_atts );
			$pause_icon = $pause_icon
				? $pause_icon
				: '<svg aria-hidden="true" viewBox="0 0 24 24" focusable="false"><path d="M6 4h4v16H6zm8 0h4v16h-4z"></path></svg>';
			$play_icon = $play_icon
				? $play_icon
				: '<svg aria-hidden="true" viewBox="0 0 24 24" focusable="false"><path d="m7 4 13 8L7 20z"></path></svg>';

			$controls .= sprintf(
				'<button class="button mai-cyber-gallery-control mai-cyber-gallery-filmstrip-autoplay" type="button" data-cyber-filmstrip-autoplay-toggle data-cyber-filmstrip-paused="false" aria-controls="%1$s" aria-label="%2$s">'
				. '<span class="mai-cyber-gallery-autoplay-pause" aria-hidden="true">%3$s</span>'
				. '<span class="mai-cyber-gallery-autoplay-play" aria-hidden="true">%4$s</span>'
				. '</button>',
				esc_attr( $track_id ),
				esc_attr__( 'Pause automatic gallery movement', 'mai-cyber-gallery' ),
				$pause_icon,
				$play_icon
			);
		}

		if ( $has_arrows ) {
			$previous_icon = mai_get_svg_icon( 'angle-left', 'solid', $icon_atts );
			$next_icon     = mai_get_svg_icon( 'angle-right', 'solid', $icon_atts );
			$previous_icon = $previous_icon ? $previous_icon : '&larr;';
			$next_icon     = $next_icon ? $next_icon : '&rarr;';

			$controls .= sprintf(
				'<button class="button mai-cyber-gallery-control mai-cyber-gallery-control-previous" type="button" data-cyber-filmstrip-previous aria-controls="%1$s" aria-label="%2$s" disabled><span aria-hidden="true">%3$s</span></button>'
				. '<button class="button mai-cyber-gallery-control mai-cyber-gallery-control-next" type="button" data-cyber-filmstrip-next aria-controls="%1$s" aria-label="%4$s"><span aria-hidden="true">%5$s</span></button>',
				esc_attr( $track_id ),
				esc_attr__( 'Previous images', 'mai-cyber-gallery' ),
				$previous_icon,
				esc_attr__( 'Next images', 'mai-cyber-gallery' ),
				$next_icon
			);
		}

		$status = sprintf(
			/* translators: 1: Current image number. 2: Total images. */
			esc_html__( 'Image %1$d of %2$d', 'mai-cyber-gallery' ),
			1,
			$total
		);
		$controls .= sprintf(
			'<p class="mai-cyber-gallery-filmstrip-status screen-reader-text" data-cyber-filmstrip-status aria-live="%1$s" aria-atomic="true">%2$s</p>',
			$has_autoplay ? 'off' : 'polite',
			esc_html( $status )
		);

		return sprintf(
			'<div class="mai-cyber-gallery-controls" role="group" aria-label="%1$s">%2$s</div>',
			esc_attr__( 'Gallery navigation', 'mai-cyber-gallery' ),
			$controls
		);
	}

	/**
	 * Get slider navigation controls.
	 *
	 * @since 0.2.0
	 *
	 * @param string                   $gallery_id Gallery ID.
	 * @param list<CyberGalleryItem>   $items      Normalized slide items.
	 *
	 * @return string
	 */
	protected function get_slider_controls( $gallery_id, $items ) {
		$navigation = array_unique(
			[
				$this->args['slider_navigation'],
				$this->args['slider_navigation_secondary'],
			]
		);
		$has_arrows           = in_array( 'arrows', $navigation, true );
		$has_dots             = in_array( 'dots', $navigation, true );
		$has_counter          = in_array( 'counter', $navigation, true );
		$has_autoplay         = $this->args['slider_autoplay'] && ! $this->args['preview'];
		$has_autoplay_control = $has_autoplay && $this->args['slider_autoplay_control'];
		$track_id             = $gallery_id . '-items';
		$total                = count( $items );
		$controls             = '';

		if ( $has_autoplay_control ) {
			$icon_atts = [
				'aria-hidden' => 'true',
				'focusable'   => 'false',
			];
			$pause_icon = mai_get_svg_icon( 'pause', 'solid', $icon_atts );
			$play_icon  = mai_get_svg_icon( 'play', 'solid', $icon_atts );
			$pause_icon = $pause_icon
				? $pause_icon
				: '<svg aria-hidden="true" viewBox="0 0 24 24" focusable="false"><path d="M6 4h4v16H6zm8 0h4v16h-4z"></path></svg>';
			$play_icon = $play_icon
				? $play_icon
				: '<svg aria-hidden="true" viewBox="0 0 24 24" focusable="false"><path d="m7 4 13 8L7 20z"></path></svg>';

			$controls .= sprintf(
				'<button class="button mai-cyber-gallery-slider-autoplay" type="button" data-cyber-slider-autoplay-toggle data-cyber-slider-paused="false" aria-controls="%1$s" aria-label="%2$s">'
				. '<span class="mai-cyber-gallery-slider-autoplay-pause" aria-hidden="true">%3$s</span>'
				. '<span class="mai-cyber-gallery-slider-autoplay-play" aria-hidden="true">%4$s</span>'
				. '</button>',
				esc_attr( $track_id ),
				esc_attr__( 'Pause automatic slides', 'mai-cyber-gallery' ),
				$pause_icon,
				$play_icon
			);
		}

		if ( $has_arrows ) {
			$icon_atts = [
				'aria-hidden' => 'true',
				'focusable'   => 'false',
			];
			$previous_icon = mai_get_svg_icon( 'angle-left', 'solid', $icon_atts );
			$next_icon     = mai_get_svg_icon( 'angle-right', 'solid', $icon_atts );
			$previous_icon = $previous_icon
				? $previous_icon
				: '<svg aria-hidden="true" viewBox="0 0 24 24" focusable="false"><path d="M15.5 4 7.5 12l8 8 1.5-1.5-6.5-6.5L17 5.5z"></path></svg>';
			$next_icon = $next_icon
				? $next_icon
				: '<svg aria-hidden="true" viewBox="0 0 24 24" focusable="false"><path d="m8.5 20 8-8-8-8L7 5.5l6.5 6.5L7 18.5z"></path></svg>';

			$controls .= sprintf(
				'<button class="button mai-cyber-gallery-slider-arrow mai-cyber-gallery-slider-previous" type="button" data-cyber-slider-previous aria-controls="%1$s" aria-label="%2$s" disabled>%3$s</button>'
				. '<button class="button mai-cyber-gallery-slider-arrow mai-cyber-gallery-slider-next" type="button" data-cyber-slider-next aria-controls="%1$s" aria-label="%4$s">%5$s</button>',
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
					'<button class="button mai-cyber-gallery-slider-dot" type="button" data-cyber-slider-dot="%1$d" aria-controls="%2$s" aria-label="%3$s"%4$s><span aria-hidden="true"></span></button>',
					$index,
					esc_attr( $track_id ),
					esc_attr( $label ),
					$current
				);
			}

			$controls .= '</div>';
		}

		if ( $has_counter ) {
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
			'<p class="mai-cyber-gallery-slider-status screen-reader-text" data-cyber-slider-status aria-live="%1$s" aria-atomic="true">%2$s</p>',
			$has_autoplay ? 'off' : 'polite',
			esc_html( $status )
		);

		if ( ! $has_autoplay_control && ! $has_arrows && ! $has_dots && ! $has_counter ) {
			return $status_markup;
		}

		return sprintf(
			'<div class="mai-cyber-gallery-slider-controls" role="group" aria-label="%1$s">%2$s%3$s</div>',
			esc_attr__( 'Slider controls', 'mai-cyber-gallery' ),
			$controls,
			$status_markup
		);
	}
}

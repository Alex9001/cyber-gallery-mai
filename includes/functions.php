<?php
/**
 * Public Mai Cyber Gallery functions.
 *
 * @package MaiCyberGallery
 */

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

/**
 * Get gallery layout choices.
 *
 * @since 0.1.0
 *
 * @return array<string,string>
 */
function mai_cyber_gallery_get_layout_choices() {
	$layouts = [
		'grid'      => esc_html__( 'Mai Grid', 'mai-cyber-gallery' ),
		'masonry'   => esc_html__( 'Masonry Columns', 'mai-cyber-gallery' ),
		'justified' => esc_html__( 'Justified Rows', 'mai-cyber-gallery' ),
		'tiled'     => esc_html__( 'Tiled Mosaic', 'mai-cyber-gallery' ),
		'mosaic'    => esc_html__( 'Editorial Mosaic', 'mai-cyber-gallery' ),
		'square'    => esc_html__( 'Square Tiles', 'mai-cyber-gallery' ),
		'circle'    => esc_html__( 'Circle Tiles', 'mai-cyber-gallery' ),
		'filmstrip' => esc_html__( 'Filmstrip', 'mai-cyber-gallery' ),
		'slider'    => esc_html__( 'Slider', 'mai-cyber-gallery' ),
	];

	/**
	 * Filter the registered gallery layouts.
	 *
	 * Custom layouts must also provide their own CSS and any required behavior.
	 *
	 * @since 0.1.0
	 *
	 * @param array<string,string> $layouts Layout slug and label pairs.
	 */
	return (array) apply_filters( 'mai_cyber_gallery_layouts', $layouts );
}

/**
 * Get arrow design choices shared by the lightbox and slider.
 *
 * @since 0.2.0
 *
 * @return array<string,string>
 */
function mai_cyber_gallery_get_arrow_style_choices() {
	$styles = [
		'circle'  => esc_html__( 'Frosted Circle', 'mai-cyber-gallery' ),
		'square'  => esc_html__( 'Rounded Square', 'mai-cyber-gallery' ),
		'minimal' => esc_html__( 'Minimal Chevron', 'mai-cyber-gallery' ),
		'edge'    => esc_html__( 'Edge Tab', 'mai-cyber-gallery' ),
	];

	/**
	 * Filter the registered arrow design choices.
	 *
	 * @since 0.2.0
	 *
	 * @param array<string,string> $styles Arrow style slug and label pairs.
	 */
	return (array) apply_filters( 'mai_cyber_gallery_arrow_styles', $styles );
}

/**
 * Get slider navigation choices.
 *
 * @since 0.2.0
 *
 * @return array<string,string>
 */
function mai_cyber_gallery_get_slider_navigation_choices() {
	$choices = [
		'arrows-dots' => esc_html__( 'Arrows + Circles', 'mai-cyber-gallery' ),
		'arrows'      => esc_html__( 'Arrows', 'mai-cyber-gallery' ),
		'dots'        => esc_html__( 'Circles', 'mai-cyber-gallery' ),
		'counter'     => esc_html__( 'Slide Counter', 'mai-cyber-gallery' ),
		'none'        => esc_html__( 'None', 'mai-cyber-gallery' ),
	];

	/**
	 * Filter the slider navigation choices.
	 *
	 * @since 0.2.0
	 *
	 * @param array<string,string> $choices Navigation slug and label pairs.
	 */
	return (array) apply_filters( 'mai_cyber_gallery_slider_navigation_choices', $choices );
}

/**
 * Get CTA button style choices shared by Slider slide rows and the PHP API.
 *
 * Values mirror Mai Engine's established button-style vocabulary.
 *
 * @since 0.3.0
 *
 * @return array<string,string>
 */
function mai_cyber_gallery_get_button_style_choices() {
	$core_choices = [
		'button'           => esc_html__( 'Primary Button', 'mai-cyber-gallery' ),
		'button_secondary' => esc_html__( 'Secondary Button', 'mai-cyber-gallery' ),
		'button_outline'   => esc_html__( 'Outline Button', 'mai-cyber-gallery' ),
		'button_link'      => esc_html__( 'Link Button', 'mai-cyber-gallery' ),
	];

	/**
	 * Filter Slider CTA button style choices.
	 *
	 * Custom choices should also add their class mapping through the
	 * `mai_cyber_gallery_button_style_classes` filter.
	 *
	 * @since 0.3.0
	 *
	 * @param array<string,string> $choices Style slug and label pairs.
	 */
	$choices = (array) apply_filters( 'mai_cyber_gallery_button_style_choices', $core_choices );
	$choices = array_filter( $choices, 'is_scalar' );

	$sanitized = [];
	foreach ( $choices as $style => $label ) {
		$style = sanitize_key( (string) $style );

		if ( $style ) {
			$sanitized[ $style ] = sanitize_text_field( (string) $label );
		}
	}

	return $sanitized ? $sanitized : [ 'button' => $core_choices['button'] ];
}

/**
 * Get the default Slider CTA style from the filtered choices.
 *
 * The primary Mai button remains preferred when available. If a filter
 * replaces the core choices, its first valid style becomes the default.
 *
 * @since 0.3.0
 *
 * @return string
 */
function mai_cyber_gallery_get_default_button_style() {
	$choices = mai_cyber_gallery_get_button_style_choices();

	return isset( $choices['button'] ) ? 'button' : array_key_first( $choices );
}

/**
 * Get sanitized Mai button classes for a Slider CTA style.
 *
 * @since 0.3.0
 *
 * @param string $style Button style slug.
 *
 * @return string
 */
function mai_cyber_gallery_get_button_style_classes( $style ) {
	$styles = [
		'button'           => [ 'button' ],
		'button_secondary' => [ 'button', 'button-secondary' ],
		'button_outline'   => [ 'button', 'button-outline' ],
		'button_link'      => [ 'button', 'button-link' ],
	];

	/**
	 * Filter Slider CTA style-to-class mappings.
	 *
	 * @since 0.3.0
	 *
	 * @param array<string,array<int,string>> $styles Style-to-class mappings.
	 */
	$styles  = (array) apply_filters( 'mai_cyber_gallery_button_style_classes', $styles );
	$style   = sanitize_key( (string) $style );
	$classes = isset( $styles[ $style ] ) ? (array) $styles[ $style ] : [ 'button' ];
	$classes = array_filter( $classes, 'is_scalar' );
	$classes = array_map(
		static function ( $class ) {
			return sanitize_html_class( (string) $class );
		},
		$classes
	);
	$classes = array_values( array_unique( array_filter( $classes ) ) );

	return $classes ? implode( ' ', $classes ) : 'button';
}

/**
 * Get gallery markup.
 *
 * @since 0.1.0
 *
 * @param array $args Gallery arguments.
 *
 * @return string
 */
function mai_get_cyber_gallery( $args = [] ) {
	$gallery = new Mai_Cyber_Gallery( $args );
	return $gallery->get();
}

/**
 * Display a gallery.
 *
 * @since 0.1.0
 *
 * @param array $args Gallery arguments.
 *
 * @return void
 */
function mai_do_cyber_gallery( $args = [] ) {
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Rendered gallery markup is escaped at its sources.
	echo mai_get_cyber_gallery( $args );
}

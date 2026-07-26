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

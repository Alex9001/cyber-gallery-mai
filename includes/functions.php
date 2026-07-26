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
		'mosaic'    => esc_html__( 'Editorial Mosaic', 'mai-cyber-gallery' ),
		'square'    => esc_html__( 'Square Tiles', 'mai-cyber-gallery' ),
		'circle'    => esc_html__( 'Circle Tiles', 'mai-cyber-gallery' ),
		'filmstrip' => esc_html__( 'Filmstrip', 'mai-cyber-gallery' ),
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

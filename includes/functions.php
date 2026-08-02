<?php
/**
 * Public Cyber Gallery for Mai Theme functions.
 *
 * @package MaiCyberGallery
 */

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

/**
 * Normalize a filtered slug-to-label choice list.
 *
 * Invalid filtered values are discarded. If a filter removes every usable
 * choice, the normalized fallback list is returned so fields and runtime
 * validation always retain at least their core choices.
 *
 * @since 1.0.0
 *
 * @param mixed                $choices  Filtered choices.
 * @param array<string,string> $fallback Trusted fallback choices.
 *
 * @return array<string,string>
 */
function mai_cyber_gallery_normalize_choices( $choices, array $fallback ): array {
	$normalize = static function ( $values ): array {
		if ( ! is_array( $values ) ) {
			return array();
		}

		$normalized = array();

		foreach ( $values as $slug => $label ) {
			if ( ! is_scalar( $label ) ) {
				continue;
			}

			$slug  = sanitize_key( (string) $slug );
			$label = sanitize_text_field( (string) $label );

			if ( $slug && $label ) {
				$normalized[ $slug ] = $label;
			}
		}

		return $normalized;
	};

	$choices = $normalize( $choices );

	return $choices ? $choices : $normalize( $fallback );
}

/**
 * Get a valid default from a normalized choice list.
 *
 * The preferred slug wins while it remains registered. Otherwise, the first
 * registered choice becomes the deterministic default.
 *
 * @since 1.0.0
 *
 * @param array<string,string> $choices   Normalized choices.
 * @param string               $preferred Preferred choice slug.
 * @param string               $exclude   Choice to avoid when an alternative exists.
 *
 * @return string
 */
function mai_cyber_gallery_get_choice_default( array $choices, $preferred, $exclude = '' ): string {
	$preferred = sanitize_key( (string) $preferred );
	$exclude   = sanitize_key( (string) $exclude );

	if (
		$preferred
		&& $preferred !== $exclude
		&& array_key_exists( $preferred, $choices )
	) {
		return $preferred;
	}

	foreach ( $choices as $slug => $label ) {
		$slug = (string) $slug;

		if ( $slug !== $exclude ) {
			return $slug;
		}
	}

	return $exclude && array_key_exists( $exclude, $choices ) ? $exclude : '';
}

/**
 * Get gallery layout choices.
 *
 * @since 0.1.0
 *
 * @return array<string,string>
 */
function mai_cyber_gallery_get_layout_choices(): array {
	$core_layouts = array(
		'grid'       => esc_html__( 'Mai Grid', 'mai-cyber-gallery' ),
		'masonry'    => esc_html__( 'Masonry Columns', 'mai-cyber-gallery' ),
		'justified'  => esc_html__( 'Justified Rows', 'mai-cyber-gallery' ),
		'tiled'      => esc_html__( 'Tiled Mosaic', 'mai-cyber-gallery' ),
		'mosaic'     => esc_html__( 'Editorial Mosaic', 'mai-cyber-gallery' ),
		'square'     => esc_html__( 'Square Tiles', 'mai-cyber-gallery' ),
		'circle'     => esc_html__( 'Circle Tiles', 'mai-cyber-gallery' ),
		'comparison' => esc_html__( 'Before / After', 'mai-cyber-gallery' ),
		'filmstrip'  => esc_html__( 'Filmstrip', 'mai-cyber-gallery' ),
		'slider'     => esc_html__( 'Slider', 'mai-cyber-gallery' ),
	);

	/**
	 * Filter the registered gallery layouts.
	 *
	 * Custom layouts must also provide their own CSS and any required behavior.
	 *
	 * @since 0.1.0
	 *
	 * @param array<string,string> $layouts Layout slug and label pairs.
	 */
	$layouts = apply_filters( 'mai_cyber_gallery_layouts', $core_layouts );

	return mai_cyber_gallery_normalize_choices( $layouts, $core_layouts );
}

/**
 * Get arrow design choices shared by the lightbox and slider.
 *
 * @since 0.2.0
 *
 * @return array<string,string>
 */
function mai_cyber_gallery_get_arrow_style_choices(): array {
	$core_styles = array(
		'circle'  => esc_html__( 'Frosted Circle', 'mai-cyber-gallery' ),
		'square'  => esc_html__( 'Rounded Square', 'mai-cyber-gallery' ),
		'minimal' => esc_html__( 'Minimal Chevron', 'mai-cyber-gallery' ),
		'edge'    => esc_html__( 'Edge Tab', 'mai-cyber-gallery' ),
	);

	/**
	 * Filter the registered arrow design choices.
	 *
	 * @since 0.2.0
	 *
	 * @param array<string,string> $styles Arrow style slug and label pairs.
	 */
	$styles = apply_filters( 'mai_cyber_gallery_arrow_styles', $core_styles );

	return mai_cyber_gallery_normalize_choices( $styles, $core_styles );
}

/**
 * Get slider navigation choices.
 *
 * @since 0.2.0
 *
 * @return array<string,string>
 */
function mai_cyber_gallery_get_slider_navigation_choices(): array {
	$core_choices = array(
		'arrows'  => esc_html__( 'Arrows', 'mai-cyber-gallery' ),
		'dots'    => esc_html__( 'Circles', 'mai-cyber-gallery' ),
		'counter' => esc_html__( 'Slide Counter', 'mai-cyber-gallery' ),
		'none'    => esc_html__( 'None', 'mai-cyber-gallery' ),
	);

	/**
	 * Filter the slider navigation choices.
	 *
	 * The renderer implements the core atomic control slugs. Filters may
	 * remove, reorder, or relabel those controls; unsupported slugs are
	 * discarded.
	 *
	 * @since 0.2.0
	 *
	 * @param array<string,string> $choices Core navigation slug and label pairs.
	 */
	$choices = apply_filters( 'mai_cyber_gallery_slider_navigation_choices', $core_choices );
	$choices = mai_cyber_gallery_normalize_choices( $choices, $core_choices );
	$choices = array_intersect_key( $choices, $core_choices );

	return $choices ? $choices : $core_choices;
}

/**
 * Get Slider transition choices.
 *
 * @since 0.5.0
 *
 * @return array<string,string>
 */
function mai_cyber_gallery_get_slider_transition_choices(): array {
	$core_choices = array(
		'native'     => esc_html__( 'Smooth Slide', 'mai-cyber-gallery' ),
		'fade'       => esc_html__( 'Fade', 'mai-cyber-gallery' ),
		'zoom-in'    => esc_html__( 'Zoom In', 'mai-cyber-gallery' ),
		'zoom-out'   => esc_html__( 'Zoom Out', 'mai-cyber-gallery' ),
		'rise'       => esc_html__( 'Rise', 'mai-cyber-gallery' ),
		'soft-focus' => esc_html__( 'Soft Focus', 'mai-cyber-gallery' ),
		'reveal'     => esc_html__( 'Reveal', 'mai-cyber-gallery' ),
		'cinematic'  => esc_html__( 'Cinematic', 'mai-cyber-gallery' ),
	);

	/**
	 * Filter Slider transition choices.
	 *
	 * @since 0.5.0
	 *
	 * @param array<string,string> $choices Transition slug and label pairs.
	 */
	$choices = apply_filters( 'mai_cyber_gallery_slider_transition_choices', $core_choices );

	return mai_cyber_gallery_normalize_choices( $choices, $core_choices );
}

/**
 * Get Slider transition speed choices.
 *
 * @since 0.5.0
 *
 * @return array<string,string>
 */
function mai_cyber_gallery_get_slider_transition_speed_choices(): array {
	$core_choices = array(
		'fast'   => esc_html__( 'Fast', 'mai-cyber-gallery' ),
		'normal' => esc_html__( 'Normal', 'mai-cyber-gallery' ),
		'slow'   => esc_html__( 'Slow', 'mai-cyber-gallery' ),
	);

	/**
	 * Filter Slider transition speed choices.
	 *
	 * @since 0.5.0
	 *
	 * @param array<string,string> $choices Speed slug and label pairs.
	 */
	$choices = apply_filters( 'mai_cyber_gallery_slider_transition_speed_choices', $core_choices );

	return mai_cyber_gallery_normalize_choices( $choices, $core_choices );
}

/**
 * Get Slider navigation hover choices.
 *
 * @since 0.5.0
 *
 * @return array<string,string>
 */
function mai_cyber_gallery_get_slider_navigation_hover_choices(): array {
	$core_choices = array(
		'color' => esc_html__( 'Color Shift', 'mai-cyber-gallery' ),
		'grow'  => esc_html__( 'Grow', 'mai-cyber-gallery' ),
		'nudge' => esc_html__( 'Nudge', 'mai-cyber-gallery' ),
		'glow'  => esc_html__( 'Glow', 'mai-cyber-gallery' ),
		'fill'  => esc_html__( 'Fill', 'mai-cyber-gallery' ),
		'ring'  => esc_html__( 'Ring', 'mai-cyber-gallery' ),
	);

	/**
	 * Filter Slider navigation hover choices.
	 *
	 * @since 0.5.0
	 *
	 * @param array<string,string> $choices Hover effect slug and label pairs.
	 */
	$choices = apply_filters( 'mai_cyber_gallery_slider_navigation_hover_choices', $core_choices );

	return mai_cyber_gallery_normalize_choices( $choices, $core_choices );
}

/**
 * Get Slider image overlay choices.
 *
 * @since 0.5.0
 *
 * @return array<string,string>
 */
function mai_cyber_gallery_get_slider_overlay_style_choices(): array {
	$core_choices = array(
		'none'            => esc_html__( 'None', 'mai-cyber-gallery' ),
		'solid'           => esc_html__( 'Solid', 'mai-cyber-gallery' ),
		'gradient-bottom' => esc_html__( 'Gradient from Bottom', 'mai-cyber-gallery' ),
		'gradient-top'    => esc_html__( 'Gradient from Top', 'mai-cyber-gallery' ),
		'gradient-left'   => esc_html__( 'Gradient from Left', 'mai-cyber-gallery' ),
		'gradient-right'  => esc_html__( 'Gradient from Right', 'mai-cyber-gallery' ),
		'vignette'        => esc_html__( 'Vignette', 'mai-cyber-gallery' ),
	);

	/**
	 * Filter Slider image overlay choices.
	 *
	 * @since 0.5.0
	 *
	 * @param array<string,string> $choices Overlay style slug and label pairs.
	 */
	$choices = apply_filters( 'mai_cyber_gallery_slider_overlay_style_choices', $core_choices );

	return mai_cyber_gallery_normalize_choices( $choices, $core_choices );
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
function mai_cyber_gallery_get_button_style_choices(): array {
	$core_choices = array(
		'button'           => esc_html__( 'Primary Button', 'mai-cyber-gallery' ),
		'button_secondary' => esc_html__( 'Secondary Button', 'mai-cyber-gallery' ),
		'button_outline'   => esc_html__( 'Outline Button', 'mai-cyber-gallery' ),
		'button_link'      => esc_html__( 'Link Button', 'mai-cyber-gallery' ),
	);

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
	$choices = apply_filters( 'mai_cyber_gallery_button_style_choices', $core_choices );

	return mai_cyber_gallery_normalize_choices( $choices, $core_choices );
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

	return mai_cyber_gallery_get_choice_default( $choices, 'button' );
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
function mai_cyber_gallery_get_button_style_classes( $style ): string {
	$styles = array(
		'button'           => array( 'button' ),
		'button_secondary' => array( 'button', 'button-secondary' ),
		'button_outline'   => array( 'button', 'button-outline' ),
		'button_link'      => array( 'button', 'button-link' ),
	);

	/**
	 * Filter Slider CTA style-to-class mappings.
	 *
	 * @since 0.3.0
	 *
	 * @param array<string,array<int,string>> $styles Style-to-class mappings.
	 */
	$styles  = (array) apply_filters( 'mai_cyber_gallery_button_style_classes', $styles );
	$style   = sanitize_key( (string) $style );
	$classes = isset( $styles[ $style ] ) ? (array) $styles[ $style ] : array( 'button' );
	$classes = array_filter( $classes, 'is_scalar' );
	$classes = array_map(
		static function ( $css_class ) {
			return sanitize_html_class( (string) $css_class );
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
 * @param array<string, mixed> $args Gallery arguments.
 *
 * @return string
 */
function mai_get_cyber_gallery( $args = array() ): string {
	$gallery = new Mai_Cyber_Gallery( $args );
	return $gallery->get();
}

/**
 * Display a gallery.
 *
 * @since 0.1.0
 *
 * @param array<string, mixed> $args Gallery arguments.
 *
 * @return void
 */
function mai_do_cyber_gallery( $args = array() ): void {
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Rendered gallery markup is escaped at its sources.
	echo mai_get_cyber_gallery( $args );
}

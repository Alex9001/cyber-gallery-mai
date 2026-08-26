<?php
/**
 * Public Cyber Gallery for Mai Theme functions.
 *
 * @package CyberGalleryMai
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
function cyber_gallery_mai_normalize_choices( $choices, array $fallback ): array {
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
function cyber_gallery_mai_get_choice_default( array $choices, $preferred, $exclude = '' ): string {
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
function cyber_gallery_mai_get_layout_choices(): array {
	$core_layouts = array(
		'grid'       => esc_html__( 'Mai Grid', 'cyber-gallery-mai' ),
		'masonry'    => esc_html__( 'Masonry Columns', 'cyber-gallery-mai' ),
		'justified'  => esc_html__( 'Justified Rows', 'cyber-gallery-mai' ),
		'tiled'      => esc_html__( 'Tiled Mosaic', 'cyber-gallery-mai' ),
		'mosaic'     => esc_html__( 'Editorial Mosaic', 'cyber-gallery-mai' ),
		'square'     => esc_html__( 'Square Tiles', 'cyber-gallery-mai' ),
		'circle'     => esc_html__( 'Circle Tiles', 'cyber-gallery-mai' ),
		'comparison' => esc_html__( 'Before / After', 'cyber-gallery-mai' ),
		'filmstrip'  => esc_html__( 'Filmstrip', 'cyber-gallery-mai' ),
		'slider'     => esc_html__( 'Slider', 'cyber-gallery-mai' ),
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
	$layouts = apply_filters( 'cyber_gallery_mai_layouts', $core_layouts );
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Preserve the documented 1.x filter alias.
	$layouts = apply_filters( 'mai_cyber_gallery_layouts', $layouts );

	return cyber_gallery_mai_normalize_choices( $layouts, $core_layouts );
}

/**
 * Get arrow design choices shared by the lightbox and slider.
 *
 * @since 0.2.0
 *
 * @return array<string,string>
 */
function cyber_gallery_mai_get_arrow_style_choices(): array {
	$core_styles = array(
		'circle'  => esc_html__( 'Frosted Circle', 'cyber-gallery-mai' ),
		'square'  => esc_html__( 'Rounded Square', 'cyber-gallery-mai' ),
		'minimal' => esc_html__( 'Minimal Chevron', 'cyber-gallery-mai' ),
		'edge'    => esc_html__( 'Edge Tab', 'cyber-gallery-mai' ),
	);

	/**
	 * Filter the registered arrow design choices.
	 *
	 * @since 0.2.0
	 *
	 * @param array<string,string> $styles Arrow style slug and label pairs.
	 */
	$styles = apply_filters( 'cyber_gallery_mai_arrow_styles', $core_styles );
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Preserve the documented 1.x filter alias.
	$styles = apply_filters( 'mai_cyber_gallery_arrow_styles', $styles );

	return cyber_gallery_mai_normalize_choices( $styles, $core_styles );
}

/**
 * Get slider navigation choices.
 *
 * @since 0.2.0
 *
 * @return array<string,string>
 */
function cyber_gallery_mai_get_slider_navigation_choices(): array {
	$core_choices = array(
		'arrows'  => esc_html__( 'Arrows', 'cyber-gallery-mai' ),
		'dots'    => esc_html__( 'Circles', 'cyber-gallery-mai' ),
		'counter' => esc_html__( 'Slide Counter', 'cyber-gallery-mai' ),
		'none'    => esc_html__( 'None', 'cyber-gallery-mai' ),
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
	$choices = apply_filters( 'cyber_gallery_mai_slider_navigation_choices', $core_choices );
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Preserve the documented 1.x filter alias.
	$choices = apply_filters( 'mai_cyber_gallery_slider_navigation_choices', $choices );
	$choices = cyber_gallery_mai_normalize_choices( $choices, $core_choices );
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
function cyber_gallery_mai_get_slider_transition_choices(): array {
	$core_choices = array(
		'native'     => esc_html__( 'Smooth Slide', 'cyber-gallery-mai' ),
		'fade'       => esc_html__( 'Fade', 'cyber-gallery-mai' ),
		'zoom-in'    => esc_html__( 'Zoom In', 'cyber-gallery-mai' ),
		'zoom-out'   => esc_html__( 'Zoom Out', 'cyber-gallery-mai' ),
		'rise'       => esc_html__( 'Rise', 'cyber-gallery-mai' ),
		'soft-focus' => esc_html__( 'Soft Focus', 'cyber-gallery-mai' ),
		'reveal'     => esc_html__( 'Reveal', 'cyber-gallery-mai' ),
		'cinematic'  => esc_html__( 'Cinematic', 'cyber-gallery-mai' ),
	);

	/**
	 * Filter Slider transition choices.
	 *
	 * @since 0.5.0
	 *
	 * @param array<string,string> $choices Transition slug and label pairs.
	 */
	$choices = apply_filters( 'cyber_gallery_mai_slider_transition_choices', $core_choices );
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Preserve the documented 1.x filter alias.
	$choices = apply_filters( 'mai_cyber_gallery_slider_transition_choices', $choices );

	return cyber_gallery_mai_normalize_choices( $choices, $core_choices );
}

/**
 * Get Slider transition speed choices.
 *
 * @since 0.5.0
 *
 * @return array<string,string>
 */
function cyber_gallery_mai_get_slider_transition_speed_choices(): array {
	$core_choices = array(
		'fast'   => esc_html__( 'Fast', 'cyber-gallery-mai' ),
		'normal' => esc_html__( 'Normal', 'cyber-gallery-mai' ),
		'slow'   => esc_html__( 'Slow', 'cyber-gallery-mai' ),
	);

	/**
	 * Filter Slider transition speed choices.
	 *
	 * @since 0.5.0
	 *
	 * @param array<string,string> $choices Speed slug and label pairs.
	 */
	$choices = apply_filters( 'cyber_gallery_mai_slider_transition_speed_choices', $core_choices );
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Preserve the documented 1.x filter alias.
	$choices = apply_filters( 'mai_cyber_gallery_slider_transition_speed_choices', $choices );

	return cyber_gallery_mai_normalize_choices( $choices, $core_choices );
}

/**
 * Get Slider navigation hover choices.
 *
 * @since 0.5.0
 *
 * @return array<string,string>
 */
function cyber_gallery_mai_get_slider_navigation_hover_choices(): array {
	$core_choices = array(
		'color' => esc_html__( 'Color Shift', 'cyber-gallery-mai' ),
		'grow'  => esc_html__( 'Grow', 'cyber-gallery-mai' ),
		'nudge' => esc_html__( 'Nudge', 'cyber-gallery-mai' ),
		'glow'  => esc_html__( 'Glow', 'cyber-gallery-mai' ),
		'fill'  => esc_html__( 'Fill', 'cyber-gallery-mai' ),
		'ring'  => esc_html__( 'Ring', 'cyber-gallery-mai' ),
	);

	/**
	 * Filter Slider navigation hover choices.
	 *
	 * @since 0.5.0
	 *
	 * @param array<string,string> $choices Hover effect slug and label pairs.
	 */
	$choices = apply_filters( 'cyber_gallery_mai_slider_navigation_hover_choices', $core_choices );
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Preserve the documented 1.x filter alias.
	$choices = apply_filters( 'mai_cyber_gallery_slider_navigation_hover_choices', $choices );

	return cyber_gallery_mai_normalize_choices( $choices, $core_choices );
}

/**
 * Get Slider image overlay choices.
 *
 * @since 0.5.0
 *
 * @return array<string,string>
 */
function cyber_gallery_mai_get_slider_overlay_style_choices(): array {
	$core_choices = array(
		'none'            => esc_html__( 'None', 'cyber-gallery-mai' ),
		'solid'           => esc_html__( 'Solid', 'cyber-gallery-mai' ),
		'gradient-bottom' => esc_html__( 'Gradient from Bottom', 'cyber-gallery-mai' ),
		'gradient-top'    => esc_html__( 'Gradient from Top', 'cyber-gallery-mai' ),
		'gradient-left'   => esc_html__( 'Gradient from Left', 'cyber-gallery-mai' ),
		'gradient-right'  => esc_html__( 'Gradient from Right', 'cyber-gallery-mai' ),
		'vignette'        => esc_html__( 'Vignette', 'cyber-gallery-mai' ),
	);

	/**
	 * Filter Slider image overlay choices.
	 *
	 * @since 0.5.0
	 *
	 * @param array<string,string> $choices Overlay style slug and label pairs.
	 */
	$choices = apply_filters( 'cyber_gallery_mai_slider_overlay_style_choices', $core_choices );
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Preserve the documented 1.x filter alias.
	$choices = apply_filters( 'mai_cyber_gallery_slider_overlay_style_choices', $choices );

	return cyber_gallery_mai_normalize_choices( $choices, $core_choices );
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
function cyber_gallery_mai_get_button_style_choices(): array {
	$core_choices = array(
		'button'           => esc_html__( 'Primary Button', 'cyber-gallery-mai' ),
		'button_secondary' => esc_html__( 'Secondary Button', 'cyber-gallery-mai' ),
		'button_outline'   => esc_html__( 'Outline Button', 'cyber-gallery-mai' ),
		'button_link'      => esc_html__( 'Link Button', 'cyber-gallery-mai' ),
	);

	/**
	 * Filter Slider CTA button style choices.
	 *
	 * Custom choices should also add their class mapping through the
	 * `cyber_gallery_mai_button_style_classes` filter.
	 *
	 * @since 0.3.0
	 *
	 * @param array<string,string> $choices Style slug and label pairs.
	 */
	$choices = apply_filters( 'cyber_gallery_mai_button_style_choices', $core_choices );
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Preserve the documented 1.x filter alias.
	$choices = apply_filters( 'mai_cyber_gallery_button_style_choices', $choices );

	return cyber_gallery_mai_normalize_choices( $choices, $core_choices );
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
function cyber_gallery_mai_get_default_button_style(): string {
	$choices = cyber_gallery_mai_get_button_style_choices();

	return cyber_gallery_mai_get_choice_default( $choices, 'button' );
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
function cyber_gallery_mai_get_button_style_classes( $style ): string {
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
	$styles = (array) apply_filters( 'cyber_gallery_mai_button_style_classes', $styles );
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Preserve the documented 1.x filter alias.
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
function cyber_gallery_mai_get_gallery( $args = array() ): string {
	$gallery = new Cyber_Gallery_Mai( $args );
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
function cyber_gallery_mai_do_gallery( $args = array() ): void {
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Rendered gallery markup is escaped at its sources.
	echo cyber_gallery_mai_get_gallery( $args );
}

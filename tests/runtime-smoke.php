<?php
/**
 * WP-CLI smoke test for a fully booted Genesis, Mai Theme, and Mai Engine site.
 *
 * Run this only after installing and activating the staged Cyber Gallery build
 * on a disposable WordPress site with the supported theme stack.
 *
 * @package CyberGalleryMai
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	throw new RuntimeException( 'This smoke test must run through WP-CLI.' );
}

$failures = array();
$check    = static function ( bool $condition, string $message ) use ( &$failures ): void {
	if ( ! $condition ) {
		$failures[] = $message;
	}
};

$check( function_exists( 'genesis' ), 'Genesis did not boot.' );
$check( current_theme_supports( 'mai-engine' ), 'The active theme does not support Mai Engine.' );
$check( class_exists( 'Mai_Engine' ), 'Mai Engine is not active.' );
$check( function_exists( 'mai_get_version' ), 'Mai Engine version API is unavailable.' );
$check(
	function_exists( 'mai_get_version' )
		&& version_compare( (string) mai_get_version(), '2.40.0', '>=' ),
	'Mai Engine 2.40.0 or later is required.'
);
$check( class_exists( 'Cyber_Gallery_Mai_Plugin' ), 'Canonical Cyber Gallery bootstrap class is unavailable.' );
$check( class_exists( 'Cyber_Gallery_Mai' ), 'Canonical Cyber Gallery renderer class is unavailable.' );
$check( function_exists( 'cyber_gallery_mai_get_gallery' ), 'Canonical Cyber Gallery public API is unavailable.' );
$check( class_exists( 'Mai_Cyber_Gallery_Plugin' ), 'The 1.x bootstrap class alias is unavailable.' );
$check( class_exists( 'Mai_Cyber_Gallery' ), 'The 1.x renderer class alias is unavailable.' );
$check( function_exists( 'mai_get_cyber_gallery' ), 'The 1.x gallery helper alias is unavailable.' );
$check( function_exists( 'mai_do_cyber_gallery' ), 'The 1.x echoing helper alias is unavailable.' );
$check( function_exists( 'mai_cyber_gallery_plugin' ), 'The 1.x plugin helper alias is unavailable.' );

foreach (
	array(
		'mai_cyber_gallery_add_logo_scale_attachment_field',
		'mai_cyber_gallery_render_linked_image_logo_scale_field',
		'mai_cyber_gallery_render_gallery_logo_scale_support',
		'mai_cyber_gallery_load_slider_navigation_secondary',
		'mai_cyber_gallery_load_logo_padding_axis',
		'mai_cyber_gallery_prepare_slider_navigation_field',
		'mai_cyber_gallery_register_block',
		'mai_cyber_gallery_render_block',
		'mai_cyber_gallery_prepare_columns_clone_field',
		'mai_cyber_gallery_get_validation_block_id',
		'mai_cyber_gallery_get_validation_context_value',
		'mai_cyber_gallery_is_required_field_active',
		'mai_cyber_gallery_validate_conditional_required_value',
		'mai_cyber_gallery_validate_slide_button_link',
		'mai_cyber_gallery_register_field_group',
	) as $legacy_function
) {
	$check(
		function_exists( $legacy_function ),
		sprintf( 'The 1.x callback/helper alias %s is unavailable.', $legacy_function )
	);
}

$check(
	defined( 'CYBER_GALLERY_MAI_VERSION' )
		&& defined( 'MAI_CYBER_GALLERY_VERSION' )
		&& CYBER_GALLERY_MAI_VERSION === MAI_CYBER_GALLERY_VERSION,
	'The 1.x version constant alias is unavailable or inconsistent.'
);
$check( function_exists( 'mai_get_columns_atts' ), 'Mai Engine column API is unavailable.' );
$check( function_exists( 'mai_get_acf_request' ), 'Mai Engine ACF API is unavailable.' );
$check(
	wp_style_is( 'cyber-gallery-mai', 'registered' ),
	'Cyber Gallery frontend style is not registered.'
);
$check(
	wp_script_is( 'cyber-gallery-mai', 'registered' ),
	'Cyber Gallery carousel script is not registered.'
);
$check(
	wp_script_is( 'cyber-gallery-mai-comparison', 'registered' ),
	'Cyber Gallery comparison script is not registered.'
);
$check(
	wp_script_is( 'cyber-gallery-mai-packing', 'registered' ),
	'Cyber Gallery packing script is not registered.'
);
$check(
	wp_script_is( 'cyber-gallery-mai-lightbox', 'registered' ),
	'Cyber Gallery lightbox script is not registered.'
);

$scripts = wp_scripts();
foreach (
	array(
		'cyber-gallery-mai',
		'cyber-gallery-mai-packing',
		'cyber-gallery-mai-lightbox',
		'cyber-gallery-mai-comparison',
	) as $runtime_handle
) {
	$registration = $scripts->registered[ $runtime_handle ] ?? null;
	$check(
		$registration instanceof _WP_Dependency
			&& 1 === ( $registration->extra['group'] ?? 0 ),
		sprintf( '%s is not registered for safe late footer output.', $runtime_handle )
	);
}

foreach (
	array(
		'mai-cyber-gallery'            => 'cyber-gallery-mai',
		'mai-cyber-gallery-packing'    => 'cyber-gallery-mai-packing',
		'mai-cyber-gallery-lightbox'   => 'cyber-gallery-mai-lightbox',
		'mai-cyber-gallery-comparison' => 'cyber-gallery-mai-comparison',
		'mai-cyber-gallery-editor'     => 'cyber-gallery-mai-editor',
	) as $legacy_handle => $canonical_handle
) {
	$registration = $scripts->registered[ $legacy_handle ] ?? null;
	$check(
		$registration instanceof _WP_Dependency
			&& array( $canonical_handle ) === $registration->deps,
		sprintf( '%1$s is not a dependency-only alias of %2$s.', $legacy_handle, $canonical_handle )
	);
}

$styles = wp_styles();
foreach (
	array(
		'mai-cyber-gallery'        => 'cyber-gallery-mai',
		'mai-cyber-gallery-editor' => 'cyber-gallery-mai-editor',
	) as $legacy_handle => $canonical_handle
) {
	$registration = $styles->registered[ $legacy_handle ] ?? null;
	$check(
		$registration instanceof _WP_Dependency
			&& array( $canonical_handle ) === $registration->deps,
		sprintf( '%1$s is not a dependency-only alias of %2$s.', $legacy_handle, $canonical_handle )
	);
}

$block_registry = WP_Block_Type_Registry::get_instance();
$block          = $block_registry->get_registered( 'acf/cyber-gallery-mai' );
$legacy_block   = $block_registry->get_registered( 'acf/mai-cyber-gallery' );
$check( $block instanceof WP_Block_Type, 'Canonical Cyber Gallery ACF block is not registered.' );
$check( $legacy_block instanceof WP_Block_Type, 'The hidden 1.x Cyber Gallery ACF block alias is not registered.' );
$check(
	function_exists( 'acf_get_local_field_group' )
		&& is_array( acf_get_local_field_group( 'mai_cyber_gallery_field_group' ) ),
	'Cyber Gallery local ACF field group is not registered.'
);

$preview = function_exists( 'cyber_gallery_mai_get_gallery' )
	? cyber_gallery_mai_get_gallery(
		array(
			'layout'  => 'comparison',
			'preview' => true,
		)
	)
	: '';
$check(
	str_contains( $preview, 'mai-cyber-gallery-placeholder' ),
	'Cyber Gallery renderer did not produce its empty Comparison preview.'
);

$image_ids = get_posts(
	array(
		'post_type'              => 'attachment',
		'post_status'            => 'inherit',
		'post_mime_type'         => 'image',
		'posts_per_page'         => 4,
		'fields'                 => 'ids',
		'orderby'                => 'ID',
		'order'                  => 'DESC',
		'no_found_rows'          => true,
		'update_post_meta_cache' => false,
		'update_post_term_cache' => false,
	)
);
$image_ids = array_values( array_filter( array_map( 'absint', $image_ids ) ) );
$check(
	2 <= count( $image_ids ),
	'The runtime fixture site needs at least two image attachments to exercise every layout.'
);

if ( 2 <= count( $image_ids ) ) {
	$ordinary_layouts = array(
		'grid',
		'masonry',
		'justified',
		'tiled',
		'mosaic',
		'square',
		'circle',
		'filmstrip',
	);
	$rendered         = array();

	foreach ( $ordinary_layouts as $layout ) {
		$args = array(
			'layout'       => $layout,
			'images'       => $image_ids,
			'click_action' => 'lightbox',
		);

		if ( 'filmstrip' === $layout ) {
			$args['filmstrip_autoplay'] = true;
			$args['filmstrip_controls'] = false;
		}

		$rendered[ $layout ] = cyber_gallery_mai_get_gallery( $args );
		$check(
			str_contains( $rendered[ $layout ], sprintf( 'data-cyber-layout="%s"', $layout ) ),
			sprintf( 'The %s layout did not render its runtime marker.', $layout )
		);
		$check(
			1 === preg_match( '/(?:class="|\s)mai-cyber-gallery(?:\s|")/', $rendered[ $layout ] ),
			sprintf( 'The %s layout did not retain the stable 1.x gallery-track class.', $layout )
		);
	}

	$rendered['slider'] = cyber_gallery_mai_get_gallery(
		array(
			'layout'           => 'slider',
			'slider_title_tag' => 'h3',
			'slides'           => array(
				array(
					'image'   => $image_ids[0],
					'title'   => 'Runtime slide',
					'subtext' => 'Mai integration smoke test',
				),
				array(
					'image' => $image_ids[1],
					'title' => 'Second runtime slide',
				),
			),
		)
	);
	$check(
		str_contains( $rendered['slider'], 'data-cyber-layout="slider"' )
			&& str_contains( $rendered['slider'], '<h3' ),
		'The Slider layout or its configured title element did not render.'
	);

	$rendered['comparison'] = cyber_gallery_mai_get_gallery(
		array(
			'layout'                  => 'comparison',
			'comparison_before_image' => $image_ids[0],
			'comparison_after_image'  => $image_ids[1],
		)
	);
	$check(
		str_contains( $rendered['comparison'], 'data-cyber-layout="comparison"' )
			&& str_contains( $rendered['comparison'], 'data-cyber-comparison-range' )
			&& str_contains( $rendered['comparison'], 'hidden disabled' ),
		'The complete Before / After layout did not render its progressive control.'
	);
	$check(
		str_contains( $rendered['filmstrip'], 'data-cyber-filmstrip-autoplay' )
			&& ! str_contains( $rendered['filmstrip'], 'mai-cyber-gallery-controls' ),
		'A control-free autoplay Filmstrip did not retain its intentional presentation.'
	);

	$check( wp_style_is( 'cyber-gallery-mai', 'enqueued' ), 'Runtime rendering did not enqueue the base style.' );
	foreach (
		array(
			'cyber-gallery-mai',
			'cyber-gallery-mai-packing',
			'cyber-gallery-mai-lightbox',
			'cyber-gallery-mai-comparison',
		) as $runtime_handle
	) {
		$check(
			wp_script_is( $runtime_handle, 'enqueued' ),
			sprintf( 'Runtime rendering did not enqueue %s.', $runtime_handle )
		);
	}
}

if ( $failures ) {
	WP_CLI::error(
		"Cyber Gallery runtime smoke test failed:\n- " . implode( "\n- ", $failures )
	);
}

WP_CLI::success(
	sprintf(
		'Cyber Gallery %1$s booted all ten layouts with Mai Engine %2$s under %3$s.',
		defined( 'CYBER_GALLERY_MAI_VERSION' ) ? CYBER_GALLERY_MAI_VERSION : 'unknown',
		(string) mai_get_version(),
		wp_get_theme()->get( 'Name' )
	)
);

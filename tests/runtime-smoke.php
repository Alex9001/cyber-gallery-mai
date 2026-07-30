<?php
/**
 * WP-CLI smoke test for a fully booted Genesis, Mai Theme, and Mai Engine site.
 *
 * Run this only after installing and activating the staged Cyber Gallery build
 * on a disposable WordPress site with the supported theme stack.
 *
 * @package MaiCyberGallery
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	throw new RuntimeException( 'This smoke test must run through WP-CLI.' );
}

$failures = [];
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
$check( class_exists( 'Mai_Cyber_Gallery_Plugin' ), 'Cyber Gallery bootstrap class is unavailable.' );
$check( class_exists( 'Mai_Cyber_Gallery' ), 'Cyber Gallery renderer class is unavailable.' );
$check( function_exists( 'mai_get_cyber_gallery' ), 'Cyber Gallery public API is unavailable.' );
$check( function_exists( 'mai_get_columns_atts' ), 'Mai Engine column API is unavailable.' );
$check( function_exists( 'mai_get_acf_request' ), 'Mai Engine ACF API is unavailable.' );
$check(
	wp_style_is( 'mai-cyber-gallery', 'registered' ),
	'Cyber Gallery frontend style is not registered.'
);
$check(
	wp_script_is( 'mai-cyber-gallery', 'registered' ),
	'Cyber Gallery carousel script is not registered.'
);
$check(
	wp_script_is( 'mai-cyber-gallery-comparison', 'registered' ),
	'Cyber Gallery comparison script is not registered.'
);
$check(
	wp_script_is( 'mai-cyber-gallery-packing', 'registered' ),
	'Cyber Gallery packing script is not registered.'
);
$check(
	wp_script_is( 'mai-cyber-gallery-lightbox', 'registered' ),
	'Cyber Gallery lightbox script is not registered.'
);

$scripts = wp_scripts();
foreach (
	[
		'mai-cyber-gallery',
		'mai-cyber-gallery-packing',
		'mai-cyber-gallery-lightbox',
		'mai-cyber-gallery-comparison',
	] as $runtime_handle
) {
	$registration = $scripts->registered[ $runtime_handle ] ?? null;
	$check(
		$registration instanceof _WP_Dependency
			&& 1 === ( $registration->extra['group'] ?? 0 ),
		sprintf( '%s is not registered for safe late footer output.', $runtime_handle )
	);
}

$block = WP_Block_Type_Registry::get_instance()->get_registered( 'acf/mai-cyber-gallery' );
$check( $block instanceof WP_Block_Type, 'Cyber Gallery ACF block is not registered.' );
$check(
	function_exists( 'acf_get_local_field_group' )
		&& is_array( acf_get_local_field_group( 'mai_cyber_gallery_field_group' ) ),
	'Cyber Gallery local ACF field group is not registered.'
);

$preview = function_exists( 'mai_get_cyber_gallery' )
	? mai_get_cyber_gallery(
		[
			'layout'  => 'comparison',
			'preview' => true,
		]
	)
	: '';
$check(
	str_contains( $preview, 'mai-cyber-gallery-placeholder' ),
	'Cyber Gallery renderer did not produce its empty Comparison preview.'
);

$image_ids = get_posts(
	[
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
	]
);
$image_ids = array_values( array_filter( array_map( 'absint', $image_ids ) ) );
$check(
	2 <= count( $image_ids ),
	'The runtime fixture site needs at least two image attachments to exercise every layout.'
);

if ( 2 <= count( $image_ids ) ) {
	$ordinary_layouts = [
		'grid',
		'masonry',
		'justified',
		'tiled',
		'mosaic',
		'square',
		'circle',
		'filmstrip',
	];
	$rendered         = [];

	foreach ( $ordinary_layouts as $layout ) {
		$args = [
			'layout'       => $layout,
			'images'       => $image_ids,
			'click_action' => 'lightbox',
		];

		if ( 'filmstrip' === $layout ) {
			$args['filmstrip_autoplay'] = true;
			$args['filmstrip_controls'] = false;
		}

		$rendered[ $layout ] = mai_get_cyber_gallery( $args );
		$check(
			str_contains( $rendered[ $layout ], sprintf( 'data-cyber-layout="%s"', $layout ) ),
			sprintf( 'The %s layout did not render its runtime marker.', $layout )
		);
	}

	$rendered['slider'] = mai_get_cyber_gallery(
		[
			'layout'           => 'slider',
			'slider_title_tag' => 'h3',
			'slides'           => [
				[
					'image'   => $image_ids[0],
					'title'   => 'Runtime slide',
					'subtext' => 'Mai integration smoke test',
				],
				[
					'image' => $image_ids[1],
					'title' => 'Second runtime slide',
				],
			],
		]
	);
	$check(
		str_contains( $rendered['slider'], 'data-cyber-layout="slider"' )
			&& str_contains( $rendered['slider'], '<h3' ),
		'The Slider layout or its configured title element did not render.'
	);

	$rendered['comparison'] = mai_get_cyber_gallery(
		[
			'layout'                  => 'comparison',
			'comparison_before_image' => $image_ids[0],
			'comparison_after_image'  => $image_ids[1],
		]
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

	$check( wp_style_is( 'mai-cyber-gallery', 'enqueued' ), 'Runtime rendering did not enqueue the base style.' );
	foreach (
		[
			'mai-cyber-gallery',
			'mai-cyber-gallery-packing',
			'mai-cyber-gallery-lightbox',
			'mai-cyber-gallery-comparison',
		] as $runtime_handle
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
		defined( 'MAI_CYBER_GALLERY_VERSION' ) ? MAI_CYBER_GALLERY_VERSION : 'unknown',
		(string) mai_get_version(),
		wp_get_theme()->get( 'Name' )
	)
);

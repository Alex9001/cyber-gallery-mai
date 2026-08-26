<?php
/**
 * Regression coverage for configurable Editorial Mosaic groups.
 *
 * @package CyberGalleryMai
 */

declare( strict_types=1 );

define( 'ABSPATH', __DIR__ . '/' );

/**
 * Minimal Mai class-list helper.
 *
 * @param string|array<int, string> $new_classes New classes.
 * @param string                    $existing    Existing classes.
 *
 * @return string
 */
function mai_add_classes( $new_classes, $existing = '' ): string {
	$existing_classes = preg_split( '/\s+/', trim( $existing ) );
	$existing_classes = false === $existing_classes ? array() : $existing_classes;
	$new_classes      = is_array( $new_classes ) ? $new_classes : preg_split( '/\s+/', trim( $new_classes ) );

	$classes = array_merge(
		$existing_classes,
		false === $new_classes ? array() : $new_classes
	);

	return implode( ' ', array_values( array_unique( array_filter( $classes ) ) ) );
}

require dirname( __DIR__ ) . '/classes/class-cyber-gallery-mai.php';

$reflection = new ReflectionClass( Cyber_Gallery_Mai::class );
$gallery    = $reflection->newInstanceWithoutConstructor();
$args       = $reflection->getProperty( 'args' );
$method     = $reflection->getMethod( 'get_mosaic_item_attributes' );

/**
 * Invoke the protected mosaic attribute helper.
 *
 * @param Cyber_Gallery_Mai $gallery Gallery test instance.
 * @param ReflectionMethod  $method  Mosaic method.
 * @param int               $index   Zero-based image index.
 * @param int               $total   Total image count.
 *
 * @return array<string, scalar>
 */
function cyber_mosaic_atts( $gallery, $method, $index, $total ): array {
	return $method->invoke(
		$gallery,
		array(
			'class' => 'mai-cyber-gallery-item',
			'style' => '',
		),
		$index,
		$total
	);
}

$args->setValue(
	$gallery,
	array(
		'mosaic_items_after_feature' => 3,
		'mosaic_feature_side'        => 'left',
		'mosaic_alternate'           => true,
	)
);

$first_feature = cyber_mosaic_atts( $gallery, $method, 0, 9 );
$first_odd     = cyber_mosaic_atts( $gallery, $method, 3, 9 );
$next_feature  = cyber_mosaic_atts( $gallery, $method, 4, 9 );
$solo_feature  = cyber_mosaic_atts( $gallery, $method, 8, 9 );

if (
	! str_contains( (string) $first_feature['class'], 'is-mosaic-feature-left' )
	|| ! str_contains( (string) $first_feature['style'], '--cyber-mosaic-feature-rows:2;' )
	|| ! str_contains( (string) $first_odd['class'], 'is-mosaic-odd-remainder' )
	|| ! str_contains( (string) $first_odd['class'], 'is-mosaic-secondary-span' )
	|| ! str_contains( (string) $next_feature['class'], 'is-mosaic-feature-right' )
	|| ! str_contains( (string) $solo_feature['class'], 'is-mosaic-feature-solo' )
) {
	throw new RuntimeException( 'Three-image Mosaic groups did not close or alternate correctly.' );
}

$args->setValue(
	$gallery,
	array(
		'mosaic_items_after_feature' => 5,
		'mosaic_feature_side'        => 'right',
		'mosaic_alternate'           => false,
	)
);

$five_feature = cyber_mosaic_atts( $gallery, $method, 0, 6 );
$five_odd     = cyber_mosaic_atts( $gallery, $method, 5, 6 );

if (
	! str_contains( (string) $five_feature['style'], '--cyber-mosaic-feature-rows:3;' )
	|| ! str_contains( (string) $five_odd['class'], 'is-mosaic-odd-remainder' )
) {
	throw new RuntimeException( 'Five-image Mosaic groups did not calculate three complete rows.' );
}

$args->setValue(
	$gallery,
	array(
		'mosaic_items_after_feature' => 4,
		'mosaic_feature_side'        => 'left',
		'mosaic_alternate'           => false,
	)
);

$short_first  = cyber_mosaic_atts( $gallery, $method, 1, 3 );
$short_second = cyber_mosaic_atts( $gallery, $method, 2, 3 );

if (
	! str_contains( (string) $short_first['class'], 'is-mosaic-secondary-span' )
	|| ! str_contains( (string) $short_second['class'], 'is-mosaic-secondary-span' )
	|| str_contains( (string) $short_second['class'], 'is-mosaic-odd-remainder' )
) {
	throw new RuntimeException( 'Incomplete two-image Mosaic remainders did not fill both rows.' );
}

echo "Verified configurable and incomplete Editorial Mosaic groups.\n";

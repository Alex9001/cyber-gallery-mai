<?php
/**
 * Regression coverage for strict comparison-side normalization.
 *
 * @package CyberGalleryMai
 */

declare( strict_types=1 );

define( 'ABSPATH', __DIR__ . '/' );

require dirname( __DIR__ ) . '/classes/class-cyber-gallery-mai.php';

$reflection = new ReflectionClass( Cyber_Gallery_Mai::class );
$gallery    = $reflection->newInstanceWithoutConstructor();
$method     = $reflection->getMethod( 'get_comparison_items' );

$before    = array(
	'image'           => '<img alt="Before">',
	'comparison_side' => 'before',
);
$after     = array(
	'image'           => '<img alt="After">',
	'comparison_side' => 'after',
);
$duplicate = array(
	'image'           => '<img alt="Duplicate">',
	'comparison_side' => 'before',
);

$complete = $method->invoke( $gallery, array( $after, $duplicate, $before ) );
$missing  = $method->invoke( $gallery, array( $before ) );
$ordinary = $method->invoke(
	$gallery,
	array(
		array(
			'image'           => '<img alt="Gallery">',
			'comparison_side' => '',
		),
	)
);

if (
	2 !== count( $complete )
	|| 'before' !== $complete[0]['comparison_side']
	|| 'after' !== $complete[1]['comparison_side']
	|| $missing
	|| $ordinary
) {
	throw new RuntimeException( 'Comparison sides were not kept strict and complete.' );
}

echo "Verified strict Before / After item selection.\n";

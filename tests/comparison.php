<?php
/**
 * Regression coverage for strict comparison-side normalization.
 *
 * @package MaiCyberGallery
 */

declare( strict_types=1 );

define( 'ABSPATH', __DIR__ . '/' );

require dirname( __DIR__ ) . '/classes/class-gallery.php';

$reflection = new ReflectionClass( Mai_Cyber_Gallery::class );
$gallery    = $reflection->newInstanceWithoutConstructor();
$method     = $reflection->getMethod( 'get_comparison_items' );

$method->setAccessible( true );

$before = [
	'image'           => '<img alt="Before">',
	'comparison_side' => 'before',
];
$after = [
	'image'           => '<img alt="After">',
	'comparison_side' => 'after',
];
$duplicate = [
	'image'           => '<img alt="Duplicate">',
	'comparison_side' => 'before',
];

$complete = $method->invoke( $gallery, [ $after, $duplicate, $before ] );
$missing  = $method->invoke( $gallery, [ $before ] );
$ordinary = $method->invoke(
	$gallery,
	[
		[
			'image'           => '<img alt="Gallery">',
			'comparison_side' => '',
		],
	]
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

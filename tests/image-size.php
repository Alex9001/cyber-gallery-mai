<?php
/**
 * Regression coverage for image-size validation and fallbacks.
 *
 * @package MaiCyberGallery
 */

declare( strict_types=1 );

define( 'ABSPATH', __DIR__ . '/' );

$mai_cyber_gallery_test_filter = null;
$mai_cyber_gallery_test_sizes  = [];

/**
 * Minimal Mai orientation check.
 *
 * @param string $orientation Candidate orientation.
 *
 * @return bool
 */
function mai_has_image_orientiation( $orientation ): bool {
	return in_array( $orientation, [ 'landscape', 'portrait', 'square' ], true );
}

/**
 * Return the test filter value when one is configured.
 *
 * @param string $hook  Hook name.
 * @param mixed  $value Original value.
 *
 * @return mixed
 */
function apply_filters( $hook, $value ) {
	global $mai_cyber_gallery_test_filter;
	unset( $hook );
	return null === $mai_cyber_gallery_test_filter ? $value : $mai_cyber_gallery_test_filter;
}

/**
 * Minimal key sanitizer.
 *
 * @param mixed $key Candidate key.
 *
 * @return string
 */
function sanitize_key( $key ): string {
	return preg_replace( '/[^a-z0-9_-]/', '', strtolower( (string) $key ) ) ?? '';
}

/**
 * Return registered image-size fixtures.
 *
 * @return array<string, array<string, mixed>>
 */
function wp_get_registered_image_subsizes(): array {
	global $mai_cyber_gallery_test_sizes;
	return $mai_cyber_gallery_test_sizes;
}

require dirname( __DIR__ ) . '/classes/class-gallery.php';

$reflection = new ReflectionClass( Mai_Cyber_Gallery::class );
$gallery    = $reflection->newInstanceWithoutConstructor();
$args       = $reflection->getProperty( 'args' );

$args->setAccessible( true );

/**
 * Resolve one image-size test case.
 *
 * @param Mai_Cyber_Gallery                  $gallery Gallery instance.
 * @param ReflectionProperty                 $args    Argument property.
 * @param array<string, mixed>               $values  Gallery arguments.
 * @param array<string, array<string, mixed>> $sizes  Registered sizes.
 * @param string|null                        $filter  Filtered size.
 *
 * @return string
 */
function cyber_image_size( $gallery, $args, array $values, array $sizes, ?string $filter ): string {
	global $mai_cyber_gallery_test_filter, $mai_cyber_gallery_test_sizes;

	$mai_cyber_gallery_test_filter = $filter;
	$mai_cyber_gallery_test_sizes  = $sizes;
	$args->setValue(
		$gallery,
		array_merge(
			[
				'layout'               => 'mosaic',
				'image_orientation'    => 'custom',
				'image_size'           => 'medium',
				'filmstrip_image_mode' => 'natural',
			],
			$values
		)
	);

	return $gallery->get_image_size();
}

$registered = [
	'medium' => [ 'crop' => false ],
	'large'  => [ 'crop' => false ],
];

if ( 'medium' !== cyber_image_size( $gallery, $args, [], $registered, 'not-registered' ) ) {
	throw new RuntimeException( 'An invalid filtered size did not fall back to the requested registered size.' );
}

if (
	'large' !== cyber_image_size(
		$gallery,
		$args,
		[ 'image_size' => 'not-registered' ],
		[ 'large' => [ 'crop' => false ] ],
		'also-invalid'
	)
) {
	throw new RuntimeException( 'An invalid requested size did not fall back to Large.' );
}

if (
	'full' !== cyber_image_size(
		$gallery,
		$args,
		[ 'image_size' => 'not-registered' ],
		[],
		'also-invalid'
	)
) {
	throw new RuntimeException( 'An invalid requested size did not ultimately fall back to Full.' );
}

if (
	'full' !== cyber_image_size(
		$gallery,
		$args,
		[
			'layout'               => 'filmstrip',
			'image_size'           => 'logo-hard',
			'filmstrip_image_mode' => 'logo-contain',
		],
		[ 'logo-hard' => [ 'crop' => true ] ],
		null
	)
) {
	throw new RuntimeException( 'Logo / Contain did not reject a registered hard-cropped size.' );
}

if (
	'logo-soft' !== cyber_image_size(
		$gallery,
		$args,
		[
			'layout'               => 'filmstrip',
			'image_size'           => 'logo-soft',
			'filmstrip_image_mode' => 'logo-contain',
		],
		[ 'logo-soft' => [ 'crop' => false ] ],
		null
	)
) {
	throw new RuntimeException( 'Logo / Contain rejected a compatible uncropped size.' );
}

echo "Verified registered image-size validation and safe fallbacks.\n";

<?php
/**
 * Regression coverage for image-size validation and fallbacks.
 *
 * @package CyberGalleryMai
 */

declare( strict_types=1 );

define( 'ABSPATH', __DIR__ . '/' );

$cyber_gallery_mai_test_filter = null;
$cyber_gallery_mai_test_sizes  = array();

/**
 * Minimal Mai orientation check.
 *
 * @param string $orientation Candidate orientation.
 *
 * @return bool
 */
function mai_has_image_orientiation( $orientation ): bool {
	return in_array( $orientation, array( 'landscape', 'portrait', 'square' ), true );
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
	global $cyber_gallery_mai_test_filter;
	unset( $hook );
	return null === $cyber_gallery_mai_test_filter ? $value : $cyber_gallery_mai_test_filter;
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
	global $cyber_gallery_mai_test_sizes;
	return $cyber_gallery_mai_test_sizes;
}

require dirname( __DIR__ ) . '/classes/class-cyber-gallery-mai.php';

$reflection = new ReflectionClass( Cyber_Gallery_Mai::class );
$gallery    = $reflection->newInstanceWithoutConstructor();
$args       = $reflection->getProperty( 'args' );

/**
 * Resolve one image-size test case.
 *
 * @param Cyber_Gallery_Mai                   $gallery Gallery instance.
 * @param ReflectionProperty                  $args    Argument property.
 * @param array<string, mixed>                $values  Gallery arguments.
 * @param array<string, array<string, mixed>> $sizes  Registered sizes.
 * @param string|null                         $filter  Filtered size.
 *
 * @return string
 */
function cyber_image_size( $gallery, $args, array $values, array $sizes, ?string $filter ): string {
	global $cyber_gallery_mai_test_filter, $cyber_gallery_mai_test_sizes;

	$cyber_gallery_mai_test_filter = $filter;
	$cyber_gallery_mai_test_sizes  = $sizes;
	$args->setValue(
		$gallery,
		array_merge(
			array(
				'layout'               => 'mosaic',
				'image_orientation'    => 'custom',
				'image_size'           => 'medium',
				'filmstrip_image_mode' => 'natural',
			),
			$values
		)
	);

	return $gallery->get_image_size();
}

$registered = array(
	'medium' => array( 'crop' => false ),
	'large'  => array( 'crop' => false ),
);

if ( 'medium' !== cyber_image_size( $gallery, $args, array(), $registered, 'not-registered' ) ) {
	throw new RuntimeException( 'An invalid filtered size did not fall back to the requested registered size.' );
}

if (
	'large' !== cyber_image_size(
		$gallery,
		$args,
		array( 'image_size' => 'not-registered' ),
		array( 'large' => array( 'crop' => false ) ),
		'also-invalid'
	)
) {
	throw new RuntimeException( 'An invalid requested size did not fall back to Large.' );
}

if (
	'full' !== cyber_image_size(
		$gallery,
		$args,
		array( 'image_size' => 'not-registered' ),
		array(),
		'also-invalid'
	)
) {
	throw new RuntimeException( 'An invalid requested size did not ultimately fall back to Full.' );
}

if (
	'full' !== cyber_image_size(
		$gallery,
		$args,
		array(
			'layout'               => 'filmstrip',
			'image_size'           => 'logo-hard',
			'filmstrip_image_mode' => 'logo-contain',
		),
		array( 'logo-hard' => array( 'crop' => true ) ),
		null
	)
) {
	throw new RuntimeException( 'Logo / Contain did not reject a registered hard-cropped size.' );
}

if (
	'logo-soft' !== cyber_image_size(
		$gallery,
		$args,
		array(
			'layout'               => 'filmstrip',
			'image_size'           => 'logo-soft',
			'filmstrip_image_mode' => 'logo-contain',
		),
		array( 'logo-soft' => array( 'crop' => false ) ),
		null
	)
) {
	throw new RuntimeException( 'Logo / Contain rejected a compatible uncropped size.' );
}

echo "Verified registered image-size validation and safe fallbacks.\n";

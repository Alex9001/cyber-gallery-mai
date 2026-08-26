<?php
/**
 * Render actual Comparison markup for browser regression tests.
 *
 * @package CyberGalleryMai
 */

declare( strict_types=1 );

define( 'ABSPATH', __DIR__ . '/' );

/**
 * Return fixture attachment dimensions.
 *
 * @return array{0: string, 1: int, 2: int}
 */
function wp_get_attachment_image_src(): array {
	return array( 'fixture.jpg', 1200, 800 );
}

/**
 * Minimal absolute-integer implementation.
 *
 * @param mixed $value Candidate value.
 *
 * @return int
 */
function absint( $value ): int {
	return abs( (int) $value );
}

/**
 * Minimal HTML-class sanitizer.
 *
 * @param mixed $value Candidate class.
 *
 * @return string
 */
function sanitize_html_class( $value ): string {
	return preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $value ) ?? '';
}

/**
 * Translation test double.
 *
 * @param string $text Source string.
 *
 * @return string
 */
function esc_html__( $text ): string {
	return htmlspecialchars( $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );
}

/**
 * Attribute-translation test double.
 *
 * @param string $text Source string.
 *
 * @return string
 */
function esc_attr__( $text ): string {
	return htmlspecialchars( $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );
}

/**
 * Attribute-escaping test double.
 *
 * @param mixed $text Source value.
 *
 * @return string
 */
function esc_attr( $text ): string {
	return htmlspecialchars( (string) $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );
}

/**
 * HTML-escaping test double.
 *
 * @param mixed $text Source value.
 *
 * @return string
 */
function esc_html( $text ): string {
	return htmlspecialchars( (string) $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );
}

/**
 * Return deterministic icon markup.
 *
 * @param string $icon Icon name.
 *
 * @return string
 */
function mai_get_svg_icon( $icon ): string {
	return sprintf( '<svg aria-hidden="true" data-icon="%s"></svg>', esc_attr( $icon ) );
}

require dirname( __DIR__, 2 ) . '/classes/class-cyber-gallery-mai.php';

$reflection = new ReflectionClass( Cyber_Gallery_Mai::class );
$gallery    = $reflection->newInstanceWithoutConstructor();
$args       = $reflection->getProperty( 'args' );
$image_size = $reflection->getProperty( 'image_size' );
$method     = $reflection->getMethod( 'get_comparison_markup' );

$args->setValue(
	$gallery,
	array(
		'comparison_position'     => 35,
		'comparison_before_label' => 'Original',
		'comparison_after_label'  => 'Updated',
		'shadow'                  => false,
		'aria_label'              => 'Product redesign comparison',
		'slider_title_tag'        => 'h3',
	)
);
$image_size->setValue( $gallery, 'large' );

$items = array(
	array(
		'id'     => 10,
		'image'  => '<img src="before.jpg" alt="Original design">',
		'width'  => 1200,
		'height' => 800,
		'ratio'  => 1.5,
	),
	array(
		'id'     => 11,
		'image'  => '<img src="after.jpg" alt="Updated design">',
		'width'  => 1200,
		'height' => 800,
		'ratio'  => 1.5,
	),
);

echo $method->invoke( $gallery, $items, 'fixture-gallery' );

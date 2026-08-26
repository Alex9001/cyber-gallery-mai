<?php
/**
 * Development-only signatures for the Mai Engine and Genesis APIs used here.
 *
 * @package CyberGalleryMai
 */

/**
 * Mai Engine bootstrap class.
 */
class Mai_Engine {}

/**
 * Build Genesis markup from supplied arguments.
 *
 * @param array<string,mixed> $args Markup arguments.
 *
 * @return string
 */
function genesis_markup( array $args = array() ): string {}

/**
 * Build Genesis attributes for a markup context.
 *
 * @param string              $context    Markup context.
 * @param array<string,mixed> $attributes Initial attributes.
 * @param array<string,mixed> $args       Filter arguments.
 *
 * @return string
 */
function genesis_attr( string $context, array $attributes = array(), array $args = array() ): string {}

/**
 * Return the active Mai Engine version.
 *
 * @return string
 */
function mai_get_version(): string {}

/**
 * Return a Mai Engine breakpoint.
 *
 * @param string $size   Breakpoint size.
 * @param string $suffix Optional breakpoint suffix.
 *
 * @return int|string
 */
function mai_get_breakpoint( string $size = 'lg', string $suffix = '' ): int|string {}

/**
 * Return registered Mai Engine colors.
 *
 * @return array<string,string>
 */
function mai_get_colors(): array {}

/**
 * Return the CSS representation of a Mai Engine color.
 *
 * @param string $color Color value or slug.
 *
 * @return string
 */
function mai_get_color_css( string $color ): string {}

/**
 * Return color choices suitable for a radio field.
 *
 * @return array<string,string>
 */
function mai_get_radio_color_choices(): array {}

/**
 * Add classes to an existing class list.
 *
 * @param string|array<int,string> $new      New classes.
 * @param string                   $existing Existing classes.
 *
 * @return string
 */
function mai_add_classes( string|array $new, string $existing = '' ): string {}

/**
 * Return responsive column counts.
 *
 * @param array<string,mixed> $args Column arguments.
 *
 * @return array<string,int>
 */
function mai_get_breakpoint_columns( array $args ): array {}

/**
 * Return HTML attributes for a Mai Engine columns wrapper.
 *
 * @param array<string,mixed> $atts  Initial attributes.
 * @param array<string,mixed> $args  Column arguments.
 * @param bool                $nested Whether the columns are nested.
 *
 * @return array<string,mixed>
 */
function mai_get_columns_atts( array $atts, array $args, bool $nested = false ): array {}

/**
 * Return default Mai Engine column arguments.
 *
 * @return array<string,mixed>
 */
function mai_get_columns_defaults(): array {}

/**
 * Sanitize Mai Engine column arguments without changing their array shape.
 *
 * @template T of array<string,mixed>
 *
 * @param array<string,mixed> $args Column arguments.
 *
 * @phpstan-param T $args
 *
 * @return array<string,mixed>
 * @phpstan-return T
 */
function mai_get_columns_sanitized( array $args ): array {}

/**
 * Return an incrementing index for a context.
 *
 * @param string $context Index context.
 * @param bool   $reset   Whether to reset the counter.
 *
 * @return int
 */
function mai_get_index( string $context, bool $reset = false ): int {}

/**
 * Return a value from the current Mai Engine ACF request.
 *
 * @param string $request Request key.
 *
 * @return mixed
 */
function mai_get_acf_request( string $request ): mixed {}

/**
 * Return registered image orientation choices.
 *
 * @return array<string,string>|null
 */
function mai_get_image_orientation_choices(): ?array {}

/**
 * Return registered image size choices.
 *
 * @return array<string,string>
 */
function mai_get_image_size_choices(): array {}

/**
 * Determine whether an image orientation is registered.
 *
 * @param string $orientation Image orientation.
 *
 * @return bool
 */
function mai_has_image_orientiation( string $orientation ): bool {}

/**
 * Determine whether a responsive size is valid.
 *
 * @param mixed $size Responsive size.
 *
 * @return bool
 */
function mai_is_valid_size( mixed $size ): bool {}

/**
 * Sanitize a boolean value using Mai Engine semantics.
 *
 * @param mixed $value Candidate value.
 *
 * @return bool
 */
function mai_sanitize_bool( mixed $value ): bool {}

/**
 * Return a unit-bearing Mai Engine value.
 *
 * @param mixed  $value    Candidate value.
 * @param string $fallback Fallback unit.
 *
 * @return mixed
 */
function mai_get_unit_value( mixed $value, string $fallback = 'px' ): mixed {}

/**
 * Prepare a legacy Mai Engine color field.
 *
 * @param array<string,mixed> $field Field configuration.
 *
 * @return array<string,mixed>
 */
function mai_prepare_legacy_color_field( array $field ): array {}

/**
 * Format an ACF color value using Mai Engine semantics.
 *
 * @param mixed               $value   Field value.
 * @param mixed               $post_id Post identifier.
 * @param array<string,mixed> $field   Field configuration.
 *
 * @return mixed
 */
function mai_format_acf_color_value( mixed $value, mixed $post_id, array $field ): mixed {}

/**
 * Return an SVG icon from Mai Engine.
 *
 * @param string               $name  Icon name.
 * @param string               $style Icon style.
 * @param array<string,scalar> $atts  SVG attributes.
 *
 * @return string
 */
function mai_get_svg_icon( string $name, string $style = 'light', array $atts = array() ): string {}

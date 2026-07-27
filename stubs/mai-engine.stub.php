<?php
/**
 * Development-only signatures for the Mai Engine and Genesis APIs used here.
 *
 * @package MaiCyberGallery
 */

/**
 * Mai Engine bootstrap class.
 */
class Mai_Engine {}

/**
 * @param array<string,mixed> $args
 */
function genesis_markup( array $args = [] ): string {}

/**
 * @param array<string,mixed> $attributes
 * @param array<string,mixed> $args
 */
function genesis_attr( string $context, array $attributes = [], array $args = [] ): string {}

function mai_get_version(): string {}

function mai_get_breakpoint( string $size = 'lg', string $suffix = '' ): int|string {}

/**
 * @return array<string,string>
 */
function mai_get_colors(): array {}

function mai_get_color_css( string $color ): string {}

/**
 * @return array<string,string>
 */
function mai_get_radio_color_choices(): array {}

/**
 * @param string|array<int,string> $new
 */
function mai_add_classes( string|array $new, string $existing = '' ): string {}

/**
 * @param array<string,mixed> $args
 * @return array<string,int>
 */
function mai_get_breakpoint_columns( array $args ): array {}

/**
 * @param array<string,mixed> $atts
 * @param array<string,mixed> $args
 * @return array<string,mixed>
 */
function mai_get_columns_atts( array $atts, array $args, bool $nested = false ): array {}

/**
 * @return array<string,mixed>
 */
function mai_get_columns_defaults(): array {}

/**
 * @template T of array<string,mixed>
 * @param T $args
 * @return T
 */
function mai_get_columns_sanitized( array $args ): array {}

function mai_get_index( string $context, bool $reset = false ): int {}

function mai_get_acf_request( string $request ): mixed {}

/**
 * @return array<string,string>|null
 */
function mai_get_image_orientation_choices(): ?array {}

/**
 * @return array<string,string>
 */
function mai_get_image_size_choices(): array {}

function mai_has_image_orientiation( string $orientation ): bool {}

function mai_is_valid_size( mixed $size ): bool {}

function mai_sanitize_bool( mixed $value ): bool {}

function mai_get_unit_value( mixed $value, string $fallback = 'px' ): mixed {}

/**
 * @param array<string,mixed> $field
 * @return array<string,mixed>
 */
function mai_prepare_legacy_color_field( array $field ): array {}

/**
 * @param array<string,mixed> $field
 */
function mai_format_acf_color_value( mixed $value, mixed $post_id, array $field ): mixed {}

/**
 * @param array<string,scalar> $atts
 */
function mai_get_svg_icon( string $name, string $style = 'light', array $atts = [] ): string {}

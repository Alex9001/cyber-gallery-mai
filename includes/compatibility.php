<?php
/**
 * Compatibility aliases for Cyber Gallery for Mai Theme 1.x integrations.
 *
 * Persisted ACF field keys, the former block name, markup classes, data
 * attributes, and Genesis contexts remain stable elsewhere. This file keeps
 * PHP integrations working while new code adopts the canonical 2.0 symbols.
 *
 * @package CyberGalleryMai
 * @since 2.0.0
 */

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

if ( ! class_exists( 'Mai_Cyber_Gallery', false ) ) {
	class_alias( Cyber_Gallery_Mai::class, 'Mai_Cyber_Gallery' );
}

if ( ! function_exists( 'mai_cyber_gallery_normalize_choices' ) ) {
	/**
	 * Normalize choices using the 1.x function name.
	 *
	 * @since 1.0.0
	 * @deprecated 2.0.0 Use cyber_gallery_mai_normalize_choices().
	 *
	 * @param mixed                $choices  Filtered choices.
	 * @param array<string,string> $fallback Trusted fallback choices.
	 *
	 * @return array<string,string>
	 */
	function mai_cyber_gallery_normalize_choices( $choices, array $fallback ): array {
		return cyber_gallery_mai_normalize_choices( $choices, $fallback );
	}
}

if ( ! function_exists( 'mai_cyber_gallery_get_choice_default' ) ) {
	/**
	 * Get a choice default using the 1.x function name.
	 *
	 * @since 1.0.0
	 * @deprecated 2.0.0 Use cyber_gallery_mai_get_choice_default().
	 *
	 * @param array<string,string> $choices   Normalized choices.
	 * @param string               $preferred Preferred choice slug.
	 * @param string               $exclude   Choice to avoid when possible.
	 *
	 * @return string
	 */
	function mai_cyber_gallery_get_choice_default( array $choices, $preferred, $exclude = '' ): string {
		return cyber_gallery_mai_get_choice_default( $choices, $preferred, $exclude );
	}
}

if ( ! function_exists( 'mai_cyber_gallery_get_layout_choices' ) ) {
	/**
	 * Get layouts using the 1.x function name.
	 *
	 * @since 0.1.0
	 * @deprecated 2.0.0 Use cyber_gallery_mai_get_layout_choices().
	 *
	 * @return array<string,string>
	 */
	function mai_cyber_gallery_get_layout_choices(): array {
		return cyber_gallery_mai_get_layout_choices();
	}
}

if ( ! function_exists( 'mai_cyber_gallery_get_arrow_style_choices' ) ) {
	/**
	 * Get arrow styles using the 1.x function name.
	 *
	 * @since 0.2.0
	 * @deprecated 2.0.0 Use cyber_gallery_mai_get_arrow_style_choices().
	 *
	 * @return array<string,string>
	 */
	function mai_cyber_gallery_get_arrow_style_choices(): array {
		return cyber_gallery_mai_get_arrow_style_choices();
	}
}

if ( ! function_exists( 'mai_cyber_gallery_get_slider_navigation_choices' ) ) {
	/**
	 * Get Slider navigation choices using the 1.x function name.
	 *
	 * @since 0.2.0
	 * @deprecated 2.0.0 Use cyber_gallery_mai_get_slider_navigation_choices().
	 *
	 * @return array<string,string>
	 */
	function mai_cyber_gallery_get_slider_navigation_choices(): array {
		return cyber_gallery_mai_get_slider_navigation_choices();
	}
}

if ( ! function_exists( 'mai_cyber_gallery_get_slider_transition_choices' ) ) {
	/**
	 * Get Slider transitions using the 1.x function name.
	 *
	 * @since 0.5.0
	 * @deprecated 2.0.0 Use cyber_gallery_mai_get_slider_transition_choices().
	 *
	 * @return array<string,string>
	 */
	function mai_cyber_gallery_get_slider_transition_choices(): array {
		return cyber_gallery_mai_get_slider_transition_choices();
	}
}

if ( ! function_exists( 'mai_cyber_gallery_get_slider_transition_speed_choices' ) ) {
	/**
	 * Get Slider speeds using the 1.x function name.
	 *
	 * @since 0.5.0
	 * @deprecated 2.0.0 Use cyber_gallery_mai_get_slider_transition_speed_choices().
	 *
	 * @return array<string,string>
	 */
	function mai_cyber_gallery_get_slider_transition_speed_choices(): array {
		return cyber_gallery_mai_get_slider_transition_speed_choices();
	}
}

if ( ! function_exists( 'mai_cyber_gallery_get_slider_navigation_hover_choices' ) ) {
	/**
	 * Get navigation hover choices using the 1.x function name.
	 *
	 * @since 0.5.0
	 * @deprecated 2.0.0 Use cyber_gallery_mai_get_slider_navigation_hover_choices().
	 *
	 * @return array<string,string>
	 */
	function mai_cyber_gallery_get_slider_navigation_hover_choices(): array {
		return cyber_gallery_mai_get_slider_navigation_hover_choices();
	}
}

if ( ! function_exists( 'mai_cyber_gallery_get_slider_overlay_style_choices' ) ) {
	/**
	 * Get overlay choices using the 1.x function name.
	 *
	 * @since 0.5.0
	 * @deprecated 2.0.0 Use cyber_gallery_mai_get_slider_overlay_style_choices().
	 *
	 * @return array<string,string>
	 */
	function mai_cyber_gallery_get_slider_overlay_style_choices(): array {
		return cyber_gallery_mai_get_slider_overlay_style_choices();
	}
}

if ( ! function_exists( 'mai_cyber_gallery_get_button_style_choices' ) ) {
	/**
	 * Get CTA style choices using the 1.x function name.
	 *
	 * @since 0.3.0
	 * @deprecated 2.0.0 Use cyber_gallery_mai_get_button_style_choices().
	 *
	 * @return array<string,string>
	 */
	function mai_cyber_gallery_get_button_style_choices(): array {
		return cyber_gallery_mai_get_button_style_choices();
	}
}

if ( ! function_exists( 'mai_cyber_gallery_get_default_button_style' ) ) {
	/**
	 * Get the default CTA style using the 1.x function name.
	 *
	 * @since 0.3.0
	 * @deprecated 2.0.0 Use cyber_gallery_mai_get_default_button_style().
	 *
	 * @return string
	 */
	function mai_cyber_gallery_get_default_button_style(): string {
		return cyber_gallery_mai_get_default_button_style();
	}
}

if ( ! function_exists( 'mai_cyber_gallery_get_button_style_classes' ) ) {
	/**
	 * Get CTA classes using the 1.x function name.
	 *
	 * @since 0.3.0
	 * @deprecated 2.0.0 Use cyber_gallery_mai_get_button_style_classes().
	 *
	 * @param string $style Button style slug.
	 *
	 * @return string
	 */
	function mai_cyber_gallery_get_button_style_classes( $style ): string {
		return cyber_gallery_mai_get_button_style_classes( $style );
	}
}

if ( ! function_exists( 'mai_cyber_gallery_add_logo_scale_attachment_field' ) ) {
	/**
	 * Add the block-local logo control using the 1.x callback name.
	 *
	 * @since 0.7.1
	 * @deprecated 2.0.0 Use cyber_gallery_mai_add_logo_scale_attachment_field().
	 *
	 * @param array<string,array<string,mixed>> $fields Attachment fields.
	 * @param WP_Post                           $post   Attachment post.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	function mai_cyber_gallery_add_logo_scale_attachment_field( $fields, $post ): array {
		return cyber_gallery_mai_add_logo_scale_attachment_field( $fields, $post );
	}
}

if ( ! function_exists( 'mai_cyber_gallery_render_linked_image_logo_scale_field' ) ) {
	/**
	 * Render a linked-image logo control using the 1.x callback name.
	 *
	 * @since 0.7.1
	 * @deprecated 2.0.0 Use cyber_gallery_mai_render_linked_image_logo_scale_field().
	 *
	 * @return void
	 */
	function mai_cyber_gallery_render_linked_image_logo_scale_field(): void {
		cyber_gallery_mai_render_linked_image_logo_scale_field();
	}
}

if ( ! function_exists( 'mai_cyber_gallery_render_gallery_logo_scale_support' ) ) {
	/**
	 * Render the Gallery-field capability marker using the 1.x callback name.
	 *
	 * @since 0.7.1
	 * @deprecated 2.0.0 Use cyber_gallery_mai_render_gallery_logo_scale_support().
	 *
	 * @return void
	 */
	function mai_cyber_gallery_render_gallery_logo_scale_support(): void {
		cyber_gallery_mai_render_gallery_logo_scale_support();
	}
}

if ( ! function_exists( 'mai_cyber_gallery_load_slider_navigation_secondary' ) ) {
	/**
	 * Load the secondary Slider navigation value using the 1.x callback name.
	 *
	 * @since 0.6.0
	 * @deprecated 2.0.0 Use cyber_gallery_mai_load_slider_navigation_secondary().
	 *
	 * @param mixed               $value   Loaded/default field value.
	 * @param int|string          $post_id ACF post or block ID.
	 * @param array<string,mixed> $field   ACF field.
	 *
	 * @return mixed
	 */
	function mai_cyber_gallery_load_slider_navigation_secondary( $value, $post_id, $field ) {
		return cyber_gallery_mai_load_slider_navigation_secondary( $value, $post_id, $field );
	}
}

if ( ! function_exists( 'mai_cyber_gallery_load_logo_padding_axis' ) ) {
	/**
	 * Load one logo-padding axis using the 1.x callback name.
	 *
	 * @since 0.8.3
	 * @deprecated 2.0.0 Use cyber_gallery_mai_load_logo_padding_axis().
	 *
	 * @param mixed               $value   Loaded/default field value.
	 * @param int|string          $post_id ACF post or block ID.
	 * @param array<string,mixed> $field   ACF field.
	 *
	 * @return mixed
	 */
	function mai_cyber_gallery_load_logo_padding_axis( $value, $post_id, $field ) {
		return cyber_gallery_mai_load_logo_padding_axis( $value, $post_id, $field );
	}
}

if ( ! function_exists( 'mai_cyber_gallery_prepare_slider_navigation_field' ) ) {
	/**
	 * Prepare the Slider navigation field using the 1.x callback name.
	 *
	 * @since 0.6.0
	 * @deprecated 2.0.0 Use cyber_gallery_mai_prepare_slider_navigation_field().
	 *
	 * @param array<string,mixed> $field ACF field.
	 *
	 * @return array<string,mixed>
	 */
	function mai_cyber_gallery_prepare_slider_navigation_field( $field ): array {
		return cyber_gallery_mai_prepare_slider_navigation_field( $field );
	}
}

if ( ! function_exists( 'mai_cyber_gallery_register_block' ) ) {
	/**
	 * Register block metadata using the 1.x callback name.
	 *
	 * @since 0.1.0
	 * @deprecated 2.0.0 Use cyber_gallery_mai_register_block().
	 *
	 * @return void
	 */
	function mai_cyber_gallery_register_block(): void {
		cyber_gallery_mai_register_block();
	}
}

if ( ! function_exists( 'mai_cyber_gallery_render_block' ) ) {
	/**
	 * Render a gallery block using the 1.x callback name.
	 *
	 * @since 0.1.0
	 * @deprecated 2.0.0 Use cyber_gallery_mai_render_block().
	 *
	 * @param array<string,mixed> $block      Block settings and attributes.
	 * @param string              $content    Inner block content.
	 * @param bool                $is_preview Whether this is an editor preview.
	 * @param int                 $post_id    Current post ID.
	 *
	 * @return void
	 */
	function mai_cyber_gallery_render_block( $block, $content = '', $is_preview = false, $post_id = 0 ): void {
		cyber_gallery_mai_render_block( $block, $content, $is_preview, $post_id );
	}
}

if ( ! function_exists( 'mai_cyber_gallery_prepare_columns_clone_field' ) ) {
	/**
	 * Prepare cloned Mai column fields using the 1.x callback name.
	 *
	 * @since 1.0.0
	 * @deprecated 2.0.0 Use cyber_gallery_mai_prepare_columns_clone_field().
	 *
	 * @param array<string,mixed> $field       Field being cloned.
	 * @param array<string,mixed> $clone_field Clone field settings.
	 *
	 * @return array<string,mixed>
	 */
	function mai_cyber_gallery_prepare_columns_clone_field( $field, $clone_field ): array {
		return cyber_gallery_mai_prepare_columns_clone_field( $field, $clone_field );
	}
}

if ( ! function_exists( 'mai_cyber_gallery_get_validation_block_id' ) ) {
	/**
	 * Read an ACF validation block ID using the 1.x helper name.
	 *
	 * @since 1.0.0
	 * @deprecated 2.0.0 Use cyber_gallery_mai_get_validation_block_id().
	 *
	 * @param mixed $input ACF input name.
	 *
	 * @return string|false
	 */
	function mai_cyber_gallery_get_validation_block_id( $input ) {
		return cyber_gallery_mai_get_validation_block_id( $input );
	}
}

if ( ! function_exists( 'mai_cyber_gallery_get_validation_context_value' ) ) {
	/**
	 * Read an ACF validation value using the 1.x helper name.
	 *
	 * @since 1.0.0
	 * @deprecated 2.0.0 Use cyber_gallery_mai_get_validation_context_value().
	 *
	 * @param string $name  Field name.
	 * @param mixed  $input ACF input name.
	 *
	 * @return string
	 */
	function mai_cyber_gallery_get_validation_context_value( string $name, $input ): string {
		return cyber_gallery_mai_get_validation_context_value( $name, $input );
	}
}

if ( ! function_exists( 'mai_cyber_gallery_is_required_field_active' ) ) {
	/**
	 * Check conditional required state using the 1.x helper name.
	 *
	 * @since 1.0.0
	 * @deprecated 2.0.0 Use cyber_gallery_mai_is_required_field_active().
	 *
	 * @param array<string,mixed> $field ACF field settings.
	 * @param mixed               $input ACF input name.
	 *
	 * @return bool
	 */
	function mai_cyber_gallery_is_required_field_active( array $field, $input ): bool {
		return cyber_gallery_mai_is_required_field_active( $field, $input );
	}
}

if ( ! function_exists( 'mai_cyber_gallery_validate_conditional_required_value' ) ) {
	/**
	 * Validate conditional required state using the 1.x callback name.
	 *
	 * @since 1.0.0
	 * @deprecated 2.0.0 Use cyber_gallery_mai_validate_conditional_required_value().
	 *
	 * @param bool|string         $valid Whether the current value is valid.
	 * @param mixed               $value Submitted field value.
	 * @param array<string,mixed> $field ACF field settings.
	 * @param mixed               $input ACF input name.
	 *
	 * @return bool|string
	 */
	function mai_cyber_gallery_validate_conditional_required_value( $valid, $value, $field, $input ) {
		return cyber_gallery_mai_validate_conditional_required_value( $valid, $value, $field, $input );
	}
}

if ( ! function_exists( 'mai_cyber_gallery_validate_slide_button_link' ) ) {
	/**
	 * Validate a Slider CTA link using the 1.x callback name.
	 *
	 * @since 0.3.0
	 * @deprecated 2.0.0 Use cyber_gallery_mai_validate_slide_button_link().
	 *
	 * @param bool|string         $valid Whether the current value is valid.
	 * @param mixed               $value Submitted Link field value.
	 * @param array<string,mixed> $field ACF field settings.
	 * @param string              $input Input name.
	 *
	 * @return bool|string
	 */
	function mai_cyber_gallery_validate_slide_button_link( $valid, $value, $field, $input ) {
		return cyber_gallery_mai_validate_slide_button_link( $valid, $value, $field, $input );
	}
}

if ( ! function_exists( 'mai_cyber_gallery_register_field_group' ) ) {
	/**
	 * Register the local ACF fields using the 1.x callback name.
	 *
	 * @since 0.1.0
	 * @deprecated 2.0.0 Use cyber_gallery_mai_register_field_group().
	 *
	 * @return void
	 */
	function mai_cyber_gallery_register_field_group(): void {
		cyber_gallery_mai_register_field_group();
	}
}

if ( ! function_exists( 'mai_get_cyber_gallery' ) ) {
	/**
	 * Get gallery markup using the 1.x function name.
	 *
	 * @since 0.1.0
	 * @deprecated 2.0.0 Use cyber_gallery_mai_get_gallery().
	 *
	 * @param array<string,mixed> $args Gallery arguments.
	 *
	 * @return string
	 */
	function mai_get_cyber_gallery( $args = array() ): string {
		return cyber_gallery_mai_get_gallery( $args );
	}
}

if ( ! function_exists( 'mai_do_cyber_gallery' ) ) {
	/**
	 * Display a gallery using the 1.x function name.
	 *
	 * @since 0.1.0
	 * @deprecated 2.0.0 Use cyber_gallery_mai_do_gallery().
	 *
	 * @param array<string,mixed> $args Gallery arguments.
	 *
	 * @return void
	 */
	function mai_do_cyber_gallery( $args = array() ): void {
		cyber_gallery_mai_do_gallery( $args );
	}
}

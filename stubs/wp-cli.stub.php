<?php
/**
 * WP-CLI declarations used only for static analysis.
 *
 * @package CyberGalleryMai
 */

/**
 * Minimal WP-CLI facade declaration.
 */
class WP_CLI {
	/**
	 * Register a WP-CLI command.
	 *
	 * @param string                $name             Command name.
	 * @param callable|class-string $command_callback Command callback or class.
	 *
	 * @return bool
	 */
	public static function add_command( string $name, callable|string $command_callback ): bool {}

	/**
	 * Write an informational message.
	 *
	 * @param string $message Message.
	 *
	 * @return void
	 */
	public static function log( string $message ): void {}

	/**
	 * Write a warning message.
	 *
	 * @param string $message Message.
	 *
	 * @return void
	 */
	public static function warning( string $message ): void {}

	/**
	 * Write a success message.
	 *
	 * @param string $message Message.
	 *
	 * @return void
	 */
	public static function success( string $message ): void {}

	/**
	 * Halt command execution with an error.
	 *
	 * @param string $message Message.
	 *
	 * @return never
	 */
	public static function error( string $message ): never {}

	/**
	 * Ask the operator to confirm a destructive operation.
	 *
	 * @param string $message Message.
	 *
	 * @return void
	 */
	public static function confirm( string $message ): void {}
}

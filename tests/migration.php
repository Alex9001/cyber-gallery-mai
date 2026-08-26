<?php
/**
 * Regression coverage for recursive 1.x block-name migration.
 *
 * @package CyberGalleryMai
 */

declare( strict_types=1 );

define( 'ABSPATH', __DIR__ . '/' );

$cyber_gallery_mai_test_cli_logs       = array();
$cyber_gallery_mai_test_cli_warnings   = array();
$cyber_gallery_mai_test_cli_successes  = array();
$cyber_gallery_mai_test_post_ids       = array();
$cyber_gallery_mai_test_post_content   = array();
$cyber_gallery_mai_test_post_failures  = array();
$cyber_gallery_mai_test_widgets        = array();
$cyber_gallery_mai_test_widget_partial = array();

/**
 * Minimal WP-CLI test double used while loading the command.
 */
class WP_CLI {
	/**
	 * Ignore command registration in the standalone test.
	 *
	 * @return void
	 */
	public static function add_command(): void {}

	/**
	 * Capture an informational message.
	 *
	 * @param string $message Message.
	 *
	 * @return void
	 */
	public static function log( $message ): void {
		global $cyber_gallery_mai_test_cli_logs;
		$cyber_gallery_mai_test_cli_logs[] = (string) $message;
	}

	/**
	 * Capture a warning.
	 *
	 * @param string $message Message.
	 *
	 * @return void
	 */
	public static function warning( $message ): void {
		global $cyber_gallery_mai_test_cli_warnings;
		$cyber_gallery_mai_test_cli_warnings[] = (string) $message;
	}

	/**
	 * Capture a success message.
	 *
	 * @param string $message Message.
	 *
	 * @return void
	 */
	public static function success( $message ): void {
		global $cyber_gallery_mai_test_cli_successes;
		$cyber_gallery_mai_test_cli_successes[] = (string) $message;
	}

	/**
	 * Ignore confirmation in the standalone test.
	 *
	 * @return void
	 */
	public static function confirm(): void {}

	/**
	 * Turn a command failure into a test failure.
	 *
	 * @param string $message Failure message.
	 *
	 * @throws RuntimeException When the production command reports an error.
	 *
	 * @return void
	 */
	public static function error( $message ): void {
		throw new RuntimeException( (string) $message );
	}
}

/**
 * Minimal WordPress error test double.
 */
class WP_Error {
	/**
	 * Error message.
	 *
	 * @var string
	 */
	private string $message;

	/**
	 * Construct an error.
	 *
	 * @param string $message Error message.
	 */
	public function __construct( string $message ) {
		$this->message = $message;
	}

	/**
	 * Return the error message.
	 *
	 * @return string
	 */
	public function get_error_message(): string {
		return $this->message;
	}
}

/**
 * Minimal post-query fixture.
 */
class WP_Query {
	/**
	 * Matching post IDs.
	 *
	 * @var list<int>
	 */
	public array $posts;

	/**
	 * Number of pages in this fixture.
	 *
	 * @var int
	 */
	public int $max_num_pages = 1;

	/**
	 * Construct the current query page.
	 *
	 * @param array<string,mixed> $args Query arguments.
	 */
	public function __construct( array $args ) {
		global $cyber_gallery_mai_test_post_ids;

		$this->posts = 1 === ( $args['paged'] ?? 1 ) ? $cyber_gallery_mai_test_post_ids : array();
	}
}

$cyber_gallery_mai_test_parsed_documents = array();
$cyber_gallery_mai_test_serialized       = array();

/**
 * Return the parsed fixture associated with test content.
 *
 * @param string $content Content token.
 *
 * @return list<array<string,mixed>>
 */
function parse_blocks( $content ): array {
	global $cyber_gallery_mai_test_parsed_documents;

	return $cyber_gallery_mai_test_parsed_documents[ $content ] ?? array();
}

/**
 * Capture a serialized block tree and return a stable content token.
 *
 * @param list<array<string,mixed>> $blocks Parsed blocks.
 *
 * @return string
 */
function serialize_blocks( $blocks ): string {
	global $cyber_gallery_mai_test_parsed_documents, $cyber_gallery_mai_test_serialized;

	$content = 'migrated-content';

	$cyber_gallery_mai_test_serialized = $blocks;

	$cyber_gallery_mai_test_parsed_documents[ $content ] = $blocks;

	return $content;
}

/**
 * Normalize a fixture integer like WordPress.
 *
 * @param mixed $value Candidate value.
 *
 * @return int
 */
function absint( $value ): int {
	return abs( (int) $value );
}

/**
 * Normalize a fixture key like WordPress.
 *
 * @param mixed $value Candidate value.
 *
 * @return string
 */
function sanitize_key( $value ): string {
	$value = strtolower( (string) $value );
	return preg_replace( '/[^a-z0-9_\-]/', '', $value ) ?? '';
}

/**
 * Return public post types for the CLI fixture.
 *
 * @return array<string,string>
 */
function get_post_types(): array {
	return array( 'post' => 'post' );
}

/**
 * Confirm a fixture post type exists.
 *
 * @param string $post_type Post type.
 *
 * @return bool
 */
function post_type_exists( $post_type ): bool {
	return 'post' === $post_type;
}

/**
 * Return raw fixture post content.
 *
 * @param string $field   Requested field.
 * @param int    $post_id Post ID.
 * @param string $context Value context.
 *
 * @return string
 */
function get_post_field( $field, $post_id, $context ): string {
	global $cyber_gallery_mai_test_post_content;
	unset( $field, $context );

	return $cyber_gallery_mai_test_post_content[ $post_id ] ?? '';
}

/**
 * Persist or fail a fixture post update.
 *
 * @param array<string,mixed> $postarr Post data.
 * @param bool                $wp_error Whether to return a WordPress error.
 *
 * @return int|WP_Error
 */
function wp_update_post( $postarr, $wp_error = false ) {
	global $cyber_gallery_mai_test_post_content, $cyber_gallery_mai_test_post_failures;
	unset( $wp_error );

	$post_id = (int) ( $postarr['ID'] ?? 0 );

	if ( in_array( $post_id, $cyber_gallery_mai_test_post_failures, true ) ) {
		return new WP_Error( 'Fixture write failure.' );
	}

	$cyber_gallery_mai_test_post_content[ $post_id ] = (string) ( $postarr['post_content'] ?? '' );
	return $post_id;
}

/**
 * Check a fixture value for a WordPress error.
 *
 * @param mixed $value Candidate value.
 *
 * @return bool
 */
function is_wp_error( $value ): bool {
	return $value instanceof WP_Error;
}

/**
 * Preserve content slashing in the standalone fixture.
 *
 * @param string $content Content.
 *
 * @return string
 */
function wp_slash( $content ): string {
	return $content;
}

/**
 * Ignore cache clearing in the standalone fixture.
 *
 * @return void
 */
function clean_post_cache(): void {}

/**
 * Return the block-widget fixture.
 *
 * @param string $option        Option name.
 * @param mixed  $default_value Default value.
 *
 * @return mixed
 */
function get_option( $option, $default_value = false ) {
	global $cyber_gallery_mai_test_widgets;
	unset( $option );

	return $cyber_gallery_mai_test_widgets ? $cyber_gallery_mai_test_widgets : $default_value;
}

/**
 * Persist either all or selected block-widget fixture rows.
 *
 * @param string                 $option Option name.
 * @param array<array-key,mixed> $value  Option value.
 *
 * @return bool
 */
function update_option( $option, $value ): bool {
	global $cyber_gallery_mai_test_widgets, $cyber_gallery_mai_test_widget_partial;
	unset( $option );

	if ( $cyber_gallery_mai_test_widget_partial ) {
		foreach ( $cyber_gallery_mai_test_widget_partial as $number ) {
			$cyber_gallery_mai_test_widgets[ $number ] = $value[ $number ];
		}

		return true;
	}

	$cyber_gallery_mai_test_widgets = $value;
	return true;
}

require dirname( __DIR__ ) . '/includes/class-cyber-gallery-mai-cli-command.php';

$legacy_data = array(
	'layout'                   => 'comparison',
	'_layout'                  => 'mai_cyber_gallery_layout',
	'comparison_before_image'  => '41',
	'_comparison_before_image' => 'mai_cyber_gallery_comparison_before_image',
);
$fixture     = array(
	array(
		'blockName'    => 'acf/mai-cyber-gallery',
		'attrs'        => array(
			'name'   => 'acf/mai-cyber-gallery',
			'anchor' => 'preserved-anchor',
			'data'   => $legacy_data,
		),
		'innerBlocks'  => array(),
		'innerHTML'    => '',
		'innerContent' => array(),
	),
	array(
		'blockName'   => 'core/group',
		'attrs'       => array( 'className' => 'preserved-group' ),
		'innerBlocks' => array(
			array(
				'blockName'   => 'acf/mai-cyber-gallery',
				'innerBlocks' => array(),
			),
		),
	),
	array(
		'blockName'   => 'acf/cyber-gallery-mai',
		'attrs'       => array(
			'name' => 'acf/cyber-gallery-mai',
			'data' => array( 'layout' => 'grid' ),
		),
		'innerBlocks' => array(),
	),
);

$cyber_gallery_mai_test_parsed_documents['fixture-content'] = $fixture;

$command   = new Cyber_Gallery_Mai_CLI_Command();
$migration = $command->migrate_content( 'fixture-content' );

if ( 2 !== $migration['count'] || 'migrated-content' !== $migration['content'] ) {
	throw new RuntimeException( 'The command did not migrate both top-level and nested legacy blocks.' );
}

if (
	'acf/cyber-gallery-mai' !== $cyber_gallery_mai_test_serialized[0]['blockName']
	|| 'acf/cyber-gallery-mai' !== $cyber_gallery_mai_test_serialized[0]['attrs']['name']
	|| 'preserved-anchor' !== $cyber_gallery_mai_test_serialized[0]['attrs']['anchor']
	|| $legacy_data !== $cyber_gallery_mai_test_serialized[0]['attrs']['data']
	|| 'acf/cyber-gallery-mai' !== $cyber_gallery_mai_test_serialized[1]['innerBlocks'][0]['blockName']
	|| 'acf/cyber-gallery-mai' !== $cyber_gallery_mai_test_serialized[1]['innerBlocks'][0]['attrs']['name']
	|| $fixture[2] !== $cyber_gallery_mai_test_serialized[2]
) {
	throw new RuntimeException( 'Migration altered saved data or failed to canonicalize a parsed block name.' );
}

$second_pass = $command->migrate_content( $migration['content'] );
if ( 0 !== $second_pass['count'] || $migration['content'] !== $second_pass['content'] ) {
	throw new RuntimeException( 'Block-name migration was not idempotent.' );
}

$legacy_content = 'fixture-content acf/mai-cyber-gallery';
$cyber_gallery_mai_test_parsed_documents[ $legacy_content ] = $fixture;

$post_method                          = ( new ReflectionClass( $command ) )->getMethod( 'migrate_posts' );
$cyber_gallery_mai_test_post_ids      = array( 101, 102 );
$cyber_gallery_mai_test_post_content  = array(
	101 => $legacy_content,
	102 => $legacy_content,
);
$cyber_gallery_mai_test_post_failures = array( 102 );
$post_results                         = $post_method->invoke( $command, array( 'post' ), true );

if (
	4 !== $post_results['blocks']
	|| 2 !== $post_results['records']
	|| 2 !== $post_results['updated_blocks']
	|| 1 !== $post_results['updated_records']
	|| 2 !== $post_results['failed_blocks']
	|| 1 !== $post_results['failed_records']
	|| 'migrated-content' !== $cyber_gallery_mai_test_post_content[101]
	|| $legacy_content !== $cyber_gallery_mai_test_post_content[102]
) {
	throw new RuntimeException( 'Post migration did not separate successful and failed writes truthfully.' );
}

$widget_method                         = ( new ReflectionClass( $command ) )->getMethod( 'migrate_widgets' );
$cyber_gallery_mai_test_widgets        = array(
	2              => array( 'content' => $legacy_content ),
	3              => array( 'content' => $legacy_content ),
	'_multiwidget' => 1,
);
$cyber_gallery_mai_test_widget_partial = array( 2 );
$widget_results                        = $widget_method->invoke( $command, true );

if (
	4 !== $widget_results['blocks']
	|| 2 !== $widget_results['records']
	|| 2 !== $widget_results['updated_blocks']
	|| 1 !== $widget_results['updated_records']
	|| 2 !== $widget_results['failed_blocks']
	|| 1 !== $widget_results['failed_records']
	|| 'migrated-content' !== $cyber_gallery_mai_test_widgets[2]['content']
	|| $legacy_content !== $cyber_gallery_mai_test_widgets[3]['content']
) {
	throw new RuntimeException( 'Widget migration did not verify and account for each persisted widget.' );
}

$cyber_gallery_mai_test_cli_successes  = array();
$cyber_gallery_mai_test_post_ids       = array( 202 );
$cyber_gallery_mai_test_post_content   = array( 202 => $legacy_content );
$cyber_gallery_mai_test_post_failures  = array( 202 );
$cyber_gallery_mai_test_widget_partial = array();
$expected_failure                      = false;

try {
	$command->migrate_blocks(
		array(),
		array(
			'execute'      => '1',
			'yes'          => '1',
			'post_type'    => 'post',
			'skip-widgets' => '1',
		)
	);
} catch ( RuntimeException $error ) {
	$expected_failure = str_contains( $error->getMessage(), '2 block(s) across 1 record(s) failed' );
}

if ( ! $expected_failure || $cyber_gallery_mai_test_cli_successes ) {
	throw new RuntimeException( 'A failed execute migration did not terminate nonzero without a success result.' );
}

echo "Verified recursive idempotency, truthful write accounting, readback, and nonzero failure.\n";

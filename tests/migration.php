<?php
/**
 * Regression coverage for recursive 1.x block-name migration.
 *
 * @package CyberGalleryMai
 */

declare( strict_types=1 );

define( 'ABSPATH', __DIR__ . '/' );

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

echo "Verified recursive, data-preserving, idempotent block-name migration.\n";

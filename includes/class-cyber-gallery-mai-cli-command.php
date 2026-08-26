<?php
/**
 * WP-CLI content migration command.
 *
 * @package CyberGalleryMai
 * @since 2.0.0
 */

// Prevent direct file access and accidental web execution.
defined( 'ABSPATH' ) || die;

/**
 * Convert saved 1.x block names to the canonical 2.0 block name.
 *
 * The command is deliberately dry-run by default. The hidden legacy block
 * remains registered indefinitely, so migration can be scheduled separately
 * from the plugin upgrade and safely repeated.
 *
 * @since 2.0.0
 */
final class Cyber_Gallery_Mai_CLI_Command {
	/**
	 * Migrate legacy Cyber Gallery block comments.
	 *
	 * ## OPTIONS
	 *
	 * [--post_type=<post-types>]
	 * : Comma-separated post types. Defaults to public types plus block, pattern,
	 *   navigation, and template storage types.
	 *
	 * [--execute]
	 * : Save changes. Without this flag, the command is a read-only dry run.
	 *
	 * [--yes]
	 * : Skip the confirmation prompt used with --execute.
	 *
	 * [--skip-widgets]
	 * : Do not inspect block-widget content in the widget_block option.
	 *
	 * ## EXAMPLES
	 *
	 *     wp cyber-gallery-mai migrate-blocks
	 *     wp cyber-gallery-mai migrate-blocks --execute --yes
	 *     wp cyber-gallery-mai migrate-blocks --post_type=post,page,wp_block --execute
	 *
	 * @since 2.0.0
	 *
	 * @param array<int,string>    $args       Positional arguments.
	 * @param array<string,string> $assoc_args Named arguments.
	 *
	 * @return void
	 */
	public function migrate_blocks( array $args, array $assoc_args ): void {
		unset( $args );

		$execute      = array_key_exists( 'execute', $assoc_args );
		$skip_widgets = array_key_exists( 'skip-widgets', $assoc_args );
		$post_types   = $this->get_post_types( $assoc_args['post_type'] ?? '' );

		if ( ! $post_types ) {
			WP_CLI::error( 'No valid post types were selected.' );
		}

		if ( $execute && ! array_key_exists( 'yes', $assoc_args ) ) {
			WP_CLI::confirm(
				'Replace legacy acf/mai-cyber-gallery block names with acf/cyber-gallery-mai?'
			);
		}

		WP_CLI::log(
			sprintf(
				'%s post types: %s',
				$execute ? 'Migrating' : 'Scanning',
				implode( ', ', $post_types )
			)
		);

		$results = $this->migrate_posts( $post_types, $execute );

		if ( ! $skip_widgets ) {
			$widget_results              = $this->migrate_widgets( $execute );
			$results['blocks']          += $widget_results['blocks'];
			$results['records']         += $widget_results['records'];
			$results['updated_records'] += $widget_results['updated_records'];
		}

		$message = sprintf(
			'%1$s %2$d legacy block(s) across %3$d content record(s); %4$d record(s) %5$s.',
			$execute ? 'Migrated' : 'Found',
			$results['blocks'],
			$results['records'],
			$results['updated_records'],
			$execute ? 'updated' : 'would be updated'
		);

		if ( $execute ) {
			WP_CLI::success( $message );
			return;
		}

		WP_CLI::log( $message );
		WP_CLI::log( 'Dry run only. Repeat with --execute and confirm, or add --yes.' );
	}

	/**
	 * Get the post types inspected by the command.
	 *
	 * @since 2.0.0
	 *
	 * @param string $requested Comma-separated requested post types.
	 *
	 * @return list<string>
	 */
	private function get_post_types( string $requested ): array {
		if ( $requested ) {
			$post_types = array_map( 'sanitize_key', explode( ',', $requested ) );
		} else {
			$post_types = array_merge(
				array_values(
					array_diff(
						get_post_types( array( 'public' => true ), 'names' ),
						array( 'attachment' )
					)
				),
				array( 'wp_block', 'wp_navigation', 'wp_template', 'wp_template_part' )
			);
		}

		$post_types = array_values( array_unique( array_filter( $post_types ) ) );

		return array_values(
			array_filter(
				$post_types,
				static fn( string $post_type ): bool => post_type_exists( $post_type )
			)
		);
	}

	/**
	 * Migrate matching posts in bounded batches.
	 *
	 * @since 2.0.0
	 *
	 * @param array<int,string> $post_types Post types to inspect.
	 * @param bool              $execute    Whether to persist changes.
	 *
	 * @return array{blocks:int,records:int,updated_records:int}
	 */
	private function migrate_posts( array $post_types, bool $execute ): array {
		$results = array(
			'blocks'          => 0,
			'records'         => 0,
			'updated_records' => 0,
		);
		$paged   = 1;

		do {
			$query = new WP_Query(
				array(
					'post_type'              => $post_types,
					'post_status'            => 'any',
					'posts_per_page'         => 100,
					'paged'                  => $paged,
					'orderby'                => 'ID',
					'order'                  => 'ASC',
					'fields'                 => 'ids',
					'no_found_rows'          => false,
					'cache_results'          => false,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
				)
			);

			foreach ( $query->posts as $post_id ) {
				$post_id = absint( $post_id );
				$content = (string) get_post_field( 'post_content', $post_id, 'raw' );

				if ( ! str_contains( $content, 'acf/mai-cyber-gallery' ) ) {
					continue;
				}

				$migration = $this->migrate_content( $content );
				if ( ! $migration['count'] ) {
					continue;
				}

				++$results['records'];
				$results['blocks'] += $migration['count'];

				if ( ! $execute ) {
					WP_CLI::log( sprintf( 'Would update post %d (%d block(s)).', $post_id, $migration['count'] ) );
					++$results['updated_records'];
					continue;
				}

				$updated = wp_update_post(
					array(
						'ID'           => $post_id,
						'post_content' => wp_slash( $migration['content'] ),
					),
					true
				);

				if ( is_wp_error( $updated ) ) {
					WP_CLI::warning(
						sprintf( 'Post %1$d was not updated: %2$s', $post_id, $updated->get_error_message() )
					);
					continue;
				}

				clean_post_cache( $post_id );
				++$results['updated_records'];
			}

			++$paged;
		} while ( $paged <= (int) $query->max_num_pages );

		return $results;
	}

	/**
	 * Migrate block-widget option content.
	 *
	 * @since 2.0.0
	 *
	 * @param bool $execute Whether to persist changes.
	 *
	 * @return array{blocks:int,records:int,updated_records:int}
	 */
	private function migrate_widgets( bool $execute ): array {
		$results = array(
			'blocks'          => 0,
			'records'         => 0,
			'updated_records' => 0,
		);
		$widgets = get_option( 'widget_block', array() );

		if ( ! is_array( $widgets ) ) {
			return $results;
		}

		foreach ( $widgets as $number => $widget ) {
			if (
				! is_array( $widget )
				|| ! isset( $widget['content'] )
				|| ! is_string( $widget['content'] )
				|| ! str_contains( $widget['content'], 'acf/mai-cyber-gallery' )
			) {
				continue;
			}

			$migration = $this->migrate_content( $widget['content'] );
			if ( ! $migration['count'] ) {
				continue;
			}

			$widgets[ $number ]['content'] = $migration['content'];
			$results['blocks']            += $migration['count'];
			++$results['records'];
			++$results['updated_records'];

			WP_CLI::log(
				sprintf(
					'%1$s block widget %2$s (%3$d block(s)).',
					$execute ? 'Updated' : 'Would update',
					(string) $number,
					$migration['count']
				)
			);
		}

		if ( $execute && $results['updated_records'] ) {
			update_option( 'widget_block', $widgets );
		}

		return $results;
	}

	/**
	 * Convert every legacy gallery in one serialized block document.
	 *
	 * @since 2.0.0
	 *
	 * @param string $content Serialized block content.
	 *
	 * @return array{content:string,count:int}
	 */
	public function migrate_content( string $content ): array {
		$blocks = parse_blocks( $content );
		$count  = $this->migrate_parsed_blocks( $blocks );

		if ( ! $count ) {
			return array(
				'content' => $content,
				'count'   => 0,
			);
		}

		$migrated = serialize_blocks( $blocks );
		$verified = parse_blocks( $migrated );

		if ( $this->count_legacy_blocks( $verified ) ) {
			WP_CLI::error( 'Migration validation failed: legacy block names remain after serialization.' );
		}

		return array(
			'content' => $migrated,
			'count'   => $count,
		);
	}

	/**
	 * Recursively rename parsed legacy blocks.
	 *
	 * @since 2.0.0
	 *
	 * @param list<array<string,mixed>> $blocks Parsed blocks, updated in place.
	 *
	 * @return int Number of blocks renamed.
	 */
	private function migrate_parsed_blocks( array &$blocks ): int {
		$count = 0;

		foreach ( $blocks as &$block ) {
			if ( ! is_array( $block ) ) {
				continue;
			}

			if ( 'acf/mai-cyber-gallery' === ( $block['blockName'] ?? '' ) ) {
				$block['blockName']     = 'acf/cyber-gallery-mai';
				$block['attrs']         = isset( $block['attrs'] ) && is_array( $block['attrs'] )
					? $block['attrs']
					: array();
				$block['attrs']['name'] = 'acf/cyber-gallery-mai';
				++$count;
			}

			if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
				$count += $this->migrate_parsed_blocks( $block['innerBlocks'] );
			}
		}
		unset( $block );

		return $count;
	}

	/**
	 * Count remaining legacy blocks recursively.
	 *
	 * @since 2.0.0
	 *
	 * @param list<array<string,mixed>> $blocks Parsed blocks.
	 *
	 * @return int
	 */
	private function count_legacy_blocks( array $blocks ): int {
		$count = 0;

		foreach ( $blocks as $block ) {
			if ( ! is_array( $block ) ) {
				continue;
			}

			$count += (int) ( 'acf/mai-cyber-gallery' === ( $block['blockName'] ?? '' ) );

			if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
				$count += $this->count_legacy_blocks( $block['innerBlocks'] );
			}
		}

		return $count;
	}
}

WP_CLI::add_command(
	'cyber-gallery-mai migrate-blocks',
	array( new Cyber_Gallery_Mai_CLI_Command(), 'migrate_blocks' )
);

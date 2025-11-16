<?php
/**
 * Heading Structure Analyzer (H1-H6)
 *
 * @package Broken Link Checker
 * @since 2.0.0
 *
 * ModuleID: heading-structure
 * ModuleCategory: analyzer
 * ModuleContext: on-demand
 * ModuleLazyInit: true
 * ModuleClassName: blcHeadingAnalyzer
 * ModulePriority: 100
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class blcHeadingAnalyzer extends blcModule {

	var $module_id = 'heading-structure';

	/**
	 * Analyze heading structure for a single post
	 *
	 * @param int $post_id Post ID to analyze
	 * @return array Analysis results
	 */
	public function analyze_post( $post_id ) {
		global $wpdb;

		$post = get_post( $post_id );
		if ( ! $post || $post->post_status !== 'publish' ) {
			return array();
		}

		$content = $post->post_content;

		// Apply filters to simulate how content is displayed
		$content = apply_filters( 'the_content', $content );

		// Extract all headings
		$headings = $this->extract_headings( $content );

		// Delete old heading records for this post
		$wpdb->delete(
			"{$wpdb->prefix}blc_headings",
			array( 'post_id' => $post_id ),
			array( '%d' )
		);

		// Analyze and save headings
		$issues = array();
		$h1_count = 0;
		$position = 0;

		foreach ( $headings as $heading ) {
			$position++;

			// Check for H1 issues
			if ( $heading['level'] === 1 ) {
				$h1_count++;
			}

			// Check hierarchy
			$hierarchy_valid = $this->check_hierarchy( $headings, $position - 1 );

			// Save heading to database
			$wpdb->insert(
				"{$wpdb->prefix}blc_headings",
				array(
					'post_id'         => $post_id,
					'level'           => $heading['level'],
					'text'            => $heading['text'],
					'text_length'     => mb_strlen( $heading['text'] ),
					'position'        => $position,
					'hierarchy_valid' => $hierarchy_valid ? 1 : 0,
					'last_checked'    => current_time( 'mysql' ),
				),
				array( '%d', '%d', '%s', '%d', '%d', '%d', '%s' )
			);
		}

		// Analyze issues
		if ( $h1_count === 0 ) {
			$issues[] = 'Missing H1 heading';
		} elseif ( $h1_count > 1 ) {
			$issues[] = sprintf( 'Multiple H1 headings found (%d)', $h1_count );
		}

		if ( empty( $headings ) ) {
			$issues[] = 'No headings found in content';
		}

		return array(
			'post_id'       => $post_id,
			'heading_count' => count( $headings ),
			'h1_count'      => $h1_count,
			'issues'        => $issues,
			'headings'      => $headings,
		);
	}

	/**
	 * Extract all headings from HTML content
	 *
	 * @param string $content HTML content
	 * @return array Array of headings with level and text
	 */
	private function extract_headings( $content ) {
		$headings = array();

		// Match all heading tags (H1-H6)
		$pattern = '/<h([1-6])[^>]*>(.*?)<\/h[1-6]>/is';

		if ( preg_match_all( $pattern, $content, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $match ) {
				$level = (int) $match[1];
				$text = strip_tags( $match[2] );
				$text = html_entity_decode( $text, ENT_QUOTES, 'UTF-8' );
				$text = trim( $text );

				if ( ! empty( $text ) ) {
					$headings[] = array(
						'level' => $level,
						'text'  => $text,
					);
				}
			}
		}

		return $headings;
	}

	/**
	 * Check if heading hierarchy is valid
	 *
	 * A hierarchy is valid if headings don't skip levels (e.g., H2 -> H4)
	 *
	 * @param array $all_headings All headings in the content
	 * @param int $current_index Current heading index
	 * @return bool True if hierarchy is valid
	 */
	private function check_hierarchy( $all_headings, $current_index ) {
		if ( $current_index === 0 ) {
			return true; // First heading is always valid
		}

		$current_level = $all_headings[ $current_index ]['level'];
		$previous_level = $all_headings[ $current_index - 1 ]['level'];

		// Check if we're skipping levels (e.g., H2 -> H4)
		if ( $current_level > $previous_level + 1 ) {
			return false;
		}

		return true;
	}

	/**
	 * Get heading structure for a post
	 *
	 * @param int $post_id Post ID
	 * @return array Heading structure
	 */
	public function get_post_headings( $post_id ) {
		global $wpdb;

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}blc_headings WHERE post_id = %d ORDER BY position ASC",
				$post_id
			),
			ARRAY_A
		);
	}

	/**
	 * Get posts with heading issues
	 *
	 * @param string $issue_type Type of issue (no_h1, multiple_h1, invalid_hierarchy)
	 * @param int $limit Number of results to return
	 * @return array Posts with issues
	 */
	public function get_posts_with_issues( $issue_type = 'no_h1', $limit = 50 ) {
		global $wpdb;

		$results = array();

		switch ( $issue_type ) {
			case 'no_h1':
				// Posts with no H1 heading
				$query = "
					SELECT DISTINCT p.ID, p.post_title, p.post_type
					FROM {$wpdb->posts} p
					LEFT JOIN {$wpdb->prefix}blc_headings h ON p.ID = h.post_id AND h.level = 1
					WHERE p.post_status = 'publish'
					AND p.post_type IN ('post', 'page')
					AND h.heading_id IS NULL
					LIMIT %d
				";
				$results = $wpdb->get_results( $wpdb->prepare( $query, $limit ), ARRAY_A );
				break;

			case 'multiple_h1':
				// Posts with multiple H1 headings
				$query = "
					SELECT p.ID, p.post_title, p.post_type, COUNT(h.heading_id) as h1_count
					FROM {$wpdb->posts} p
					INNER JOIN {$wpdb->prefix}blc_headings h ON p.ID = h.post_id AND h.level = 1
					WHERE p.post_status = 'publish'
					AND p.post_type IN ('post', 'page')
					GROUP BY p.ID
					HAVING h1_count > 1
					LIMIT %d
				";
				$results = $wpdb->get_results( $wpdb->prepare( $query, $limit ), ARRAY_A );
				break;

			case 'invalid_hierarchy':
				// Posts with invalid heading hierarchy
				$query = "
					SELECT DISTINCT p.ID, p.post_title, p.post_type
					FROM {$wpdb->posts} p
					INNER JOIN {$wpdb->prefix}blc_headings h ON p.ID = h.post_id
					WHERE p.post_status = 'publish'
					AND p.post_type IN ('post', 'page')
					AND h.hierarchy_valid = 0
					LIMIT %d
				";
				$results = $wpdb->get_results( $wpdb->prepare( $query, $limit ), ARRAY_A );
				break;
		}

		return $results;
	}

	/**
	 * Get heading issues count
	 *
	 * @return array Issue counts by type
	 */
	public function get_issues_count() {
		global $wpdb;

		$results = array(
			'no_h1'              => 0,
			'multiple_h1'        => 0,
			'invalid_hierarchy'  => 0,
			'total_issues'       => 0,
		);

		// Count posts with no H1
		$query = "
			SELECT COUNT(DISTINCT p.ID)
			FROM {$wpdb->posts} p
			LEFT JOIN {$wpdb->prefix}blc_headings h ON p.ID = h.post_id AND h.level = 1
			WHERE p.post_status = 'publish'
			AND p.post_type IN ('post', 'page')
			AND h.heading_id IS NULL
		";
		$results['no_h1'] = (int) $wpdb->get_var( $query );

		// Count posts with multiple H1
		$query = "
			SELECT COUNT(*)
			FROM (
				SELECT p.ID
				FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->prefix}blc_headings h ON p.ID = h.post_id AND h.level = 1
				WHERE p.post_status = 'publish'
				AND p.post_type IN ('post', 'page')
				GROUP BY p.ID
				HAVING COUNT(h.heading_id) > 1
			) as multiple_h1_posts
		";
		$results['multiple_h1'] = (int) $wpdb->get_var( $query );

		// Count posts with invalid hierarchy
		$query = "
			SELECT COUNT(DISTINCT p.ID)
			FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->prefix}blc_headings h ON p.ID = h.post_id
			WHERE p.post_status = 'publish'
			AND p.post_type IN ('post', 'page')
			AND h.hierarchy_valid = 0
		";
		$results['invalid_hierarchy'] = (int) $wpdb->get_var( $query );

		$results['total_issues'] = $results['no_h1'] + $results['multiple_h1'] + $results['invalid_hierarchy'];

		return $results;
	}

	/**
	 * Analyze all published posts
	 *
	 * @param int $limit Number of posts to analyze per batch
	 * @return int Number of posts analyzed
	 */
	public function analyze_all_posts( $limit = 50 ) {
		$args = array(
			'post_type'      => array( 'post', 'page' ),
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'orderby'        => 'modified',
			'order'          => 'DESC',
			'fields'         => 'ids',
		);

		$posts = get_posts( $args );
		$analyzed = 0;

		foreach ( $posts as $post_id ) {
			$this->analyze_post( $post_id );
			$analyzed++;
		}

		return $analyzed;
	}
}

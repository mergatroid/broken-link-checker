<?php
/**
 * SEO Title and Meta Description Analyzer
 *
 * @package Broken Link Checker
 * @since 2.0.0
 *
 * ModuleID: seo-titles
 * ModuleCategory: analyzer
 * ModuleContext: on-demand
 * ModuleLazyInit: true
 * ModuleClassName: blcSeoTitlesAnalyzer
 * ModulePriority: 100
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class blcSeoTitlesAnalyzer extends blcModule {

	var $module_id = 'seo-titles';

	/**
	 * Analyze SEO elements for a single post
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

		$conf = blc_get_configuration();

		$results = array(
			'post_id'        => $post_id,
			'post_type'      => $post->post_type,
			'issues'         => array(),
			'has_title'      => false,
			'has_meta_description' => false,
			'title_optimal'  => false,
			'meta_optimal'   => false,
		);

		// Get title
		$title = $this->get_post_title( $post_id );
		if ( $title ) {
			$results['title'] = $title;
			$results['title_length'] = mb_strlen( $title );
			$results['has_title'] = true;

			// Check title length
			$min_length = $conf->get( 'seo_title_min_length', 30 );
			$max_length = $conf->get( 'seo_title_max_length', 60 );

			if ( $results['title_length'] < $min_length ) {
				$results['issues'][] = sprintf( 'Title too short (%d chars, minimum %d)', $results['title_length'], $min_length );
			} elseif ( $results['title_length'] > $max_length ) {
				$results['issues'][] = sprintf( 'Title too long (%d chars, maximum %d)', $results['title_length'], $max_length );
			} else {
				$results['title_optimal'] = true;
			}
		} else {
			$results['issues'][] = 'Missing title tag';
		}

		// Get meta description
		$meta_description = $this->get_meta_description( $post_id );
		if ( $meta_description ) {
			$results['meta_description'] = $meta_description;
			$results['meta_description_length'] = mb_strlen( $meta_description );
			$results['has_meta_description'] = true;

			// Check meta description length
			$min_length = $conf->get( 'seo_meta_min_length', 50 );
			$max_length = $conf->get( 'seo_meta_max_length', 160 );

			if ( $results['meta_description_length'] < $min_length ) {
				$results['issues'][] = sprintf( 'Meta description too short (%d chars, minimum %d)', $results['meta_description_length'], $min_length );
			} elseif ( $results['meta_description_length'] > $max_length ) {
				$results['issues'][] = sprintf( 'Meta description too long (%d chars, maximum %d)', $results['meta_description_length'], $max_length );
			} else {
				$results['meta_optimal'] = true;
			}
		} else {
			$results['issues'][] = 'Missing meta description';
		}

		// Get canonical URL
		$canonical = $this->get_canonical_url( $post_id );
		if ( $canonical ) {
			$results['canonical_url'] = $canonical;
		}

		// Get robots meta
		$robots = $this->get_robots_meta( $post_id );
		if ( $robots ) {
			$results['robots_meta'] = $robots;
		}

		// Save results to database
		$this->save_results( $results );

		return $results;
	}

	/**
	 * Get the SEO title for a post
	 *
	 * Checks Yoast SEO, Rank Math, and All in One SEO Pack
	 *
	 * @param int $post_id Post ID
	 * @return string|null Title or null if not found
	 */
	private function get_post_title( $post_id ) {
		// Try Yoast SEO
		$yoast_title = get_post_meta( $post_id, '_yoast_wpseo_title', true );
		if ( $yoast_title ) {
			return $this->replace_variables( $yoast_title, $post_id );
		}

		// Try Rank Math
		$rankmath_title = get_post_meta( $post_id, 'rank_math_title', true );
		if ( $rankmath_title ) {
			return $this->replace_variables( $rankmath_title, $post_id );
		}

		// Try All in One SEO Pack
		$aioseo_title = get_post_meta( $post_id, '_aioseo_title', true );
		if ( $aioseo_title ) {
			return $this->replace_variables( $aioseo_title, $post_id );
		}

		// Fallback to post title
		$post = get_post( $post_id );
		return $post ? $post->post_title : null;
	}

	/**
	 * Get the meta description for a post
	 *
	 * @param int $post_id Post ID
	 * @return string|null Description or null if not found
	 */
	private function get_meta_description( $post_id ) {
		// Try Yoast SEO
		$yoast_desc = get_post_meta( $post_id, '_yoast_wpseo_metadesc', true );
		if ( $yoast_desc ) {
			return $this->replace_variables( $yoast_desc, $post_id );
		}

		// Try Rank Math
		$rankmath_desc = get_post_meta( $post_id, 'rank_math_description', true );
		if ( $rankmath_desc ) {
			return $this->replace_variables( $rankmath_desc, $post_id );
		}

		// Try All in One SEO Pack
		$aioseo_desc = get_post_meta( $post_id, '_aioseo_description', true );
		if ( $aioseo_desc ) {
			return $this->replace_variables( $aioseo_desc, $post_id );
		}

		// Generate from excerpt or content
		$post = get_post( $post_id );
		if ( $post ) {
			if ( $post->post_excerpt ) {
				return wp_trim_words( $post->post_excerpt, 25, '...' );
			}
			return wp_trim_words( strip_tags( $post->post_content ), 25, '...' );
		}

		return null;
	}

	/**
	 * Get canonical URL for a post
	 *
	 * @param int $post_id Post ID
	 * @return string|null Canonical URL or null
	 */
	private function get_canonical_url( $post_id ) {
		// Try Yoast SEO
		$yoast_canonical = get_post_meta( $post_id, '_yoast_wpseo_canonical', true );
		if ( $yoast_canonical ) {
			return $yoast_canonical;
		}

		// Try Rank Math
		$rankmath_canonical = get_post_meta( $post_id, 'rank_math_canonical_url', true );
		if ( $rankmath_canonical ) {
			return $rankmath_canonical;
		}

		// Fallback to permalink
		return get_permalink( $post_id );
	}

	/**
	 * Get robots meta directives for a post
	 *
	 * @param int $post_id Post ID
	 * @return string|null Robots directives or null
	 */
	private function get_robots_meta( $post_id ) {
		$directives = array();

		// Try Yoast SEO
		$yoast_noindex = get_post_meta( $post_id, '_yoast_wpseo_meta-robots-noindex', true );
		if ( $yoast_noindex === '1' ) {
			$directives[] = 'noindex';
		}

		$yoast_nofollow = get_post_meta( $post_id, '_yoast_wpseo_meta-robots-nofollow', true );
		if ( $yoast_nofollow === '1' ) {
			$directives[] = 'nofollow';
		}

		// Try Rank Math
		$rankmath_robots = get_post_meta( $post_id, 'rank_math_robots', true );
		if ( is_array( $rankmath_robots ) ) {
			$directives = array_merge( $directives, $rankmath_robots );
		}

		return ! empty( $directives ) ? implode( ', ', $directives ) : null;
	}

	/**
	 * Replace SEO plugin variables with actual values
	 *
	 * @param string $text Text with variables
	 * @param int $post_id Post ID
	 * @return string Processed text
	 */
	private function replace_variables( $text, $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return $text;
		}

		$replacements = array(
			'%%title%%'       => $post->post_title,
			'%%sitename%%'    => get_bloginfo( 'name' ),
			'%%sep%%'         => '-',
			'%%excerpt%%'     => $post->post_excerpt,
			'%title%'         => $post->post_title,
			'%sitename%'      => get_bloginfo( 'name' ),
			'%sep%'           => '-',
			'%excerpt%'       => $post->post_excerpt,
		);

		return str_replace( array_keys( $replacements ), array_values( $replacements ), $text );
	}

	/**
	 * Save analysis results to database
	 *
	 * @param array $results Analysis results
	 * @return bool Success
	 */
	private function save_results( $results ) {
		global $wpdb;

		$data = array(
			'post_id'                  => $results['post_id'],
			'post_type'                => $results['post_type'],
			'title'                    => isset( $results['title'] ) ? $results['title'] : null,
			'title_length'             => isset( $results['title_length'] ) ? $results['title_length'] : null,
			'meta_description'         => isset( $results['meta_description'] ) ? $results['meta_description'] : null,
			'meta_description_length'  => isset( $results['meta_description_length'] ) ? $results['meta_description_length'] : null,
			'canonical_url'            => isset( $results['canonical_url'] ) ? $results['canonical_url'] : null,
			'robots_meta'              => isset( $results['robots_meta'] ) ? $results['robots_meta'] : null,
			'has_title'                => $results['has_title'] ? 1 : 0,
			'has_meta_description'     => $results['has_meta_description'] ? 1 : 0,
			'title_optimal'            => $results['title_optimal'] ? 1 : 0,
			'meta_optimal'             => $results['meta_optimal'] ? 1 : 0,
			'last_checked'             => current_time( 'mysql' ),
			'issues'                   => ! empty( $results['issues'] ) ? wp_json_encode( $results['issues'] ) : null,
		);

		// Check if record exists
		$exists = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT element_id FROM {$wpdb->prefix}blc_seo_elements WHERE post_id = %d",
				$results['post_id']
			)
		);

		if ( $exists ) {
			// Update existing record
			return $wpdb->update(
				"{$wpdb->prefix}blc_seo_elements",
				$data,
				array( 'post_id' => $results['post_id'] ),
				array( '%d', '%s', '%s', '%d', '%s', '%d', '%s', '%s', '%d', '%d', '%d', '%d', '%s', '%s' ),
				array( '%d' )
			);
		} else {
			// Insert new record
			return $wpdb->insert(
				"{$wpdb->prefix}blc_seo_elements",
				$data,
				array( '%d', '%s', '%s', '%d', '%s', '%d', '%s', '%s', '%d', '%d', '%d', '%d', '%s', '%s' )
			);
		}
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

	/**
	 * Get SEO issues count
	 *
	 * @return array Issue counts by type
	 */
	public function get_issues_count() {
		global $wpdb;

		$results = array(
			'missing_title'       => 0,
			'title_too_short'     => 0,
			'title_too_long'      => 0,
			'missing_meta'        => 0,
			'meta_too_short'      => 0,
			'meta_too_long'       => 0,
			'total_issues'        => 0,
		);

		// Count missing titles
		$results['missing_title'] = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->prefix}blc_seo_elements WHERE has_title = 0"
		);

		// Count title length issues
		$results['title_too_short'] = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->prefix}blc_seo_elements WHERE title_length < 30 AND has_title = 1"
		);

		$results['title_too_long'] = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->prefix}blc_seo_elements WHERE title_length > 60 AND has_title = 1"
		);

		// Count missing meta descriptions
		$results['missing_meta'] = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->prefix}blc_seo_elements WHERE has_meta_description = 0"
		);

		// Count meta description length issues
		$results['meta_too_short'] = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->prefix}blc_seo_elements WHERE meta_description_length < 50 AND has_meta_description = 1"
		);

		$results['meta_too_long'] = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->prefix}blc_seo_elements WHERE meta_description_length > 160 AND has_meta_description = 1"
		);

		$results['total_issues'] = array_sum( array_values( $results ) ) - $results['total_issues'];

		return $results;
	}
}

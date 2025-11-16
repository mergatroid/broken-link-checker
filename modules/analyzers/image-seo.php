<?php
/**
 * Image SEO Analyzer (Alt Text Auditor)
 *
 * @package Broken Link Checker
 * @since 2.0.0
 *
 * ModuleID: image-seo
 * ModuleCategory: analyzer
 * ModuleContext: on-demand
 * ModuleLazyInit: true
 * ModuleClassName: blcImageSeoAnalyzer
 * ModulePriority: 100
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class blcImageSeoAnalyzer extends blcModule {

	var $module_id = 'image-seo';

	/**
	 * Analyze images in a single post
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

		// Extract all images
		$images = $this->extract_images( $content );

		// Delete old image SEO records for this post
		$wpdb->delete(
			"{$wpdb->prefix}blc_image_seo",
			array( 'post_id' => $post_id ),
			array( '%d' )
		);

		// Analyze and save images
		$issues = array();
		$total_images = count( $images );
		$images_without_alt = 0;

		foreach ( $images as $image ) {
			// Check alt text
			if ( empty( $image['alt_text'] ) ) {
				$images_without_alt++;
			}

			// Save image data to database
			$wpdb->insert(
				"{$wpdb->prefix}blc_image_seo",
				array(
					'instance_id'      => 0, // Will be linked to blc_instances later
					'post_id'          => $post_id,
					'image_url'        => $image['url'],
					'alt_text'         => $image['alt_text'],
					'alt_text_length'  => mb_strlen( $image['alt_text'] ),
					'has_alt_text'     => ! empty( $image['alt_text'] ) ? 1 : 0,
					'title_attribute'  => isset( $image['title'] ) ? $image['title'] : null,
					'last_checked'     => current_time( 'mysql' ),
				),
				array( '%d', '%d', '%s', '%s', '%d', '%d', '%s', '%s' )
			);
		}

		// Compile issues
		if ( $images_without_alt > 0 ) {
			$issues[] = sprintf( '%d image(s) missing alt text', $images_without_alt );
		}

		return array(
			'post_id'             => $post_id,
			'total_images'        => $total_images,
			'images_without_alt'  => $images_without_alt,
			'issues'              => $issues,
			'images'              => $images,
		);
	}

	/**
	 * Extract all images from HTML content with their attributes
	 *
	 * @param string $content HTML content
	 * @return array Array of images with URL and attributes
	 */
	private function extract_images( $content ) {
		$images = array();

		// Match all img tags
		$pattern = '/<img\s+([^>]+)>/i';

		if ( preg_match_all( $pattern, $content, $matches ) ) {
			foreach ( $matches[1] as $img_attributes ) {
				$image_data = array(
					'url'       => '',
					'alt_text'  => '',
					'title'     => '',
				);

				// Extract src
				if ( preg_match( '/src\s*=\s*["\']([^"\']+)["\']/i', $img_attributes, $src_match ) ) {
					$image_data['url'] = $src_match[1];
				}

				// Extract alt
				if ( preg_match( '/alt\s*=\s*["\']([^"\']*)["\']/i', $img_attributes, $alt_match ) ) {
					$image_data['alt_text'] = html_entity_decode( $alt_match[1], ENT_QUOTES, 'UTF-8' );
				}

				// Extract title
				if ( preg_match( '/title\s*=\s*["\']([^"\']+)["\']/i', $img_attributes, $title_match ) ) {
					$image_data['title'] = html_entity_decode( $title_match[1], ENT_QUOTES, 'UTF-8' );
				}

				// Only add if we have a URL
				if ( ! empty( $image_data['url'] ) ) {
					$images[] = $image_data;
				}
			}
		}

		return $images;
	}

	/**
	 * Get image SEO data for a post
	 *
	 * @param int $post_id Post ID
	 * @return array Image SEO data
	 */
	public function get_post_images( $post_id ) {
		global $wpdb;

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}blc_image_seo WHERE post_id = %d ORDER BY image_seo_id ASC",
				$post_id
			),
			ARRAY_A
		);
	}

	/**
	 * Get posts with image SEO issues
	 *
	 * @param int $limit Number of results to return
	 * @return array Posts with missing alt text
	 */
	public function get_posts_with_missing_alt( $limit = 50 ) {
		global $wpdb;

		$query = "
			SELECT DISTINCT p.ID, p.post_title, p.post_type, COUNT(i.image_seo_id) as images_without_alt
			FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->prefix}blc_image_seo i ON p.ID = i.post_id
			WHERE p.post_status = 'publish'
			AND p.post_type IN ('post', 'page')
			AND i.has_alt_text = 0
			GROUP BY p.ID
			ORDER BY images_without_alt DESC
			LIMIT %d
		";

		return $wpdb->get_results( $wpdb->prepare( $query, $limit ), ARRAY_A );
	}

	/**
	 * Get image SEO issues count
	 *
	 * @return array Issue counts
	 */
	public function get_issues_count() {
		global $wpdb;

		$results = array(
			'images_without_alt' => 0,
			'posts_with_issues'  => 0,
			'total_images'       => 0,
		);

		// Count images without alt text
		$results['images_without_alt'] = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->prefix}blc_image_seo WHERE has_alt_text = 0"
		);

		// Count posts with images missing alt text
		$query = "
			SELECT COUNT(DISTINCT post_id)
			FROM {$wpdb->prefix}blc_image_seo
			WHERE has_alt_text = 0
		";
		$results['posts_with_issues'] = (int) $wpdb->get_var( $query );

		// Count total images analyzed
		$results['total_images'] = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->prefix}blc_image_seo"
		);

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

	/**
	 * Get all images missing alt text
	 *
	 * @param int $limit Number of results
	 * @return array Images without alt text
	 */
	public function get_images_without_alt( $limit = 100 ) {
		global $wpdb;

		$query = "
			SELECT i.*, p.post_title, p.post_type
			FROM {$wpdb->prefix}blc_image_seo i
			INNER JOIN {$wpdb->posts} p ON i.post_id = p.ID
			WHERE i.has_alt_text = 0
			AND p.post_status = 'publish'
			ORDER BY i.last_checked DESC
			LIMIT %d
		";

		return $wpdb->get_results( $wpdb->prepare( $query, $limit ), ARRAY_A );
	}
}

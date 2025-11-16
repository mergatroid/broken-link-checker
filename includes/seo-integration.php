<?php
/**
 * SEO Analysis Integration
 *
 * Integrates Phase 1 SEO analysis features into BLC core
 *
 * @package Broken Link Checker
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Initialize SEO analysis features
 */
function blc_init_seo_analysis() {
	$conf = blc_get_configuration();

	// Check if SEO analysis is enabled
	if ( ! $conf->get( 'seo_analysis_enabled', true ) ) {
		return;
	}

	// Add admin menu
	add_action( 'admin_menu', 'blc_add_seo_menu', 11 );

	// Load analyzer modules if they exist
	if ( file_exists( BLC_DIRECTORY . '/modules/analyzers/seo-titles.php' ) ) {
		require_once BLC_DIRECTORY . '/modules/analyzers/seo-titles.php';
	}

	if ( file_exists( BLC_DIRECTORY . '/modules/analyzers/heading-structure.php' ) ) {
		require_once BLC_DIRECTORY . '/modules/analyzers/heading-structure.php';
	}

	if ( file_exists( BLC_DIRECTORY . '/modules/analyzers/image-seo.php' ) ) {
		require_once BLC_DIRECTORY . '/modules/analyzers/image-seo.php';
	}

	// Hook into post save to trigger SEO analysis
	add_action( 'save_post', 'blc_trigger_seo_analysis', 10, 2 );

	// Add cron schedule for periodic SEO scans
	add_action( 'blc_seo_scan_hook', 'blc_cron_seo_scan' );
	if ( ! wp_next_scheduled( 'blc_seo_scan_hook' ) ) {
		$interval = $conf->get( 'seo_scan_interval', 168 ); // Default 168 hours (weekly)
		wp_schedule_event( time(), 'weekly', 'blc_seo_scan_hook' );
	}
}

/**
 * Add SEO Issues menu to Tools
 */
function blc_add_seo_menu() {
	// Load admin page file
	require_once BLC_DIRECTORY . '/includes/admin/seo-issues-page.php';

	// Add menu page
	$seo_page_hook = add_management_page(
		__( 'SEO Issues', 'broken-link-checker' ),
		__( 'SEO Issues', 'broken-link-checker' ),
		'edit_others_posts',
		'blc-seo-issues',
		'blc_render_seo_issues_page'
	);

	// Add styles
	add_action( 'admin_print_styles-' . $seo_page_hook, 'blc_seo_page_styles' );
}

/**
 * Enqueue styles for SEO pages
 */
function blc_seo_page_styles() {
	wp_enqueue_style( 'wp-color-picker' );
}

/**
 * Trigger SEO analysis when a post is saved
 *
 * @param int $post_id Post ID
 * @param WP_Post $post Post object
 */
function blc_trigger_seo_analysis( $post_id, $post ) {
	// Skip autosaves and revisions
	if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
		return;
	}

	// Only analyze published posts and pages
	if ( $post->post_status !== 'publish' || ! in_array( $post->post_type, array( 'post', 'page' ), true ) ) {
		return;
	}

	// Run analysis in background to avoid slowing down post save
	wp_schedule_single_event( time() + 10, 'blc_analyze_single_post', array( $post_id ) );
}

/**
 * Analyze a single post (triggered by cron)
 *
 * @param int $post_id Post ID
 */
function blc_analyze_single_post( $post_id ) {
	if ( class_exists( 'blcSeoTitlesAnalyzer' ) ) {
		$title_analyzer = new blcSeoTitlesAnalyzer();
		$title_analyzer->analyze_post( $post_id );
	}

	if ( class_exists( 'blcHeadingAnalyzer' ) ) {
		$heading_analyzer = new blcHeadingAnalyzer();
		$heading_analyzer->analyze_post( $post_id );
	}

	if ( class_exists( 'blcImageSeoAnalyzer' ) ) {
		$image_analyzer = new blcImageSeoAnalyzer();
		$image_analyzer->analyze_post( $post_id );
	}
}
add_action( 'blc_analyze_single_post', 'blc_analyze_single_post' );

/**
 * Periodic SEO scan via cron
 */
function blc_cron_seo_scan() {
	if ( class_exists( 'blcSeoTitlesAnalyzer' ) ) {
		$title_analyzer = new blcSeoTitlesAnalyzer();
		$title_analyzer->analyze_all_posts( 50 );
	}

	if ( class_exists( 'blcHeadingAnalyzer' ) ) {
		$heading_analyzer = new blcHeadingAnalyzer();
		$heading_analyzer->analyze_all_posts( 50 );
	}

	if ( class_exists( 'blcImageSeoAnalyzer' ) ) {
		$image_analyzer = new blcImageSeoAnalyzer();
		$image_analyzer->analyze_all_posts( 50 );
	}
}

/**
 * Get SEO health score (0-100)
 *
 * @return int Health score
 */
function blc_get_seo_health_score() {
	global $wpdb;

	$total_posts = (int) $wpdb->get_var(
		"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_type IN ('post', 'page')"
	);

	if ( $total_posts === 0 ) {
		return 100;
	}

	$title_analyzer = new blcSeoTitlesAnalyzer();
	$heading_analyzer = new blcHeadingAnalyzer();
	$image_analyzer = new blcImageSeoAnalyzer();

	$title_issues = $title_analyzer->get_issues_count();
	$heading_issues = $heading_analyzer->get_issues_count();
	$image_issues = $image_analyzer->get_issues_count();

	$total_issues = $title_issues['total_issues'] + $heading_issues['total_issues'];

	// Calculate score (fewer issues = higher score)
	$issues_per_post = $total_issues / $total_posts;
	$score = max( 0, 100 - ( $issues_per_post * 20 ) );

	return (int) $score;
}

// Initialize SEO analysis
blc_init_seo_analysis();

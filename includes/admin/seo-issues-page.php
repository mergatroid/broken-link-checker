<?php
/**
 * SEO Issues Admin Page
 *
 * @package Broken Link Checker
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the SEO Issues page
 */
function blc_render_seo_issues_page() {
	global $wpdb;

	// Check user permissions
	if ( ! current_user_can( 'edit_others_posts' ) ) {
		wp_die( __( 'You do not have sufficient permissions to access this page.', 'broken-link-checker' ) );
	}

	// Handle actions
	if ( isset( $_GET['action'] ) && $_GET['action'] === 'scan' && check_admin_referer( 'blc-scan-seo' ) ) {
		blc_scan_seo_all_posts();
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'SEO scan completed!', 'broken-link-checker' ) . '</p></div>';
	}

	// Get issue counts
	$title_analyzer = new blcSeoTitlesAnalyzer();
	$heading_analyzer = new blcHeadingAnalyzer();
	$image_analyzer = new blcImageSeoAnalyzer();

	$title_issues = $title_analyzer->get_issues_count();
	$heading_issues = $heading_analyzer->get_issues_count();
	$image_issues = $image_analyzer->get_issues_count();

	$total_issues = $title_issues['total_issues'] + $heading_issues['total_issues'] + $image_issues['images_without_alt'];

	// Get current tab
	$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'titles';

	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'SEO Issues', 'broken-link-checker' ); ?></h1>

		<div class="blc-seo-summary">
			<h2><?php esc_html_e( 'Summary', 'broken-link-checker' ); ?></h2>
			<div class="blc-stats-grid">
				<div class="blc-stat-card">
					<div class="blc-stat-number"><?php echo esc_html( $total_issues ); ?></div>
					<div class="blc-stat-label"><?php esc_html_e( 'Total SEO Issues', 'broken-link-checker' ); ?></div>
				</div>
				<div class="blc-stat-card">
					<div class="blc-stat-number"><?php echo esc_html( $title_issues['total_issues'] ); ?></div>
					<div class="blc-stat-label"><?php esc_html_e( 'Title & Meta Issues', 'broken-link-checker' ); ?></div>
				</div>
				<div class="blc-stat-card">
					<div class="blc-stat-number"><?php echo esc_html( $heading_issues['total_issues'] ); ?></div>
					<div class="blc-stat-label"><?php esc_html_e( 'Heading Issues', 'broken-link-checker' ); ?></div>
				</div>
				<div class="blc-stat-card">
					<div class="blc-stat-number"><?php echo esc_html( $image_issues['images_without_alt'] ); ?></div>
					<div class="blc-stat-label"><?php esc_html_e( 'Images Without Alt', 'broken-link-checker' ); ?></div>
				</div>
			</div>
		</div>

		<p>
			<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'tools.php?page=blc-seo-issues&action=scan' ), 'blc-scan-seo' ) ); ?>" class="button button-primary">
				<?php esc_html_e( 'Scan All Posts', 'broken-link-checker' ); ?>
			</a>
		</p>

		<h2 class="nav-tab-wrapper">
			<a href="?page=blc-seo-issues&tab=titles" class="nav-tab <?php echo $current_tab === 'titles' ? 'nav-tab-active' : ''; ?>">
				<?php esc_html_e( 'Titles & Meta', 'broken-link-checker' ); ?>
				<span class="count">(<?php echo esc_html( $title_issues['total_issues'] ); ?>)</span>
			</a>
			<a href="?page=blc-seo-issues&tab=headings" class="nav-tab <?php echo $current_tab === 'headings' ? 'nav-tab-active' : ''; ?>">
				<?php esc_html_e( 'Headings', 'broken-link-checker' ); ?>
				<span class="count">(<?php echo esc_html( $heading_issues['total_issues'] ); ?>)</span>
			</a>
			<a href="?page=blc-seo-issues&tab=images" class="nav-tab <?php echo $current_tab === 'images' ? 'nav-tab-active' : ''; ?>">
				<?php esc_html_e( 'Image Alt Text', 'broken-link-checker' ); ?>
				<span class="count">(<?php echo esc_html( $image_issues['images_without_alt'] ); ?>)</span>
			</a>
		</h2>

		<div class="blc-tab-content">
			<?php
			switch ( $current_tab ) {
				case 'titles':
					blc_render_title_issues_tab( $title_analyzer );
					break;
				case 'headings':
					blc_render_heading_issues_tab( $heading_analyzer );
					break;
				case 'images':
					blc_render_image_issues_tab( $image_analyzer );
					break;
			}
			?>
		</div>
	</div>

	<style>
	.blc-stats-grid {
		display: grid;
		grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
		gap: 20px;
		margin: 20px 0;
	}
	.blc-stat-card {
		background: #fff;
		border: 1px solid #ccd0d4;
		border-radius: 4px;
		padding: 20px;
		text-align: center;
	}
	.blc-stat-number {
		font-size: 32px;
		font-weight: bold;
		color: #2271b1;
	}
	.blc-stat-label {
		margin-top: 8px;
		color: #646970;
		font-size: 14px;
	}
	.blc-tab-content {
		background: #fff;
		border: 1px solid #ccd0d4;
		border-top: none;
		padding: 20px;
	}
	.blc-issue-table {
		width: 100%;
		border-collapse: collapse;
	}
	.blc-issue-table th,
	.blc-issue-table td {
		padding: 10px;
		text-align: left;
		border-bottom: 1px solid #ccd0d4;
	}
	.blc-issue-table th {
		background: #f0f0f1;
		font-weight: 600;
	}
	.blc-issue-badge {
		display: inline-block;
		padding: 3px 8px;
		border-radius: 3px;
		font-size: 12px;
		font-weight: 500;
	}
	.blc-issue-badge.error {
		background: #d63638;
		color: #fff;
	}
	.blc-issue-badge.warning {
		background: #dba617;
		color: #fff;
	}
	.nav-tab .count {
		background: #d63638;
		color: #fff;
		padding: 2px 6px;
		border-radius: 10px;
		font-size: 11px;
		margin-left: 4px;
	}
	.nav-tab-active .count {
		background: #2271b1;
	}
	</style>
	<?php
}

/**
 * Render title and meta description issues tab
 */
function blc_render_title_issues_tab( $analyzer ) {
	global $wpdb;

	// Get posts with SEO issues
	$posts_with_issues = $wpdb->get_results(
		"SELECT s.*, p.post_title, p.post_type
		FROM {$wpdb->prefix}blc_seo_elements s
		INNER JOIN {$wpdb->posts} p ON s.post_id = p.ID
		WHERE (s.title_optimal = 0 OR s.meta_optimal = 0 OR s.has_title = 0 OR s.has_meta_description = 0)
		AND p.post_status = 'publish'
		ORDER BY s.last_checked DESC
		LIMIT 100",
		ARRAY_A
	);

	if ( empty( $posts_with_issues ) ) {
		echo '<p>' . esc_html__( 'No title or meta description issues found!', 'broken-link-checker' ) . '</p>';
		return;
	}

	?>
	<table class="blc-issue-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Post Title', 'broken-link-checker' ); ?></th>
				<th><?php esc_html_e( 'SEO Title', 'broken-link-checker' ); ?></th>
				<th><?php esc_html_e( 'Meta Description', 'broken-link-checker' ); ?></th>
				<th><?php esc_html_e( 'Issues', 'broken-link-checker' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'broken-link-checker' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $posts_with_issues as $post ) : ?>
				<?php $issues = json_decode( $post['issues'], true ); ?>
				<tr>
					<td>
						<strong><?php echo esc_html( $post['post_title'] ); ?></strong><br>
						<span class="row-actions">
							<a href="<?php echo esc_url( get_permalink( $post['post_id'] ) ); ?>" target="_blank"><?php esc_html_e( 'View', 'broken-link-checker' ); ?></a>
						</span>
					</td>
					<td>
						<?php if ( $post['title'] ) : ?>
							<?php echo esc_html( $post['title'] ); ?><br>
							<small><?php echo esc_html( $post['title_length'] ); ?> chars</small>
						<?php else : ?>
							<span class="blc-issue-badge error"><?php esc_html_e( 'Missing', 'broken-link-checker' ); ?></span>
						<?php endif; ?>
					</td>
					<td>
						<?php if ( $post['meta_description'] ) : ?>
							<?php echo esc_html( wp_trim_words( $post['meta_description'], 15 ) ); ?><br>
							<small><?php echo esc_html( $post['meta_description_length'] ); ?> chars</small>
						<?php else : ?>
							<span class="blc-issue-badge error"><?php esc_html_e( 'Missing', 'broken-link-checker' ); ?></span>
						<?php endif; ?>
					</td>
					<td>
						<?php if ( ! empty( $issues ) ) : ?>
							<?php foreach ( $issues as $issue ) : ?>
								<span class="blc-issue-badge warning"><?php echo esc_html( $issue ); ?></span><br>
							<?php endforeach; ?>
						<?php endif; ?>
					</td>
					<td>
						<a href="<?php echo esc_url( get_edit_post_link( $post['post_id'] ) ); ?>" class="button button-small">
							<?php esc_html_e( 'Edit', 'broken-link-checker' ); ?>
						</a>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<?php
}

/**
 * Render heading issues tab
 */
function blc_render_heading_issues_tab( $analyzer ) {
	$posts_no_h1 = $analyzer->get_posts_with_issues( 'no_h1', 50 );
	$posts_multiple_h1 = $analyzer->get_posts_with_issues( 'multiple_h1', 50 );
	$posts_invalid_hierarchy = $analyzer->get_posts_with_issues( 'invalid_hierarchy', 50 );

	$all_issues = array_merge( $posts_no_h1, $posts_multiple_h1, $posts_invalid_hierarchy );

	if ( empty( $all_issues ) ) {
		echo '<p>' . esc_html__( 'No heading issues found!', 'broken-link-checker' ) . '</p>';
		return;
	}

	?>
	<table class="blc-issue-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Post Title', 'broken-link-checker' ); ?></th>
				<th><?php esc_html_e( 'Post Type', 'broken-link-checker' ); ?></th>
				<th><?php esc_html_e( 'Issue Type', 'broken-link-checker' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'broken-link-checker' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $posts_no_h1 as $post ) : ?>
				<tr>
					<td><strong><?php echo esc_html( $post['post_title'] ); ?></strong></td>
					<td><?php echo esc_html( $post['post_type'] ); ?></td>
					<td><span class="blc-issue-badge error"><?php esc_html_e( 'Missing H1', 'broken-link-checker' ); ?></span></td>
					<td>
						<a href="<?php echo esc_url( get_edit_post_link( $post['ID'] ) ); ?>" class="button button-small">
							<?php esc_html_e( 'Edit', 'broken-link-checker' ); ?>
						</a>
					</td>
				</tr>
			<?php endforeach; ?>
			<?php foreach ( $posts_multiple_h1 as $post ) : ?>
				<tr>
					<td><strong><?php echo esc_html( $post['post_title'] ); ?></strong></td>
					<td><?php echo esc_html( $post['post_type'] ); ?></td>
					<td>
						<span class="blc-issue-badge warning">
							<?php echo esc_html( sprintf( __( 'Multiple H1 (%d)', 'broken-link-checker' ), $post['h1_count'] ) ); ?>
						</span>
					</td>
					<td>
						<a href="<?php echo esc_url( get_edit_post_link( $post['ID'] ) ); ?>" class="button button-small">
							<?php esc_html_e( 'Edit', 'broken-link-checker' ); ?>
						</a>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<?php
}

/**
 * Render image alt text issues tab
 */
function blc_render_image_issues_tab( $analyzer ) {
	$posts_with_issues = $analyzer->get_posts_with_missing_alt( 50 );

	if ( empty( $posts_with_issues ) ) {
		echo '<p>' . esc_html__( 'No image alt text issues found!', 'broken-link-checker' ) . '</p>';
		return;
	}

	?>
	<table class="blc-issue-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Post Title', 'broken-link-checker' ); ?></th>
				<th><?php esc_html_e( 'Post Type', 'broken-link-checker' ); ?></th>
				<th><?php esc_html_e( 'Images Without Alt', 'broken-link-checker' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'broken-link-checker' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $posts_with_issues as $post ) : ?>
				<tr>
					<td><strong><?php echo esc_html( $post['post_title'] ); ?></strong></td>
					<td><?php echo esc_html( $post['post_type'] ); ?></td>
					<td>
						<span class="blc-issue-badge error">
							<?php echo esc_html( $post['images_without_alt'] ); ?> <?php esc_html_e( 'image(s)', 'broken-link-checker' ); ?>
						</span>
					</td>
					<td>
						<a href="<?php echo esc_url( get_edit_post_link( $post['ID'] ) ); ?>" class="button button-small">
							<?php esc_html_e( 'Edit', 'broken-link-checker' ); ?>
						</a>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<?php
}

/**
 * Scan all posts for SEO issues
 */
function blc_scan_seo_all_posts() {
	$title_analyzer = new blcSeoTitlesAnalyzer();
	$heading_analyzer = new blcHeadingAnalyzer();
	$image_analyzer = new blcImageSeoAnalyzer();

	$title_analyzer->analyze_all_posts( 100 );
	$heading_analyzer->analyze_all_posts( 100 );
	$image_analyzer->analyze_all_posts( 100 );
}

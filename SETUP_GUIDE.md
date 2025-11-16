# Setup & Installation Guide - Phase 1 SEO Analysis

**Version:** 2.0.0-alpha
**Last Updated:** 2025-11-16

---

## Quick Start Guide

### Prerequisites

- WordPress 5.0 or higher (tested up to 6.x)
- PHP 7.0 or higher
- MySQL 5.6 or higher
- Administrator access to WordPress

---

## Installation Options

### Option 1: From Git Repository (Development/Testing)

#### Step 1: Clone or Copy to WordPress Plugins

```bash
# Navigate to your WordPress plugins directory
cd /path/to/wordpress/wp-content/plugins/

# If cloning from repository
git clone https://github.com/mergatroid/broken-link-checker.git

# Or if you already have the repo, copy it
cp -r /path/to/broken-link-checker ./broken-link-checker

# Ensure correct permissions
chmod -R 755 broken-link-checker
```

#### Step 2: Checkout the Phase 1 Branch

```bash
cd broken-link-checker
git checkout claude/review-repository-011X3G9y4Ffutb54HQEhz1hh
```

#### Step 3: Activate the Plugin

**Via WordPress Admin:**
1. Log in to WordPress Admin (`http://yoursite.com/wp-admin`)
2. Navigate to **Plugins > Installed Plugins**
3. Find "Broken Link Checker"
4. Click **"Activate"**

**Via WP-CLI:**
```bash
wp plugin activate broken-link-checker
```

---

### Option 2: Manual Installation (Production)

#### Step 1: Download Plugin Files

Download the plugin folder with Phase 1 implementation to your local machine.

#### Step 2: Upload to WordPress

**Method A: Via WordPress Admin (ZIP)**
1. Zip the `broken-link-checker` folder
2. Go to **Plugins > Add New > Upload Plugin**
3. Choose the ZIP file
4. Click **"Install Now"**
5. Click **"Activate Plugin"**

**Method B: Via FTP/SFTP**
1. Connect to your server via FTP/SFTP
2. Navigate to `wp-content/plugins/`
3. Upload the `broken-link-checker` folder
4. In WordPress Admin, go to **Plugins** and activate

---

## Initial Setup

### Step 1: Verify Database Migration

After activation, the plugin automatically creates new database tables.

**Check via phpMyAdmin:**
```sql
-- You should see these tables in your WordPress database:
SHOW TABLES LIKE 'wp_blc_seo_elements';
SHOW TABLES LIKE 'wp_blc_headings';
SHOW TABLES LIKE 'wp_blc_image_seo';

-- Check database version is 10
SELECT option_value
FROM wp_options
WHERE option_name = 'wsblc_options';
-- Look for: "current_db_version":10
```

**Check via WP-CLI:**
```bash
# List database tables
wp db query "SHOW TABLES LIKE 'wp_blc_%'"

# Should see:
# wp_blc_filters
# wp_blc_headings          # NEW in Phase 1
# wp_blc_image_seo         # NEW in Phase 1
# wp_blc_instances
# wp_blc_links
# wp_blc_seo_elements      # NEW in Phase 1
# wp_blc_synch
```

### Step 2: Access the SEO Issues Page

1. In WordPress Admin, navigate to **Tools > SEO Issues**
2. You should see the SEO Analysis dashboard with summary cards
3. Initially, all counts will be 0 (no data scanned yet)

**Expected Display:**
```
┌─────────────────────────────────────────────┐
│ SEO Issues                                  │
├─────────────────────────────────────────────┤
│ Summary                                     │
│ [0] Total SEO Issues                        │
│ [0] Title & Meta Issues                     │
│ [0] Heading Issues                          │
│ [0] Images Without Alt                      │
├─────────────────────────────────────────────┤
│ [Scan All Posts] Button                     │
└─────────────────────────────────────────────┘
```

### Step 3: Run Initial SEO Scan

**Click the "Scan All Posts" button** to analyze your existing content.

This will:
- ✅ Analyze titles and meta descriptions
- ✅ Extract and validate heading structure
- ✅ Check all images for alt text
- ✅ Store results in the database

**Processing Time:**
- ~0.5-1 second per post
- Batch size: 50 posts per execution
- For 100 posts: ~1-2 minutes total

**Via WP-CLI (Alternative):**
```bash
# Trigger SEO scan programmatically
wp eval 'blc_scan_seo_all_posts();'
```

### Step 4: Review Results

After scanning, refresh the page to see updated metrics:

**Navigate through the 3 tabs:**

1. **Titles & Meta Tab**
   - Shows posts with missing/suboptimal titles
   - Shows posts with missing/suboptimal meta descriptions
   - Displays character counts
   - Color-coded issue badges

2. **Headings Tab**
   - Posts missing H1 tags
   - Posts with multiple H1 tags
   - Posts with invalid hierarchy

3. **Image Alt Text Tab**
   - Posts containing images without alt text
   - Count of affected images per post
   - Direct edit links

---

## Configuration

### Access Settings

Navigate to **Settings > Link Checker** to configure SEO analysis.

### Available Options

```php
// Default values (in core/init.php)
'seo_analysis_enabled' => true,       // Enable/disable SEO features
'seo_check_titles' => true,           // Check page titles
'seo_check_meta_descriptions' => true,// Check meta descriptions
'seo_check_headings' => true,         // Check heading structure
'seo_check_images' => true,           // Check image alt text
'seo_title_min_length' => 30,         // Minimum title length (chars)
'seo_title_max_length' => 60,         // Maximum title length (chars)
'seo_meta_min_length' => 50,          // Minimum meta desc length
'seo_meta_max_length' => 160,         // Maximum meta desc length
'seo_scan_interval' => 168,           // Scan interval (hours = weekly)
```

### Modify Settings (if needed)

**Via WordPress Database:**
```sql
-- Get current settings
SELECT option_value FROM wp_options WHERE option_name = 'wsblc_options';

-- Settings are stored as JSON
-- Edit via WordPress Admin or programmatically
```

**Via Code (functions.php):**
```php
add_action('init', function() {
    $conf = blc_get_configuration();

    // Customize title length limits
    $conf->options['seo_title_min_length'] = 40;
    $conf->options['seo_title_max_length'] = 70;

    // Customize meta description limits
    $conf->options['seo_meta_min_length'] = 60;
    $conf->options['seo_meta_max_length'] = 155;

    $conf->save_options();
}, 999);
```

---

## Testing the Features

### Test 1: Title & Meta Description Analysis

#### Create Test Posts

**Post 1: Optimal (No Issues)**
```
Title: "Complete Guide to WordPress SEO Best Practices"
(52 characters - within 30-60 range ✓)

Meta Description: "Learn the essential WordPress SEO techniques to improve your search rankings. Comprehensive guide covering titles, meta tags, and more."
(145 characters - within 50-160 range ✓)
```

**Post 2: Title Too Short**
```
Title: "SEO Guide"
(9 characters - below 30 minimum ✗)

Meta Description: "Learn SEO best practices for WordPress websites."
(49 characters - below 50 minimum ✗)
```

**Post 3: Title Too Long**
```
Title: "The Ultimate Comprehensive and Complete Guide to WordPress Search Engine Optimization Techniques and Best Practices"
(118 characters - above 60 maximum ✗)
```

**Post 4: Missing Meta**
```
Title: "WordPress Performance Optimization Tips"
(40 characters - optimal ✓)

Meta Description: (leave empty ✗)
```

#### Publish and Scan

1. Publish these test posts
2. Go to **Tools > SEO Issues**
3. Click **"Scan All Posts"**
4. Check **"Titles & Meta"** tab
5. Verify issues are detected correctly

#### Expected Results

You should see:
- Post 2: "Title too short (9 chars, minimum 30)"
- Post 2: "Meta description too short (49 chars, minimum 50)"
- Post 3: "Title too long (118 chars, maximum 60)"
- Post 4: "Missing meta description"

---

### Test 2: Heading Structure Analysis

#### Create Test Posts

**Post 5: Perfect Heading Structure**
```html
<h1>Main Title</h1>
<h2>Section 1</h2>
<h3>Subsection 1.1</h3>
<h3>Subsection 1.2</h3>
<h2>Section 2</h2>
```
✓ Single H1, proper hierarchy

**Post 6: Missing H1**
```html
<h2>Section Title</h2>
<h3>Subsection</h3>
```
✗ No H1 tag

**Post 7: Multiple H1s**
```html
<h1>First Title</h1>
<p>Some content</p>
<h1>Second Title</h1>
```
✗ Multiple H1 tags (SEO anti-pattern)

**Post 8: Invalid Hierarchy**
```html
<h1>Main Title</h1>
<h4>Skipped to H4</h4>
```
✗ Skips from H1 to H4 (should be H2)

#### Publish and Scan

1. Publish these test posts
2. **Tools > SEO Issues > Headings** tab
3. Click **"Scan All Posts"**
4. Verify detected issues

#### Expected Results

- Post 6: "Missing H1 heading"
- Post 7: "Multiple H1 headings found (2)"
- Post 8: "Invalid heading hierarchy"

---

### Test 3: Image Alt Text Analysis

#### Create Test Posts

**Post 9: Images with Alt Text**
```html
<img src="logo.png" alt="Company Logo">
<img src="banner.jpg" alt="Welcome Banner">
```
✓ All images have alt text

**Post 10: Images Missing Alt**
```html
<img src="photo1.jpg">
<img src="photo2.jpg" alt="">
<img src="photo3.jpg" alt="Good Alt Text">
```
✗ 2 images without proper alt text

#### Publish and Scan

1. Publish test posts
2. **Tools > SEO Issues > Image Alt Text** tab
3. Click **"Scan All Posts"**
4. Verify detection

#### Expected Results

Post 10 should appear with:
- "2 image(s) missing alt text"

---

### Test 4: Automatic Scanning on Post Save

1. Create a new post with SEO issues
2. Publish the post
3. Wait 10-15 seconds (background task delay)
4. Refresh **Tools > SEO Issues**
5. Verify the new post appears in the issues list

**Check Scheduled Tasks:**
```bash
# Via WP-CLI
wp cron event list --fields=hook,next_run | grep blc

# Should show:
# blc_analyze_single_post
# blc_seo_scan_hook
```

---

### Test 5: SEO Plugin Integration

#### With Yoast SEO

1. Install and activate **Yoast SEO**
2. Edit a post
3. Scroll to **Yoast SEO meta box**
4. Set custom SEO title and meta description
5. Publish post
6. Run SEO scan
7. Verify BLC detects Yoast's custom values

**Expected:** BLC should read from:
- `_yoast_wpseo_title` meta field
- `_yoast_wpseo_metadesc` meta field

#### With Rank Math

1. Install and activate **Rank Math**
2. Set custom SEO fields in post editor
3. Publish and scan
4. Verify detection

#### Without SEO Plugin

1. Deactivate all SEO plugins
2. Create post with only WordPress title
3. Scan
4. Verify fallback to `post_title` and `post_excerpt`

---

## Troubleshooting

### Issue 1: SEO Issues Menu Not Appearing

**Check:**
```php
// Verify user has correct capabilities
current_user_can('edit_others_posts'); // Should return true

// Check if SEO integration loaded
if (function_exists('blc_init_seo_analysis')) {
    echo "SEO integration loaded ✓";
} else {
    echo "SEO integration NOT loaded ✗";
}
```

**Solution:**
1. Ensure you're logged in as Administrator
2. Verify plugin is activated
3. Check for PHP errors in debug.log
4. Deactivate and reactivate plugin

### Issue 2: Database Tables Not Created

**Manual Creation:**
```bash
# Via WP-CLI
wp eval "
require_once(WP_PLUGIN_DIR . '/broken-link-checker/includes/admin/db-upgrade.php');
blcDatabaseUpgrader::upgrade_database();
"
```

**Via PHP (add to functions.php temporarily):**
```php
add_action('admin_init', function() {
    require_once(WP_PLUGIN_DIR . '/broken-link-checker/includes/admin/db-upgrade.php');
    blcDatabaseUpgrader::upgrade_database();
    // Remove this code after running once
});
```

### Issue 3: Scan Button Does Nothing

**Enable WordPress Debug:**
```php
// Add to wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

**Check Logs:**
```bash
tail -f wp-content/debug.log
```

**Manual Scan via Code:**
```php
// Add to functions.php temporarily
add_action('admin_init', function() {
    if (isset($_GET['manual_seo_scan'])) {
        blc_scan_seo_all_posts();
        echo '<div class="notice notice-success"><p>Scan complete!</p></div>';
    }
});

// Visit: /wp-admin/?manual_seo_scan=1
```

### Issue 4: No Issues Detected (All Zeros)

**Verify Post Content:**
```sql
-- Check if posts exist
SELECT COUNT(*) FROM wp_posts WHERE post_status = 'publish' AND post_type IN ('post', 'page');

-- Check if SEO data was saved
SELECT COUNT(*) FROM wp_blc_seo_elements;
SELECT COUNT(*) FROM wp_blc_headings;
SELECT COUNT(*) FROM wp_blc_image_seo;
```

**Force Re-analysis:**
```php
// Trigger manual analysis
$title_analyzer = new blcSeoTitlesAnalyzer();
$result = $title_analyzer->analyze_post(1); // Replace 1 with actual post ID
print_r($result);
```

### Issue 5: PHP Errors After Activation

**Common Errors:**

**"Class not found"**
- Solution: Ensure all files are uploaded correctly
- Check file permissions (755 for directories, 644 for files)

**"Table doesn't exist"**
- Solution: Run database upgrade manually (see Issue 2)

**Memory Limit Exceeded**
- Solution: Increase PHP memory limit in wp-config.php:
```php
define('WP_MEMORY_LIMIT', '256M');
```

---

## Production Deployment

### Pre-Deployment Checklist

- [ ] Backup WordPress database
- [ ] Test on staging environment first
- [ ] Verify PHP version compatibility (7.0+)
- [ ] Check available disk space
- [ ] Review current plugin settings

### Deployment Steps

1. **Backup Database**
```bash
# Via WP-CLI
wp db export backup-before-blc-phase1.sql

# Or via phpMyAdmin
# Export > Quick > SQL format > Go
```

2. **Upload Plugin Files**
- Use FTP/SFTP or hosting file manager
- Upload to `wp-content/plugins/broken-link-checker/`

3. **Activate Plugin**
```bash
wp plugin activate broken-link-checker
```

4. **Verify Installation**
```bash
# Check database version
wp option get wsblc_options --format=json | grep current_db_version
# Should output: "current_db_version":10

# Check tables exist
wp db query "SHOW TABLES LIKE 'wp_blc_seo_%'"
```

5. **Run Initial Scan**
- Navigate to **Tools > SEO Issues**
- Click **"Scan All Posts"**
- Monitor progress (may take several minutes for large sites)

6. **Configure Cron**
```bash
# Verify cron scheduled
wp cron event list | grep blc_seo_scan_hook
```

### Post-Deployment Verification

**Run Health Check:**
```php
// Add to a test page or functions.php
add_action('wp_footer', function() {
    if (current_user_can('manage_options')) {
        echo '<!-- BLC Phase 1 Health Check -->';
        echo '<!-- DB Version: ' . blc_get_configuration()->get('current_db_version') . ' -->';
        echo '<!-- SEO Enabled: ' . (blc_get_configuration()->get('seo_analysis_enabled') ? 'YES' : 'NO') . ' -->';

        global $wpdb;
        $seo_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}blc_seo_elements");
        echo '<!-- SEO Records: ' . $seo_count . ' -->';
    }
});
```

**Monitor Cron Execution:**
```bash
# Watch cron events
watch -n 60 'wp cron event list | grep blc'
```

---

## Performance Optimization

### For Large Sites (10,000+ Posts)

**1. Increase Batch Size (Careful!)**
```php
// In functions.php - only if server can handle it
add_filter('blc_seo_batch_size', function($size) {
    return 100; // Default is 50
});
```

**2. Disable Real-time Analysis**
```php
// Disable automatic scanning on post save
remove_action('save_post', 'blc_trigger_seo_analysis');

// Rely on weekly cron instead
```

**3. Schedule Scans During Low Traffic**
```php
// Run scans at 3 AM
add_filter('blc_seo_scan_time', function() {
    return strtotime('tomorrow 3:00 AM');
});
```

**4. Database Optimization**
```sql
-- Add indexes if not present
ALTER TABLE wp_blc_seo_elements ADD INDEX idx_post_id (post_id);
ALTER TABLE wp_blc_headings ADD INDEX idx_post_level (post_id, level);
ALTER TABLE wp_blc_image_seo ADD INDEX idx_has_alt (has_alt_text);
```

---

## Integration Examples

### Example 1: Display SEO Health Score on Dashboard

```php
add_action('wp_dashboard_setup', function() {
    wp_add_dashboard_widget(
        'blc_seo_health_widget',
        'SEO Health Score',
        function() {
            if (function_exists('blc_get_seo_health_score')) {
                $score = blc_get_seo_health_score();
                $color = $score >= 80 ? 'green' : ($score >= 60 ? 'orange' : 'red');

                echo '<div style="text-align:center;">';
                echo '<h2 style="color:' . $color . '; font-size:48px; margin:0;">' . $score . '</h2>';
                echo '<p>SEO Health Score</p>';
                echo '<a href="' . admin_url('tools.php?page=blc-seo-issues') . '" class="button">View Issues</a>';
                echo '</div>';
            }
        }
    );
});
```

### Example 2: Email Weekly SEO Report

```php
add_action('blc_seo_scan_hook', function() {
    // Run after weekly scan
    $title_analyzer = new blcSeoTitlesAnalyzer();
    $heading_analyzer = new blcHeadingAnalyzer();
    $image_analyzer = new blcImageSeoAnalyzer();

    $title_issues = $title_analyzer->get_issues_count();
    $heading_issues = $heading_analyzer->get_issues_count();
    $image_issues = $image_analyzer->get_issues_count();

    $total = $title_issues['total_issues'] +
             $heading_issues['total_issues'] +
             $image_issues['images_without_alt'];

    $message = "Weekly SEO Report\n\n";
    $message .= "Total Issues: $total\n";
    $message .= "- Title/Meta: " . $title_issues['total_issues'] . "\n";
    $message .= "- Headings: " . $heading_issues['total_issues'] . "\n";
    $message .= "- Images: " . $image_issues['images_without_alt'] . "\n\n";
    $message .= "View details: " . admin_url('tools.php?page=blc-seo-issues');

    wp_mail(
        get_option('admin_email'),
        'Weekly SEO Report',
        $message
    );
}, 20);
```

### Example 3: Custom Issue Notifications

```php
add_action('save_post', function($post_id) {
    // After post analysis
    global $wpdb;

    $issues = $wpdb->get_var($wpdb->prepare(
        "SELECT issues FROM {$wpdb->prefix}blc_seo_elements WHERE post_id = %d",
        $post_id
    ));

    if ($issues) {
        $issues_array = json_decode($issues, true);
        if (!empty($issues_array)) {
            // Send notification to post author
            $post = get_post($post_id);
            $author_email = get_the_author_meta('user_email', $post->post_author);

            wp_mail(
                $author_email,
                'SEO Issues Detected in Your Post',
                'Your post "' . $post->post_title . '" has ' . count($issues_array) . ' SEO issues. Please review.'
            );
        }
    }
}, 100);
```

---

## Uninstallation

### Clean Removal

**1. Deactivate Plugin**
- **Plugins > Installed Plugins**
- Click "Deactivate" under Broken Link Checker

**2. Delete Plugin (Optional)**
- Click "Delete" to remove files
- This will trigger `uninstall.php` which removes:
  - All database tables
  - All plugin options
  - All scheduled cron events

**3. Manual Database Cleanup (if needed)**
```sql
-- Remove SEO tables
DROP TABLE IF EXISTS wp_blc_seo_elements;
DROP TABLE IF EXISTS wp_blc_headings;
DROP TABLE IF EXISTS wp_blc_image_seo;

-- Remove core BLC tables
DROP TABLE IF EXISTS wp_blc_links;
DROP TABLE IF EXISTS wp_blc_instances;
DROP TABLE IF EXISTS wp_blc_synch;
DROP TABLE IF EXISTS wp_blc_filters;

-- Remove options
DELETE FROM wp_options WHERE option_name = 'wsblc_options';
DELETE FROM wp_options WHERE option_name = 'blc_installation_log';
```

---

## Support & Resources

**Documentation:**
- `PHASE_1_IMPLEMENTATION.md` - Feature documentation
- `SCREAMING_FROG_FEATURE_ALIGNMENT.md` - Full roadmap
- `REPOSITORY_REVIEW.md` - Security & code review

**Debugging:**
```php
// Enable BLC logging (in wp-config.php)
define('BLC_DEBUG', true);

// Check logs in wp-content/debug.log
```

**WP-CLI Commands:**
```bash
# Plugin info
wp plugin status broken-link-checker

# Force database upgrade
wp eval "require_once(WP_PLUGIN_DIR . '/broken-link-checker/includes/admin/db-upgrade.php'); blcDatabaseUpgrader::upgrade_database();"

# Run SEO scan
wp eval "blc_scan_seo_all_posts();"

# Get SEO health score
wp eval "echo blc_get_seo_health_score();"
```

---

## Next Steps

After successful installation and testing:

1. ✅ Review SEO issues in your content
2. ✅ Fix critical issues (missing H1, missing alt text)
3. ✅ Optimize titles and meta descriptions
4. ✅ Monitor weekly scan reports
5. ✅ Prepare for Phase 2 features (coming Q2 2025)

---

**Setup Complete!** 🎉

Your Broken Link Checker is now a comprehensive SEO auditing platform.

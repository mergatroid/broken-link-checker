<?php
/**
 * Database schema for SEO analysis features (Phase 1)
 *
 * @package Broken Link Checker
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! function_exists( 'blc_get_seo_db_schema' ) ) {

	/**
	 * Get the database schema for SEO analysis tables
	 *
	 * @return string SQL schema definition
	 */
	function blc_get_seo_db_schema() {
		global $wpdb;

		// Use the character set and collation that's configured for WP tables
		$charset_collate = '';
		if ( ! empty( $wpdb->charset ) ) {
			$charset = str_replace( '-', '', $wpdb->charset );
			$charset_collate = "DEFAULT CHARACTER SET {$charset}";
		}
		if ( ! empty( $wpdb->collate ) ) {
			$charset_collate .= " COLLATE {$wpdb->collate}";
		}

		$seo_schema = <<<EOM

CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}blc_seo_elements` (
	`element_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`post_id` BIGINT UNSIGNED NOT NULL,
	`post_type` VARCHAR(40) NOT NULL DEFAULT 'post',
	`title` VARCHAR(255) DEFAULT NULL,
	`title_length` INT DEFAULT NULL,
	`meta_description` TEXT DEFAULT NULL,
	`meta_description_length` INT DEFAULT NULL,
	`canonical_url` TEXT DEFAULT NULL,
	`robots_meta` VARCHAR(100) DEFAULT NULL,
	`has_title` BOOLEAN DEFAULT FALSE,
	`has_meta_description` BOOLEAN DEFAULT FALSE,
	`title_optimal` BOOLEAN DEFAULT FALSE,
	`meta_optimal` BOOLEAN DEFAULT FALSE,
	`last_checked` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
	`issues` TEXT DEFAULT NULL,

	PRIMARY KEY (`element_id`),
	KEY `post_id` (`post_id`),
	KEY `post_type` (`post_type`),
	KEY `last_checked` (`last_checked`),
	KEY `title_optimal` (`title_optimal`),
	KEY `meta_optimal` (`meta_optimal`)
) {$charset_collate};

CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}blc_headings` (
	`heading_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`post_id` BIGINT UNSIGNED NOT NULL,
	`level` TINYINT NOT NULL,
	`text` TEXT NOT NULL,
	`text_length` INT DEFAULT NULL,
	`position` INT DEFAULT NULL,
	`hierarchy_valid` BOOLEAN DEFAULT TRUE,
	`last_checked` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',

	PRIMARY KEY (`heading_id`),
	KEY `post_id` (`post_id`),
	KEY `level` (`level`),
	KEY `last_checked` (`last_checked`)
) {$charset_collate};

CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}blc_image_seo` (
	`image_seo_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`instance_id` BIGINT UNSIGNED NOT NULL,
	`post_id` BIGINT UNSIGNED NOT NULL,
	`image_url` TEXT NOT NULL,
	`alt_text` TEXT DEFAULT NULL,
	`alt_text_length` INT DEFAULT NULL,
	`has_alt_text` BOOLEAN DEFAULT FALSE,
	`title_attribute` VARCHAR(255) DEFAULT NULL,
	`image_file_size` INT DEFAULT NULL,
	`image_dimensions` VARCHAR(20) DEFAULT NULL,
	`last_checked` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',

	PRIMARY KEY (`image_seo_id`),
	KEY `instance_id` (`instance_id`),
	KEY `post_id` (`post_id`),
	KEY `has_alt_text` (`has_alt_text`),
	KEY `last_checked` (`last_checked`)
) {$charset_collate};

EOM;

		return $seo_schema;
	}

}

<?php
/*
 * Plugin Name: POM Bloom
 * Version: 1.0
 * Plugin URI: https://github.com/jloosli/pom-bloom/
 * Description: Power of Moms Bloom Program.
 * Author: Jared Loosli
 * Author URI: https://github.com/jloosli/
 * Requires at least: 4.0
 * Tested up to: 4.0
 *
 * Text Domain: pom-bloom
 * Domain Path: /lang/
 *
 * @package WordPress
 * @author Jared Loosli
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Load plugin class files
require_once( 'includes/class-pom-bloom.php' );
require_once( 'includes/class-pom-bloom-settings.php' );

// Load plugin libraries
require_once( 'includes/lib/class-pom-bloom-admin-api.php' );
require_once( 'includes/lib/class-pom-bloom-post-type.php' );
require_once( 'includes/lib/class-pom-bloom-taxonomy.php' );

/**
 * Returns the main instance of POM_Bloom to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return object POM_Bloom
 */
function POM_Bloom () {
	$instance = POM_Bloom::instance( __FILE__, '1.0.0' );

	if ( is_null( $instance->settings ) ) {
		$instance->settings = POM_Bloom_Settings::instance( $instance );
	}

	return $instance;
}

POM_Bloom();
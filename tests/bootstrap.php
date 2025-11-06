<?php
/**
 * Bootstrap file for PHPUnit tests
 */

// Define constants for testing environment
define('ABSPATH', dirname(__DIR__) . '/');
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

// Load WordPress test framework
$_tests_dir = getenv('WP_TESTS_DIR');
if (!$_tests_dir) {
    $_tests_dir = '/tmp/wordpress-tests-lib';
}

// Give access to tests_add_filter() function
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested
 */
function _manually_load_plugin() {
    require dirname(__DIR__) . '/visitor-contact-collector.php';
}
tests_add_filter('muplugins_loaded', '_manually_load_plugin');

// Start up the WP testing environment
require $_tests_dir . '/includes/bootstrap.php';

// Load test helper classes
require_once dirname(__FILE__) . '/helpers/class-test-helper.php';
require_once dirname(__FILE__) . '/helpers/class-mock-wp-functions.php';
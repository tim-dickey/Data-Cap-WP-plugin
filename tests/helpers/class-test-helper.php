<?php
/**
 * Test Helper Class
 * Provides utility methods for testing
 */

class VCC_Test_Helper {
    
    /**
     * Create a test contact data array
     */
    public static function get_test_contact_data($overrides = array()) {
        $defaults = array(
            'full_name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'phone' => '+1 (555) 123-4567',
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0 (Test Browser)',
            'consent_given' => 1,
            'submission_date' => current_time('mysql'),
            'source_page' => 'http://example.com/contact'
        );
        
        return array_merge($defaults, $overrides);
    }
    
    /**
     * Create multiple test contacts
     */
    public static function create_test_contacts($count = 5) {
        $contacts = array();
        
        for ($i = 1; $i <= $count; $i++) {
            $contacts[] = self::get_test_contact_data(array(
                'full_name' => "Test User {$i}",
                'email' => "test{$i}@example.com",
                'phone' => "+1 (555) 123-456{$i}"
            ));
        }
        
        return $contacts;
    }
    
    /**
     * Clean up test data
     */
    public static function cleanup_test_data() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vcc_contacts';
        $wpdb->query("DELETE FROM {$table_name} WHERE email LIKE '%@example.com'");
    }
    
    /**
     * Assert array has required keys
     */
    public static function assertArrayHasKeys($keys, $array, $message = '') {
        foreach ($keys as $key) {
            if (!array_key_exists($key, $array)) {
                throw new Exception($message ?: "Array missing required key: {$key}");
            }
        }
    }
    
    /**
     * Generate random email
     */
    public static function generate_random_email() {
        return 'test' . wp_rand(1000, 9999) . '@example.com';
    }
    
    /**
     * Generate random phone number
     */
    public static function generate_random_phone() {
        return '+1 (555) ' . wp_rand(100, 999) . '-' . wp_rand(1000, 9999);
    }
    
    /**
     * Mock WordPress functions for testing
     */
    public static function mock_wp_functions() {
        if (!function_exists('wp_verify_nonce')) {
            function wp_verify_nonce($nonce, $action = -1) {
                return true;
            }
        }
        
        if (!function_exists('sanitize_text_field')) {
            function sanitize_text_field($str) {
                return trim(strip_tags($str));
            }
        }
        
        if (!function_exists('sanitize_email')) {
            function sanitize_email($email) {
                return filter_var($email, FILTER_SANITIZE_EMAIL);
            }
        }
        
        if (!function_exists('is_email')) {
            function is_email($email) {
                return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
            }
        }
        
        if (!function_exists('current_time')) {
            function current_time($type) {
                return date('Y-m-d H:i:s');
            }
        }
        
        if (!function_exists('wp_rand')) {
            function wp_rand($min = 0, $max = 0) {
                return rand($min, $max);
            }
        }
    }
    
    /**
     * Create test WordPress user
     */
    public static function create_test_user($role = 'administrator') {
        $user_id = wp_create_user(
            'testuser' . wp_rand(1000, 9999),
            'password',
            'testuser@example.com'
        );
        
        if (!is_wp_error($user_id)) {
            $user = new WP_User($user_id);
            $user->set_role($role);
            return $user_id;
        }
        
        return false;
    }
    
    /**
     * Clean up test users
     */
    public static function cleanup_test_users() {
        $users = get_users(array(
            'meta_query' => array(
                array(
                    'key' => 'user_email',
                    'value' => 'testuser@example.com',
                    'compare' => 'LIKE'
                )
            )
        ));
        
        foreach ($users as $user) {
            wp_delete_user($user->ID);
        }
    }
}
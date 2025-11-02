<?php
/**
 * Plugin Name: Visitor Contact Collector
 * Plugin URI: https://www.timdickey.com
 * Description: A WordPress plugin that collects visitor contact information (Full Name, Email, Mobile Phone) to build a contact list with GDPR compliance.
 * Version: 1.0.0
 * Author: Tim Dickey
 * Author URI: https://www.timdickey.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: visitor-contact-collector
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('VCC_VERSION', '1.0.0');
define('VCC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('VCC_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('VCC_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Plugin Class
 */
class VisitorContactCollector {
    
    /**
     * Instance of this class
     */
    private static $instance = null;
    
    /**
     * Get instance of the class
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        register_uninstall_hook(__FILE__, array('VisitorContactCollector', 'uninstall'));
    }
    
    /**
     * Initialize the plugin
     */
    public function init() {
        // Load text domain for translations
        load_plugin_textdomain('visitor-contact-collector', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Include required files
        $this->includes();
        
        // Initialize components
        $this->init_hooks();
    }
    
    /**
     * Include required files
     */
    private function includes() {
        require_once VCC_PLUGIN_PATH . 'includes/class-vcc-database.php';
        require_once VCC_PLUGIN_PATH . 'includes/class-vcc-admin.php';
        require_once VCC_PLUGIN_PATH . 'includes/class-vcc-frontend.php';
        require_once VCC_PLUGIN_PATH . 'includes/class-vcc-shortcode.php';
        require_once VCC_PLUGIN_PATH . 'includes/class-vcc-export.php';
        require_once VCC_PLUGIN_PATH . 'includes/class-vcc-gdpr.php';
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Initialize database
        VCC_Database::get_instance();
        
        // Initialize admin
        if (is_admin()) {
            VCC_Admin::get_instance();
        }
        
        // Initialize frontend
        if (!is_admin()) {
            VCC_Frontend::get_instance();
        }
        
        // Initialize shortcode
        VCC_Shortcode::get_instance();
        
        // Initialize export functionality
        VCC_Export::get_instance();
        
        // Initialize GDPR functionality
        VCC_GDPR::get_instance();
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create database table
        VCC_Database::create_table();
        
        // Set default options
        $default_options = array(
            'form_title' => __('Join Our Contact List', 'visitor-contact-collector'),
            'form_description' => __('Stay connected with us by sharing your contact information.', 'visitor-contact-collector'),
            'submit_button_text' => __('Submit', 'visitor-contact-collector'),
            'success_message' => __('Thank you for joining our contact list!', 'visitor-contact-collector'),
            'enable_gdpr' => 1,
            'gdpr_text' => __('I agree to the privacy policy and terms of service.', 'visitor-contact-collector'),
            'email_notifications' => 0,
            'notification_email' => get_option('admin_email'),
            'form_style' => 'rounded',
            'primary_color' => '#0073aa',
            'data_retention_days' => 365
        );
        
        add_option('vcc_settings', $default_options);
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin uninstall
     */
    public static function uninstall() {
        // Delete options
        delete_option('vcc_settings');
        
        // Drop database table
        VCC_Database::drop_table();
    }
    
    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_frontend_scripts() {
        wp_enqueue_style('vcc-frontend', VCC_PLUGIN_URL . 'assets/css/frontend.css', array(), VCC_VERSION);
        wp_enqueue_script('vcc-frontend', VCC_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), VCC_VERSION, true);
        
        // Localize script for AJAX
        wp_localize_script('vcc-frontend', 'vcc_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vcc_submit_nonce'),
            'messages' => array(
                'error' => __('An error occurred. Please try again.', 'visitor-contact-collector'),
                'invalid_email' => __('Please enter a valid email address.', 'visitor-contact-collector'),
                'invalid_phone' => __('Please enter a valid phone number.', 'visitor-contact-collector'),
                'required_field' => __('This field is required.', 'visitor-contact-collector'),
                'gdpr_required' => __('You must agree to the privacy policy.', 'visitor-contact-collector')
            )
        ));
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        // Only load on plugin pages
        if (strpos($hook, 'visitor-contact-collector') !== false) {
            wp_enqueue_style('vcc-admin', VCC_PLUGIN_URL . 'assets/css/admin.css', array(), VCC_VERSION);
            wp_enqueue_script('vcc-admin', VCC_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), VCC_VERSION, true);
            
            // Localize script for AJAX
            wp_localize_script('vcc-admin', 'vcc_admin_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('vcc_admin_nonce')
            ));
        }
    }
}

// Initialize the plugin
function vcc_init() {
    return VisitorContactCollector::get_instance();
}

// Start the plugin
add_action('plugins_loaded', 'vcc_init');

/**
 * Helper function to get plugin settings
 */
function vcc_get_settings() {
    $defaults = array(
        'form_title' => __('Join Our Contact List', 'visitor-contact-collector'),
        'form_description' => __('Stay connected with us by sharing your contact information.', 'visitor-contact-collector'),
        'submit_button_text' => __('Submit', 'visitor-contact-collector'),
        'success_message' => __('Thank you for joining our contact list!', 'visitor-contact-collector'),
        'enable_gdpr' => 1,
        'gdpr_text' => __('I agree to the privacy policy and terms of service.', 'visitor-contact-collector'),
        'email_notifications' => 0,
        'notification_email' => get_option('admin_email'),
        'form_style' => 'rounded',
        'primary_color' => '#0073aa',
        'data_retention_days' => 365
    );
    
    $settings = get_option('vcc_settings', $defaults);
    return wp_parse_args($settings, $defaults);
}

/**
 * Helper function to sanitize phone number
 */
function vcc_sanitize_phone($phone) {
    // Remove all non-numeric characters except + for international numbers
    return preg_replace('/[^+0-9]/', '', $phone);
}

/**
 * Helper function to validate email
 */
function vcc_validate_email($email) {
    return is_email($email);
}

/**
 * Helper function to validate phone number
 */
function vcc_validate_phone($phone) {
    $sanitized = vcc_sanitize_phone($phone);
    // Basic validation - at least 10 digits
    return preg_match('/^[\+]?[0-9]{10,15}$/', $sanitized);
}
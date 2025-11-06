<?php
/**
 * Environment Configuration Class
 * Handles loading and managing environment variables
 */

if (!defined('ABSPATH')) {
    exit;
}

class VCC_Config {
    
    /**
     * Singleton instance
     */
    private static $instance = null;
    
    /**
     * Configuration cache
     */
    private $config = array();
    
    /**
     * Environment variables loaded flag
     */
    private $env_loaded = false;
    
    /**
     * Get singleton instance
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
        $this->load_environment();
        $this->set_defaults();
    }
    
    /**
     * Load environment variables from .env file
     */
    private function load_environment() {
        $env_file = VCC_PLUGIN_PATH . '.env';
        
        if (file_exists($env_file)) {
            $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            
            foreach ($lines as $line) {
                // Skip comments and empty lines
                if (strpos($line, '#') === 0 || empty(trim($line))) {
                    continue;
                }
                
                // Parse key=value pairs
                if (strpos($line, '=') !== false) {
                    list($key, $value) = explode('=', $line, 2);
                    $key = trim($key);
                    $value = trim($value);
                    
                    // Remove quotes if present
                    if (preg_match('/^(["\'])(.*)\\1$/', $value, $matches)) {
                        $value = $matches[2];
                    }
                    
                    // Set environment variable if not already set
                    if (!array_key_exists($key, $_ENV)) {
                        $_ENV[$key] = $value;
                        putenv("$key=$value");
                    }
                }
            }
            
            $this->env_loaded = true;
        }
    }
    
    /**
     * Set default configuration values
     */
    private function set_defaults() {
        $this->config = array(
            // Application settings
            'environment' => $this->get_env('VCC_ENVIRONMENT', 'production'),
            'debug' => $this->get_env_bool('VCC_DEBUG', false),
            'log_level' => $this->get_env('VCC_LOG_LEVEL', 'info'),
            
            // Database settings
            'test_db_name' => $this->get_env('WP_TEST_DB_NAME', 'wordpress_test'),
            'test_db_user' => $this->get_env('WP_TEST_DB_USER', 'root'),
            'test_db_pass' => $this->get_env('WP_TEST_DB_PASS', 'root'),
            'test_db_host' => $this->get_env('WP_TEST_DB_HOST', 'localhost'),
            'test_db_port' => $this->get_env_int('WP_TEST_DB_PORT', 3306),
            'test_wp_version' => $this->get_env('WP_TEST_VERSION', 'latest'),
            
            // Email settings
            'default_admin_email' => $this->get_env('VCC_DEFAULT_ADMIN_EMAIL', get_option('admin_email', 'admin@example.com')),
            'email_notifications' => $this->get_env_bool('VCC_EMAIL_NOTIFICATIONS', true),
            'smtp_host' => $this->get_env('VCC_SMTP_HOST', ''),
            'smtp_port' => $this->get_env_int('VCC_SMTP_PORT', 587),
            'smtp_username' => $this->get_env('VCC_SMTP_USERNAME', ''),
            'smtp_password' => $this->get_env('VCC_SMTP_PASSWORD', ''),
            'smtp_encryption' => $this->get_env('VCC_SMTP_ENCRYPTION', 'tls'),
            
            // Security settings
            'rate_limit_enabled' => $this->get_env_bool('VCC_RATE_LIMIT_ENABLED', true),
            'rate_limit_attempts' => $this->get_env_int('VCC_RATE_LIMIT_ATTEMPTS', 5),
            'rate_limit_window' => $this->get_env_int('VCC_RATE_LIMIT_WINDOW', 900),
            'honeypot_field' => $this->get_env('VCC_HONEYPOT_FIELD', 'website_url'),
            'encryption_key' => $this->get_env('VCC_ENCRYPTION_KEY', ''),
            
            // GDPR settings
            'data_retention_days' => $this->get_env_int('VCC_DATA_RETENTION_DAYS', 730),
            'auto_cleanup' => $this->get_env_bool('VCC_AUTO_CLEANUP', true),
            'privacy_policy_url' => $this->get_env('VCC_PRIVACY_POLICY_URL', ''),
            'cookie_consent' => $this->get_env_bool('VCC_COOKIE_CONSENT', false),
            
            // API settings
            'mailchimp_api_key' => $this->get_env('VCC_MAILCHIMP_API_KEY', ''),
            'mailchimp_list_id' => $this->get_env('VCC_MAILCHIMP_LIST_ID', ''),
            'sendgrid_api_key' => $this->get_env('VCC_SENDGRID_API_KEY', ''),
            'recaptcha_site_key' => $this->get_env('VCC_RECAPTCHA_SITE_KEY', ''),
            'recaptcha_secret_key' => $this->get_env('VCC_RECAPTCHA_SECRET_KEY', ''),
            'webhook_url' => $this->get_env('VCC_WEBHOOK_URL', ''),
            'webhook_secret' => $this->get_env('VCC_WEBHOOK_SECRET', ''),
            
            // Deployment settings
            'svn_username' => $this->get_env('SVN_USERNAME', ''),
            'svn_password' => $this->get_env('SVN_PASSWORD', ''),
            'github_owner' => $this->get_env('GITHUB_OWNER', 'your-username'),
            'github_repo' => $this->get_env('GITHUB_REPO', 'Data-Cap-WP-plugin'),
            'codacy_project_token' => $this->get_env('CODACY_PROJECT_TOKEN', ''),
            'codacy_api_token' => $this->get_env('CODACY_API_TOKEN', ''),
            
            // Asset settings
            'use_cdn' => $this->get_env_bool('VCC_USE_CDN', false),
            'cdn_url' => $this->get_env('VCC_CDN_URL', ''),
            'minify_css' => $this->get_env_bool('VCC_MINIFY_CSS', true),
            'minify_js' => $this->get_env_bool('VCC_MINIFY_JS', true),
            'compress_images' => $this->get_env_bool('VCC_COMPRESS_IMAGES', true),
            
            // Testing settings
            'test_mode' => $this->get_env_bool('VCC_TEST_MODE', false),
            'test_email' => $this->get_env('VCC_TEST_EMAIL', 'test@example.com'),
            'coverage_threshold' => $this->get_env_int('VCC_COVERAGE_THRESHOLD', 80),
            'performance_tests' => $this->get_env_bool('VCC_PERFORMANCE_TESTS', false),
            'load_test_users' => $this->get_env_int('VCC_LOAD_TEST_USERS', 100),
            
            // Monitoring settings
            'error_reporting' => $this->get_env_bool('VCC_ERROR_REPORTING', true),
            'log_errors' => $this->get_env_bool('VCC_LOG_ERRORS', true),
            'log_file_path' => $this->get_env('VCC_LOG_FILE_PATH', ''),
            'google_analytics_id' => $this->get_env('VCC_GOOGLE_ANALYTICS_ID', ''),
            'tracking_enabled' => $this->get_env_bool('VCC_TRACKING_ENABLED', false),
            'health_check_enabled' => $this->get_env_bool('VCC_HEALTH_CHECK_ENABLED', true),
            
            // Custom settings
            'custom_css_path' => $this->get_env('VCC_CUSTOM_CSS_PATH', ''),
            'custom_js_path' => $this->get_env('VCC_CUSTOM_JS_PATH', ''),
            'form_theme' => $this->get_env('VCC_FORM_THEME', 'default'),
            'custom_fields' => $this->get_env('VCC_CUSTOM_FIELDS', ''),
            'email_template_path' => $this->get_env('VCC_EMAIL_TEMPLATE_PATH', ''),
        );
    }
    
    /**
     * Get configuration value
     */
    public function get($key, $default = null) {
        return isset($this->config[$key]) ? $this->config[$key] : $default;
    }
    
    /**
     * Set configuration value
     */
    public function set($key, $value) {
        $this->config[$key] = $value;
    }
    
    /**
     * Get all configuration values
     */
    public function get_all() {
        return $this->config;
    }
    
    /**
     * Check if running in development mode
     */
    public function is_development() {
        return $this->get('environment') === 'development';
    }
    
    /**
     * Check if debug mode is enabled
     */
    public function is_debug() {
        return $this->get('debug');
    }
    
    /**
     * Check if test mode is enabled
     */
    public function is_test_mode() {
        return $this->get('test_mode');
    }
    
    /**
     * Get environment variable as string
     */
    private function get_env($key, $default = '') {
        return isset($_ENV[$key]) ? $_ENV[$key] : $default;
    }
    
    /**
     * Get environment variable as boolean
     */
    private function get_env_bool($key, $default = false) {
        $value = $this->get_env($key);
        if ($value === '') {
            return $default;
        }
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
    
    /**
     * Get environment variable as integer
     */
    private function get_env_int($key, $default = 0) {
        $value = $this->get_env($key);
        return $value !== '' ? intval($value) : $default;
    }
    
    /**
     * Get environment variable as float
     */
    private function get_env_float($key, $default = 0.0) {
        $value = $this->get_env($key);
        return $value !== '' ? floatval($value) : $default;
    }
    
    /**
     * Check if environment file was loaded
     */
    public function env_file_loaded() {
        return $this->env_loaded;
    }
    
    /**
     * Get database configuration for testing
     */
    public function get_test_db_config() {
        return array(
            'name' => $this->get('test_db_name'),
            'user' => $this->get('test_db_user'),
            'pass' => $this->get('test_db_pass'),
            'host' => $this->get('test_db_host'),
            'port' => $this->get('test_db_port'),
            'wp_version' => $this->get('test_wp_version')
        );
    }
    
    /**
     * Get SMTP configuration
     */
    public function get_smtp_config() {
        return array(
            'host' => $this->get('smtp_host'),
            'port' => $this->get('smtp_port'),
            'username' => $this->get('smtp_username'),
            'password' => $this->get('smtp_password'),
            'encryption' => $this->get('smtp_encryption')
        );
    }
    
    /**
     * Get security configuration
     */
    public function get_security_config() {
        return array(
            'rate_limit_enabled' => $this->get('rate_limit_enabled'),
            'rate_limit_attempts' => $this->get('rate_limit_attempts'),
            'rate_limit_window' => $this->get('rate_limit_window'),
            'honeypot_field' => $this->get('honeypot_field'),
            'encryption_key' => $this->get('encryption_key')
        );
    }
}
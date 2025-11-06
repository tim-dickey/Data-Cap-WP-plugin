<?php
/**
 * Mock WordPress Functions for Unit Testing
 */

class VCC_Mock_WP_Functions {
    
    private static $options = array();
    private static $transients = array();
    private static $current_user_id = 1;
    
    /**
     * Mock get_option
     */
    public static function get_option($option, $default = false) {
        return isset(self::$options[$option]) ? self::$options[$option] : $default;
    }
    
    /**
     * Mock update_option
     */
    public static function update_option($option, $value) {
        self::$options[$option] = $value;
        return true;
    }
    
    /**
     * Mock delete_option
     */
    public static function delete_option($option) {
        unset(self::$options[$option]);
        return true;
    }
    
    /**
     * Mock get_transient
     */
    public static function get_transient($transient) {
        return isset(self::$transients[$transient]) ? self::$transients[$transient] : false;
    }
    
    /**
     * Mock set_transient
     */
    public static function set_transient($transient, $value, $expiration = 0) {
        self::$transients[$transient] = $value;
        return true;
    }
    
    /**
     * Mock delete_transient
     */
    public static function delete_transient($transient) {
        unset(self::$transients[$transient]);
        return true;
    }
    
    /**
     * Mock get_current_user_id
     */
    public static function get_current_user_id() {
        return self::$current_user_id;
    }
    
    /**
     * Set current user ID for testing
     */
    public static function set_current_user_id($user_id) {
        self::$current_user_id = $user_id;
    }
    
    /**
     * Mock current_user_can
     */
    public static function current_user_can($capability) {
        // For testing, assume admin user
        return in_array($capability, array(
            'manage_options',
            'export',
            'delete_users',
            'edit_posts'
        ));
    }
    
    /**
     * Mock wp_die
     */
    public static function wp_die($message, $title = '', $args = array()) {
        throw new Exception("wp_die called: {$message}");
    }
    
    /**
     * Mock wp_redirect
     */
    public static function wp_redirect($location, $status = 302) {
        return true;
    }
    
    /**
     * Mock wp_mail
     */
    public static function wp_mail($to, $subject, $message, $headers = '', $attachments = array()) {
        // Store email data for testing
        self::$sent_emails[] = array(
            'to' => $to,
            'subject' => $subject,
            'message' => $message,
            'headers' => $headers,
            'attachments' => $attachments
        );
        return true;
    }
    
    private static $sent_emails = array();
    
    /**
     * Get sent emails for testing
     */
    public static function get_sent_emails() {
        return self::$sent_emails;
    }
    
    /**
     * Clear sent emails
     */
    public static function clear_sent_emails() {
        self::$sent_emails = array();
    }
    
    /**
     * Reset all mock data
     */
    public static function reset() {
        self::$options = array();
        self::$transients = array();
        self::$current_user_id = 1;
        self::$sent_emails = array();
    }
}
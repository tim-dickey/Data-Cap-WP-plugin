<?php
/**
 * GDPR Compliance Class
 * Handles GDPR compliance features including data retention, deletion requests, and privacy tools
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class VCC_GDPR {
    
    /**
     * Instance of this class
     */
    private static $instance = null;
    
    /**
     * Database instance
     */
    private $db;
    
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
        $this->db = VCC_Database::get_instance();
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // WordPress privacy hooks
        add_filter('wp_privacy_personal_data_exporters', array($this, 'register_data_exporter'));
        add_filter('wp_privacy_personal_data_erasers', array($this, 'register_data_eraser'));
        
        // Scheduled cleanup
        add_action('vcc_cleanup_old_data', array($this, 'cleanup_old_data'));
        
        // Admin hooks
        add_action('wp_ajax_vcc_gdpr_export', array($this, 'handle_gdpr_export'));
        add_action('wp_ajax_vcc_gdpr_delete', array($this, 'handle_gdpr_delete'));
        
        // Schedule cleanup if not already scheduled
        if (!wp_next_scheduled('vcc_cleanup_old_data')) {
            wp_schedule_event(time(), 'daily', 'vcc_cleanup_old_data');
        }
        
        // Privacy policy suggestions
        add_action('admin_init', array($this, 'add_privacy_policy_content'));
    }
    
    /**
     * Register personal data exporter
     */
    public function register_data_exporter($exporters) {
        $exporters['visitor-contact-collector'] = array(
            'exporter_friendly_name' => __('Visitor Contact Collector', 'visitor-contact-collector'),
            'callback' => array($this, 'export_personal_data'),
        );
        
        return $exporters;
    }
    
    /**
     * Register personal data eraser
     */
    public function register_data_eraser($erasers) {
        $erasers['visitor-contact-collector'] = array(
            'eraser_friendly_name' => __('Visitor Contact Collector', 'visitor-contact-collector'),
            'callback' => array($this, 'erase_personal_data'),
        );
        
        return $erasers;
    }
    
    /**
     * Export personal data for GDPR request
     */
    public function export_personal_data($email_address, $page = 1) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vcc_contacts';
        $per_page = 100;
        $offset = ($page - 1) * $per_page;
        
        $contacts = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE email = %s AND status != 'deleted' ORDER BY submission_date DESC LIMIT %d OFFSET %d",
            $email_address,
            $per_page,
            $offset
        ));
        
        $export_items = array();
        
        foreach ($contacts as $contact) {
            $item_id = "vcc-contact-{$contact->id}";
            
            $group_id = 'visitor-contact-collector';
            $group_label = __('Contact Form Submissions', 'visitor-contact-collector');
            
            $data = array();
            
            // Basic contact information
            $data[] = array(
                'name' => __('Full Name', 'visitor-contact-collector'),
                'value' => $contact->full_name
            );
            
            $data[] = array(
                'name' => __('Email Address', 'visitor-contact-collector'),
                'value' => $contact->email
            );
            
            $data[] = array(
                'name' => __('Phone Number', 'visitor-contact-collector'),
                'value' => $contact->phone
            );
            
            $data[] = array(
                'name' => __('Submission Date', 'visitor-contact-collector'),
                'value' => $contact->submission_date
            );
            
            // Consent information
            if ($contact->consent_given) {
                $data[] = array(
                    'name' => __('Consent Given', 'visitor-contact-collector'),
                    'value' => __('Yes', 'visitor-contact-collector')
                );
                
                if ($contact->consent_timestamp) {
                    $data[] = array(
                        'name' => __('Consent Date', 'visitor-contact-collector'),
                        'value' => $contact->consent_timestamp
                    );
                }
            } else {
                $data[] = array(
                    'name' => __('Consent Given', 'visitor-contact-collector'),
                    'value' => __('No', 'visitor-contact-collector')
                );
            }
            
            // Technical information (if available)
            if (!empty($contact->ip_address)) {
                $data[] = array(
                    'name' => __('IP Address', 'visitor-contact-collector'),
                    'value' => $contact->ip_address
                );
            }
            
            if (!empty($contact->user_agent)) {
                $data[] = array(
                    'name' => __('User Agent', 'visitor-contact-collector'),
                    'value' => $contact->user_agent
                );
            }
            
            $export_items[] = array(
                'group_id' => $group_id,
                'group_label' => $group_label,
                'item_id' => $item_id,
                'data' => $data,
            );
        }
        
        // Check if there are more contacts to export
        $total_contacts = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE email = %s AND status != 'deleted'",
            $email_address
        ));
        
        $done = count($contacts) < $per_page || ($offset + count($contacts)) >= $total_contacts;
        
        return array(
            'data' => $export_items,
            'done' => $done,
        );
    }
    
    /**
     * Erase personal data for GDPR request
     */
    public function erase_personal_data($email_address, $page = 1) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vcc_contacts';
        $per_page = 100;
        $offset = ($page - 1) * $per_page;
        
        $contacts = $wpdb->get_results($wpdb->prepare(
            "SELECT id FROM $table_name WHERE email = %s AND status != 'deleted' LIMIT %d OFFSET %d",
            $email_address,
            $per_page,
            $offset
        ));
        
        $items_removed = 0;
        $items_retained = 0;
        $messages = array();
        
        foreach ($contacts as $contact) {
            // Check if we should retain this data for legal reasons
            if ($this->should_retain_data($contact->id)) {
                $items_retained++;
                continue;
            }
            
            // Anonymize the data instead of deleting completely
            $anonymized = $this->anonymize_contact($contact->id);
            
            if ($anonymized) {
                $items_removed++;
            } else {
                $messages[] = sprintf(
                    __('Failed to anonymize contact with ID %d', 'visitor-contact-collector'),
                    $contact->id
                );
            }
        }
        
        // Check if there are more contacts to process
        $remaining_contacts = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE email = %s AND status != 'deleted'",
            $email_address
        ));
        
        $done = count($contacts) < $per_page || $remaining_contacts <= $per_page;
        
        return array(
            'items_removed' => $items_removed,
            'items_retained' => $items_retained,
            'messages' => $messages,
            'done' => $done,
        );
    }
    
    /**
     * Check if data should be retained for legal reasons
     */
    private function should_retain_data($contact_id) {
        // Add your business logic here
        // For example, you might want to retain data for active customers
        // or data that's required for legal compliance
        
        // This is a placeholder - customize based on your needs
        return false;
    }
    
    /**
     * Anonymize contact data
     */
    private function anonymize_contact($contact_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vcc_contacts';
        
        $result = $wpdb->update(
            $table_name,
            array(
                'full_name' => __('[Anonymized]', 'visitor-contact-collector'),
                'email' => 'anonymized@localhost.local',
                'phone' => '[Anonymized]',
                'ip_address' => '',
                'user_agent' => '',
                'status' => 'anonymized',
                'last_modified' => current_time('mysql')
            ),
            array('id' => $contact_id),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s'),
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Cleanup old data based on retention policy
     */
    public function cleanup_old_data() {
        $settings = vcc_get_settings();
        $retention_days = (int) $settings['data_retention_days'];
        
        if ($retention_days <= 0) {
            return; // No automatic cleanup
        }
        
        $deleted_count = $this->db->cleanup_old_contacts();
        
        // Log cleanup activity
        if ($deleted_count > 0) {
            error_log("VCC: Automatically marked {$deleted_count} old contacts as deleted based on {$retention_days} day retention policy.");
        }
        
        return $deleted_count;
    }
    
    /**
     * Handle GDPR export request from admin
     */
    public function handle_gdpr_export() {
        check_ajax_referer('vcc_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'visitor-contact-collector')));
        }
        
        $email = sanitize_email($_POST['email']);
        
        if (!$email) {
            wp_send_json_error(array('message' => __('Invalid email address.', 'visitor-contact-collector')));
        }
        
        $export_data = $this->export_personal_data($email);
        
        wp_send_json_success(array(
            'data' => $export_data,
            'message' => __('Data exported successfully.', 'visitor-contact-collector')
        ));
    }
    
    /**
     * Handle GDPR deletion request from admin
     */
    public function handle_gdpr_delete() {
        check_ajax_referer('vcc_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'visitor-contact-collector')));
        }
        
        $email = sanitize_email($_POST['email']);
        
        if (!$email) {
            wp_send_json_error(array('message' => __('Invalid email address.', 'visitor-contact-collector')));
        }
        
        $erase_data = $this->erase_personal_data($email);
        
        $message = sprintf(
            __('%d items removed, %d items retained.', 'visitor-contact-collector'),
            $erase_data['items_removed'],
            $erase_data['items_retained']
        );
        
        wp_send_json_success(array(
            'data' => $erase_data,
            'message' => $message
        ));
    }
    
    /**
     * Add privacy policy content suggestions
     */
    public function add_privacy_policy_content() {
        if (function_exists('wp_add_privacy_policy_content')) {
            $content = $this->get_privacy_policy_content();
            
            wp_add_privacy_policy_content(
                __('Visitor Contact Collector', 'visitor-contact-collector'),
                $content
            );
        }
    }
    
    /**
     * Get privacy policy content suggestions
     */
    private function get_privacy_policy_content() {
        $content = '<h2>' . __('Contact Form Data Collection', 'visitor-contact-collector') . '</h2>';
        
        $content .= '<p>' . __('When you submit our contact form, we collect the following information:', 'visitor-contact-collector') . '</p>';
        
        $content .= '<ul>';
        $content .= '<li>' . __('Your full name', 'visitor-contact-collector') . '</li>';
        $content .= '<li>' . __('Your email address', 'visitor-contact-collector') . '</li>';
        $content .= '<li>' . __('Your phone number', 'visitor-contact-collector') . '</li>';
        $content .= '<li>' . __('The date and time of your submission', 'visitor-contact-collector') . '</li>';
        $content .= '<li>' . __('Your IP address (for security purposes)', 'visitor-contact-collector') . '</li>';
        $content .= '<li>' . __('Your browser information (user agent)', 'visitor-contact-collector') . '</li>';
        $content .= '</ul>';
        
        $content .= '<h3>' . __('How We Use Your Information', 'visitor-contact-collector') . '</h3>';
        $content .= '<p>' . __('We use the information you provide to:', 'visitor-contact-collector') . '</p>';
        
        $content .= '<ul>';
        $content .= '<li>' . __('Respond to your inquiries', 'visitor-contact-collector') . '</li>';
        $content .= '<li>' . __('Send you information you have requested', 'visitor-contact-collector') . '</li>';
        $content .= '<li>' . __('Maintain our contact database for legitimate business purposes', 'visitor-contact-collector') . '</li>';
        $content .= '</ul>';
        
        $content .= '<h3>' . __('Data Retention', 'visitor-contact-collector') . '</h3>';
        
        $settings = vcc_get_settings();
        $retention_days = (int) $settings['data_retention_days'];
        
        if ($retention_days > 0) {
            $content .= '<p>' . sprintf(
                __('We will retain your contact information for %d days from the date of submission, after which it will be automatically removed from our systems.', 'visitor-contact-collector'),
                $retention_days
            ) . '</p>';
        } else {
            $content .= '<p>' . __('We will retain your contact information until you request its removal or we no longer have a legitimate business need for it.', 'visitor-contact-collector') . '</p>';
        }
        
        $content .= '<h3>' . __('Your Rights', 'visitor-contact-collector') . '</h3>';
        $content .= '<p>' . __('Under data protection regulations, you have the right to:', 'visitor-contact-collector') . '</p>';
        
        $content .= '<ul>';
        $content .= '<li>' . __('Request access to your personal data', 'visitor-contact-collector') . '</li>';
        $content .= '<li>' . __('Request correction of your personal data', 'visitor-contact-collector') . '</li>';
        $content .= '<li>' . __('Request deletion of your personal data', 'visitor-contact-collector') . '</li>';
        $content .= '<li>' . __('Request restriction of processing your personal data', 'visitor-contact-collector') . '</li>';
        $content .= '<li>' . __('Object to processing of your personal data', 'visitor-contact-collector') . '</li>';
        $content .= '<li>' . __('Request transfer of your personal data', 'visitor-contact-collector') . '</li>';
        $content .= '</ul>';
        
        $content .= '<p>' . __('To exercise any of these rights, please contact us using the information provided on this website.', 'visitor-contact-collector') . '</p>';
        
        return $content;
    }
    
    /**
     * Get consent statistics
     */
    public function get_consent_statistics() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vcc_contacts';
        
        $total = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'active'");
        $with_consent = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'active' AND consent_given = 1");
        
        return array(
            'total' => (int) $total,
            'with_consent' => (int) $with_consent,
            'consent_rate' => $total > 0 ? round(($with_consent / $total) * 100, 2) : 0
        );
    }
    
    /**
     * Generate data processing record
     */
    public function generate_processing_record() {
        $settings = vcc_get_settings();
        $stats = $this->get_consent_statistics();
        
        return array(
            'purpose' => __('Contact form data collection for customer communication', 'visitor-contact-collector'),
            'legal_basis' => __('Consent (Article 6(1)(a) GDPR)', 'visitor-contact-collector'),
            'data_categories' => array(
                __('Identity data (name)', 'visitor-contact-collector'),
                __('Contact data (email, phone)', 'visitor-contact-collector'),
                __('Technical data (IP address, browser info)', 'visitor-contact-collector')
            ),
            'data_subjects' => __('Website visitors who submit contact forms', 'visitor-contact-collector'),
            'retention_period' => $settings['data_retention_days'] > 0 ? 
                sprintf(__('%d days', 'visitor-contact-collector'), $settings['data_retention_days']) : 
                __('Until consent is withdrawn or no longer needed', 'visitor-contact-collector'),
            'current_records' => $stats['total'],
            'consent_rate' => $stats['consent_rate'] . '%',
            'last_updated' => current_time('mysql')
        );
    }
}
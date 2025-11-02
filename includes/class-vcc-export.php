<?php
/**
 * Export Handler Class
 * Handles CSV and JSON export functionality
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class VCC_Export {
    
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
        add_action('wp_ajax_vcc_export_contacts', array($this, 'handle_export'));
        add_action('admin_post_vcc_export_contacts', array($this, 'handle_export'));
    }
    
    /**
     * Handle export request
     */
    public function handle_export() {
        // Security check
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to export contacts.', 'visitor-contact-collector'));
        }
        
        // Verify nonce
        if (!wp_verify_nonce($_REQUEST['vcc_export_nonce'], 'vcc_export_nonce')) {
            wp_die(__('Security check failed.', 'visitor-contact-collector'));
        }
        
        // Get export parameters
        $format = isset($_REQUEST['export_format']) ? sanitize_text_field($_REQUEST['export_format']) : 'csv';
        $date_range = isset($_REQUEST['date_range']) ? sanitize_text_field($_REQUEST['date_range']) : 'all';
        $include_fields = isset($_REQUEST['include_fields']) ? array_map('sanitize_text_field', $_REQUEST['include_fields']) : array('full_name', 'email', 'phone');
        $contact_ids = isset($_REQUEST['contact_ids']) ? array_map('intval', explode(',', $_REQUEST['contact_ids'])) : array();
        
        // Build query arguments
        $args = array(
            'per_page' => -1, // Get all contacts
            'status' => 'active'
        );
        
        // Apply date range filter
        $this->apply_date_filter($args, $date_range);
        
        // Get contacts
        if (!empty($contact_ids)) {
            $contacts = $this->get_contacts_by_ids($contact_ids);
        } else {
            $contacts = $this->db->get_contacts($args);
        }
        
        // Generate export based on format
        if ($format === 'json') {
            $this->export_json($contacts, $include_fields);
        } else {
            $this->export_csv($contacts, $include_fields);
        }
    }
    
    /**
     * Apply date filter to query arguments
     */
    private function apply_date_filter(&$args, $date_range) {
        global $wpdb;
        
        switch ($date_range) {
            case 'last_7_days':
                $start_date = date('Y-m-d H:i:s', strtotime('-7 days'));
                break;
                
            case 'last_30_days':
                $start_date = date('Y-m-d H:i:s', strtotime('-30 days'));
                break;
                
            case 'custom':
                $start_date = isset($_REQUEST['start_date']) ? sanitize_text_field($_REQUEST['start_date']) . ' 00:00:00' : null;
                $end_date = isset($_REQUEST['end_date']) ? sanitize_text_field($_REQUEST['end_date']) . ' 23:59:59' : null;
                
                if ($start_date && $end_date) {
                    // For custom date range, we need to modify the database query directly
                    add_filter('vcc_contacts_query_where', function($where) use ($wpdb, $start_date, $end_date) {
                        $table_name = $wpdb->prefix . 'vcc_contacts';
                        return $where . $wpdb->prepare(" AND submission_date BETWEEN %s AND %s", $start_date, $end_date);
                    });
                }
                return;
                
            default:
                return; // All contacts
        }
        
        if (isset($start_date)) {
            add_filter('vcc_contacts_query_where', function($where) use ($wpdb, $start_date) {
                $table_name = $wpdb->prefix . 'vcc_contacts';
                return $where . $wpdb->prepare(" AND submission_date >= %s", $start_date);
            });
        }
    }
    
    /**
     * Get contacts by specific IDs
     */
    private function get_contacts_by_ids($contact_ids) {
        global $wpdb;
        
        if (empty($contact_ids)) {
            return array();
        }
        
        $table_name = $wpdb->prefix . 'vcc_contacts';
        $ids_placeholder = implode(',', array_fill(0, count($contact_ids), '%d'));
        
        $sql = $wpdb->prepare(
            "SELECT * FROM $table_name WHERE id IN ($ids_placeholder) AND status = 'active' ORDER BY submission_date DESC",
            $contact_ids
        );
        
        return $wpdb->get_results($sql);
    }
    
    /**
     * Export contacts as CSV
     */
    private function export_csv($contacts, $include_fields) {
        $filename = 'visitor-contacts-' . date('Y-m-d-H-i-s') . '.csv';
        
        // Set headers for download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Create output stream
        $output = fopen('php://output', 'w');
        
        // Add BOM for UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Write CSV header
        $headers = $this->get_csv_headers($include_fields);
        fputcsv($output, $headers);
        
        // Write contact data
        foreach ($contacts as $contact) {
            $row = $this->format_contact_for_csv($contact, $include_fields);
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Export contacts as JSON
     */
    private function export_json($contacts, $include_fields) {
        $filename = 'visitor-contacts-' . date('Y-m-d-H-i-s') . '.json';
        
        // Set headers for download
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Format data for JSON
        $export_data = array(
            'export_info' => array(
                'plugin' => 'Visitor Contact Collector',
                'version' => VCC_VERSION,
                'exported_at' => current_time('c'),
                'total_contacts' => count($contacts),
                'fields_included' => $include_fields
            ),
            'contacts' => array()
        );
        
        foreach ($contacts as $contact) {
            $export_data['contacts'][] = $this->format_contact_for_json($contact, $include_fields);
        }
        
        echo wp_json_encode($export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Get CSV headers based on included fields
     */
    private function get_csv_headers($include_fields) {
        $field_labels = array(
            'id' => __('ID', 'visitor-contact-collector'),
            'full_name' => __('Full Name', 'visitor-contact-collector'),
            'email' => __('Email', 'visitor-contact-collector'),
            'phone' => __('Phone', 'visitor-contact-collector'),
            'consent_given' => __('Consent Given', 'visitor-contact-collector'),
            'consent_timestamp' => __('Consent Date', 'visitor-contact-collector'),
            'submission_date' => __('Submission Date', 'visitor-contact-collector'),
            'ip_address' => __('IP Address', 'visitor-contact-collector'),
            'user_agent' => __('User Agent', 'visitor-contact-collector'),
            'status' => __('Status', 'visitor-contact-collector')
        );
        
        $headers = array();
        foreach ($include_fields as $field) {
            if (isset($field_labels[$field])) {
                $headers[] = $field_labels[$field];
            }
        }
        
        return $headers;
    }
    
    /**
     * Format contact data for CSV export
     */
    private function format_contact_for_csv($contact, $include_fields) {
        $row = array();
        
        foreach ($include_fields as $field) {
            switch ($field) {
                case 'id':
                    $row[] = $contact->id;
                    break;
                    
                case 'full_name':
                    $row[] = $contact->full_name;
                    break;
                    
                case 'email':
                    $row[] = $contact->email;
                    break;
                    
                case 'phone':
                    $row[] = $contact->phone;
                    break;
                    
                case 'consent_given':
                    $row[] = $contact->consent_given ? __('Yes', 'visitor-contact-collector') : __('No', 'visitor-contact-collector');
                    break;
                    
                case 'consent_timestamp':
                    $row[] = $contact->consent_timestamp ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($contact->consent_timestamp)) : '';
                    break;
                    
                case 'submission_date':
                    $row[] = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($contact->submission_date));
                    break;
                    
                case 'ip_address':
                    $row[] = $contact->ip_address;
                    break;
                    
                case 'user_agent':
                    $row[] = $contact->user_agent;
                    break;
                    
                case 'status':
                    $row[] = $contact->status;
                    break;
                    
                default:
                    $row[] = '';
                    break;
            }
        }
        
        return $row;
    }
    
    /**
     * Format contact data for JSON export
     */
    private function format_contact_for_json($contact, $include_fields) {
        $data = array();
        
        foreach ($include_fields as $field) {
            switch ($field) {
                case 'id':
                    $data['id'] = (int) $contact->id;
                    break;
                    
                case 'full_name':
                    $data['full_name'] = $contact->full_name;
                    break;
                    
                case 'email':
                    $data['email'] = $contact->email;
                    break;
                    
                case 'phone':
                    $data['phone'] = $contact->phone;
                    break;
                    
                case 'consent_given':
                    $data['consent_given'] = (bool) $contact->consent_given;
                    break;
                    
                case 'consent_timestamp':
                    $data['consent_timestamp'] = $contact->consent_timestamp;
                    break;
                    
                case 'submission_date':
                    $data['submission_date'] = $contact->submission_date;
                    break;
                    
                case 'ip_address':
                    $data['ip_address'] = $contact->ip_address;
                    break;
                    
                case 'user_agent':
                    $data['user_agent'] = $contact->user_agent;
                    break;
                    
                case 'status':
                    $data['status'] = $contact->status;
                    break;
            }
        }
        
        return $data;
    }
    
    /**
     * Quick export all contacts as CSV
     */
    public function quick_export_csv() {
        $contacts = $this->db->get_contacts(array(
            'per_page' => -1,
            'status' => 'active'
        ));
        
        $include_fields = array('full_name', 'email', 'phone', 'submission_date');
        $this->export_csv($contacts, $include_fields);
    }
    
    /**
     * Get export statistics
     */
    public function get_export_stats() {
        $stats = $this->db->get_statistics();
        
        return array(
            'total_contacts' => $stats['total'],
            'last_export' => get_option('vcc_last_export_date', ''),
            'export_count' => get_option('vcc_export_count', 0)
        );
    }
    
    /**
     * Update export statistics
     */
    private function update_export_stats() {
        update_option('vcc_last_export_date', current_time('mysql'));
        $count = get_option('vcc_export_count', 0);
        update_option('vcc_export_count', $count + 1);
    }
}
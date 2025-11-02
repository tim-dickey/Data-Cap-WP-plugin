<?php
/**
 * Database Management Class
 * Handles database table creation and data operations
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class VCC_Database {
    
    /**
     * Instance of this class
     */
    private static $instance = null;
    
    /**
     * Table name
     */
    private $table_name;
    
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
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'vcc_contacts';
    }
    
    /**
     * Create the contacts table
     */
    public static function create_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vcc_contacts';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            full_name varchar(255) NOT NULL,
            email varchar(255) NOT NULL,
            phone varchar(50) NOT NULL,
            consent_given tinyint(1) DEFAULT 0,
            consent_timestamp datetime DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            submission_date datetime DEFAULT CURRENT_TIMESTAMP,
            last_modified datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            status varchar(20) DEFAULT 'active',
            PRIMARY KEY (id),
            UNIQUE KEY email (email),
            KEY submission_date (submission_date),
            KEY status (status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Check if table was created successfully
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            error_log('VCC: Failed to create contacts table');
        }
    }
    
    /**
     * Drop the contacts table
     */
    public static function drop_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vcc_contacts';
        $wpdb->query("DROP TABLE IF EXISTS $table_name");
    }
    
    /**
     * Insert a new contact
     */
    public function insert_contact($data) {
        global $wpdb;
        
        $defaults = array(
            'full_name' => '',
            'email' => '',
            'phone' => '',
            'consent_given' => 0,
            'consent_timestamp' => null,
            'ip_address' => $this->get_user_ip(),
            'user_agent' => $this->get_user_agent(),
            'status' => 'active'
        );
        
        $data = wp_parse_args($data, $defaults);
        
        // Sanitize data
        $data['full_name'] = sanitize_text_field($data['full_name']);
        $data['email'] = sanitize_email($data['email']);
        $data['phone'] = vcc_sanitize_phone($data['phone']);
        $data['consent_given'] = (int) $data['consent_given'];
        $data['ip_address'] = sanitize_text_field($data['ip_address']);
        $data['user_agent'] = sanitize_text_field($data['user_agent']);
        $data['status'] = sanitize_text_field($data['status']);
        
        // Validate required fields
        if (empty($data['full_name']) || empty($data['email']) || empty($data['phone'])) {
            return new WP_Error('missing_data', __('All fields are required.', 'visitor-contact-collector'));
        }
        
        // Validate email format
        if (!vcc_validate_email($data['email'])) {
            return new WP_Error('invalid_email', __('Invalid email address.', 'visitor-contact-collector'));
        }
        
        // Validate phone format
        if (!vcc_validate_phone($data['phone'])) {
            return new WP_Error('invalid_phone', __('Invalid phone number.', 'visitor-contact-collector'));
        }
        
        // Check if email already exists
        if ($this->email_exists($data['email'])) {
            return new WP_Error('email_exists', __('This email address is already in our system.', 'visitor-contact-collector'));
        }
        
        // Set consent timestamp if consent is given
        if ($data['consent_given']) {
            $data['consent_timestamp'] = current_time('mysql');
        }
        
        $result = $wpdb->insert(
            $this->table_name,
            $data,
            array(
                '%s', // full_name
                '%s', // email
                '%s', // phone
                '%d', // consent_given
                '%s', // consent_timestamp
                '%s', // ip_address
                '%s', // user_agent
                '%s'  // status
            )
        );
        
        if ($result === false) {
            return new WP_Error('db_error', __('Database error occurred.', 'visitor-contact-collector'));
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Check if email exists
     */
    public function email_exists($email) {
        global $wpdb;
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $this->table_name WHERE email = %s AND status != 'deleted'",
            $email
        ));
        
        return $count > 0;
    }
    
    /**
     * Get all contacts with pagination
     */
    public function get_contacts($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'per_page' => 20,
            'page' => 1,
            'orderby' => 'submission_date',
            'order' => 'DESC',
            'status' => 'active',
            'search' => ''
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $offset = ($args['page'] - 1) * $args['per_page'];
        
        $where_clauses = array();
        $where_values = array();
        
        // Status filter
        if (!empty($args['status'])) {
            $where_clauses[] = "status = %s";
            $where_values[] = $args['status'];
        }
        
        // Search filter
        if (!empty($args['search'])) {
            $where_clauses[] = "(full_name LIKE %s OR email LIKE %s OR phone LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }
        
        $where_sql = '';
        if (!empty($where_clauses)) {
            $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
        }
        
        // Sanitize orderby
        $allowed_orderby = array('id', 'full_name', 'email', 'phone', 'submission_date', 'last_modified');
        if (!in_array($args['orderby'], $allowed_orderby)) {
            $args['orderby'] = 'submission_date';
        }
        
        // Sanitize order
        $args['order'] = strtoupper($args['order']);
        if (!in_array($args['order'], array('ASC', 'DESC'))) {
            $args['order'] = 'DESC';
        }
        
        $sql = $wpdb->prepare(
            "SELECT * FROM $this->table_name $where_sql ORDER BY {$args['orderby']} {$args['order']} LIMIT %d OFFSET %d",
            array_merge($where_values, array($args['per_page'], $offset))
        );
        
        return $wpdb->get_results($sql);
    }
    
    /**
     * Get total count of contacts
     */
    public function get_contacts_count($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'status' => 'active',
            'search' => ''
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where_clauses = array();
        $where_values = array();
        
        // Status filter
        if (!empty($args['status'])) {
            $where_clauses[] = "status = %s";
            $where_values[] = $args['status'];
        }
        
        // Search filter
        if (!empty($args['search'])) {
            $where_clauses[] = "(full_name LIKE %s OR email LIKE %s OR phone LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }
        
        $where_sql = '';
        if (!empty($where_clauses)) {
            $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
        }
        
        if (!empty($where_values)) {
            $sql = $wpdb->prepare("SELECT COUNT(*) FROM $this->table_name $where_sql", $where_values);
        } else {
            $sql = "SELECT COUNT(*) FROM $this->table_name $where_sql";
        }
        
        return (int) $wpdb->get_var($sql);
    }
    
    /**
     * Get a single contact by ID
     */
    public function get_contact($id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $this->table_name WHERE id = %d",
            $id
        ));
    }
    
    /**
     * Update a contact
     */
    public function update_contact($id, $data) {
        global $wpdb;
        
        // Sanitize data
        if (isset($data['full_name'])) {
            $data['full_name'] = sanitize_text_field($data['full_name']);
        }
        if (isset($data['email'])) {
            $data['email'] = sanitize_email($data['email']);
        }
        if (isset($data['phone'])) {
            $data['phone'] = vcc_sanitize_phone($data['phone']);
        }
        if (isset($data['status'])) {
            $data['status'] = sanitize_text_field($data['status']);
        }
        
        $result = $wpdb->update(
            $this->table_name,
            $data,
            array('id' => $id),
            null,
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Delete a contact (soft delete)
     */
    public function delete_contact($id) {
        return $this->update_contact($id, array('status' => 'deleted'));
    }
    
    /**
     * Permanently delete a contact
     */
    public function delete_contact_permanently($id) {
        global $wpdb;
        
        return $wpdb->delete(
            $this->table_name,
            array('id' => $id),
            array('%d')
        );
    }
    
    /**
     * Delete old contacts based on retention policy
     */
    public function cleanup_old_contacts() {
        global $wpdb;
        
        $settings = vcc_get_settings();
        $retention_days = (int) $settings['data_retention_days'];
        
        if ($retention_days <= 0) {
            return; // No automatic deletion
        }
        
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$retention_days} days"));
        
        $result = $wpdb->query($wpdb->prepare(
            "UPDATE $this->table_name SET status = 'deleted' WHERE submission_date < %s AND status = 'active'",
            $cutoff_date
        ));
        
        return $result;
    }
    
    /**
     * Get user IP address
     */
    private function get_user_ip() {
        $settings = vcc_get_settings();
        
        // Check if IP tracking is disabled
        if (isset($settings['disable_ip_tracking']) && $settings['disable_ip_tracking']) {
            return '';
        }
        
        // Get IP address
        $ip_keys = array('HTTP_CF_CONNECTING_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
    }
    
    /**
     * Get user agent
     */
    private function get_user_agent() {
        $settings = vcc_get_settings();
        
        // Check if user agent tracking is disabled
        if (isset($settings['disable_user_agent_tracking']) && $settings['disable_user_agent_tracking']) {
            return '';
        }
        
        return isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
    }
    
    /**
     * Get statistics
     */
    public function get_statistics() {
        global $wpdb;
        
        $stats = array();
        
        // Total contacts
        $stats['total'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM $this->table_name WHERE status = 'active'");
        
        // Contacts this month
        $stats['this_month'] = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $this->table_name WHERE status = 'active' AND submission_date >= %s",
            date('Y-m-01 00:00:00')
        ));
        
        // Contacts this week
        $stats['this_week'] = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $this->table_name WHERE status = 'active' AND submission_date >= %s",
            date('Y-m-d 00:00:00', strtotime('monday this week'))
        ));
        
        // Contacts today
        $stats['today'] = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $this->table_name WHERE status = 'active' AND DATE(submission_date) = %s",
            date('Y-m-d')
        ));
        
        // Consent rate
        $total_with_consent = (int) $wpdb->get_var("SELECT COUNT(*) FROM $this->table_name WHERE status = 'active' AND consent_given = 1");
        $stats['consent_rate'] = $stats['total'] > 0 ? round(($total_with_consent / $stats['total']) * 100, 1) : 0;
        
        return $stats;
    }
}
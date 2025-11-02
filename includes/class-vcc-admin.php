<?php
/**
 * Admin Panel Class
 * Handles the WordPress admin interface for the plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class VCC_Admin {
    
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
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        add_action('wp_ajax_vcc_delete_contact', array($this, 'ajax_delete_contact'));
        add_action('wp_ajax_vcc_bulk_action', array($this, 'ajax_bulk_action'));
        add_filter('plugin_action_links_' . VCC_PLUGIN_BASENAME, array($this, 'add_action_links'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        $capability = 'manage_options';
        
        // Main menu page
        add_menu_page(
            __('Contact Collector', 'visitor-contact-collector'),
            __('Contact Collector', 'visitor-contact-collector'),
            $capability,
            'visitor-contact-collector',
            array($this, 'contacts_page'),
            'dashicons-groups',
            30
        );
        
        // Contacts submenu (same as main page)
        add_submenu_page(
            'visitor-contact-collector',
            __('Contacts', 'visitor-contact-collector'),
            __('Contacts', 'visitor-contact-collector'),
            $capability,
            'visitor-contact-collector',
            array($this, 'contacts_page')
        );
        
        // Settings submenu
        add_submenu_page(
            'visitor-contact-collector',
            __('Settings', 'visitor-contact-collector'),
            __('Settings', 'visitor-contact-collector'),
            $capability,
            'vcc-settings',
            array($this, 'settings_page')
        );
        
        // Export submenu
        add_submenu_page(
            'visitor-contact-collector',
            __('Export', 'visitor-contact-collector'),
            __('Export', 'visitor-contact-collector'),
            $capability,
            'vcc-export',
            array($this, 'export_page')
        );
    }
    
    /**
     * Admin init
     */
    public function admin_init() {
        // Register settings
        register_setting('vcc_settings_group', 'vcc_settings', array($this, 'sanitize_settings'));
        
        // Add settings sections and fields
        $this->add_settings_sections();
    }
    
    /**
     * Add settings sections and fields
     */
    private function add_settings_sections() {
        // Form settings section
        add_settings_section(
            'vcc_form_settings',
            __('Form Settings', 'visitor-contact-collector'),
            array($this, 'form_settings_callback'),
            'vcc_settings'
        );
        
        add_settings_field(
            'form_title',
            __('Form Title', 'visitor-contact-collector'),
            array($this, 'text_field_callback'),
            'vcc_settings',
            'vcc_form_settings',
            array('field' => 'form_title')
        );
        
        add_settings_field(
            'form_description',
            __('Form Description', 'visitor-contact-collector'),
            array($this, 'textarea_field_callback'),
            'vcc_settings',
            'vcc_form_settings',
            array('field' => 'form_description')
        );
        
        add_settings_field(
            'submit_button_text',
            __('Submit Button Text', 'visitor-contact-collector'),
            array($this, 'text_field_callback'),
            'vcc_settings',
            'vcc_form_settings',
            array('field' => 'submit_button_text')
        );
        
        add_settings_field(
            'success_message',
            __('Success Message', 'visitor-contact-collector'),
            array($this, 'textarea_field_callback'),
            'vcc_settings',
            'vcc_form_settings',
            array('field' => 'success_message')
        );
        
        // Style settings section
        add_settings_section(
            'vcc_style_settings',
            __('Style Settings', 'visitor-contact-collector'),
            array($this, 'style_settings_callback'),
            'vcc_settings'
        );
        
        add_settings_field(
            'form_style',
            __('Form Style', 'visitor-contact-collector'),
            array($this, 'select_field_callback'),
            'vcc_settings',
            'vcc_style_settings',
            array(
                'field' => 'form_style',
                'options' => array(
                    'rounded' => __('Rounded Corners', 'visitor-contact-collector'),
                    'square' => __('Square Corners', 'visitor-contact-collector'),
                    'minimal' => __('Minimal', 'visitor-contact-collector')
                )
            )
        );
        
        add_settings_field(
            'primary_color',
            __('Primary Color', 'visitor-contact-collector'),
            array($this, 'color_field_callback'),
            'vcc_settings',
            'vcc_style_settings',
            array('field' => 'primary_color')
        );
        
        // GDPR settings section
        add_settings_section(
            'vcc_gdpr_settings',
            __('GDPR & Privacy Settings', 'visitor-contact-collector'),
            array($this, 'gdpr_settings_callback'),
            'vcc_settings'
        );
        
        add_settings_field(
            'enable_gdpr',
            __('Enable GDPR Checkbox', 'visitor-contact-collector'),
            array($this, 'checkbox_field_callback'),
            'vcc_settings',
            'vcc_gdpr_settings',
            array('field' => 'enable_gdpr')
        );
        
        add_settings_field(
            'gdpr_text',
            __('GDPR Checkbox Text', 'visitor-contact-collector'),
            array($this, 'textarea_field_callback'),
            'vcc_settings',
            'vcc_gdpr_settings',
            array('field' => 'gdpr_text')
        );
        
        add_settings_field(
            'data_retention_days',
            __('Data Retention (Days)', 'visitor-contact-collector'),
            array($this, 'number_field_callback'),
            'vcc_settings',
            'vcc_gdpr_settings',
            array(
                'field' => 'data_retention_days',
                'description' => __('Number of days to keep contact data. Set to 0 for no automatic deletion.', 'visitor-contact-collector')
            )
        );
        
        // Notification settings section
        add_settings_section(
            'vcc_notification_settings',
            __('Notification Settings', 'visitor-contact-collector'),
            array($this, 'notification_settings_callback'),
            'vcc_settings'
        );
        
        add_settings_field(
            'email_notifications',
            __('Enable Email Notifications', 'visitor-contact-collector'),
            array($this, 'checkbox_field_callback'),
            'vcc_settings',
            'vcc_notification_settings',
            array('field' => 'email_notifications')
        );
        
        add_settings_field(
            'notification_email',
            __('Notification Email', 'visitor-contact-collector'),
            array($this, 'text_field_callback'),
            'vcc_settings',
            'vcc_notification_settings',
            array('field' => 'notification_email')
        );
    }
    
    /**
     * Contacts page
     */
    public function contacts_page() {
        // Handle bulk actions
        if (isset($_POST['action']) && $_POST['action'] !== '-1' && check_admin_referer('vcc_bulk_action')) {
            $this->handle_bulk_action();
        }
        
        // Get current page
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $per_page = 20;
        
        // Get search term
        $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        
        // Get contacts
        $args = array(
            'page' => $current_page,
            'per_page' => $per_page,
            'search' => $search
        );
        
        $contacts = $this->db->get_contacts($args);
        $total_contacts = $this->db->get_contacts_count($args);
        $total_pages = ceil($total_contacts / $per_page);
        
        // Get statistics
        $stats = $this->db->get_statistics();
        
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php _e('Contact Collector', 'visitor-contact-collector'); ?></h1>
            
            <!-- Statistics Dashboard -->
            <div class="vcc-stats-grid">
                <div class="vcc-stat-box">
                    <h3><?php echo number_format($stats['total']); ?></h3>
                    <p><?php _e('Total Contacts', 'visitor-contact-collector'); ?></p>
                </div>
                <div class="vcc-stat-box">
                    <h3><?php echo number_format($stats['this_month']); ?></h3>
                    <p><?php _e('This Month', 'visitor-contact-collector'); ?></p>
                </div>
                <div class="vcc-stat-box">
                    <h3><?php echo number_format($stats['this_week']); ?></h3>
                    <p><?php _e('This Week', 'visitor-contact-collector'); ?></p>
                </div>
                <div class="vcc-stat-box">
                    <h3><?php echo number_format($stats['today']); ?></h3>
                    <p><?php _e('Today', 'visitor-contact-collector'); ?></p>
                </div>
                <div class="vcc-stat-box">
                    <h3><?php echo $stats['consent_rate']; ?>%</h3>
                    <p><?php _e('Consent Rate', 'visitor-contact-collector'); ?></p>
                </div>
            </div>
            
            <!-- Search Form -->
            <form method="get" action="">
                <input type="hidden" name="page" value="visitor-contact-collector" />
                <p class="search-box">
                    <label class="screen-reader-text" for="contact-search-input"><?php _e('Search Contacts:', 'visitor-contact-collector'); ?></label>
                    <input type="search" id="contact-search-input" name="s" value="<?php echo esc_attr($search); ?>" />
                    <input type="submit" id="search-submit" class="button" value="<?php _e('Search Contacts', 'visitor-contact-collector'); ?>" />
                </p>
            </form>
            
            <!-- Contacts Table -->
            <form method="post" action="">
                <?php wp_nonce_field('vcc_bulk_action'); ?>
                
                <div class="tablenav top">
                    <div class="alignleft actions bulkactions">
                        <label for="bulk-action-selector-top" class="screen-reader-text"><?php _e('Select bulk action', 'visitor-contact-collector'); ?></label>
                        <select name="action" id="bulk-action-selector-top">
                            <option value="-1"><?php _e('Bulk Actions', 'visitor-contact-collector'); ?></option>
                            <option value="delete"><?php _e('Delete', 'visitor-contact-collector'); ?></option>
                            <option value="export"><?php _e('Export Selected', 'visitor-contact-collector'); ?></option>
                        </select>
                        <input type="submit" id="doaction" class="button action" value="<?php _e('Apply', 'visitor-contact-collector'); ?>" />
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <div class="tablenav-pages">
                        <span class="displaying-num"><?php printf(__('%s items', 'visitor-contact-collector'), number_format($total_contacts)); ?></span>
                        <?php
                        echo paginate_links(array(
                            'base' => add_query_arg('paged', '%#%'),
                            'format' => '',
                            'prev_text' => __('&laquo;'),
                            'next_text' => __('&raquo;'),
                            'total' => $total_pages,
                            'current' => $current_page
                        ));
                        ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <td class="manage-column column-cb check-column">
                                <label class="screen-reader-text" for="cb-select-all-1"><?php _e('Select All'); ?></label>
                                <input id="cb-select-all-1" type="checkbox" />
                            </td>
                            <th scope="col" class="manage-column column-name column-primary"><?php _e('Name', 'visitor-contact-collector'); ?></th>
                            <th scope="col" class="manage-column column-email"><?php _e('Email', 'visitor-contact-collector'); ?></th>
                            <th scope="col" class="manage-column column-phone"><?php _e('Phone', 'visitor-contact-collector'); ?></th>
                            <th scope="col" class="manage-column column-consent"><?php _e('Consent', 'visitor-contact-collector'); ?></th>
                            <th scope="col" class="manage-column column-date"><?php _e('Date', 'visitor-contact-collector'); ?></th>
                            <th scope="col" class="manage-column column-actions"><?php _e('Actions', 'visitor-contact-collector'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($contacts)): ?>
                            <?php foreach ($contacts as $contact): ?>
                                <tr>
                                    <th scope="row" class="check-column">
                                        <input type="checkbox" name="contacts[]" value="<?php echo esc_attr($contact->id); ?>" />
                                    </th>
                                    <td class="column-name column-primary">
                                        <strong><?php echo esc_html($contact->full_name); ?></strong>
                                        <div class="row-actions">
                                            <span class="delete">
                                                <a href="#" class="vcc-delete-contact" data-id="<?php echo esc_attr($contact->id); ?>" data-name="<?php echo esc_attr($contact->full_name); ?>">
                                                    <?php _e('Delete', 'visitor-contact-collector'); ?>
                                                </a>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="column-email">
                                        <a href="mailto:<?php echo esc_attr($contact->email); ?>"><?php echo esc_html($contact->email); ?></a>
                                    </td>
                                    <td class="column-phone">
                                        <a href="tel:<?php echo esc_attr($contact->phone); ?>"><?php echo esc_html($contact->phone); ?></a>
                                    </td>
                                    <td class="column-consent">
                                        <?php if ($contact->consent_given): ?>
                                            <span class="vcc-consent-yes"><?php _e('Yes', 'visitor-contact-collector'); ?></span>
                                        <?php else: ?>
                                            <span class="vcc-consent-no"><?php _e('No', 'visitor-contact-collector'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="column-date">
                                        <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($contact->submission_date))); ?>
                                    </td>
                                    <td class="column-actions">
                                        <a href="#" class="button button-small vcc-delete-contact" data-id="<?php echo esc_attr($contact->id); ?>" data-name="<?php echo esc_attr($contact->full_name); ?>">
                                            <?php _e('Delete', 'visitor-contact-collector'); ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7">
                                    <p><?php _e('No contacts found.', 'visitor-contact-collector'); ?></p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </form>
            
            <div class="vcc-shortcode-info">
                <h3><?php _e('Usage', 'visitor-contact-collector'); ?></h3>
                <p><?php _e('To display the contact form on your site, use the following shortcode:', 'visitor-contact-collector'); ?></p>
                <code>[visitor_contact_form]</code>
                <p><?php _e('You can also use the Gutenberg block "Contact Collector Form" in the block editor.', 'visitor-contact-collector'); ?></p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Contact Collector Settings', 'visitor-contact-collector'); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('vcc_settings_group');
                do_settings_sections('vcc_settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Export page
     */
    public function export_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Export Contacts', 'visitor-contact-collector'); ?></h1>
            
            <div class="vcc-export-options">
                <h3><?php _e('Export Options', 'visitor-contact-collector'); ?></h3>
                
                <form method="post" action="<?php echo admin_url('admin-ajax.php'); ?>">
                    <?php wp_nonce_field('vcc_export_nonce', 'vcc_export_nonce'); ?>
                    <input type="hidden" name="action" value="vcc_export_contacts" />
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Export Format', 'visitor-contact-collector'); ?></th>
                            <td>
                                <label>
                                    <input type="radio" name="export_format" value="csv" checked />
                                    <?php _e('CSV (Excel compatible)', 'visitor-contact-collector'); ?>
                                </label><br />
                                <label>
                                    <input type="radio" name="export_format" value="json" />
                                    <?php _e('JSON', 'visitor-contact-collector'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Date Range', 'visitor-contact-collector'); ?></th>
                            <td>
                                <label>
                                    <input type="radio" name="date_range" value="all" checked />
                                    <?php _e('All contacts', 'visitor-contact-collector'); ?>
                                </label><br />
                                <label>
                                    <input type="radio" name="date_range" value="last_30_days" />
                                    <?php _e('Last 30 days', 'visitor-contact-collector'); ?>
                                </label><br />
                                <label>
                                    <input type="radio" name="date_range" value="last_7_days" />
                                    <?php _e('Last 7 days', 'visitor-contact-collector'); ?>
                                </label><br />
                                <label>
                                    <input type="radio" name="date_range" value="custom" />
                                    <?php _e('Custom range', 'visitor-contact-collector'); ?>
                                </label>
                                <div id="custom-date-range" style="margin-top: 10px; display: none;">
                                    <input type="date" name="start_date" />
                                    <?php _e('to', 'visitor-contact-collector'); ?>
                                    <input type="date" name="end_date" />
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Include Fields', 'visitor-contact-collector'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="include_fields[]" value="full_name" checked />
                                    <?php _e('Full Name', 'visitor-contact-collector'); ?>
                                </label><br />
                                <label>
                                    <input type="checkbox" name="include_fields[]" value="email" checked />
                                    <?php _e('Email', 'visitor-contact-collector'); ?>
                                </label><br />
                                <label>
                                    <input type="checkbox" name="include_fields[]" value="phone" checked />
                                    <?php _e('Phone', 'visitor-contact-collector'); ?>
                                </label><br />
                                <label>
                                    <input type="checkbox" name="include_fields[]" value="consent_given" />
                                    <?php _e('Consent Given', 'visitor-contact-collector'); ?>
                                </label><br />
                                <label>
                                    <input type="checkbox" name="include_fields[]" value="submission_date" />
                                    <?php _e('Submission Date', 'visitor-contact-collector'); ?>
                                </label><br />
                                <label>
                                    <input type="checkbox" name="include_fields[]" value="ip_address" />
                                    <?php _e('IP Address', 'visitor-contact-collector'); ?>
                                </label>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <input type="submit" class="button-primary" value="<?php _e('Download Export', 'visitor-contact-collector'); ?>" />
                    </p>
                </form>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('input[name="date_range"]').change(function() {
                if ($(this).val() === 'custom') {
                    $('#custom-date-range').show();
                } else {
                    $('#custom-date-range').hide();
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * Handle bulk actions
     */
    private function handle_bulk_action() {
        $action = sanitize_text_field($_POST['action']);
        $contact_ids = isset($_POST['contacts']) ? array_map('intval', $_POST['contacts']) : array();
        
        if (empty($contact_ids)) {
            return;
        }
        
        switch ($action) {
            case 'delete':
                foreach ($contact_ids as $contact_id) {
                    $this->db->delete_contact($contact_id);
                }
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-success is-dismissible"><p>' . __('Selected contacts have been deleted.', 'visitor-contact-collector') . '</p></div>';
                });
                break;
                
            case 'export':
                // Redirect to export with selected IDs
                $export_url = add_query_arg(array(
                    'action' => 'vcc_export_contacts',
                    'contact_ids' => implode(',', $contact_ids),
                    'vcc_export_nonce' => wp_create_nonce('vcc_export_nonce')
                ), admin_url('admin-ajax.php'));
                wp_redirect($export_url);
                exit;
                break;
        }
    }
    
    /**
     * AJAX delete contact
     */
    public function ajax_delete_contact() {
        check_ajax_referer('vcc_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions.', 'visitor-contact-collector'));
        }
        
        $contact_id = intval($_POST['contact_id']);
        
        if ($this->db->delete_contact($contact_id)) {
            wp_send_json_success(array(
                'message' => __('Contact deleted successfully.', 'visitor-contact-collector')
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Failed to delete contact.', 'visitor-contact-collector')
            ));
        }
    }
    
    /**
     * Sanitize settings
     */
    public function sanitize_settings($settings) {
        $sanitized = array();
        
        // Text fields
        $text_fields = array('form_title', 'submit_button_text', 'notification_email', 'primary_color');
        foreach ($text_fields as $field) {
            if (isset($settings[$field])) {
                $sanitized[$field] = sanitize_text_field($settings[$field]);
            }
        }
        
        // Textarea fields
        $textarea_fields = array('form_description', 'success_message', 'gdpr_text');
        foreach ($textarea_fields as $field) {
            if (isset($settings[$field])) {
                $sanitized[$field] = sanitize_textarea_field($settings[$field]);
            }
        }
        
        // Checkbox fields
        $checkbox_fields = array('enable_gdpr', 'email_notifications');
        foreach ($checkbox_fields as $field) {
            $sanitized[$field] = isset($settings[$field]) ? 1 : 0;
        }
        
        // Number fields
        if (isset($settings['data_retention_days'])) {
            $sanitized['data_retention_days'] = max(0, intval($settings['data_retention_days']));
        }
        
        // Select fields
        if (isset($settings['form_style'])) {
            $allowed_styles = array('rounded', 'square', 'minimal');
            $sanitized['form_style'] = in_array($settings['form_style'], $allowed_styles) ? $settings['form_style'] : 'rounded';
        }
        
        return $sanitized;
    }
    
    /**
     * Add action links to plugin page
     */
    public function add_action_links($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=vcc-settings') . '">' . __('Settings', 'visitor-contact-collector') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
    
    // Settings field callbacks
    public function form_settings_callback() {
        echo '<p>' . __('Configure the appearance and text of your contact form.', 'visitor-contact-collector') . '</p>';
    }
    
    public function style_settings_callback() {
        echo '<p>' . __('Customize the visual appearance of your contact form.', 'visitor-contact-collector') . '</p>';
    }
    
    public function gdpr_settings_callback() {
        echo '<p>' . __('Configure GDPR compliance and privacy settings.', 'visitor-contact-collector') . '</p>';
    }
    
    public function notification_settings_callback() {
        echo '<p>' . __('Configure email notifications for new contact submissions.', 'visitor-contact-collector') . '</p>';
    }
    
    public function text_field_callback($args) {
        $settings = vcc_get_settings();
        $value = isset($settings[$args['field']]) ? $settings[$args['field']] : '';
        echo '<input type="text" name="vcc_settings[' . esc_attr($args['field']) . ']" value="' . esc_attr($value) . '" class="regular-text" />';
        if (isset($args['description'])) {
            echo '<p class="description">' . esc_html($args['description']) . '</p>';
        }
    }
    
    public function textarea_field_callback($args) {
        $settings = vcc_get_settings();
        $value = isset($settings[$args['field']]) ? $settings[$args['field']] : '';
        echo '<textarea name="vcc_settings[' . esc_attr($args['field']) . ']" rows="3" class="large-text">' . esc_textarea($value) . '</textarea>';
        if (isset($args['description'])) {
            echo '<p class="description">' . esc_html($args['description']) . '</p>';
        }
    }
    
    public function checkbox_field_callback($args) {
        $settings = vcc_get_settings();
        $value = isset($settings[$args['field']]) ? $settings[$args['field']] : 0;
        echo '<input type="checkbox" name="vcc_settings[' . esc_attr($args['field']) . ']" value="1" ' . checked(1, $value, false) . ' />';
        if (isset($args['description'])) {
            echo '<p class="description">' . esc_html($args['description']) . '</p>';
        }
    }
    
    public function select_field_callback($args) {
        $settings = vcc_get_settings();
        $value = isset($settings[$args['field']]) ? $settings[$args['field']] : '';
        echo '<select name="vcc_settings[' . esc_attr($args['field']) . ']">';
        foreach ($args['options'] as $option_value => $option_label) {
            echo '<option value="' . esc_attr($option_value) . '" ' . selected($value, $option_value, false) . '>' . esc_html($option_label) . '</option>';
        }
        echo '</select>';
        if (isset($args['description'])) {
            echo '<p class="description">' . esc_html($args['description']) . '</p>';
        }
    }
    
    public function color_field_callback($args) {
        $settings = vcc_get_settings();
        $value = isset($settings[$args['field']]) ? $settings[$args['field']] : '#0073aa';
        echo '<input type="color" name="vcc_settings[' . esc_attr($args['field']) . ']" value="' . esc_attr($value) . '" />';
        if (isset($args['description'])) {
            echo '<p class="description">' . esc_html($args['description']) . '</p>';
        }
    }
    
    public function number_field_callback($args) {
        $settings = vcc_get_settings();
        $value = isset($settings[$args['field']]) ? $settings[$args['field']] : 0;
        echo '<input type="number" name="vcc_settings[' . esc_attr($args['field']) . ']" value="' . esc_attr($value) . '" min="0" class="small-text" />';
        if (isset($args['description'])) {
            echo '<p class="description">' . esc_html($args['description']) . '</p>';
        }
    }
}
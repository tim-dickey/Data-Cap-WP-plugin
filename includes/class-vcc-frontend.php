<?php
/**
 * Frontend Form Handler Class
 * Handles the display and processing of the contact form on the frontend
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class VCC_Frontend {
    
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
        add_action('wp_ajax_vcc_submit_form', array($this, 'handle_form_submission'));
        add_action('wp_ajax_nopriv_vcc_submit_form', array($this, 'handle_form_submission'));
        add_action('wp_footer', array($this, 'add_form_styles'));
    }
    
    /**
     * Display the contact form
     */
    public function display_form($atts = array()) {
        $settings = vcc_get_settings();
        
        // Parse attributes
        $atts = shortcode_atts(array(
            'title' => $settings['form_title'],
            'description' => $settings['form_description'],
            'style' => $settings['form_style'],
            'color' => $settings['primary_color'],
            'show_title' => 'true',
            'show_description' => 'true',
            'class' => ''
        ), $atts);
        
        // Generate unique form ID
        $form_id = 'vcc-form-' . wp_generate_uuid4();
        
        ob_start();
        ?>
        <div class="vcc-form-container vcc-style-<?php echo esc_attr($atts['style']); ?> <?php echo esc_attr($atts['class']); ?>" 
             data-color="<?php echo esc_attr($atts['color']); ?>">
            
            <?php if ($atts['show_title'] === 'true' && !empty($atts['title'])): ?>
                <h3 class="vcc-form-title"><?php echo esc_html($atts['title']); ?></h3>
            <?php endif; ?>
            
            <?php if ($atts['show_description'] === 'true' && !empty($atts['description'])): ?>
                <p class="vcc-form-description"><?php echo esc_html($atts['description']); ?></p>
            <?php endif; ?>
            
            <div id="<?php echo esc_attr($form_id); ?>-messages" class="vcc-messages"></div>
            
            <form id="<?php echo esc_attr($form_id); ?>" class="vcc-contact-form" method="post" action="">
                <?php wp_nonce_field('vcc_submit_nonce', 'vcc_nonce'); ?>
                
                <div class="vcc-form-row">
                    <div class="vcc-form-field">
                        <label for="<?php echo esc_attr($form_id); ?>-full-name">
                            <?php _e('Full Name', 'visitor-contact-collector'); ?> <span class="vcc-required">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="<?php echo esc_attr($form_id); ?>-full-name" 
                            name="full_name" 
                            required 
                            maxlength="255"
                            placeholder="<?php esc_attr_e('Enter your full name', 'visitor-contact-collector'); ?>"
                        />
                        <div class="vcc-field-error"></div>
                    </div>
                </div>
                
                <div class="vcc-form-row">
                    <div class="vcc-form-field">
                        <label for="<?php echo esc_attr($form_id); ?>-email">
                            <?php _e('Email Address', 'visitor-contact-collector'); ?> <span class="vcc-required">*</span>
                        </label>
                        <input 
                            type="email" 
                            id="<?php echo esc_attr($form_id); ?>-email" 
                            name="email" 
                            required 
                            maxlength="255"
                            placeholder="<?php esc_attr_e('Enter your email address', 'visitor-contact-collector'); ?>"
                        />
                        <div class="vcc-field-error"></div>
                    </div>
                </div>
                
                <div class="vcc-form-row">
                    <div class="vcc-form-field">
                        <label for="<?php echo esc_attr($form_id); ?>-phone">
                            <?php _e('Mobile Phone Number', 'visitor-contact-collector'); ?> <span class="vcc-required">*</span>
                        </label>
                        <input 
                            type="tel" 
                            id="<?php echo esc_attr($form_id); ?>-phone" 
                            name="phone" 
                            required 
                            maxlength="50"
                            placeholder="<?php esc_attr_e('Enter your phone number', 'visitor-contact-collector'); ?>"
                        />
                        <div class="vcc-field-error"></div>
                    </div>
                </div>
                
                <?php if ($settings['enable_gdpr']): ?>
                <div class="vcc-form-row">
                    <div class="vcc-form-field vcc-checkbox-field">
                        <label class="vcc-checkbox-label">
                            <input 
                                type="checkbox" 
                                id="<?php echo esc_attr($form_id); ?>-consent" 
                                name="consent" 
                                required 
                                value="1"
                            />
                            <span class="vcc-checkbox-text">
                                <?php echo esc_html($settings['gdpr_text']); ?> <span class="vcc-required">*</span>
                            </span>
                        </label>
                        <div class="vcc-field-error"></div>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="vcc-form-row">
                    <div class="vcc-form-submit">
                        <button type="submit" class="vcc-submit-btn">
                            <span class="vcc-submit-text"><?php echo esc_html($settings['submit_button_text']); ?></span>
                            <span class="vcc-submit-spinner" style="display: none;">
                                <svg class="vcc-spinner" viewBox="0 0 50 50">
                                    <circle class="vcc-spinner-path" cx="25" cy="25" r="20" fill="none" stroke="currentColor" stroke-width="2" stroke-miterlimit="10"/>
                                </svg>
                            </span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof VCCForm !== 'undefined') {
                new VCCForm('<?php echo esc_js($form_id); ?>');
            }
        });
        </script>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Handle form submission via AJAX
     */
    public function handle_form_submission() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['vcc_nonce'], 'vcc_submit_nonce')) {
            wp_send_json_error(array(
                'message' => __('Security check failed. Please refresh the page and try again.', 'visitor-contact-collector')
            ));
        }
        
        // Rate limiting - prevent spam
        if (!$this->check_rate_limit()) {
            wp_send_json_error(array(
                'message' => __('Too many submissions. Please wait a moment before trying again.', 'visitor-contact-collector')
            ));
        }
        
        // Sanitize and validate input data
        $full_name = sanitize_text_field($_POST['full_name']);
        $email = sanitize_email($_POST['email']);
        $phone = vcc_sanitize_phone($_POST['phone']);
        $consent = isset($_POST['consent']) ? 1 : 0;
        
        // Server-side validation
        $errors = array();
        
        if (empty($full_name)) {
            $errors['full_name'] = __('Full name is required.', 'visitor-contact-collector');
        } elseif (strlen($full_name) > 255) {
            $errors['full_name'] = __('Full name is too long.', 'visitor-contact-collector');
        }
        
        if (empty($email)) {
            $errors['email'] = __('Email address is required.', 'visitor-contact-collector');
        } elseif (!vcc_validate_email($email)) {
            $errors['email'] = __('Please enter a valid email address.', 'visitor-contact-collector');
        }
        
        if (empty($phone)) {
            $errors['phone'] = __('Phone number is required.', 'visitor-contact-collector');
        } elseif (!vcc_validate_phone($phone)) {
            $errors['phone'] = __('Please enter a valid phone number.', 'visitor-contact-collector');
        }
        
        // Check GDPR consent if enabled
        $settings = vcc_get_settings();
        if ($settings['enable_gdpr'] && !$consent) {
            $errors['consent'] = __('You must agree to the privacy policy to continue.', 'visitor-contact-collector');
        }
        
        // Return validation errors
        if (!empty($errors)) {
            wp_send_json_error(array(
                'message' => __('Please correct the errors below and try again.', 'visitor-contact-collector'),
                'errors' => $errors
            ));
        }
        
        // Prepare data for insertion
        $contact_data = array(
            'full_name' => $full_name,
            'email' => $email,
            'phone' => $phone,
            'consent_given' => $consent
        );
        
        // Insert contact into database
        $result = $this->db->insert_contact($contact_data);
        
        if (is_wp_error($result)) {
            // Handle specific errors
            if ($result->get_error_code() === 'email_exists') {
                wp_send_json_error(array(
                    'message' => __('This email address is already in our system. Thank you for your interest!', 'visitor-contact-collector'),
                    'errors' => array('email' => $result->get_error_message())
                ));
            } else {
                wp_send_json_error(array(
                    'message' => __('An error occurred while saving your information. Please try again.', 'visitor-contact-collector')
                ));
            }
        } else {
            // Success - send notifications if enabled
            if ($settings['email_notifications']) {
                $this->send_notification_email($contact_data);
            }
            
            wp_send_json_success(array(
                'message' => $settings['success_message'],
                'contact_id' => $result
            ));
        }
    }
    
    /**
     * Check rate limiting to prevent spam
     */
    private function check_rate_limit() {
        $ip = $this->get_client_ip();
        $transient_key = 'vcc_rate_limit_' . md5($ip);
        $submissions = get_transient($transient_key);
        
        if ($submissions === false) {
            $submissions = 0;
        }
        
        // Allow max 3 submissions per IP per hour
        if ($submissions >= 3) {
            return false;
        }
        
        // Increment counter
        set_transient($transient_key, $submissions + 1, HOUR_IN_SECONDS);
        
        return true;
    }
    
    /**
     * Send notification email to admin
     */
    private function send_notification_email($contact_data) {
        $settings = vcc_get_settings();
        $to = $settings['notification_email'];
        $subject = __('New Contact Form Submission', 'visitor-contact-collector');
        
        $message = sprintf(
            __("A new contact has been submitted to your website.\n\nDetails:\n\nName: %s\nEmail: %s\nPhone: %s\nConsent Given: %s\nSubmission Time: %s\n\nYou can view all contacts in your WordPress admin panel.", 'visitor-contact-collector'),
            $contact_data['full_name'],
            $contact_data['email'],
            $contact_data['phone'],
            $contact_data['consent_given'] ? __('Yes', 'visitor-contact-collector') : __('No', 'visitor-contact-collector'),
            current_time('mysql')
        );
        
        $headers = array('Content-Type: text/plain; charset=UTF-8');
        
        wp_mail($to, $subject, $message, $headers);
    }
    
    /**
     * Get client IP address
     */
    private function get_client_ip() {
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
     * Add inline form styles based on settings
     */
    public function add_form_styles() {
        if (!$this->should_load_styles()) {
            return;
        }
        
        $settings = vcc_get_settings();
        $primary_color = $settings['primary_color'];
        
        ?>
        <style>
        :root {
            --vcc-primary-color: <?php echo esc_html($primary_color); ?>;
            --vcc-primary-color-hover: <?php echo esc_html($this->adjust_brightness($primary_color, -20)); ?>;
            --vcc-primary-color-light: <?php echo esc_html($this->adjust_brightness($primary_color, 90)); ?>;
        }
        </style>
        <?php
    }
    
    /**
     * Check if styles should be loaded on current page
     */
    private function should_load_styles() {
        global $post;
        
        // Always load on pages with shortcode
        if (is_singular() && $post && has_shortcode($post->post_content, 'visitor_contact_form')) {
            return true;
        }
        
        // Check if any widgets contain the shortcode
        $widget_contents = wp_cache_get('vcc_widget_check');
        if ($widget_contents === false) {
            $sidebars = wp_get_sidebars_widgets();
            $widget_contents = '';
            
            foreach ($sidebars as $sidebar => $widgets) {
                if (is_array($widgets)) {
                    foreach ($widgets as $widget) {
                        if (strpos($widget, 'text') === 0) {
                            $widget_instance = get_option('widget_text');
                            if (is_array($widget_instance)) {
                                foreach ($widget_instance as $instance) {
                                    if (isset($instance['text'])) {
                                        $widget_contents .= $instance['text'];
                                    }
                                }
                            }
                        }
                    }
                }
            }
            
            wp_cache_set('vcc_widget_check', $widget_contents, '', 300); // Cache for 5 minutes
        }
        
        if (has_shortcode($widget_contents, 'visitor_contact_form')) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Adjust color brightness
     */
    private function adjust_brightness($hex, $percent) {
        // Remove # if present
        $hex = ltrim($hex, '#');
        
        // Convert to RGB
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        
        // Adjust brightness
        $r = max(0, min(255, $r + ($r * $percent / 100)));
        $g = max(0, min(255, $g + ($g * $percent / 100)));
        $b = max(0, min(255, $b + ($b * $percent / 100)));
        
        // Convert back to hex
        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }
    
    /**
     * Get form for admin preview
     */
    public function get_form_preview() {
        return $this->display_form(array(
            'show_title' => 'true',
            'show_description' => 'true',
            'class' => 'vcc-preview'
        ));
    }
}
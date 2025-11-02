<?php
/**
 * Shortcode Handler Class
 * Handles shortcode registration and Gutenberg block integration
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class VCC_Shortcode {
    
    /**
     * Instance of this class
     */
    private static $instance = null;
    
    /**
     * Frontend instance
     */
    private $frontend;
    
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
        $this->frontend = VCC_Frontend::get_instance();
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('init', array($this, 'register_shortcode'));
        add_action('init', array($this, 'register_gutenberg_block'));
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_block_editor_assets'));
    }
    
    /**
     * Register the shortcode
     */
    public function register_shortcode() {
        add_shortcode('visitor_contact_form', array($this, 'shortcode_callback'));
    }
    
    /**
     * Shortcode callback function
     */
    public function shortcode_callback($atts) {
        // Parse shortcode attributes
        $atts = shortcode_atts(array(
            'title' => '',
            'description' => '',
            'style' => '',
            'color' => '',
            'show_title' => 'true',
            'show_description' => 'true',
            'class' => ''
        ), $atts, 'visitor_contact_form');
        
        // Sanitize attributes
        $atts['title'] = sanitize_text_field($atts['title']);
        $atts['description'] = sanitize_textarea_field($atts['description']);
        $atts['style'] = sanitize_text_field($atts['style']);
        $atts['color'] = sanitize_hex_color($atts['color']);
        $atts['show_title'] = in_array($atts['show_title'], array('true', 'false')) ? $atts['show_title'] : 'true';
        $atts['show_description'] = in_array($atts['show_description'], array('true', 'false')) ? $atts['show_description'] : 'true';
        $atts['class'] = sanitize_html_class($atts['class']);
        
        return $this->frontend->display_form($atts);
    }
    
    /**
     * Register Gutenberg block
     */
    public function register_gutenberg_block() {
        // Only register if Gutenberg is available
        if (!function_exists('register_block_type')) {
            return;
        }
        
        register_block_type('visitor-contact-collector/contact-form', array(
            'editor_script' => 'vcc-block-editor',
            'render_callback' => array($this, 'render_gutenberg_block'),
            'attributes' => array(
                'title' => array(
                    'type' => 'string',
                    'default' => ''
                ),
                'description' => array(
                    'type' => 'string',
                    'default' => ''
                ),
                'style' => array(
                    'type' => 'string',
                    'default' => ''
                ),
                'color' => array(
                    'type' => 'string',
                    'default' => ''
                ),
                'showTitle' => array(
                    'type' => 'boolean',
                    'default' => true
                ),
                'showDescription' => array(
                    'type' => 'boolean',
                    'default' => true
                ),
                'className' => array(
                    'type' => 'string',
                    'default' => ''
                )
            )
        ));
    }
    
    /**
     * Render Gutenberg block
     */
    public function render_gutenberg_block($attributes) {
        $atts = array(
            'title' => isset($attributes['title']) ? $attributes['title'] : '',
            'description' => isset($attributes['description']) ? $attributes['description'] : '',
            'style' => isset($attributes['style']) ? $attributes['style'] : '',
            'color' => isset($attributes['color']) ? $attributes['color'] : '',
            'show_title' => isset($attributes['showTitle']) ? ($attributes['showTitle'] ? 'true' : 'false') : 'true',
            'show_description' => isset($attributes['showDescription']) ? ($attributes['showDescription'] ? 'true' : 'false') : 'true',
            'class' => isset($attributes['className']) ? $attributes['className'] : ''
        );
        
        return $this->frontend->display_form($atts);
    }
    
    /**
     * Enqueue block editor assets
     */
    public function enqueue_block_editor_assets() {
        wp_enqueue_script(
            'vcc-block-editor',
            VCC_PLUGIN_URL . 'assets/js/block-editor.js',
            array('wp-blocks', 'wp-i18n', 'wp-element', 'wp-components', 'wp-editor'),
            VCC_VERSION,
            true
        );
        
        wp_localize_script('vcc-block-editor', 'vccBlockData', array(
            'pluginUrl' => VCC_PLUGIN_URL,
            'settings' => vcc_get_settings(),
            'preview' => $this->get_block_preview()
        ));
        
        wp_enqueue_style(
            'vcc-block-editor',
            VCC_PLUGIN_URL . 'assets/css/block-editor.css',
            array('wp-edit-blocks'),
            VCC_VERSION
        );
    }
    
    /**
     * Get block preview HTML
     */
    private function get_block_preview() {
        $settings = vcc_get_settings();
        
        ob_start();
        ?>
        <div class="vcc-block-preview">
            <div class="vcc-form-container vcc-style-rounded">
                <h3 class="vcc-form-title"><?php echo esc_html($settings['form_title']); ?></h3>
                <p class="vcc-form-description"><?php echo esc_html($settings['form_description']); ?></p>
                
                <div class="vcc-form-field">
                    <label><?php _e('Full Name', 'visitor-contact-collector'); ?> <span class="vcc-required">*</span></label>
                    <input type="text" placeholder="<?php esc_attr_e('Enter your full name', 'visitor-contact-collector'); ?>" disabled />
                </div>
                
                <div class="vcc-form-field">
                    <label><?php _e('Email Address', 'visitor-contact-collector'); ?> <span class="vcc-required">*</span></label>
                    <input type="email" placeholder="<?php esc_attr_e('Enter your email address', 'visitor-contact-collector'); ?>" disabled />
                </div>
                
                <div class="vcc-form-field">
                    <label><?php _e('Mobile Phone Number', 'visitor-contact-collector'); ?> <span class="vcc-required">*</span></label>
                    <input type="tel" placeholder="<?php esc_attr_e('Enter your phone number', 'visitor-contact-collector'); ?>" disabled />
                </div>
                
                <?php if ($settings['enable_gdpr']): ?>
                <div class="vcc-form-field vcc-checkbox-field">
                    <label class="vcc-checkbox-label">
                        <input type="checkbox" disabled />
                        <span class="vcc-checkbox-text"><?php echo esc_html($settings['gdpr_text']); ?> <span class="vcc-required">*</span></span>
                    </label>
                </div>
                <?php endif; ?>
                
                <div class="vcc-form-submit">
                    <button type="button" class="vcc-submit-btn" disabled>
                        <?php echo esc_html($settings['submit_button_text']); ?>
                    </button>
                </div>
            </div>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Get shortcode documentation
     */
    public function get_shortcode_documentation() {
        ob_start();
        ?>
        <div class="vcc-shortcode-docs">
            <h3><?php _e('Shortcode Usage', 'visitor-contact-collector'); ?></h3>
            
            <h4><?php _e('Basic Usage', 'visitor-contact-collector'); ?></h4>
            <code>[visitor_contact_form]</code>
            
            <h4><?php _e('With Custom Parameters', 'visitor-contact-collector'); ?></h4>
            <code>[visitor_contact_form title="Join Our Newsletter" description="Get the latest updates" style="minimal" color="#ff6b6b"]</code>
            
            <h4><?php _e('Available Parameters', 'visitor-contact-collector'); ?></h4>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php _e('Parameter', 'visitor-contact-collector'); ?></th>
                        <th><?php _e('Description', 'visitor-contact-collector'); ?></th>
                        <th><?php _e('Default', 'visitor-contact-collector'); ?></th>
                        <th><?php _e('Example', 'visitor-contact-collector'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>title</code></td>
                        <td><?php _e('Custom form title', 'visitor-contact-collector'); ?></td>
                        <td><?php _e('From settings', 'visitor-contact-collector'); ?></td>
                        <td><code>title="Join Our List"</code></td>
                    </tr>
                    <tr>
                        <td><code>description</code></td>
                        <td><?php _e('Custom form description', 'visitor-contact-collector'); ?></td>
                        <td><?php _e('From settings', 'visitor-contact-collector'); ?></td>
                        <td><code>description="Stay connected"</code></td>
                    </tr>
                    <tr>
                        <td><code>style</code></td>
                        <td><?php _e('Form style (rounded, square, minimal)', 'visitor-contact-collector'); ?></td>
                        <td><?php _e('From settings', 'visitor-contact-collector'); ?></td>
                        <td><code>style="minimal"</code></td>
                    </tr>
                    <tr>
                        <td><code>color</code></td>
                        <td><?php _e('Primary color (hex code)', 'visitor-contact-collector'); ?></td>
                        <td><?php _e('From settings', 'visitor-contact-collector'); ?></td>
                        <td><code>color="#ff6b6b"</code></td>
                    </tr>
                    <tr>
                        <td><code>show_title</code></td>
                        <td><?php _e('Show/hide title (true/false)', 'visitor-contact-collector'); ?></td>
                        <td><code>true</code></td>
                        <td><code>show_title="false"</code></td>
                    </tr>
                    <tr>
                        <td><code>show_description</code></td>
                        <td><?php _e('Show/hide description (true/false)', 'visitor-contact-collector'); ?></td>
                        <td><code>true</code></td>
                        <td><code>show_description="false"</code></td>
                    </tr>
                    <tr>
                        <td><code>class</code></td>
                        <td><?php _e('Additional CSS classes', 'visitor-contact-collector'); ?></td>
                        <td><code>""</code></td>
                        <td><code>class="my-custom-form"</code></td>
                    </tr>
                </tbody>
            </table>
            
            <h4><?php _e('Examples', 'visitor-contact-collector'); ?></h4>
            
            <h5><?php _e('Minimal Form (No Title/Description)', 'visitor-contact-collector'); ?></h5>
            <code>[visitor_contact_form show_title="false" show_description="false" style="minimal"]</code>
            
            <h5><?php _e('Custom Styled Form', 'visitor-contact-collector'); ?></h5>
            <code>[visitor_contact_form title="Get In Touch" color="#2c3e50" style="square" class="contact-sidebar"]</code>
            
            <h5><?php _e('Newsletter Signup', 'visitor-contact-collector'); ?></h5>
            <code>[visitor_contact_form title="Newsletter Signup" description="Get weekly updates delivered to your inbox" color="#e74c3c"]</code>
        </div>
        <?php
        
        return ob_get_clean();
    }
}
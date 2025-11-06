<?php
/**
 * Unit Tests for VCC_Shortcode Class
 */

class Test_VCC_Shortcode extends WP_UnitTestCase {
    
    private $shortcode;
    private $frontend;
    
    public function setUp() {
        parent::setUp();
        
        $this->shortcode = VCC_Shortcode::get_instance();
        $this->frontend = VCC_Frontend::get_instance();
        
        // Clean up test data
        VCC_Test_Helper::cleanup_test_data();
    }
    
    public function tearDown() {
        VCC_Test_Helper::cleanup_test_data();
        parent::tearDown();
    }
    
    /**
     * Test shortcode registration
     */
    public function test_shortcode_registration() {
        $this->shortcode->register_shortcode();
        
        $this->assertTrue(shortcode_exists('visitor_contact_form'), 'Shortcode should be registered');
    }
    
    /**
     * Test basic shortcode output
     */
    public function test_shortcode_basic_output() {
        $this->shortcode->register_shortcode();
        
        $output = do_shortcode('[visitor_contact_form]');
        
        $this->assertNotEmpty($output, 'Shortcode should produce output');
        $this->assertStringContainsString('<form', $output, 'Should contain form tag');
        $this->assertStringContainsString('vcc-contact-form', $output, 'Should contain form class');
        $this->assertStringContainsString('name="vcc_full_name"', $output, 'Should contain name field');
        $this->assertStringContainsString('name="vcc_email"', $output, 'Should contain email field');
        $this->assertStringContainsString('name="vcc_phone"', $output, 'Should contain phone field');
    }
    
    /**
     * Test shortcode with custom title
     */
    public function test_shortcode_with_custom_title() {
        $this->shortcode->register_shortcode();
        
        $custom_title = 'Custom Contact Form Title';
        $output = do_shortcode("[visitor_contact_form title=\"{$custom_title}\"]");
        
        $this->assertStringContainsString($custom_title, $output, 'Should contain custom title');
    }
    
    /**
     * Test shortcode with custom class
     */
    public function test_shortcode_with_custom_class() {
        $this->shortcode->register_shortcode();
        
        $custom_class = 'my-custom-form-class';
        $output = do_shortcode("[visitor_contact_form class=\"{$custom_class}\"]");
        
        $this->assertStringContainsString($custom_class, $output, 'Should contain custom class');
    }
    
    /**
     * Test shortcode with custom ID
     */
    public function test_shortcode_with_custom_id() {
        $this->shortcode->register_shortcode();
        
        $custom_id = 'my-contact-form';
        $output = do_shortcode("[visitor_contact_form id=\"{$custom_id}\"]");
        
        $this->assertStringContainsString("id=\"{$custom_id}\"", $output, 'Should contain custom ID');
    }
    
    /**
     * Test shortcode with multiple attributes
     */
    public function test_shortcode_with_multiple_attributes() {
        $this->shortcode->register_shortcode();
        
        $attributes = array(
            'title' => 'Contact Us Today',
            'class' => 'special-form',
            'id' => 'homepage-contact'
        );
        
        $shortcode_string = '[visitor_contact_form';
        foreach ($attributes as $key => $value) {
            $shortcode_string .= " {$key}=\"{$value}\"";
        }
        $shortcode_string .= ']';
        
        $output = do_shortcode($shortcode_string);
        
        foreach ($attributes as $key => $value) {
            $this->assertStringContainsString($value, $output, "Should contain {$key} attribute value");
        }
    }
    
    /**
     * Test shortcode attribute sanitization
     */
    public function test_shortcode_attribute_sanitization() {
        $this->shortcode->register_shortcode();
        
        // Test with potentially dangerous attributes
        $output = do_shortcode('[visitor_contact_form title="<script>alert(\'XSS\')</script>Safe Title" class="safe-class<script>"]');
        
        $this->assertStringNotContainsString('<script>', $output, 'Script tags should be sanitized');
        $this->assertStringContainsString('Safe Title', $output, 'Safe content should remain');
    }
    
    /**
     * Test shortcode default attributes
     */
    public function test_shortcode_default_attributes() {
        $this->shortcode->register_shortcode();
        
        // Get default attributes from the shortcode method
        $default_atts = array(
            'title' => '',
            'class' => '',
            'id' => '',
            'show_title' => 'true'
        );
        
        $output = do_shortcode('[visitor_contact_form]');
        
        // Should work without any attributes
        $this->assertNotEmpty($output, 'Shortcode should work with default attributes');
        $this->assertStringContainsString('<form', $output, 'Should contain form element');
    }
    
    /**
     * Test Gutenberg block registration
     */
    public function test_gutenberg_block_registration() {
        // Mock Gutenberg environment
        if (!function_exists('register_block_type')) {
            function register_block_type($block_name, $args = array()) {
                global $registered_blocks;
                $registered_blocks[$block_name] = $args;
                return true;
            }
        }
        
        $this->shortcode->register_gutenberg_block();
        
        global $registered_blocks;
        $this->assertArrayHasKey('vcc/contact-form', $registered_blocks, 'Gutenberg block should be registered');
    }
    
    /**
     * Test Gutenberg block render callback
     */
    public function test_gutenberg_block_render() {
        $attributes = array(
            'title' => 'Block Contact Form',
            'className' => 'block-form-class'
        );
        
        $output = $this->shortcode->render_gutenberg_block($attributes);
        
        $this->assertNotEmpty($output, 'Block render should produce output');
        $this->assertStringContainsString('Block Contact Form', $output, 'Should contain block title');
        $this->assertStringContainsString('block-form-class', $output, 'Should contain block class');
    }
    
    /**
     * Test shortcode with show_title attribute
     */
    public function test_shortcode_show_title_attribute() {
        $this->shortcode->register_shortcode();
        
        // Test with show_title="false"
        $output_no_title = do_shortcode('[visitor_contact_form title="Hidden Title" show_title="false"]');
        $this->assertStringNotContainsString('Hidden Title', $output_no_title, 'Title should be hidden when show_title is false');
        
        // Test with show_title="true"
        $output_with_title = do_shortcode('[visitor_contact_form title="Visible Title" show_title="true"]');
        $this->assertStringContainsString('Visible Title', $output_with_title, 'Title should be visible when show_title is true');
    }
    
    /**
     * Test shortcode caching (if implemented)
     */
    public function test_shortcode_caching() {
        $this->shortcode->register_shortcode();
        
        // First call
        $start_time = microtime(true);
        $output1 = do_shortcode('[visitor_contact_form title="Cache Test"]');
        $first_call_time = microtime(true) - $start_time;
        
        // Second call (should be cached if caching is implemented)
        $start_time = microtime(true);
        $output2 = do_shortcode('[visitor_contact_form title="Cache Test"]');
        $second_call_time = microtime(true) - $start_time;
        
        $this->assertEquals($output1, $output2, 'Output should be consistent');
        
        // If caching is implemented, second call should be faster
        // This is optional and depends on implementation
    }
    
    /**
     * Test shortcode error handling
     */
    public function test_shortcode_error_handling() {
        $this->shortcode->register_shortcode();
        
        // Mock a database error scenario
        global $wpdb;
        $original_wpdb = $wpdb;
        
        // Create a mock wpdb that will cause errors
        $wpdb = new stdClass();
        $wpdb->prefix = 'wp_';
        
        $output = do_shortcode('[visitor_contact_form]');
        
        // Even with errors, should return some output (error message or fallback)
        $this->assertNotEmpty($output, 'Should handle errors gracefully');
        
        // Restore original wpdb
        $wpdb = $original_wpdb;
    }
    
    /**
     * Test shortcode within post content
     */
    public function test_shortcode_in_post_content() {
        $this->shortcode->register_shortcode();
        
        // Create a test post with shortcode
        $post_content = 'Here is our contact form: [visitor_contact_form title="Post Contact Form"] Thank you!';
        
        $processed_content = do_shortcode($post_content);
        
        $this->assertStringContainsString('Here is our contact form:', $processed_content, 'Should preserve surrounding content');
        $this->assertStringContainsString('<form', $processed_content, 'Should process shortcode');
        $this->assertStringContainsString('Post Contact Form', $processed_content, 'Should use shortcode attributes');
        $this->assertStringContainsString('Thank you!', $processed_content, 'Should preserve content after shortcode');
    }
    
    /**
     * Test nested shortcodes (if supported)
     */
    public function test_nested_shortcode_handling() {
        $this->shortcode->register_shortcode();
        
        // Test with content that might contain other shortcodes
        $content_with_nested = '[visitor_contact_form title="Form with [nested] content"]';
        
        $output = do_shortcode($content_with_nested);
        
        $this->assertNotEmpty($output, 'Should handle nested content');
        $this->assertStringContainsString('<form', $output, 'Should render main shortcode');
    }
    
    /**
     * Test shortcode with empty attributes
     */
    public function test_shortcode_with_empty_attributes() {
        $this->shortcode->register_shortcode();
        
        $output = do_shortcode('[visitor_contact_form title="" class="" id=""]');
        
        $this->assertNotEmpty($output, 'Should work with empty attributes');
        $this->assertStringContainsString('<form', $output, 'Should contain form element');
    }
    
    /**
     * Test shortcode attribute case sensitivity
     */
    public function test_shortcode_attribute_case_sensitivity() {
        $this->shortcode->register_shortcode();
        
        // WordPress shortcodes are case-insensitive for attribute names
        $output1 = do_shortcode('[visitor_contact_form title="Test"]');
        $output2 = do_shortcode('[visitor_contact_form TITLE="Test"]');
        
        // Both should work (WordPress handles case-insensitivity)
        $this->assertStringContainsString('Test', $output1, 'Lowercase attribute should work');
        $this->assertStringContainsString('Test', $output2, 'Uppercase attribute should work');
    }
}
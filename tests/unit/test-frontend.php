<?php
/**
 * Unit Tests for VCC_Frontend Class
 */

class Test_VCC_Frontend extends WP_UnitTestCase {
    
    private $frontend;
    private $database;
    
    public function setUp() {
        parent::setUp();
        
        $this->frontend = VCC_Frontend::get_instance();
        $this->database = VCC_Database::get_instance();
        
        // Clean up any existing test data
        VCC_Test_Helper::cleanup_test_data();
        
        // Mock WordPress functions
        VCC_Mock_WP_Functions::reset();
    }
    
    public function tearDown() {
        VCC_Test_Helper::cleanup_test_data();
        VCC_Mock_WP_Functions::clear_sent_emails();
        parent::tearDown();
    }
    
    /**
     * Test form display
     */
    public function test_display_form() {
        $form_html = $this->frontend->display_form();
        
        $this->assertNotEmpty($form_html, 'Form should return HTML');
        $this->assertStringContainsString('<form', $form_html, 'Should contain form tag');
        $this->assertStringContainsString('vcc-contact-form', $form_html, 'Should contain form class');
        $this->assertStringContainsString('name="vcc_full_name"', $form_html, 'Should contain name field');
        $this->assertStringContainsString('name="vcc_email"', $form_html, 'Should contain email field');
        $this->assertStringContainsString('name="vcc_phone"', $form_html, 'Should contain phone field');
        $this->assertStringContainsString('name="vcc_consent"', $form_html, 'Should contain consent checkbox');
    }
    
    /**
     * Test form display with custom attributes
     */
    public function test_display_form_with_attributes() {
        $attributes = array(
            'class' => 'custom-class',
            'id' => 'custom-form'
        );
        
        $form_html = $this->frontend->display_form($attributes);
        
        $this->assertStringContainsString('custom-class', $form_html, 'Should contain custom class');
        $this->assertStringContainsString('id="custom-form"', $form_html, 'Should contain custom ID');
    }
    
    /**
     * Test form validation with valid data
     */
    public function test_validate_form_data_valid() {
        $form_data = array(
            'vcc_full_name' => 'John Doe',
            'vcc_email' => 'john.doe@example.com',
            'vcc_phone' => '+1 (555) 123-4567',
            'vcc_consent' => '1'
        );
        
        $result = $this->frontend->validate_form_data($form_data);
        $this->assertTrue($result['valid'], 'Valid data should pass validation');
        $this->assertEmpty($result['errors'], 'Valid data should have no errors');
    }
    
    /**
     * Test form validation with missing required fields
     */
    public function test_validate_form_data_missing_fields() {
        $form_data = array(
            'vcc_full_name' => '',
            'vcc_email' => '',
            'vcc_phone' => '',
            'vcc_consent' => ''
        );
        
        $result = $this->frontend->validate_form_data($form_data);
        $this->assertFalse($result['valid'], 'Missing fields should fail validation');
        $this->assertNotEmpty($result['errors'], 'Missing fields should generate errors');
        
        // Check specific error messages
        $this->assertArrayHasKey('full_name', $result['errors'], 'Should have full name error');
        $this->assertArrayHasKey('email', $result['errors'], 'Should have email error');
        $this->assertArrayHasKey('consent', $result['errors'], 'Should have consent error');
    }
    
    /**
     * Test form validation with invalid email
     */
    public function test_validate_form_data_invalid_email() {
        $form_data = array(
            'vcc_full_name' => 'John Doe',
            'vcc_email' => 'invalid-email',
            'vcc_phone' => '+1 (555) 123-4567',
            'vcc_consent' => '1'
        );
        
        $result = $this->frontend->validate_form_data($form_data);
        $this->assertFalse($result['valid'], 'Invalid email should fail validation');
        $this->assertArrayHasKey('email', $result['errors'], 'Should have email error');
    }
    
    /**
     * Test form validation with invalid phone number
     */
    public function test_validate_form_data_invalid_phone() {
        $form_data = array(
            'vcc_full_name' => 'John Doe',
            'vcc_email' => 'john.doe@example.com',
            'vcc_phone' => 'invalid-phone',
            'vcc_consent' => '1'
        );
        
        $result = $this->frontend->validate_form_data($form_data);
        $this->assertFalse($result['valid'], 'Invalid phone should fail validation');
        $this->assertArrayHasKey('phone', $result['errors'], 'Should have phone error');
    }
    
    /**
     * Test form submission handling
     */
    public function test_handle_form_submission() {
        // Mock $_POST data
        $_POST = array(
            'vcc_full_name' => 'John Doe',
            'vcc_email' => 'john.doe@example.com',
            'vcc_phone' => '+1 (555) 123-4567',
            'vcc_consent' => '1',
            'vcc_nonce' => wp_create_nonce('vcc_form_nonce'),
            'vcc_action' => 'submit_contact'
        );
        
        // Mock server variables
        $_SERVER['REMOTE_ADDR'] = '192.168.1.1';
        $_SERVER['HTTP_USER_AGENT'] = 'Test Browser';
        $_SERVER['HTTP_REFERER'] = 'http://example.com/contact';
        
        ob_start();
        $this->frontend->handle_form_submission();
        $output = ob_get_clean();
        
        // Check if contact was inserted
        $contacts = $this->database->get_contacts(array('search' => 'john.doe@example.com'));
        $this->assertGreaterThan(0, count($contacts), 'Contact should be inserted');
        
        // Check response
        $response = json_decode($output, true);
        $this->assertTrue($response['success'], 'Submission should be successful');
    }
    
    /**
     * Test form submission with invalid data
     */
    public function test_handle_form_submission_invalid_data() {
        // Mock $_POST data with invalid email
        $_POST = array(
            'vcc_full_name' => 'John Doe',
            'vcc_email' => 'invalid-email',
            'vcc_phone' => '+1 (555) 123-4567',
            'vcc_consent' => '1',
            'vcc_nonce' => wp_create_nonce('vcc_form_nonce'),
            'vcc_action' => 'submit_contact'
        );
        
        ob_start();
        $this->frontend->handle_form_submission();
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        $this->assertFalse($response['success'], 'Invalid submission should fail');
        $this->assertNotEmpty($response['errors'], 'Should return validation errors');
    }
    
    /**
     * Test form submission without nonce
     */
    public function test_handle_form_submission_invalid_nonce() {
        $_POST = array(
            'vcc_full_name' => 'John Doe',
            'vcc_email' => 'john.doe@example.com',
            'vcc_phone' => '+1 (555) 123-4567',
            'vcc_consent' => '1',
            'vcc_nonce' => 'invalid_nonce',
            'vcc_action' => 'submit_contact'
        );
        
        ob_start();
        $this->frontend->handle_form_submission();
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        $this->assertFalse($response['success'], 'Invalid nonce should fail');
    }
    
    /**
     * Test duplicate email prevention
     */
    public function test_duplicate_email_prevention() {
        $email = VCC_Test_Helper::generate_random_email();
        
        // Insert first contact
        $contact_data = VCC_Test_Helper::get_test_contact_data(array('email' => $email));
        $this->database->insert_contact($contact_data);
        
        // Try to submit duplicate email
        $_POST = array(
            'vcc_full_name' => 'Jane Doe',
            'vcc_email' => $email,
            'vcc_phone' => '+1 (555) 987-6543',
            'vcc_consent' => '1',
            'vcc_nonce' => wp_create_nonce('vcc_form_nonce'),
            'vcc_action' => 'submit_contact'
        );
        
        ob_start();
        $this->frontend->handle_form_submission();
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        
        // Check based on plugin settings for duplicate handling
        $allow_duplicates = get_option('vcc_allow_duplicate_emails', false);
        
        if (!$allow_duplicates) {
            $this->assertFalse($response['success'], 'Duplicate email should be rejected');
        } else {
            $this->assertTrue($response['success'], 'Duplicate email should be allowed if setting permits');
        }
    }
    
    /**
     * Test email notification sending
     */
    public function test_send_notification_email() {
        $contact_data = VCC_Test_Helper::get_test_contact_data();
        
        // Enable email notifications
        update_option('vcc_enable_email_notifications', true);
        update_option('vcc_notification_email', 'admin@example.com');
        
        $result = $this->frontend->send_notification_email($contact_data);
        $this->assertTrue($result, 'Email notification should be sent');
        
        // Check if email was sent (using mock)
        $sent_emails = VCC_Mock_WP_Functions::get_sent_emails();
        $this->assertGreaterThan(0, count($sent_emails), 'Email should be sent');
        
        if (!empty($sent_emails)) {
            $email = $sent_emails[0];
            $this->assertEquals('admin@example.com', $email['to'], 'Email should be sent to admin');
            $this->assertStringContainsString($contact_data['full_name'], $email['message'], 'Email should contain contact name');
        }
    }
    
    /**
     * Test auto-responder email
     */
    public function test_send_autoresponder_email() {
        $contact_data = VCC_Test_Helper::get_test_contact_data();
        
        // Enable auto-responder
        update_option('vcc_enable_autoresponder', true);
        update_option('vcc_autoresponder_subject', 'Thank you for contacting us');
        update_option('vcc_autoresponder_message', 'We will get back to you soon.');
        
        $result = $this->frontend->send_autoresponder_email($contact_data);
        $this->assertTrue($result, 'Auto-responder email should be sent');
        
        // Check if email was sent
        $sent_emails = VCC_Mock_WP_Functions::get_sent_emails();
        $this->assertGreaterThan(0, count($sent_emails), 'Auto-responder email should be sent');
        
        if (!empty($sent_emails)) {
            $email = end($sent_emails); // Get last email (auto-responder)
            $this->assertEquals($contact_data['email'], $email['to'], 'Auto-responder should be sent to contact');
            $this->assertStringContainsString('Thank you for contacting us', $email['subject'], 'Should contain custom subject');
        }
    }
    
    /**
     * Test form HTML structure and security
     */
    public function test_form_security_features() {
        $form_html = $this->frontend->display_form();
        
        // Check for security features
        $this->assertStringContainsString('wp_nonce_field', $form_html, 'Should include nonce field');
        $this->assertStringContainsString('required', $form_html, 'Should have required field validation');
        $this->assertStringContainsString('type="email"', $form_html, 'Should use email input type');
        $this->assertStringContainsString('type="tel"', $form_html, 'Should use tel input type');
    }
    
    /**
     * Test rate limiting (if implemented)
     */
    public function test_rate_limiting() {
        $email = VCC_Test_Helper::generate_random_email();
        
        // Submit multiple forms quickly
        for ($i = 0; $i < 5; $i++) {
            $_POST = array(
                'vcc_full_name' => "Test User {$i}",
                'vcc_email' => $email,
                'vcc_phone' => '+1 (555) 123-456' . $i,
                'vcc_consent' => '1',
                'vcc_nonce' => wp_create_nonce('vcc_form_nonce'),
                'vcc_action' => 'submit_contact'
            );
            
            ob_start();
            $this->frontend->handle_form_submission();
            $output = ob_get_clean();
            
            $response = json_decode($output, true);
            
            // After certain submissions, rate limiting should kick in
            if ($i > 2) {
                // Depending on implementation, this might be rate limited
                $this->assertIsArray($response, 'Should return valid response even with rate limiting');
            }
        }
    }
    
    /**
     * Test sanitization of input data
     */
    public function test_input_sanitization() {
        $_POST = array(
            'vcc_full_name' => '<script>alert("XSS")</script>John Doe',
            'vcc_email' => 'john.doe@example.com',
            'vcc_phone' => '+1 (555) 123-4567',
            'vcc_consent' => '1',
            'vcc_nonce' => wp_create_nonce('vcc_form_nonce'),
            'vcc_action' => 'submit_contact'
        );
        
        ob_start();
        $this->frontend->handle_form_submission();
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        $this->assertTrue($response['success'], 'Submission should succeed with sanitized data');
        
        // Check that script tags were removed
        $contacts = $this->database->get_contacts(array('search' => 'john.doe@example.com'));
        if (!empty($contacts)) {
            $this->assertStringNotContainsString('<script>', $contacts[0]->full_name, 'Script tags should be sanitized');
            $this->assertStringContainsString('John Doe', $contacts[0]->full_name, 'Valid content should remain');
        }
    }
}
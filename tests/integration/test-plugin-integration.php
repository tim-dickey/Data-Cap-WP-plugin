<?php
/**
 * Integration Tests for Visitor Contact Collector Plugin
 */

class Test_VCC_Integration extends WP_UnitTestCase {
    
    private $plugin;
    private $user_id;
    
    public function setUp() {
        parent::setUp();
        
        // Create admin user
        $this->user_id = VCC_Test_Helper::create_test_user('administrator');
        wp_set_current_user($this->user_id);
        
        // Initialize plugin
        $this->plugin = new VisitorContactCollector();
        
        // Clean test data
        VCC_Test_Helper::cleanup_test_data();
    }
    
    public function tearDown() {
        VCC_Test_Helper::cleanup_test_data();
        VCC_Test_Helper::cleanup_test_users();
        parent::tearDown();
    }
    
    /**
     * Test complete plugin activation flow
     */
    public function test_plugin_activation_flow() {
        global $wpdb;
        
        // Drop table if exists
        $table_name = $wpdb->prefix . 'vcc_contacts';
        $wpdb->query("DROP TABLE IF EXISTS {$table_name}");
        
        // Activate plugin
        $this->plugin->activate();
        
        // Check that table was created
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
        $this->assertTrue($table_exists, 'Database table should be created on activation');
        
        // Check that default options were set
        $default_options = array(
            'vcc_enable_email_notifications' => false,
            'vcc_notification_email' => get_option('admin_email'),
            'vcc_form_title' => 'Contact Us',
            'vcc_success_message' => 'Thank you for your submission!',
            'vcc_data_retention_days' => 365
        );
        
        foreach ($default_options as $option_name => $expected_value) {
            $actual_value = get_option($option_name);
            $this->assertEquals($expected_value, $actual_value, "Option {$option_name} should be set correctly");
        }
    }
    
    /**
     * Test complete form submission workflow
     */
    public function test_complete_form_submission_workflow() {
        // Enable email notifications for testing
        update_option('vcc_enable_email_notifications', true);
        update_option('vcc_notification_email', 'admin@example.com');
        
        // Prepare form data
        $_POST = array(
            'vcc_full_name' => 'Integration Test User',
            'vcc_email' => 'integration@example.com',
            'vcc_phone' => '+1 (555) 123-4567',
            'vcc_consent' => '1',
            'vcc_nonce' => wp_create_nonce('vcc_form_nonce'),
            'vcc_action' => 'submit_contact'
        );
        
        $_SERVER['REMOTE_ADDR'] = '192.168.1.100';
        $_SERVER['HTTP_USER_AGENT'] = 'Integration Test Browser';
        $_SERVER['HTTP_REFERER'] = 'http://example.com/contact-page';
        
        // Process form submission
        $frontend = VCC_Frontend::get_instance();
        
        ob_start();
        $frontend->handle_form_submission();
        $response_json = ob_get_clean();
        
        $response = json_decode($response_json, true);
        
        // Verify successful submission
        $this->assertTrue($response['success'], 'Form submission should succeed');
        $this->assertNotEmpty($response['message'], 'Should return success message');
        
        // Verify contact was stored in database
        $database = VCC_Database::get_instance();
        $contacts = $database->get_contacts(array('search' => 'integration@example.com'));
        
        $this->assertCount(1, $contacts, 'Exactly one contact should be stored');
        
        $contact = $contacts[0];
        $this->assertEquals('Integration Test User', $contact->full_name, 'Name should be stored correctly');
        $this->assertEquals('integration@example.com', $contact->email, 'Email should be stored correctly');
        $this->assertEquals('+1 (555) 123-4567', $contact->phone, 'Phone should be stored correctly');
        $this->assertEquals('192.168.1.100', $contact->ip_address, 'IP address should be stored');
        $this->assertEquals('Integration Test Browser', $contact->user_agent, 'User agent should be stored');
        $this->assertEquals(1, $contact->consent_given, 'Consent should be recorded');
    }
    
    /**
     * Test shortcode integration with WordPress
     */
    public function test_shortcode_integration() {
        // Register shortcode
        $shortcode = VCC_Shortcode::get_instance();
        $shortcode->register_shortcode();
        
        // Create a post with shortcode
        $post_id = wp_insert_post(array(
            'post_title' => 'Contact Page',
            'post_content' => 'Please fill out our contact form: [visitor_contact_form title="Get In Touch"]',
            'post_status' => 'publish',
            'post_type' => 'page'
        ));
        
        $this->assertGreaterThan(0, $post_id, 'Test post should be created');
        
        // Get post content and process shortcodes
        $post = get_post($post_id);
        $content = do_shortcode($post->post_content);
        
        // Verify shortcode was processed
        $this->assertStringContainsString('<form', $content, 'Shortcode should render form');
        $this->assertStringContainsString('Get In Touch', $content, 'Custom title should be displayed');
        $this->assertStringContainsString('vcc-contact-form', $content, 'Form should have correct CSS class');
        
        // Clean up
        wp_delete_post($post_id, true);
    }
    
    /**
     * Test admin interface integration
     */
    public function test_admin_interface_integration() {
        // Insert test contacts
        $database = VCC_Database::get_instance();
        $test_contacts = VCC_Test_Helper::create_test_contacts(3);
        
        foreach ($test_contacts as $contact) {
            $database->insert_contact($contact);
        }
        
        // Test admin contacts page
        $admin = VCC_Admin::get_instance();
        
        ob_start();
        $admin->contacts_page();
        $contacts_page_output = ob_get_clean();
        
        $this->assertNotEmpty($contacts_page_output, 'Admin contacts page should produce output');
        $this->assertStringContainsString('Test User', $contacts_page_output, 'Should display test contacts');
        $this->assertStringContainsString('wp-list-table', $contacts_page_output, 'Should use WordPress list table');
        
        // Test export functionality from admin
        $_POST['export_format'] = 'csv';
        $_POST['date_range'] = 'all';
        $_POST['vcc_export_nonce'] = wp_create_nonce('vcc_export_nonce');
        
        ob_start();
        $admin->handle_export();
        $export_output = ob_get_clean();
        
        $this->assertNotEmpty($export_output, 'Export should produce output');
        $this->assertStringContainsString('Test User', $export_output, 'Export should contain contact data');
    }
    
    /**
     * Test GDPR integration with WordPress privacy tools
     */
    public function test_gdpr_wordpress_integration() {
        $email = 'gdpr-test@example.com';
        
        // Insert test contact
        $database = VCC_Database::get_instance();
        $contact_data = VCC_Test_Helper::get_test_contact_data(array('email' => $email));
        $database->insert_contact($contact_data);
        
        // Test WordPress privacy export integration
        $gdpr = VCC_GDPR::get_instance();
        
        // Register privacy hooks
        $gdpr->register_privacy_hooks();
        
        // Test data export
        $export_data = $gdpr->export_personal_data($email, 1);
        
        $this->assertIsArray($export_data, 'GDPR export should return array');
        $this->assertArrayHasKey('data', $export_data, 'Should contain data');
        $this->assertNotEmpty($export_data['data'], 'Should contain personal data');
        
        // Test data erasure
        $erasure_data = $gdpr->erase_personal_data($email, 1);
        
        $this->assertIsArray($erasure_data, 'GDPR erasure should return array');
        $this->assertGreaterThan(0, $erasure_data['items_removed'], 'Should remove personal data');
        
        // Verify data was actually erased
        $remaining_contacts = $database->get_contacts(array('search' => $email));
        $this->assertEmpty($remaining_contacts, 'Contact should be erased after GDPR deletion');
    }
    
    /**
     * Test plugin settings integration
     */
    public function test_plugin_settings_integration() {
        $admin = VCC_Admin::get_instance();
        
        // Test settings save
        $_POST = array(
            'vcc_enable_email_notifications' => '1',
            'vcc_notification_email' => 'custom@example.com',
            'vcc_form_title' => 'Custom Form Title',
            'vcc_success_message' => 'Custom success message',
            'vcc_enable_autoresponder' => '1',
            'vcc_autoresponder_subject' => 'Thank you!',
            'vcc_settings_nonce' => wp_create_nonce('vcc_settings_nonce')
        );
        
        $admin->save_settings();
        
        // Verify settings were saved
        $this->assertEquals('1', get_option('vcc_enable_email_notifications'), 'Email notifications should be enabled');
        $this->assertEquals('custom@example.com', get_option('vcc_notification_email'), 'Custom email should be saved');
        $this->assertEquals('Custom Form Title', get_option('vcc_form_title'), 'Form title should be saved');
        
        // Test that settings affect form display
        $frontend = VCC_Frontend::get_instance();
        $form_html = $frontend->display_form();
        
        $this->assertStringContainsString('Custom Form Title', $form_html, 'Form should use custom title');
    }
    
    /**
     * Test email notifications integration
     */
    public function test_email_notifications_integration() {
        // Enable notifications
        update_option('vcc_enable_email_notifications', true);
        update_option('vcc_notification_email', 'notifications@example.com');
        update_option('vcc_enable_autoresponder', true);
        update_option('vcc_autoresponder_subject', 'Thank you for contacting us');
        
        // Clear any previous emails
        VCC_Mock_WP_Functions::clear_sent_emails();
        
        // Submit contact form
        $contact_data = VCC_Test_Helper::get_test_contact_data();
        
        $frontend = VCC_Frontend::get_instance();
        $frontend->send_notification_email($contact_data);
        $frontend->send_autoresponder_email($contact_data);
        
        // Check emails were sent
        $sent_emails = VCC_Mock_WP_Functions::get_sent_emails();
        
        $this->assertGreaterThanOrEqual(2, count($sent_emails), 'Should send notification and autoresponder emails');
        
        // Check notification email
        $notification_email = $sent_emails[0];
        $this->assertEquals('notifications@example.com', $notification_email['to'], 'Notification should go to admin');
        $this->assertStringContainsString($contact_data['full_name'], $notification_email['message'], 'Should contain contact name');
        
        // Check autoresponder email
        $autoresponder_email = $sent_emails[1];
        $this->assertEquals($contact_data['email'], $autoresponder_email['to'], 'Autoresponder should go to contact');
        $this->assertStringContainsString('Thank you for contacting us', $autoresponder_email['subject'], 'Should have custom subject');
    }
    
    /**
     * Test bulk operations integration
     */
    public function test_bulk_operations_integration() {
        $database = VCC_Database::get_instance();
        $admin = VCC_Admin::get_instance();
        
        // Insert multiple test contacts
        $contact_ids = array();
        for ($i = 0; $i < 5; $i++) {
            $contact_data = VCC_Test_Helper::get_test_contact_data(array(
                'full_name' => "Bulk Test User {$i}",
                'email' => "bulk{$i}@example.com"
            ));
            $contact_ids[] = $database->insert_contact($contact_data);
        }
        
        // Test bulk delete
        $_POST = array(
            'action' => 'delete',
            'contacts' => array($contact_ids[0], $contact_ids[1], $contact_ids[2]),
            '_wpnonce' => wp_create_nonce('bulk-contacts')
        );
        
        $admin->handle_bulk_action();
        
        // Verify first 3 contacts were deleted
        for ($i = 0; $i < 3; $i++) {
            $contact = $database->get_contact($contact_ids[$i]);
            $this->assertNull($contact, "Contact {$i} should be deleted");
        }
        
        // Verify last 2 contacts still exist
        for ($i = 3; $i < 5; $i++) {
            $contact = $database->get_contact($contact_ids[$i]);
            $this->assertNotNull($contact, "Contact {$i} should still exist");
        }
    }
    
    /**
     * Test export functionality integration
     */
    public function test_export_functionality_integration() {
        $database = VCC_Database::get_instance();
        $export = VCC_Export::get_instance();
        
        // Insert test data with different dates
        $contacts = array(
            VCC_Test_Helper::get_test_contact_data(array(
                'submission_date' => date('Y-m-d H:i:s', strtotime('-1 week'))
            )),
            VCC_Test_Helper::get_test_contact_data(array(
                'submission_date' => date('Y-m-d H:i:s', strtotime('-1 month'))
            )),
            VCC_Test_Helper::get_test_contact_data(array(
                'submission_date' => date('Y-m-d H:i:s')
            ))
        );
        
        foreach ($contacts as $contact) {
            $database->insert_contact($contact);
        }
        
        // Test CSV export with date filtering
        $csv_data = $export->export_csv(array(
            'start_date' => date('Y-m-d', strtotime('-2 weeks')),
            'end_date' => date('Y-m-d')
        ));
        
        $this->assertNotEmpty($csv_data, 'CSV export should return data');
        $this->assertStringContainsString('Full Name,Email,Phone', $csv_data, 'Should contain headers');
        
        // Count rows to verify filtering
        $rows = explode("\n", trim($csv_data));
        $this->assertGreaterThan(1, count($rows), 'Should contain header plus data rows');
        
        // Test JSON export
        $json_data = $export->export_json();
        $json_array = json_decode($json_data, true);
        
        $this->assertIsArray($json_array, 'JSON export should decode properly');
        $this->assertGreaterThanOrEqual(3, count($json_array), 'Should contain all test contacts');
    }
    
    /**
     * Test plugin deactivation
     */
    public function test_plugin_deactivation() {
        // Set some options
        update_option('vcc_test_option', 'test_value');
        
        // Deactivate plugin
        $this->plugin->deactivate();
        
        // Options should still exist after deactivation (not deleted)
        $this->assertEquals('test_value', get_option('vcc_test_option'), 'Options should persist after deactivation');
        
        // Clean up
        delete_option('vcc_test_option');
    }
    
    /**
     * Test complete user journey
     */
    public function test_complete_user_journey() {
        // 1. User visits contact page with form
        $shortcode = VCC_Shortcode::get_instance();
        $shortcode->register_shortcode();
        
        $form_html = do_shortcode('[visitor_contact_form title="Contact Us"]');
        $this->assertStringContainsString('<form', $form_html, 'Form should be displayed');
        
        // 2. User submits form
        $_POST = array(
            'vcc_full_name' => 'Journey Test User',
            'vcc_email' => 'journey@example.com',
            'vcc_phone' => '+1 (555) 999-8888',
            'vcc_consent' => '1',
            'vcc_nonce' => wp_create_nonce('vcc_form_nonce'),
            'vcc_action' => 'submit_contact'
        );
        
        $frontend = VCC_Frontend::get_instance();
        ob_start();
        $frontend->handle_form_submission();
        $submission_response = ob_get_clean();
        
        $response = json_decode($submission_response, true);
        $this->assertTrue($response['success'], 'Form submission should succeed');
        
        // 3. Admin views contact in dashboard
        $database = VCC_Database::get_instance();
        $contacts = $database->get_contacts();
        
        $journey_contact = null;
        foreach ($contacts as $contact) {
            if ($contact->email === 'journey@example.com') {
                $journey_contact = $contact;
                break;
            }
        }
        
        $this->assertNotNull($journey_contact, 'Contact should appear in admin dashboard');
        
        // 4. Admin exports data
        $export = VCC_Export::get_instance();
        $csv_data = $export->export_csv();
        $this->assertStringContainsString('Journey Test User', $csv_data, 'Contact should appear in export');
        
        // 5. GDPR request - export user data
        $gdpr = VCC_GDPR::get_instance();
        $export_data = $gdpr->export_personal_data('journey@example.com', 1);
        $this->assertNotEmpty($export_data['data'], 'GDPR export should contain user data');
        
        // 6. GDPR request - delete user data
        $erasure_data = $gdpr->erase_personal_data('journey@example.com', 1);
        $this->assertGreaterThan(0, $erasure_data['items_removed'], 'GDPR erasure should remove data');
        
        // 7. Verify data is gone
        $remaining_contacts = $database->get_contacts(array('search' => 'journey@example.com'));
        $this->assertEmpty($remaining_contacts, 'Contact data should be completely removed');
    }
}
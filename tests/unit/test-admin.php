<?php
/**
 * Unit Tests for VCC_Admin Class
 */

class Test_VCC_Admin extends WP_UnitTestCase {
    
    private $admin;
    private $database;
    private $user_id;
    
    public function setUp() {
        parent::setUp();
        
        $this->admin = VCC_Admin::get_instance();
        $this->database = VCC_Database::get_instance();
        
        // Create admin user for testing
        $this->user_id = VCC_Test_Helper::create_test_user('administrator');
        wp_set_current_user($this->user_id);
        
        // Clean up test data
        VCC_Test_Helper::cleanup_test_data();
        
        // Reset mock functions
        VCC_Mock_WP_Functions::reset();
    }
    
    public function tearDown() {
        VCC_Test_Helper::cleanup_test_data();
        VCC_Test_Helper::cleanup_test_users();
        parent::tearDown();
    }
    
    /**
     * Test admin menu registration
     */
    public function test_admin_menu_registration() {
        // Test that admin menus are registered
        $this->admin->add_admin_menu();
        
        global $menu, $submenu;
        
        // Check if main menu is added
        $menu_found = false;
        if (is_array($menu)) {
            foreach ($menu as $menu_item) {
                if (isset($menu_item[0]) && strpos($menu_item[0], 'Contact Collector') !== false) {
                    $menu_found = true;
                    break;
                }
            }
        }
        
        $this->assertTrue($menu_found, 'Admin menu should be registered');
    }
    
    /**
     * Test contacts list page
     */
    public function test_contacts_page() {
        // Insert test contacts
        $test_contacts = VCC_Test_Helper::create_test_contacts(3);
        foreach ($test_contacts as $contact) {
            $this->database->insert_contact($contact);
        }
        
        ob_start();
        $this->admin->contacts_page();
        $output = ob_get_clean();
        
        $this->assertNotEmpty($output, 'Contacts page should produce output');
        $this->assertStringContainsString('wp-list-table', $output, 'Should contain list table');
        $this->assertStringContainsString('Test User', $output, 'Should show test contacts');
    }
    
    /**
     * Test settings page display
     */
    public function test_settings_page() {
        ob_start();
        $this->admin->settings_page();
        $output = ob_get_clean();
        
        $this->assertNotEmpty($output, 'Settings page should produce output');
        $this->assertStringContainsString('<form', $output, 'Should contain form');
        $this->assertStringContainsString('vcc_settings', $output, 'Should contain settings form');
    }
    
    /**
     * Test export page
     */
    public function test_export_page() {
        ob_start();
        $this->admin->export_page();
        $output = ob_get_clean();
        
        $this->assertNotEmpty($output, 'Export page should produce output');
        $this->assertStringContainsString('export', $output, 'Should contain export functionality');
        $this->assertStringContainsString('CSV', $output, 'Should mention CSV export');
        $this->assertStringContainsString('JSON', $output, 'Should mention JSON export');
    }
    
    /**
     * Test GDPR tools page
     */
    public function test_gdpr_tools_page() {
        ob_start();
        $this->admin->gdpr_tools_page();
        $output = ob_get_clean();
        
        $this->assertNotEmpty($output, 'GDPR tools page should produce output');
        $this->assertStringContainsString('GDPR', $output, 'Should contain GDPR content');
        $this->assertStringContainsString('export', $output, 'Should contain export option');
        $this->assertStringContainsString('delete', $output, 'Should contain delete option');
    }
    
    /**
     * Test bulk actions handling
     */
    public function test_handle_bulk_action() {
        // Insert test contacts
        $contact_ids = array();
        $test_contacts = VCC_Test_Helper::create_test_contacts(3);
        
        foreach ($test_contacts as $contact) {
            $contact_ids[] = $this->database->insert_contact($contact);
        }
        
        // Mock bulk delete action
        $_POST['action'] = 'delete';
        $_POST['contacts'] = $contact_ids;
        $_POST['_wpnonce'] = wp_create_nonce('bulk-contacts');
        
        $result = $this->admin->handle_bulk_action();
        
        // Check that contacts were deleted
        foreach ($contact_ids as $contact_id) {
            $contact = $this->database->get_contact($contact_id);
            $this->assertNull($contact, "Contact {$contact_id} should be deleted");
        }
    }
    
    /**
     * Test contact deletion via AJAX
     */
    public function test_ajax_delete_contact() {
        // Insert test contact
        $contact_data = VCC_Test_Helper::get_test_contact_data();
        $contact_id = $this->database->insert_contact($contact_data);
        
        // Mock AJAX request
        $_POST['action'] = 'vcc_delete_contact';
        $_POST['contact_id'] = $contact_id;
        $_POST['nonce'] = wp_create_nonce('vcc_admin_nonce');
        
        ob_start();
        $this->admin->ajax_delete_contact();
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        $this->assertTrue($response['success'], 'AJAX delete should succeed');
        
        // Verify contact was deleted
        $contact = $this->database->get_contact($contact_id);
        $this->assertNull($contact, 'Contact should be deleted');
    }
    
    /**
     * Test contact statistics
     */
    public function test_get_contact_statistics() {
        // Insert test contacts with different dates
        $today = current_time('mysql');
        $yesterday = date('Y-m-d H:i:s', strtotime('-1 day'));
        $last_week = date('Y-m-d H:i:s', strtotime('-1 week'));
        
        $contacts = array(
            VCC_Test_Helper::get_test_contact_data(array('submission_date' => $today)),
            VCC_Test_Helper::get_test_contact_data(array('submission_date' => $yesterday)),
            VCC_Test_Helper::get_test_contact_data(array('submission_date' => $last_week))
        );
        
        foreach ($contacts as $contact) {
            $this->database->insert_contact($contact);
        }
        
        $stats = $this->admin->get_contact_statistics();
        
        $this->assertIsArray($stats, 'Statistics should return array');
        $this->assertArrayHasKey('total', $stats, 'Should include total count');
        $this->assertArrayHasKey('today', $stats, 'Should include today count');
        $this->assertArrayHasKey('this_week', $stats, 'Should include week count');
        $this->assertArrayHasKey('this_month', $stats, 'Should include month count');
        
        $this->assertGreaterThanOrEqual(3, $stats['total'], 'Total should be at least 3');
        $this->assertGreaterThanOrEqual(1, $stats['today'], 'Today should be at least 1');
    }
    
    /**
     * Test settings validation and saving
     */
    public function test_save_settings() {
        $settings_data = array(
            'vcc_enable_email_notifications' => true,
            'vcc_notification_email' => 'admin@example.com',
            'vcc_enable_autoresponder' => false,
            'vcc_form_title' => 'Contact Us',
            'vcc_success_message' => 'Thank you for your submission!'
        );
        
        // Mock POST data
        $_POST = array_merge($_POST, $settings_data);
        $_POST['vcc_settings_nonce'] = wp_create_nonce('vcc_settings_nonce');
        
        $this->admin->save_settings();
        
        // Verify settings were saved
        foreach ($settings_data as $key => $value) {
            $saved_value = get_option($key);
            $this->assertEquals($value, $saved_value, "Setting {$key} should be saved correctly");
        }
    }
    
    /**
     * Test search functionality
     */
    public function test_search_contacts() {
        // Insert contacts with unique names
        $unique_name = 'Unique Search Test ' . wp_rand(1000, 9999);
        $contact_data = VCC_Test_Helper::get_test_contact_data(array(
            'full_name' => $unique_name
        ));
        $this->database->insert_contact($contact_data);
        
        // Also insert a contact that shouldn't match
        $other_contact = VCC_Test_Helper::get_test_contact_data(array(
            'full_name' => 'Different Name'
        ));
        $this->database->insert_contact($other_contact);
        
        // Test search
        $_GET['s'] = $unique_name;
        
        $search_results = $this->admin->get_contacts_for_list_table();
        
        $this->assertNotEmpty($search_results, 'Search should return results');
        
        // Check that search results contain the searched contact
        $found = false;
        foreach ($search_results as $contact) {
            if ($contact->full_name === $unique_name) {
                $found = true;
                break;
            }
        }
        
        $this->assertTrue($found, 'Search should find the specific contact');
    }
    
    /**
     * Test pagination
     */
    public function test_pagination() {
        // Insert many test contacts
        $test_contacts = VCC_Test_Helper::create_test_contacts(25);
        foreach ($test_contacts as $contact) {
            $this->database->insert_contact($contact);
        }
        
        // Test first page
        $_GET['paged'] = 1;
        $contacts_page_1 = $this->admin->get_contacts_for_list_table(20); // 20 per page
        
        $this->assertCount(20, $contacts_page_1, 'First page should have 20 contacts');
        
        // Test second page
        $_GET['paged'] = 2;
        $contacts_page_2 = $this->admin->get_contacts_for_list_table(20);
        
        $this->assertGreaterThan(0, count($contacts_page_2), 'Second page should have contacts');
        $this->assertLessThanOrEqual(20, count($contacts_page_2), 'Second page should not exceed 20 contacts');
    }
    
    /**
     * Test capability checks
     */
    public function test_capability_checks() {
        // Test with non-admin user
        $subscriber_id = VCC_Test_Helper::create_test_user('subscriber');
        wp_set_current_user($subscriber_id);
        
        // Admin pages should not be accessible
        $this->expectException(Exception::class);
        $this->admin->contacts_page();
    }
    
    /**
     * Test contact export functionality
     */
    public function test_contact_export() {
        // Insert test contacts
        $test_contacts = VCC_Test_Helper::create_test_contacts(5);
        foreach ($test_contacts as $contact) {
            $this->database->insert_contact($contact);
        }
        
        // Test CSV export
        $_POST['export_format'] = 'csv';
        $_POST['date_range'] = 'all';
        $_POST['vcc_export_nonce'] = wp_create_nonce('vcc_export_nonce');
        
        ob_start();
        $this->admin->handle_export();
        $output = ob_get_clean();
        
        $this->assertNotEmpty($output, 'Export should produce output');
        $this->assertStringContainsString('Test User', $output, 'Export should contain test data');
    }
    
    /**
     * Test GDPR data export
     */
    public function test_gdpr_data_export() {
        $email = VCC_Test_Helper::generate_random_email();
        
        // Insert contact with specific email
        $contact_data = VCC_Test_Helper::get_test_contact_data(array('email' => $email));
        $this->database->insert_contact($contact_data);
        
        // Mock AJAX request for GDPR export
        $_POST['action'] = 'vcc_gdpr_export';
        $_POST['email'] = $email;
        $_POST['nonce'] = wp_create_nonce('vcc_admin_nonce');
        
        ob_start();
        $this->admin->ajax_gdpr_export();
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        $this->assertTrue($response['success'], 'GDPR export should succeed');
        $this->assertArrayHasKey('data', $response['data'], 'Should contain exported data');
    }
    
    /**
     * Test GDPR data deletion
     */
    public function test_gdpr_data_deletion() {
        $email = VCC_Test_Helper::generate_random_email();
        
        // Insert contact with specific email
        $contact_data = VCC_Test_Helper::get_test_contact_data(array('email' => $email));
        $contact_id = $this->database->insert_contact($contact_data);
        
        // Mock AJAX request for GDPR deletion
        $_POST['action'] = 'vcc_gdpr_delete';
        $_POST['email'] = $email;
        $_POST['nonce'] = wp_create_nonce('vcc_admin_nonce');
        
        ob_start();
        $this->admin->ajax_gdpr_delete();
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        $this->assertTrue($response['success'], 'GDPR deletion should succeed');
        
        // Verify contact was deleted
        $contact = $this->database->get_contact($contact_id);
        $this->assertNull($contact, 'Contact should be deleted');
    }
    
    /**
     * Test admin notices
     */
    public function test_admin_notices() {
        // Set a test notice
        set_transient('vcc_admin_notice', array(
            'message' => 'Test notice message',
            'type' => 'success'
        ), 30);
        
        ob_start();
        $this->admin->show_admin_notices();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('Test notice message', $output, 'Should display admin notice');
        $this->assertStringContainsString('notice-success', $output, 'Should have correct notice type');
    }
}
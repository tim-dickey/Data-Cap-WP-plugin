<?php
/**
 * Unit Tests for VCC_Database Class
 */

class Test_VCC_Database extends WP_UnitTestCase {
    
    private $database;
    private $table_name;
    
    public function setUp() {
        parent::setUp();
        
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'vcc_contacts';
        $this->database = VCC_Database::get_instance();
        
        // Ensure table exists
        $this->database->create_table();
        
        // Clean up any existing test data
        VCC_Test_Helper::cleanup_test_data();
    }
    
    public function tearDown() {
        VCC_Test_Helper::cleanup_test_data();
        parent::tearDown();
    }
    
    /**
     * Test table creation
     */
    public function test_table_creation() {
        global $wpdb;
        
        // Drop table first
        $wpdb->query("DROP TABLE IF EXISTS {$this->table_name}");
        
        // Create table
        $result = $this->database->create_table();
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$this->table_name}'") === $this->table_name;
        $this->assertTrue($table_exists, 'Database table should be created');
    }
    
    /**
     * Test inserting a contact
     */
    public function test_insert_contact() {
        $contact_data = VCC_Test_Helper::get_test_contact_data();
        
        $contact_id = $this->database->insert_contact($contact_data);
        
        $this->assertIsInt($contact_id, 'Insert should return contact ID');
        $this->assertGreaterThan(0, $contact_id, 'Contact ID should be positive integer');
    }
    
    /**
     * Test inserting contact with invalid data
     */
    public function test_insert_contact_invalid_data() {
        // Missing required fields
        $invalid_data = array(
            'full_name' => 'Test User'
            // Missing email and other required fields
        );
        
        $result = $this->database->insert_contact($invalid_data);
        $this->assertFalse($result, 'Insert should fail with invalid data');
    }
    
    /**
     * Test inserting contact with invalid email
     */
    public function test_insert_contact_invalid_email() {
        $contact_data = VCC_Test_Helper::get_test_contact_data(array(
            'email' => 'invalid-email'
        ));
        
        $result = $this->database->insert_contact($contact_data);
        $this->assertFalse($result, 'Insert should fail with invalid email');
    }
    
    /**
     * Test getting contacts
     */
    public function test_get_contacts() {
        // Insert test contacts
        $test_contacts = VCC_Test_Helper::create_test_contacts(3);
        
        foreach ($test_contacts as $contact) {
            $this->database->insert_contact($contact);
        }
        
        $contacts = $this->database->get_contacts();
        
        $this->assertIsArray($contacts, 'get_contacts should return array');
        $this->assertGreaterThanOrEqual(3, count($contacts), 'Should return at least 3 contacts');
    }
    
    /**
     * Test getting contacts with limit
     */
    public function test_get_contacts_with_limit() {
        // Insert test contacts
        $test_contacts = VCC_Test_Helper::create_test_contacts(5);
        
        foreach ($test_contacts as $contact) {
            $this->database->insert_contact($contact);
        }
        
        $contacts = $this->database->get_contacts(array('limit' => 3));
        
        $this->assertCount(3, $contacts, 'Should return exactly 3 contacts when limited');
    }
    
    /**
     * Test getting contacts with search
     */
    public function test_get_contacts_with_search() {
        // Insert specific test contact
        $unique_name = 'Unique Test User ' . wp_rand(1000, 9999);
        $contact_data = VCC_Test_Helper::get_test_contact_data(array(
            'full_name' => $unique_name
        ));
        
        $this->database->insert_contact($contact_data);
        
        $contacts = $this->database->get_contacts(array('search' => $unique_name));
        
        $this->assertGreaterThan(0, count($contacts), 'Search should return matching contacts');
        $this->assertEquals($unique_name, $contacts[0]->full_name, 'Returned contact should match search');
    }
    
    /**
     * Test getting contact by ID
     */
    public function test_get_contact_by_id() {
        $contact_data = VCC_Test_Helper::get_test_contact_data();
        $contact_id = $this->database->insert_contact($contact_data);
        
        $contact = $this->database->get_contact($contact_id);
        
        $this->assertIsObject($contact, 'get_contact should return object');
        $this->assertEquals($contact_data['full_name'], $contact->full_name, 'Contact name should match');
        $this->assertEquals($contact_data['email'], $contact->email, 'Contact email should match');
    }
    
    /**
     * Test getting non-existent contact
     */
    public function test_get_nonexistent_contact() {
        $contact = $this->database->get_contact(99999);
        $this->assertNull($contact, 'Should return null for non-existent contact');
    }
    
    /**
     * Test deleting contact
     */
    public function test_delete_contact() {
        $contact_data = VCC_Test_Helper::get_test_contact_data();
        $contact_id = $this->database->insert_contact($contact_data);
        
        $result = $this->database->delete_contact($contact_id);
        $this->assertTrue($result, 'Delete should return true');
        
        $contact = $this->database->get_contact($contact_id);
        $this->assertNull($contact, 'Contact should not exist after deletion');
    }
    
    /**
     * Test deleting contacts by email
     */
    public function test_delete_contacts_by_email() {
        $email = VCC_Test_Helper::generate_random_email();
        
        // Insert multiple contacts with same email
        for ($i = 0; $i < 3; $i++) {
            $contact_data = VCC_Test_Helper::get_test_contact_data(array(
                'email' => $email,
                'full_name' => "User {$i}"
            ));
            $this->database->insert_contact($contact_data);
        }
        
        $result = $this->database->delete_contacts_by_email($email);
        $this->assertGreaterThan(0, $result, 'Should delete multiple contacts');
        
        $contacts = $this->database->get_contacts(array('search' => $email));
        $this->assertEmpty($contacts, 'No contacts should remain with that email');
    }
    
    /**
     * Test getting contact count
     */
    public function test_get_contact_count() {
        $initial_count = $this->database->get_contact_count();
        
        // Insert test contacts
        $test_contacts = VCC_Test_Helper::create_test_contacts(3);
        foreach ($test_contacts as $contact) {
            $this->database->insert_contact($contact);
        }
        
        $new_count = $this->database->get_contact_count();
        $this->assertEquals($initial_count + 3, $new_count, 'Count should increase by 3');
    }
    
    /**
     * Test data validation
     */
    public function test_validate_contact_data() {
        $valid_data = VCC_Test_Helper::get_test_contact_data();
        $this->assertTrue($this->database->validate_contact_data($valid_data), 'Valid data should pass validation');
        
        // Test missing required field
        $invalid_data = $valid_data;
        unset($invalid_data['email']);
        $this->assertFalse($this->database->validate_contact_data($invalid_data), 'Missing email should fail validation');
        
        // Test invalid email format
        $invalid_data = $valid_data;
        $invalid_data['email'] = 'invalid-email';
        $this->assertFalse($this->database->validate_contact_data($invalid_data), 'Invalid email format should fail validation');
    }
    
    /**
     * Test cleanup old contacts
     */
    public function test_cleanup_old_contacts() {
        global $wpdb;
        
        // Insert old contact (simulate old date)
        $old_date = date('Y-m-d H:i:s', strtotime('-2 years'));
        $contact_data = VCC_Test_Helper::get_test_contact_data(array(
            'submission_date' => $old_date
        ));
        
        $wpdb->insert(
            $this->table_name,
            $contact_data,
            array('%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s')
        );
        
        $cleanup_count = $this->database->cleanup_old_contacts(365); // Delete contacts older than 1 year
        $this->assertGreaterThan(0, $cleanup_count, 'Should cleanup old contacts');
    }
    
    /**
     * Test export contacts
     */
    public function test_export_contacts() {
        // Insert test contacts
        $test_contacts = VCC_Test_Helper::create_test_contacts(3);
        foreach ($test_contacts as $contact) {
            $this->database->insert_contact($contact);
        }
        
        $exported_data = $this->database->export_contacts();
        
        $this->assertIsArray($exported_data, 'Export should return array');
        $this->assertGreaterThanOrEqual(3, count($exported_data), 'Should export at least 3 contacts');
        
        // Check data structure
        if (!empty($exported_data)) {
            $first_contact = $exported_data[0];
            $required_keys = array('id', 'full_name', 'email', 'phone', 'submission_date');
            VCC_Test_Helper::assertArrayHasKeys($required_keys, $first_contact, 'Exported contact should have required keys');
        }
    }
}
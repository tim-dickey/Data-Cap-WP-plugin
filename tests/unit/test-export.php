<?php
/**
 * Unit Tests for VCC_Export Class
 */

class Test_VCC_Export extends WP_UnitTestCase {
    
    private $export;
    private $database;
    
    public function setUp() {
        parent::setUp();
        
        $this->export = VCC_Export::get_instance();
        $this->database = VCC_Database::get_instance();
        
        // Clean up test data
        VCC_Test_Helper::cleanup_test_data();
        
        // Insert test contacts for export testing
        $test_contacts = VCC_Test_Helper::create_test_contacts(5);
        foreach ($test_contacts as $contact) {
            $this->database->insert_contact($contact);
        }
    }
    
    public function tearDown() {
        VCC_Test_Helper::cleanup_test_data();
        parent::tearDown();
    }
    
    /**
     * Test CSV export functionality
     */
    public function test_csv_export() {
        $csv_data = $this->export->export_csv();
        
        $this->assertNotEmpty($csv_data, 'CSV export should return data');
        $this->assertStringContainsString('Full Name,Email,Phone', $csv_data, 'CSV should contain headers');
        $this->assertStringContainsString('Test User', $csv_data, 'CSV should contain test data');
        $this->assertStringContainsString('@example.com', $csv_data, 'CSV should contain email data');
    }
    
    /**
     * Test CSV export with date filtering
     */
    public function test_csv_export_with_date_filter() {
        // Insert contact with specific date
        $specific_date = date('Y-m-d H:i:s', strtotime('-1 week'));
        $contact_data = VCC_Test_Helper::get_test_contact_data(array(
            'full_name' => 'Filtered Contact',
            'submission_date' => $specific_date
        ));
        $this->database->insert_contact($contact_data);
        
        // Export with date filter
        $start_date = date('Y-m-d', strtotime('-2 weeks'));
        $end_date = date('Y-m-d', strtotime('-3 days'));
        
        $csv_data = $this->export->export_csv(array(
            'start_date' => $start_date,
            'end_date' => $end_date
        ));
        
        $this->assertStringContainsString('Filtered Contact', $csv_data, 'Filtered CSV should contain specific contact');
    }
    
    /**
     * Test JSON export functionality
     */
    public function test_json_export() {
        $json_data = $this->export->export_json();
        
        $this->assertNotEmpty($json_data, 'JSON export should return data');
        
        $decoded_data = json_decode($json_data, true);
        $this->assertIsArray($decoded_data, 'JSON should decode to array');
        $this->assertGreaterThan(0, count($decoded_data), 'JSON should contain contact data');
        
        // Check structure of first contact
        if (!empty($decoded_data)) {
            $first_contact = $decoded_data[0];
            $required_fields = array('id', 'full_name', 'email', 'phone', 'submission_date');
            
            foreach ($required_fields as $field) {
                $this->assertArrayHasKey($field, $first_contact, "JSON should contain {$field} field");
            }
        }
    }
    
    /**
     * Test JSON export with date filtering
     */
    public function test_json_export_with_date_filter() {
        $start_date = date('Y-m-d', strtotime('-1 week'));
        $end_date = date('Y-m-d');
        
        $json_data = $this->export->export_json(array(
            'start_date' => $start_date,
            'end_date' => $end_date
        ));
        
        $decoded_data = json_decode($json_data, true);
        $this->assertIsArray($decoded_data, 'Filtered JSON should decode to array');
        
        // Verify dates are within range
        foreach ($decoded_data as $contact) {
            $contact_date = date('Y-m-d', strtotime($contact['submission_date']));
            $this->assertGreaterThanOrEqual($start_date, $contact_date, 'Contact date should be after start date');
            $this->assertLessThanOrEqual($end_date, $contact_date, 'Contact date should be before end date');
        }
    }
    
    /**
     * Test export with empty dataset
     */
    public function test_export_empty_dataset() {
        // Clear all test data
        VCC_Test_Helper::cleanup_test_data();
        
        $csv_data = $this->export->export_csv();
        $json_data = $this->export->export_json();
        
        // Should still return headers for CSV
        $this->assertStringContainsString('Full Name,Email,Phone', $csv_data, 'Empty CSV should contain headers');
        
        // JSON should return empty array
        $decoded_json = json_decode($json_data, true);
        $this->assertIsArray($decoded_json, 'Empty JSON should be valid array');
        $this->assertEmpty($decoded_json, 'Empty JSON should be empty array');
    }
    
    /**
     * Test export field selection
     */
    public function test_export_field_selection() {
        $selected_fields = array('full_name', 'email');
        
        $csv_data = $this->export->export_csv(array('fields' => $selected_fields));
        
        $this->assertStringContainsString('Full Name,Email', $csv_data, 'CSV should contain selected headers');
        $this->assertStringNotContainsString('Phone', $csv_data, 'CSV should not contain unselected fields');
    }
    
    /**
     * Test CSV special character handling
     */
    public function test_csv_special_characters() {
        // Insert contact with special characters
        $special_contact = VCC_Test_Helper::get_test_contact_data(array(
            'full_name' => 'Test, "User" With Special Chars',
            'email' => 'special@example.com'
        ));
        $this->database->insert_contact($special_contact);
        
        $csv_data = $this->export->export_csv();
        
        // CSV should properly escape special characters
        $this->assertStringContainsString('"Test, ""User"" With Special Chars"', $csv_data, 'CSV should properly escape quotes and commas');
    }
    
    /**
     * Test export with large dataset
     */
    public function test_export_large_dataset() {
        // Insert many contacts to test memory handling
        for ($i = 0; $i < 100; $i++) {
            $contact_data = VCC_Test_Helper::get_test_contact_data(array(
                'full_name' => "Bulk User {$i}",
                'email' => "bulk{$i}@example.com"
            ));
            $this->database->insert_contact($contact_data);
        }
        
        $csv_data = $this->export->export_csv();
        $json_data = $this->export->export_json();
        
        $this->assertNotEmpty($csv_data, 'Large CSV export should succeed');
        $this->assertNotEmpty($json_data, 'Large JSON export should succeed');
        
        // Count lines in CSV (should be 100+ contacts plus header)
        $csv_lines = substr_count($csv_data, "\n");
        $this->assertGreaterThan(100, $csv_lines, 'CSV should contain all contacts');
        
        // Count JSON objects
        $json_array = json_decode($json_data, true);
        $this->assertGreaterThan(100, count($json_array), 'JSON should contain all contacts');
    }
    
    /**
     * Test date range validation
     */
    public function test_date_range_validation() {
        // Test with invalid date range (end before start)
        $invalid_range = array(
            'start_date' => '2023-12-31',
            'end_date' => '2023-01-01'
        );
        
        $csv_data = $this->export->export_csv($invalid_range);
        
        // Should handle invalid range gracefully (return empty or all data)
        $this->assertIsString($csv_data, 'Invalid date range should be handled gracefully');
    }
    
    /**
     * Test export formatting options
     */
    public function test_export_formatting_options() {
        $options = array(
            'include_headers' => false
        );
        
        $csv_data = $this->export->export_csv($options);
        
        // If headers are disabled, should not contain header row
        if (isset($options['include_headers']) && !$options['include_headers']) {
            $this->assertStringNotContainsString('Full Name,Email,Phone', $csv_data, 'Should not contain headers when disabled');
        }
    }
    
    /**
     * Test export file generation
     */
    public function test_generate_export_file() {
        $temp_file = $this->export->generate_csv_file();
        
        if ($temp_file) {
            $this->assertFileExists($temp_file, 'Export file should be created');
            $this->assertStringContainsString('.csv', $temp_file, 'File should have CSV extension');
            
            $file_content = file_get_contents($temp_file);
            $this->assertStringContainsString('Test User', $file_content, 'File should contain export data');
            
            // Clean up
            unlink($temp_file);
        }
    }
    
    /**
     * Test export with custom delimiter
     */
    public function test_export_custom_delimiter() {
        $options = array(
            'delimiter' => ';'
        );
        
        $csv_data = $this->export->export_csv($options);
        
        $this->assertStringContainsString('Full Name;Email;Phone', $csv_data, 'Should use custom delimiter');
        $this->assertStringNotContainsString('Full Name,Email,Phone', $csv_data, 'Should not use default delimiter');
    }
    
    /**
     * Test data privacy in exports
     */
    public function test_export_data_privacy() {
        // Test that sensitive data is properly handled
        $csv_data = $this->export->export_csv();
        $json_data = $this->export->export_json();
        
        // Should not contain IP addresses in basic export (privacy consideration)
        $this->assertStringNotContainsString('192.168.1.1', $csv_data, 'Basic export should not expose IP addresses');
        
        $json_array = json_decode($json_data, true);
        if (!empty($json_array)) {
            $first_contact = $json_array[0];
            $this->assertArrayNotHasKey('ip_address', $first_contact, 'JSON export should not expose IP addresses by default');
        }
    }
    
    /**
     * Test export statistics
     */
    public function test_export_statistics() {
        $stats = $this->export->get_export_statistics();
        
        $this->assertIsArray($stats, 'Export statistics should return array');
        $this->assertArrayHasKey('total_contacts', $stats, 'Should include total contacts count');
        $this->assertArrayHasKey('date_range', $stats, 'Should include date range info');
        
        $this->assertGreaterThan(0, $stats['total_contacts'], 'Should have contact count');
    }
    
    /**
     * Test export error handling
     */
    public function test_export_error_handling() {
        // Mock database error
        global $wpdb;
        $original_wpdb = $wpdb;
        
        // Create mock wpdb that will cause errors
        $wpdb = new stdClass();
        $wpdb->prefix = 'wp_';
        $wpdb->get_results = function() {
            return false;
        };
        
        $csv_data = $this->export->export_csv();
        
        // Should handle errors gracefully
        $this->assertIsString($csv_data, 'Should return string even on error');
        
        // Restore wpdb
        $wpdb = $original_wpdb;
    }
}
<?php
/**
 * Unit Tests for VCC_GDPR Class
 */

class Test_VCC_GDPR extends WP_UnitTestCase {
    
    private $gdpr;
    private $database;
    
    public function setUp() {
        parent::setUp();
        
        $this->gdpr = VCC_GDPR::get_instance();
        $this->database = VCC_Database::get_instance();
        
        // Clean up test data
        VCC_Test_Helper::cleanup_test_data();
    }
    
    public function tearDown() {
        VCC_Test_Helper::cleanup_test_data();
        parent::tearDown();
    }
    
    /**
     * Test GDPR data export
     */
    public function test_export_personal_data() {
        $email = VCC_Test_Helper::generate_random_email();
        
        // Insert test contact
        $contact_data = VCC_Test_Helper::get_test_contact_data(array('email' => $email));
        $this->database->insert_contact($contact_data);
        
        // Test data export
        $export_data = $this->gdpr->export_personal_data($email, 1);
        
        $this->assertIsArray($export_data, 'Export should return array');
        $this->assertArrayHasKey('data', $export_data, 'Export should contain data key');
        $this->assertArrayHasKey('done', $export_data, 'Export should contain done key');
        
        if (!empty($export_data['data'])) {
            $contact_export = $export_data['data'][0];
            $this->assertArrayHasKey('group_id', $contact_export, 'Should have group_id');
            $this->assertArrayHasKey('group_label', $contact_export, 'Should have group_label');
            $this->assertArrayHasKey('item_id', $contact_export, 'Should have item_id');
            $this->assertArrayHasKey('data', $contact_export, 'Should have data array');
            
            // Check that exported data contains personal information
            $personal_data = $contact_export['data'];
            $this->assertNotEmpty($personal_data, 'Should contain personal data');
            
            // Verify structure of personal data items
            foreach ($personal_data as $data_item) {
                $this->assertArrayHasKey('name', $data_item, 'Data item should have name');
                $this->assertArrayHasKey('value', $data_item, 'Data item should have value');
            }
        }
    }
    
    /**
     * Test GDPR data export for non-existent email
     */
    public function test_export_personal_data_nonexistent_email() {
        $non_existent_email = 'nonexistent@example.com';
        
        $export_data = $this->gdpr->export_personal_data($non_existent_email, 1);
        
        $this->assertIsArray($export_data, 'Export should return array');
        $this->assertEmpty($export_data['data'], 'Should return empty data for non-existent email');
        $this->assertTrue($export_data['done'], 'Should mark as done even for non-existent email');
    }
    
    /**
     * Test GDPR data erasure
     */
    public function test_erase_personal_data() {
        $email = VCC_Test_Helper::generate_random_email();
        
        // Insert test contact
        $contact_data = VCC_Test_Helper::get_test_contact_data(array('email' => $email));
        $contact_id = $this->database->insert_contact($contact_data);
        
        // Verify contact exists
        $contact = $this->database->get_contact($contact_id);
        $this->assertNotNull($contact, 'Contact should exist before erasure');
        
        // Test data erasure
        $erasure_result = $this->gdpr->erase_personal_data($email, 1);
        
        $this->assertIsArray($erasure_result, 'Erasure should return array');
        $this->assertArrayHasKey('items_removed', $erasure_result, 'Should have items_removed count');
        $this->assertArrayHasKey('items_retained', $erasure_result, 'Should have items_retained count');
        $this->assertArrayHasKey('messages', $erasure_result, 'Should have messages array');
        $this->assertArrayHasKey('done', $erasure_result, 'Should have done flag');
        
        $this->assertGreaterThan(0, $erasure_result['items_removed'], 'Should report items removed');
        $this->assertTrue($erasure_result['done'], 'Should mark erasure as done');
        
        // Verify contact was deleted
        $contact_after_erasure = $this->database->get_contact($contact_id);
        $this->assertNull($contact_after_erasure, 'Contact should be deleted after erasure');
    }
    
    /**
     * Test GDPR data erasure for non-existent email
     */
    public function test_erase_personal_data_nonexistent_email() {
        $non_existent_email = 'nonexistent@example.com';
        
        $erasure_result = $this->gdpr->erase_personal_data($non_existent_email, 1);
        
        $this->assertIsArray($erasure_result, 'Erasure should return array');
        $this->assertEquals(0, $erasure_result['items_removed'], 'Should remove 0 items for non-existent email');
        $this->assertTrue($erasure_result['done'], 'Should mark as done for non-existent email');
    }
    
    /**
     * Test automatic data cleanup based on retention policy
     */
    public function test_cleanup_old_data() {
        global $wpdb;
        
        // Insert old contacts (simulate old dates)
        $old_date = date('Y-m-d H:i:s', strtotime('-2 years'));
        $recent_date = date('Y-m-d H:i:s', strtotime('-1 month'));
        
        $old_contact = VCC_Test_Helper::get_test_contact_data(array(
            'email' => 'old@example.com',
            'submission_date' => $old_date
        ));
        
        $recent_contact = VCC_Test_Helper::get_test_contact_data(array(
            'email' => 'recent@example.com',
            'submission_date' => $recent_date
        ));
        
        $old_id = $this->database->insert_contact($old_contact);
        $recent_id = $this->database->insert_contact($recent_contact);
        
        // Set retention period to 1 year
        update_option('vcc_data_retention_days', 365);
        
        // Run cleanup
        $cleanup_count = $this->gdpr->cleanup_old_data();
        
        $this->assertIsInt($cleanup_count, 'Cleanup should return integer count');
        $this->assertGreaterThan(0, $cleanup_count, 'Should cleanup old data');
        
        // Verify old contact was deleted
        $old_contact_after = $this->database->get_contact($old_id);
        $this->assertNull($old_contact_after, 'Old contact should be deleted');
        
        // Verify recent contact still exists
        $recent_contact_after = $this->database->get_contact($recent_id);
        $this->assertNotNull($recent_contact_after, 'Recent contact should still exist');
    }
    
    /**
     * Test WordPress privacy hook registration
     */
    public function test_privacy_hooks_registration() {
        // Test that privacy hooks are registered
        $this->gdpr->register_privacy_hooks();
        
        // Check if export hook is registered
        $export_registered = has_filter('wp_privacy_personal_data_exporters', array($this->gdpr, 'register_data_exporter'));
        $this->assertNotFalse($export_registered, 'Data exporter should be registered');
        
        // Check if erasure hook is registered
        $erasure_registered = has_filter('wp_privacy_personal_data_erasers', array($this->gdpr, 'register_data_eraser'));
        $this->assertNotFalse($erasure_registered, 'Data eraser should be registered');
    }
    
    /**
     * Test data exporter registration
     */
    public function test_register_data_exporter() {
        $exporters = array();
        $updated_exporters = $this->gdpr->register_data_exporter($exporters);
        
        $this->assertIsArray($updated_exporters, 'Should return array of exporters');
        $this->assertCount(1, $updated_exporters, 'Should add one exporter');
        
        $vcc_exporter = $updated_exporters[0];
        $this->assertArrayHasKey('exporter_friendly_name', $vcc_exporter, 'Should have friendly name');
        $this->assertArrayHasKey('callback', $vcc_exporter, 'Should have callback');
        $this->assertEquals('Visitor Contact Collector', $vcc_exporter['exporter_friendly_name'], 'Should have correct name');
    }
    
    /**
     * Test data eraser registration
     */
    public function test_register_data_eraser() {
        $erasers = array();
        $updated_erasers = $this->gdpr->register_data_eraser($erasers);
        
        $this->assertIsArray($updated_erasers, 'Should return array of erasers');
        $this->assertCount(1, $updated_erasers, 'Should add one eraser');
        
        $vcc_eraser = $updated_erasers[0];
        $this->assertArrayHasKey('eraser_friendly_name', $vcc_eraser, 'Should have friendly name');
        $this->assertArrayHasKey('callback', $vcc_eraser, 'Should have callback');
        $this->assertEquals('Visitor Contact Collector', $vcc_eraser['eraser_friendly_name'], 'Should have correct name');
    }
    
    /**
     * Test consent validation
     */
    public function test_validate_consent() {
        // Test with valid consent
        $valid_consent_data = array(
            'vcc_consent' => '1'
        );
        $this->assertTrue($this->gdpr->validate_consent($valid_consent_data), 'Valid consent should pass validation');
        
        // Test with missing consent
        $no_consent_data = array();
        $this->assertFalse($this->gdpr->validate_consent($no_consent_data), 'Missing consent should fail validation');
        
        // Test with explicit rejection
        $rejected_consent_data = array(
            'vcc_consent' => '0'
        );
        $this->assertFalse($this->gdpr->validate_consent($rejected_consent_data), 'Rejected consent should fail validation');
    }
    
    /**
     * Test consent record storage
     */
    public function test_record_consent() {
        $contact_data = VCC_Test_Helper::get_test_contact_data();
        $contact_id = $this->database->insert_contact($contact_data);
        
        $consent_data = array(
            'contact_id' => $contact_id,
            'consent_given' => true,
            'consent_date' => current_time('mysql'),
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Test Browser'
        );
        
        $result = $this->gdpr->record_consent($consent_data);
        $this->assertTrue($result, 'Consent recording should succeed');
        
        // Verify consent was recorded
        $contact = $this->database->get_contact($contact_id);
        $this->assertEquals(1, $contact->consent_given, 'Consent should be recorded in database');
    }
    
    /**
     * Test privacy policy text generation
     */
    public function test_get_privacy_policy_text() {
        $privacy_text = $this->gdpr->get_privacy_policy_text();
        
        $this->assertNotEmpty($privacy_text, 'Privacy policy text should not be empty');
        $this->assertStringContainsString('contact', $privacy_text, 'Should mention contact data');
        $this->assertStringContainsString('email', $privacy_text, 'Should mention email collection');
        $this->assertStringContainsString('GDPR', $privacy_text, 'Should mention GDPR compliance');
    }
    
    /**
     * Test data anonymization
     */
    public function test_anonymize_contact_data() {
        $email = VCC_Test_Helper::generate_random_email();
        $contact_data = VCC_Test_Helper::get_test_contact_data(array('email' => $email));
        $contact_id = $this->database->insert_contact($contact_data);
        
        // Anonymize the contact
        $result = $this->gdpr->anonymize_contact_data($contact_id);
        $this->assertTrue($result, 'Anonymization should succeed');
        
        // Verify data was anonymized
        $contact = $this->database->get_contact($contact_id);
        $this->assertNotNull($contact, 'Contact should still exist after anonymization');
        $this->assertNotEquals($contact_data['full_name'], $contact->full_name, 'Name should be anonymized');
        $this->assertNotEquals($email, $contact->email, 'Email should be anonymized');
        $this->assertStringContainsString('anonymized', strtolower($contact->full_name), 'Should indicate anonymization');
    }
    
    /**
     * Test data retention policy enforcement
     */
    public function test_data_retention_policy() {
        // Set retention policy
        update_option('vcc_data_retention_days', 30);
        update_option('vcc_enable_auto_cleanup', true);
        
        // Insert contact older than retention period
        $old_date = date('Y-m-d H:i:s', strtotime('-45 days'));
        $contact_data = VCC_Test_Helper::get_test_contact_data(array(
            'submission_date' => $old_date
        ));
        $contact_id = $this->database->insert_contact($contact_data);
        
        // Run retention policy enforcement
        $this->gdpr->enforce_data_retention_policy();
        
        // Verify old contact was handled according to policy
        $contact = $this->database->get_contact($contact_id);
        
        // Depending on policy, contact should be deleted or anonymized
        $policy_action = get_option('vcc_retention_action', 'delete');
        
        if ($policy_action === 'delete') {
            $this->assertNull($contact, 'Old contact should be deleted');
        } elseif ($policy_action === 'anonymize') {
            $this->assertNotNull($contact, 'Old contact should exist but be anonymized');
            $this->assertStringContainsString('anonymized', strtolower($contact->full_name), 'Should be anonymized');
        }
    }
    
    /**
     * Test GDPR compliance check
     */
    public function test_gdpr_compliance_check() {
        $compliance_status = $this->gdpr->check_gdpr_compliance();
        
        $this->assertIsArray($compliance_status, 'Compliance check should return array');
        $this->assertArrayHasKey('overall_compliant', $compliance_status, 'Should have overall compliance status');
        $this->assertArrayHasKey('checks', $compliance_status, 'Should have individual checks');
        
        // Verify individual compliance checks
        $checks = $compliance_status['checks'];
        $this->assertArrayHasKey('privacy_policy', $checks, 'Should check privacy policy');
        $this->assertArrayHasKey('consent_mechanism', $checks, 'Should check consent mechanism');
        $this->assertArrayHasKey('data_retention', $checks, 'Should check data retention policy');
        $this->assertArrayHasKey('export_capability', $checks, 'Should check export capability');
        $this->assertArrayHasKey('erasure_capability', $checks, 'Should check erasure capability');
    }
}
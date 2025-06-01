<?php
/**
 * Test script for SMS functionality
 */

require_once __DIR__ . '/vendor/autoload.php';

// Load environment variables
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
} else {
    die("Error: .env file not found. Please copy .env.example to .env and configure it.\n");
}

// Include SMS Notifier
require_once __DIR__ . '/includes/SMSNotifier.php';

// Test data
$testPhone = getenv('SMS_TEST_RECIPIENT');
if (empty($testPhone)) {
    die("Error: SMS_TEST_RECIPIENT not set in .env\n");
}

$testMessage = "Test message from HealthAssist Pro at " . date('Y-m-d H:i:s');

// Initialize SMS Notifier
$smsNotifier = new SMSNotifier('twilio', [
    'twilio_account_sid' => getenv('TWILIO_ACCOUNT_SID'),
    'twilio_auth_token' => getenv('TWILIO_AUTH_TOKEN'),
    'twilio_from_number' => getenv('TWILIO_FROM_NUMBER'),
    'test_mode' => getenv('SMS_TEST_MODE') === 'true',
    'test_recipient' => $testPhone
]);

// Send test message
echo "Sending test SMS to: $testPhone\n";
echo "Message: $testMessage\n\n";

$result = $smsNotifier->sendSMS($testPhone, $testMessage);

// Display result
echo "=== Test Result ===\n";
echo "Success: " . ($result['success'] ? 'Yes' : 'No') . "\n";
echo "Message: " . $result['message'] . "\n";

if (isset($result['provider_response'])) {
    if (is_string($result['provider_response'])) {
        echo "Provider Response: " . $result['provider_response'] . "\n";
    } else {
        echo "Provider Response: " . json_encode($result['provider_response'], JSON_PRETTY_PRINT) . "\n";
    }
}

// Test medication reminder
if ($result['success']) {
    echo "\n=== Testing Medication Reminder ===\n";
    
    $testMedication = [
        'name' => 'Test Medication',
        'dosage' => '1 tablet',
        'notes' => 'Take with water'
    ];
    
    $testUser = [
        'phone_number' => $testPhone,
        'first_name' => 'Test',
        'last_name' => 'User',
        'timezone' => 'UTC'
    ];
    
    $testReminder = [
        'reminder_time' => date('H:i:s'),
        'days_of_week' => '1,2,3,4,5,6,7'
    ];
    
    $result = $smsNotifier->sendMedicationReminder($testUser, $testMedication, $testReminder);
    
    echo "Medication Reminder Test: " . ($result['success'] ? 'SUCCESS' : 'FAILED') . "\n";
    echo "Message: " . $result['message'] . "\n";
}

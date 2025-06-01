<?php
/**
 * This script should be set up as a cron job to run every minute:
 * * * * * php /path/to/your/site/cron/send_medication_reminders.php
 */

// Set timezone to UTC for consistency
if (!ini_get('date.timezone')) {
    date_default_timezone_set('UTC');
}

// Load configuration
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/SMSNotifier.php';

// Load environment variables if using .env
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && substr(trim($line), 0, 1) !== '#') {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, "'\" ");
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get current time in UTC
    $now = new DateTime('now', new DateTimeZone('UTC'));
    $current_time = $now->format('H:i:00'); // Format as HH:MM:SS
    $current_day = $now->format('N'); // 1 (Monday) to 7 (Sunday)
    
    // Find all active reminders for the current time
    $query = "
        SELECT mr.*, m.name, m.dosage, m.notes, 
               u.phone_number, u.first_name, u.last_name, u.timezone,
               u.sms_notifications_enabled
        FROM medication_reminders mr
        JOIN medications m ON m.id = mr.medication_id
        JOIN users u ON u.id = mr.user_id
        WHERE mr.is_active = 1
        AND mr.reminder_time = :current_time
        AND FIND_IN_SET(:current_day, mr.days_of_week)
        AND (mr.last_sent_at IS NULL OR 
             DATE(mr.last_sent_at) < DATE(:now) OR
             (mr.last_sent_at < DATE_SUB(:now, INTERVAL 23 HOUR)))
    ";
    
    $stmt = $db->prepare($query);
    $stmt->bindValue(':current_time', $current_time);
    $stmt->bindValue(':current_day', $current_day);
    $stmt->bindValue(':now', $now->format('Y-m-d H:i:s'));
    $stmt->execute();
    
    $reminders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($reminders)) {
        exit("No reminders to send at " . $now->format('Y-m-d H:i:s') . "\n");
    }
    
    // Initialize SMS notifier
    $smsNotifier = new SMSNotifier('twilio', [
        'twilio_account_sid' => getenv('TWILIO_ACCOUNT_SID'),
        'twilio_auth_token' => getenv('TWILIO_AUTH_TOKEN'),
        'twilio_from_number' => getenv('TWILIO_FROM_NUMBER'),
        'test_mode' => getenv('SMS_TEST_MODE') === 'true',
        'test_recipient' => getenv('SMS_TEST_RECIPIENT')
    ]);
    
    $sent_count = 0;
    $error_count = 0;
    
    foreach ($reminders as $reminder) {
        try {
            // Skip if user doesn't have SMS notifications enabled or no phone number
            if (!$reminder['sms_notifications_enabled'] || empty($reminder['phone_number'])) {
                continue;
            }
            
            // Format medication data
            $medication = [
                'name' => $reminder['name'],
                'dosage' => $reminder['dosage'],
                'notes' => $reminder['notes']
            ];
            
            // Format user data
            $user = [
                'phone_number' => $reminder['phone_number'],
                'first_name' => $reminder['first_name'],
                'last_name' => $reminder['last_name'],
                'timezone' => $reminder['timezone']
            ];
            
            // Send the reminder
            $result = $smsNotifier->sendMedicationReminder($user, $medication, $reminder);
            
            if ($result['success']) {
                $sent_count++;
                
                // Update last_sent_at
                $updateStmt = $db->prepare("
                    UPDATE medication_reminders 
                    SET last_sent_at = NOW() 
                    WHERE id = :id
                ");
                $updateStmt->execute(['id' => $reminder['id']]);
                
                error_log(sprintf(
                    "Sent medication reminder for %s to %s",
                    $medication['name'],
                    $user['phone_number']
                ));
            } else {
                $error_count++;
                error_log(sprintf(
                    "Failed to send medication reminder: %s",
                    $result['message']
                ));
            }
            
            // Small delay to avoid hitting rate limits
            usleep(200000); // 200ms
            
        } catch (Exception $e) {
            $error_count++;
            error_log("Error processing reminder ID {$reminder['id']}: " . $e->getMessage());
        }
    }
    
    echo sprintf(
        "Sent %d reminders with %d errors at %s\n",
        $sent_count,
        $error_count,
        $now->format('Y-m-d H:i:s')
    );
    
} catch (Exception $e) {
    error_log("Critical error in send_medication_reminders: " . $e->getMessage());
    exit(1);
}

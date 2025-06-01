<?php
/**
 * Setup script for HealthAssist Pro Medication Reminders
 */

echo "=== HealthAssist Pro - Medication Reminders Setup ===\n\n";

// Check PHP version
$minPhpVersion = '7.4';
if (version_compare(PHP_VERSION, $minPhpVersion, '<')) {
    die("Error: PHP $minPhpVersion or higher is required. You are running " . PHP_VERSION . "\n");
}

// Check if Composer is installed
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    die("Error: Composer dependencies not installed. Run 'composer install' first.\n");
}

require_once __DIR__ . '/vendor/autoload.php';

// Check if .env file exists
if (!file_exists(__DIR__ . '/.env')) {
    if (copy(__DIR__ . '/.env.example', __DIR__ . '/.env')) {
        echo "Created .env file from .env.example\n";
    } else {
        die("Error: Could not create .env file. Please copy .env.example to .env manually.\n");
    }
}

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Check database connection
echo "Checking database connection... ";
try {
    require_once __DIR__ . '/config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    echo "OK\n";
} catch (Exception $e) {
    die("FAILED\nError: " . $e->getMessage() . "\n");
}

// Run database migrations
echo "Running database migrations...\n";
$migrationFile = __DIR__ . '/database/run_medication_migrations.php';
if (file_exists($migrationFile)) {
    try {
        require_once $migrationFile;
        echo "Migrations completed successfully.\n";
    } catch (Exception $e) {
        die("Error running migrations: " . $e->getMessage() . "\n");
    }
} else {
    die("Error: Migration file not found at $migrationFile\n");
}

// Check Twilio configuration
echo "\n=== Twilio Configuration Check ===\n";
$twilioSid = $_ENV['TWILIO_ACCOUNT_SID'] ?? '';
$twilioToken = $_ENV['TWILIO_AUTH_TOKEN'] ?? '';
$twilioFrom = $_ENV['TWILIO_FROM_NUMBER'] ?? '';

if (empty($twilioSid) || $twilioSid === 'your_account_sid_here') {
    echo "WARNING: TWILIO_ACCOUNT_SID is not set in .env\n";
}

if (empty($twilioToken) || $twilioToken === 'your_auth_token_here') {
    echo "WARNING: TWILIO_AUTH_TOKEN is not set in .env\n";
}

if (empty($twilioFrom) || $twilioFrom === '+1234567890') {
    echo "WARNING: TWILIO_FROM_NUMBER is not set in .env\n";
}

if (!empty($twilioSid) && !empty($twilioToken) && !empty($twilioFrom) && 
    $twilioSid !== 'your_account_sid_here' && 
    $twilioToken !== 'your_auth_token_here' &&
    $twilioFrom !== '+1234567890') {
    
    echo "Twilio configuration looks good.\n";
    
    // Test Twilio client
    if (!class_exists('Twilio\\Rest\\Client')) {
        echo "WARNING: Twilio SDK not found. Run 'composer require twilio/sdk'\n";
    } else {
        try {
            $client = new Twilio\Rest\Client($twilioSid, $twilioToken);
            $account = $client->api->v2010->accounts($twilioSid)->fetch();
            echo "Twilio connection successful. Account SID: " . $account->sid . "\n";
            echo "Account Status: " . $account->status . "\n";
        } catch (Exception $e) {
            echo "WARNING: Could not connect to Twilio: " . $e->getMessage() . "\n";
        }
    }
}

// Check cron job setup
echo "\n=== Cron Job Setup ===\n";
$cronCommand = "* * * * * php " . escapeshellarg(__DIR__ . "/cron/send_medication_reminders.php") . " >> " . 
               escapeshellarg(__DIR__ . "/logs/medication_reminders.log") . " 2>&1";

echo "Add the following line to your crontab (crontab -e):\n";
echo $cronCommand . "\n\n";

// Check if logs directory exists
$logsDir = __DIR__ . '/logs';
if (!file_exists($logsDir)) {
    if (mkdir($logsDir, 0755, true)) {
        echo "Created logs directory at: $logsDir\n";
    } else {
        echo "WARNING: Could not create logs directory at: $logsDir\n";
        echo "Make sure the web server has write permissions to the application directory.\n";
    }
}

// Check file permissions
$writableDirs = [
    $logsDir,
    __DIR__ . '/cache',
    __DIR__ . '/uploads'
];

foreach ($writableDirs as $dir) {
    if (file_exists($dir) && !is_writable($dir)) {
        echo "WARNING: Directory is not writable: $dir\n";
        echo "Run: chmod 755 $dir\n";
    }
}

echo "\n=== Setup Complete ===\n";
echo "1. Make sure to configure your .env file with the correct database and Twilio settings.\n";
echo "2. Set up the cron job as shown above.\n";
echo "3. Visit the Medications page in your browser to start setting up reminders.\n\n";

echo "For more information, see the documentation in docs/MEDICATION_REMINDERS.md\n";

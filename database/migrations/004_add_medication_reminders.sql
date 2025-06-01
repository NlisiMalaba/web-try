-- Add medication_reminders table
CREATE TABLE IF NOT EXISTS medication_reminders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    medication_id INT NOT NULL,
    user_id INT NOT NULL,
    reminder_time TIME NOT NULL,
    days_of_week VARCHAR(20) DEFAULT '1,2,3,4,5,6,7', -- Comma-separated list of days (1=Monday to 7=Sunday)
    is_active BOOLEAN DEFAULT TRUE,
    notify_via_sms BOOLEAN DEFAULT FALSE,
    notify_via_email BOOLEAN DEFAULT TRUE,
    timezone VARCHAR(50) DEFAULT 'UTC',
    last_sent_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (medication_id) REFERENCES medications(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_reminder (medication_id, reminder_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add phone number to users table if it doesn't exist
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS phone_number VARCHAR(20) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS sms_notifications_enabled BOOLEAN DEFAULT FALSE,
ADD COLUMN IF NOT EXISTS timezone VARCHAR(50) DEFAULT 'UTC';

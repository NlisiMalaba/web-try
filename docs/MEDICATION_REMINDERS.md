# Medication Reminders with SMS Notifications

This feature allows users to set up SMS reminders for their medications, helping them stay on track with their medication schedule.

## Features

- Set up multiple reminders per medication
- Choose specific days of the week for each reminder
- Enable/disable SMS notifications
- View and manage all reminders in one place
- Responsive design that works on all devices

## Prerequisites

1. PHP 7.4 or higher
2. MySQL 5.7 or higher
3. Web server (Apache/Nginx)
4. Twilio Account (for sending SMS)

## Setup Instructions

### 1. Database Setup

Run the database migration to create the necessary tables:

```bash
php database/run_medication_migrations.php
```

### 2. Twilio Configuration

1. Sign up for a [Twilio account](https://www.twilio.com/try-twilio) if you don't have one
2. Get your Account SID and Auth Token from the Twilio Console
3. Get a Twilio phone number that will be used to send SMS messages

### 3. Environment Configuration

Create a `.env` file in your project root with the following variables:

```env
# Twilio Settings
TWILIO_ACCOUNT_SID=your_account_sid
TWILIO_AUTH_TOKEN=your_auth_token
TWILIO_FROM_NUMBER=+1234567890  # Your Twilio phone number

# SMS Settings
SMS_TEST_MODE=true  # Set to false in production
SMS_TEST_RECIPIENT=+1234567890  # Your phone number for testing
```

### 4. Cron Job Setup

Set up a cron job to run the reminder script every minute:

```bash
* * * * * php /path/to/your/site/cron/send_medication_reminders.php >> /path/to/your/site/logs/medication_reminders.log 2>&1
```

## Usage

1. Log in to your account
2. Go to the Medications page
3. Add your medications if you haven't already
4. Click "Add Reminder" next to a medication
5. Set the time and days for the reminder
6. Make sure your phone number is set in the Notification Settings
7. Enable SMS notifications
8. Save your settings

## Testing

1. Set `SMS_TEST_MODE=true` in your `.env` file
2. Add a reminder with a time a few minutes in the future
3. The script will log the SMS that would be sent (without actually sending it)
4. Check the log file to verify the reminder was processed

## Troubleshooting

- **No reminders are being sent**:
  - Check if the cron job is running
  - Verify the database tables were created correctly
  - Check the PHP error log for any issues
  - Make sure the timezone is set correctly in the database and PHP configuration

- **SMS not being received**:
  - Verify your Twilio credentials are correct
  - Check if your Twilio account has sufficient balance
  - Make sure the phone number is in the correct format (including country code)
  - Check the Twilio console for any error messages

## Security Considerations

- Never commit your `.env` file to version control
- Use environment variables for sensitive information
- Validate all user input to prevent SQL injection
- Use HTTPS to protect sensitive data in transit
- Implement rate limiting to prevent abuse of the SMS sending functionality

## Dependencies

- PHP 7.4+
- MySQL 5.7+
- Twilio PHP SDK (automatically installed via Composer)

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

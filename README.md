# HealthAssist Pro

A comprehensive health management system that helps users track their medications, health metrics, and receive timely reminders.

## Features

- **Medication Management**: Add, edit, and track medications
- **SMS Reminders**: Get notified when it's time to take your medication
- **Health Metrics**: Track vital health statistics over time
- **Wearable Integration**: Sync with wearable devices for automatic health tracking
- **Responsive Design**: Works on desktop and mobile devices

## Getting Started

### Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Composer (for dependency management)
- Web server (Apache/Nginx)
- Twilio Account (for SMS functionality)

### Installation

1. Clone the repository:
   ```bash
   git clone [repository-url]
   cd HealthAssistPro
   ```

2. Install PHP dependencies:
   ```bash
   composer install
   ```

3. Copy the example environment file and configure it:
   ```bash
   cp .env.example .env
   # Edit .env with your database and Twilio credentials
   ```

4. Run the setup script:
   ```bash
   php setup.php
   ```

5. Set up the cron job for medication reminders:
   ```bash
   crontab -e
   # Add the following line (update paths as needed):
   * * * * * php /path/to/HealthAssistPro/cron/send_medication_reminders.php >> /path/to/HealthAssistPro/logs/medication_reminders.log 2>&1
   ```

6. Make sure the web server has write permissions to the following directories:
   - `/logs`
   - `/cache`
   - `/uploads`

## Documentation

- [Medication Reminders](docs/MEDICATION_REMINDERS.md) - Guide to setting up and using SMS medication reminders
- [API Documentation](docs/API.md) - API endpoints and usage
- [Developer Guide](docs/DEVELOPER.md) - Contributing to the project

## Testing

To test the SMS functionality:

```bash
php test_sms.php
```

## Security

- Always keep your `.env` file secure and never commit it to version control
- Use strong passwords for database and API credentials
- Keep the application and its dependencies up to date
- Enable HTTPS in production

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Support

For support, please open an issue in the GitHub issue tracker or contact the development team.

---

*HealthAssist Pro - Your personal health companion*

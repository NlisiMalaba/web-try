<?php
class SMSNotifier {
    private $provider;
    private $config;
    
    public function __construct($provider = 'twilio', $config = []) {
        $this->provider = $provider;
        $this->config = array_merge([
            'twilio_account_sid' => '',
            'twilio_auth_token' => '',
            'twilio_from_number' => '',
            'test_mode' => true,
            'test_recipient' => ''
        ], $config);
    }
    
    /**
     * Send an SMS message
     * 
     * @param string $to Phone number to send to
     * @param string $message Message content
     * @return array ['success' => bool, 'message' => string, 'provider_response' => mixed]
     */
    public function sendSMS($to, $message) {
        if ($this->config['test_mode']) {
            $to = $this->config['test_recipient'] ?: $to;
            error_log("[SMS TEST MODE] Would send to $to: $message");
            return [
                'success' => true,
                'message' => 'Test mode - message not actually sent',
                'provider_response' => 'test_mode'
            ];
        }
        
        try {
            switch ($this->provider) {
                case 'twilio':
                    return $this->sendViaTwilio($to, $message);
                // Add more providers here as needed
                default:
                    throw new Exception("Unsupported SMS provider: " . $this->provider);
            }
        } catch (Exception $e) {
            error_log("SMS Sending Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'provider_response' => null
            ];
        }
    }
    
    /**
     * Send SMS via Twilio
     */
    private function sendViaTwilio($to, $message) {
        if (empty($this->config['twilio_account_sid']) || 
            empty($this->config['twilio_auth_token']) || 
            empty($this->config['twilio_from_number'])) {
            throw new Exception("Twilio credentials not configured");
        }
        
        // Format phone number (remove any non-numeric characters)
        $to = preg_replace('/[^0-9+]/', '', $to);
        
        // Initialize Twilio client
        $client = new \Twilio\Rest\Client(
            $this->config['twilio_account_sid'],
            $this->config['twilio_auth_token']
        );
        
        $message = $client->messages->create(
            $to,
            [
                'from' => $this->config['twilio_from_number'],
                'body' => $message
            ]
        );
        
        return [
            'success' => true,
            'message' => 'Message sent successfully',
            'provider_response' => $message->sid
        ];
    }
    
    /**
     * Send medication reminder
     */
    public function sendMedicationReminder($user, $medication, $reminder) {
        $message = sprintf(
            "Reminder: It's time to take %s (%s). %s",
            htmlspecialchars($medication['name']),
            htmlspecialchars($medication['dosage']),
            !empty($medication['notes']) ? "Note: " . htmlspecialchars($medication['notes']) : ""
        );
        
        return $this->sendSMS($user['phone_number'], $message);
    }
}

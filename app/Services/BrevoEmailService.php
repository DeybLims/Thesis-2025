<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Brevo\Client\Api\TransactionalEmailsApi;
use Brevo\Client\Configuration;
use Brevo\Client\Model\SendSmtpEmail;
use Brevo\Client\Model\SendSmtpEmailTo;
use GuzzleHttp\Client;

class BrevoEmailService
{
    private $apiKey;
    private $apiInstance;

    public function __construct()
    {
        $this->apiKey = env('BREVO_API_KEY');
        
        if ($this->apiKey) {
            $config = Configuration::getDefaultConfiguration()->setApiKey('api-key', $this->apiKey);
            $this->apiInstance = new TransactionalEmailsApi(new Client(), $config);
        }
    }

    /**
     * Send email using Brevo API
     *
     * @param string $to Recipient email address
     * @param string $subject Email subject
     * @param string $htmlContent HTML content of the email
     * @param string|null $fromEmail Sender email (defaults to MAIL_FROM_ADDRESS)
     * @param string|null $fromName Sender name (defaults to MAIL_FROM_NAME)
     * @return array Response with success status and message
     */
    public function sendEmail($to, $subject, $htmlContent, $fromEmail = null, $fromName = null)
    {
        // Skip if no API key configured
        if (empty($this->apiKey)) {
            Log::warning('Brevo email not sent: BREVO_API_KEY not configured');
            return [
                'success' => false,
                'message' => 'Brevo email service not configured'
            ];
        }

        if (!$this->apiInstance) {
            Log::error('Brevo API instance not initialized');
            return [
                'success' => false,
                'message' => 'Brevo API instance not initialized'
            ];
        }

        try {
            $sendSmtpEmail = new SendSmtpEmail([
                'to' => [new SendSmtpEmailTo(['email' => $to])],
                'subject' => $subject,
                'htmlContent' => $htmlContent,
                'sender' => [
                    'email' => $fromEmail ?? env('MAIL_FROM_ADDRESS', 'noreply@pinpoint.app'),
                    'name' => $fromName ?? env('MAIL_FROM_NAME', 'PinPoint Attendance')
                ]
            ]);

            $result = $this->apiInstance->sendTransacEmail($sendSmtpEmail);

            Log::info('✅ Email sent successfully via Brevo API', [
                'to' => $to,
                'message_id' => $result->getMessageId()
            ]);

            return [
                'success' => true,
                'message' => 'Email sent successfully',
                'message_id' => $result->getMessageId()
            ];
        } catch (\Exception $e) {
            Log::error('❌ Failed to send email via Brevo API', [
                'to' => $to,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to send email: ' . $e->getMessage()
            ];
        }
    }
}


<?php

namespace App\Services;

use App\Models\UserModel;
use CodeIgniter\Email\Email;
use Config\Email as EmailConfig;

/**
 * EmailService
 * 
 * Service responsible for sending HTML emails.
 * Uses CodeIgniter Email library and view templates.
 */
class EmailService
{
    protected EmailConfig $config;
    protected UserModel $userModel;

    public function __construct()
    {
        $this->config = config('Email');
        $this->userModel = new UserModel();
    }

    /**
     * Send notification email to a user
     * 
     * @param int $userId User ID
     * @param array $notificationData Notification data (type, title, message, link)
     * @return bool Success status
     */
    public function sendNotificationEmail(int $userId, array $notificationData): bool
    {
        // Get user email
        $user = $this->userModel->find($userId);
        if (!$user || empty($user['email'])) {
            log_message('error', "EmailService: User #{$userId} not found or has no email");
            return false;
        }

        // Render email template
        $emailBody = $this->renderEmailTemplate('notification', [
            'user' => $user,
            'notification' => $notificationData,
        ]);

        // Get email configuration
        $fromEmail = env('email.fromEmail', $this->config->fromEmail) ?: 'noreply@supercom.ro';
        $fromName = env('email.fromName', $this->config->fromName) ?: 'SuperCom ATLAS';

        // Send email
        try {
            $email = \Config\Services::email();

            // Configure email
            if (!empty($this->config->SMTPHost)) {
                $email->initialize([
                    'protocol' => 'smtp',
                    'SMTPHost' => $this->config->SMTPHost,
                    'SMTPUser' => $this->config->SMTPUser,
                    'SMTPPass' => $this->config->SMTPPass,
                    'SMTPPort' => $this->config->SMTPPort,
                    'SMTPCrypto' => $this->config->SMTPCrypto,
                    'mailType' => 'html',
                    'charset' => 'UTF-8',
                ]);
            } else {
                $email->initialize([
                    'protocol' => $this->config->protocol,
                    'mailType' => 'html',
                    'charset' => 'UTF-8',
                ]);
            }

            $email->setFrom($fromEmail, $fromName);
            $email->setTo($user['email']);
            $email->setSubject($notificationData['title']);
            $email->setMessage($emailBody);

            if ($email->send()) {
                log_message('info', "EmailService: Notification email sent to user #{$userId} ({$user['email']})");
                return true;
            } else {
                log_message('error', "EmailService: Failed to send email to user #{$userId}. Error: " . $email->printDebugger(['headers']));
                return false;
            }
        } catch (\Exception $e) {
            log_message('error', "EmailService: Exception while sending email to user #{$userId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send welcome email to new user with login credentials
     * 
     * @param int $userId User ID
     * @param string $temporaryPassword Generated temporary password
     * @param string $setPasswordToken Token for password setup
     * @return bool Success status
     */
    public function sendWelcomeEmail(int $userId, string $temporaryPassword, string $setPasswordToken): bool
    {
        // Get user data
        $user = $this->userModel->find($userId);

        if (!$user || empty($user['email'])) {
            log_message('error', "EmailService: User #{$userId} not found or has no email for welcome email");
            return false;
        }

        // Render email template
        $emailBody = $this->renderEmailTemplate('welcome', [
            'user' => $user,
            'temporary_password' => $temporaryPassword,
            'set_password_url' => site_url('auth/set-password?token=' . $setPasswordToken),
        ]);

        // Get email configuration
        $fromEmail = env('email.fromEmail', $this->config->fromEmail) ?: 'noreply@supercom.ro';
        $fromName = env('email.fromName', $this->config->fromName) ?: 'SuperCom ATLAS';

        // Send email
        try {
            $email = \Config\Services::email();

            // Configure email
            if (!empty($this->config->SMTPHost)) {
                $email->initialize([
                    'protocol' => 'smtp',
                    'SMTPHost' => $this->config->SMTPHost,
                    'SMTPUser' => $this->config->SMTPUser,
                    'SMTPPass' => $this->config->SMTPPass,
                    'SMTPPort' => $this->config->SMTPPort,
                    'SMTPCrypto' => $this->config->SMTPCrypto,
                    'mailType' => 'html',
                    'charset' => 'UTF-8',
                ]);
            } else {
                $email->initialize([
                    'protocol' => $this->config->protocol,
                    'mailType' => 'html',
                    'charset' => 'UTF-8',
                ]);
            }

            $email->setFrom($fromEmail, $fromName);
            $email->setTo($user['email']);
            $email->setSubject('Bun venit în ATLAS. - Datele tale de logare');
            $email->setMessage($emailBody);

            if ($email->send()) {
                log_message('info', "EmailService: Welcome email sent to user #{$userId} ({$user['email']})");
                return true;
            } else {
                log_message('error', "EmailService: Failed to send welcome email to user #{$userId}. Error: " . $email->printDebugger(['headers']));
                return false;
            }
        } catch (\Exception $e) {
            log_message('error', "EmailService: Exception while sending welcome email to user #{$userId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Render email template
     * 
     * @param string $template Template name (without .php extension)
     * @param array $data Data to pass to template
     * @return string Rendered HTML
     */
    protected function renderEmailTemplate(string $template, array $data): string
    {
        $view = service('renderer');
        return $view->setData($data)->render('emails/' . $template);
    }
}

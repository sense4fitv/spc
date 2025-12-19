<?php

namespace App\Services;

use App\Models\NotificationModel;

/**
 * NotificationService
 * 
 * Orchestrator service for notifications.
 * Handles hybrid system: Real-time (Pusher) + Email fallback.
 */
class NotificationService
{
    protected NotificationModel $notificationModel;
    protected PusherService $pusherService;
    protected EmailService $emailService;

    public function __construct()
    {
        $this->notificationModel = new NotificationModel();
        $this->pusherService = new PusherService();
        $this->emailService = new EmailService();
    }

    /**
     * Send notification to a single user
     * Hybrid system: Tries Pusher first, falls back to Email if offline
     * 
     * @param int $userId User ID
     * @param string $type Notification type (info, success, warning, error)
     * @param string $title Notification title
     * @param string $message Notification message
     * @param string|null $link Optional link (relative URL)
     * @return bool Success status
     */
    public function send(
        int $userId,
        string $type,
        string $title,
        string $message,
        ?string $link = null
    ): bool {
        // Always create notification in database first
        $notificationId = $this->notificationModel->createNotification(
            $userId,
            $type,
            $title,
            $message,
            $link
        );

        if (!$notificationId) {
            log_message('error', "NotificationService: Failed to create notification in DB for user #{$userId}");
            return false;
        }

        // Prepare notification data
        $notificationData = [
            'id' => $notificationId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'link' => $link ? site_url($link) : null,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        // Try real-time delivery via Pusher
        $userOnline = $this->pusherService->isUserOnline($userId);
        log_message('info', "NotificationService: Checking if user #{$userId} is online. Result: " . ($userOnline ? 'YES' : 'NO'));

        if ($userOnline) {
            // User is online - send via Pusher
            $pusherData = [
                'notification' => $notificationData,
            ];

            log_message('info', "NotificationService: Attempting to send notification via Pusher to user #{$userId}. Channel: private-user-{$userId}, Event: new-notification");
            log_message('debug', "NotificationService: Pusher data: " . json_encode($pusherData));

            $pusherSuccess = $this->pusherService->triggerUser(
                $userId,
                'new-notification',
                $pusherData
            );

            if ($pusherSuccess) {
                log_message('info', "NotificationService: ✅ Real-time notification sent successfully to user #{$userId} via Pusher");
                return true;
            } else {
                log_message('warning', "NotificationService: ❌ Pusher trigger failed for user #{$userId}, falling back to email");
                // Fall through to email fallback
            }
        } else {
            log_message('info', "NotificationService: User #{$userId} is offline, sending email instead");
        }

        // User is offline or Pusher failed - send email
        log_message('info', "NotificationService: Sending email notification to user #{$userId} (offline or Pusher failed)");
        
        $emailSuccess = $this->emailService->sendNotificationEmail($userId, [
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'link' => $link ? site_url($link) : null,
        ]);

        if (!$emailSuccess) {
            log_message('error', "NotificationService: Email fallback also failed for user #{$userId}");
        }

        // Return true because notification was saved in DB
        return true;
    }

    /**
     * Send notification to multiple users
     * 
     * @param array $userIds Array of user IDs
     * @param string $type Notification type
     * @param string $title Notification title
     * @param string $message Notification message
     * @param string|null $link Optional link
     * @return array Results array with userId => success status
     */
    public function sendToMultiple(
        array $userIds,
        string $type,
        string $title,
        string $message,
        ?string $link = null
    ): array {
        $results = [];

        foreach ($userIds as $userId) {
            $results[$userId] = $this->send($userId, $type, $title, $message, $link);
        }

        return $results;
    }

    /**
     * Send global notification (real-time only, not stored in DB per user)
     * 
     * @param string $type Notification type
     * @param string $title Notification title
     * @param string $message Notification message
     * @param string|null $link Optional link
     * @return bool Success status
     */
    public function sendGlobal(
        string $type,
        string $title,
        string $message,
        ?string $link = null
    ): bool {
        $notificationData = [
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'link' => $link ? site_url($link) : null,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        $pusherData = [
            'notification' => $notificationData,
        ];

        $success = $this->pusherService->triggerGlobal('global-notification', $pusherData);

        if ($success) {
            log_message('info', "NotificationService: Global notification sent via Pusher");
        } else {
            log_message('error', "NotificationService: Failed to send global notification via Pusher");
        }

        return $success;
    }

    /**
     * Send notification with explicit preference (force Pusher or Email)
     * Useful for testing or special cases
     * 
     * @param int $userId User ID
     * @param string $type Notification type
     * @param string $title Notification title
     * @param string $message Notification message
     * @param string|null $link Optional link
     * @param string $method 'pusher', 'email', or 'auto' (default)
     * @return bool Success status
     */
    public function sendWithMethod(
        int $userId,
        string $type,
        string $title,
        string $message,
        ?string $link = null,
        string $method = 'auto'
    ): bool {
        // Always create in DB first
        $notificationId = $this->notificationModel->createNotification(
            $userId,
            $type,
            $title,
            $message,
            $link
        );

        if (!$notificationId) {
            return false;
        }

        $notificationData = [
            'id' => $notificationId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'link' => $link ? site_url($link) : null,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        if ($method === 'pusher' || ($method === 'auto' && $this->pusherService->isUserOnline($userId))) {
            return $this->pusherService->triggerUser($userId, 'new-notification', ['notification' => $notificationData]);
        } else {
            return $this->emailService->sendNotificationEmail($userId, [
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'link' => $link ? site_url($link) : null,
            ]);
        }
    }
}


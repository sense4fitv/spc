<?php

namespace App\Services;

use Config\Pusher as PusherConfig;
use Pusher\Pusher;
use Pusher\PusherException;

/**
 * PusherService
 * 
 * Service responsible for managing Pusher real-time communication.
 * Handles channel subscriptions, event triggering, and online user checking.
 */
class PusherService
{
    protected PusherConfig $config;
    protected ?Pusher $pusher = null;

    public function __construct()
    {
        $this->config = config('Pusher');
    }

    /**
     * Initialize Pusher client
     * 
     * @return Pusher
     * @throws PusherException
     */
    protected function getPusher(): Pusher
    {
        if ($this->pusher !== null) {
            return $this->pusher;
        }

        if (!$this->config->isConfigured()) {
            throw new PusherException('Pusher is not properly configured. Please check your environment variables.');
        }

        $this->pusher = new Pusher(
            $this->config->key,
            $this->config->secret,
            $this->config->appId,
            [
                'cluster' => $this->config->cluster,
                'useTLS' => $this->config->useTLS,
            ]
        );

        return $this->pusher;
    }

    /**
     * Trigger an event on a channel
     * 
     * @param string $channel Channel name (e.g., 'user-123' or 'global')
     * @param string $event Event name (e.g., 'new-notification')
     * @param array $data Event data
     * @return bool Success status
     */
    public function trigger(string $channel, string $event, array $data): bool
    {
        try {
            $pusher = $this->getPusher();
            
            // For private channels, prefix with 'private-'
            if (strpos($channel, 'private-') === 0) {
                // Already prefixed
            } elseif (strpos($channel, 'user-') === 0) {
                $channel = 'private-' . $channel;
            }

            $pusher->trigger($channel, $event, $data);
            return true;
        } catch (PusherException $e) {
            log_message('error', 'Pusher trigger failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Trigger an event on a global channel
     * 
     * @param string $event Event name
     * @param array $data Event data
     * @return bool
     */
    public function triggerGlobal(string $event, array $data): bool
    {
        return $this->trigger('global', $event, $data);
    }

    /**
     * Trigger an event on a user's private channel
     * 
     * @param int $userId User ID
     * @param string $event Event name
     * @param array $data Event data
     * @return bool
     */
    public function triggerUser(int $userId, string $event, array $data): bool
    {
        return $this->trigger('user-' . $userId, $event, $data);
    }

    /**
     * Check if a user is currently online (subscribed to their private channel)
     * Uses Pusher REST API to check channel presence
     * 
     * @param int $userId User ID
     * @return bool True if user is online
     */
    public function isUserOnline(int $userId): bool
    {
        try {
            $channelName = 'private-user-' . $userId;
            $pusher = $this->getPusher();
            
            // Get channel info using REST API
            $response = $pusher->get('/channels/' . $channelName, [
                'info' => 'subscription_count'
            ]);

            // Convert response to array if it's an object
            if (is_object($response)) {
                $response = json_decode(json_encode($response), true);
            }

            if (isset($response['subscription_count']) && $response['subscription_count'] > 0) {
                return true;
            }

            return false;
        } catch (PusherException $e) {
            log_message('error', 'Pusher online check failed: ' . $e->getMessage());
            // If we can't check, assume offline (safer for email fallback)
            return false;
        }
    }

    /**
     * Get channel information
     * 
     * @param string $channel Channel name
     * @param array $info Info parameters (e.g., ['subscription_count', 'user_count'])
     * @return array|null Channel info or null on error
     */
    public function getChannelInfo(string $channel, array $info = ['subscription_count']): ?array
    {
        try {
            $pusher = $this->getPusher();
            
            // For private channels, ensure prefix
            if (strpos($channel, 'private-') === false && strpos($channel, 'user-') === 0) {
                $channel = 'private-' . $channel;
            }

            $params = [
                'info' => implode(',', $info)
            ];

            $response = $pusher->get('/channels/' . $channel, $params);
            
            // Convert response to array if it's an object
            if (is_object($response)) {
                $response = json_decode(json_encode($response), true);
            }

            return $response;
        } catch (PusherException $e) {
            log_message('error', 'Pusher channel info failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get Pusher configuration for frontend
     * Returns public config needed for client-side connection
     * 
     * @return array
     */
    public function getFrontendConfig(): array
    {
        return [
            'key' => $this->config->key,
            'cluster' => $this->config->cluster,
            'useTLS' => $this->config->useTLS,
        ];
    }

    /**
     * Get Pusher instance for authentication
     * Used by NotificationController for private channel authentication
     * 
     * @return Pusher
     * @throws PusherException
     */
    public function getPusherInstance(): Pusher
    {
        return $this->getPusher();
    }
}


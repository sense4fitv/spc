<?php

namespace App\Controllers;

use App\Models\NotificationModel;
use CodeIgniter\HTTP\ResponseInterface;

class NotificationController extends BaseController
{
    protected NotificationModel $notificationModel;

    public function __construct()
    {
        $this->notificationModel = new NotificationModel();
    }

    /**
     * Get all notifications for current user
     * GET /api/notifications
     */
    public function index(): ResponseInterface
    {
        $userId = session()->get('user_id');
        
        if (!$userId) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Not authenticated',
            ])->setStatusCode(401);
        }

        $limit = (int)($this->request->getGet('limit') ?? 50);
        $notifications = $this->notificationModel->getUserNotifications($userId, $limit);

        return $this->response->setJSON([
            'success' => true,
            'data' => $notifications,
        ]);
    }

    /**
     * Get unread notifications for current user
     * GET /api/notifications/unread
     */
    public function unread(): ResponseInterface
    {
        $userId = session()->get('user_id');
        
        if (!$userId) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Not authenticated',
            ])->setStatusCode(401);
        }

        $notifications = $this->notificationModel->getUnread($userId);
        $count = count($notifications);

        return $this->response->setJSON([
            'success' => true,
            'data' => $notifications,
            'count' => $count,
        ]);
    }

    /**
     * Get unread count only
     * GET /api/notifications/unread-count
     */
    public function unreadCount(): ResponseInterface
    {
        $userId = session()->get('user_id');
        
        if (!$userId) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Not authenticated',
            ])->setStatusCode(401);
        }

        $count = $this->notificationModel->countUnread($userId);

        return $this->response->setJSON([
            'success' => true,
            'count' => $count,
        ]);
    }

    /**
     * Mark notification as read
     * POST /api/notifications/{id}/read
     */
    public function markAsRead(int $id): ResponseInterface
    {
        $userId = session()->get('user_id');
        
        if (!$userId) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Not authenticated',
            ])->setStatusCode(401);
        }

        // Verify notification belongs to user
        $notification = $this->notificationModel->find($id);
        
        if (!$notification || $notification['user_id'] != $userId) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Notification not found',
            ])->setStatusCode(404);
        }

        $success = $this->notificationModel->markAsRead($id);

        return $this->response->setJSON([
            'success' => $success,
            'message' => $success ? 'Notification marked as read' : 'Failed to mark as read',
        ]);
    }

    /**
     * Mark all notifications as read for current user
     * POST /api/notifications/read-all
     */
    public function markAllAsRead(): ResponseInterface
    {
        $userId = session()->get('user_id');
        
        if (!$userId) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Not authenticated',
            ])->setStatusCode(401);
        }

        $success = $this->notificationModel->markAllAsRead($userId);

        return $this->response->setJSON([
            'success' => $success,
            'message' => $success ? 'All notifications marked as read' : 'Failed to mark as read',
        ]);
    }

    /**
     * Get Pusher configuration for frontend
     * GET /api/pusher/config
     */
    public function pusherConfig(): ResponseInterface
    {
        $userId = session()->get('user_id');
        
        if (!$userId) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Not authenticated',
            ])->setStatusCode(401);
        }

        $pusherService = new \App\Services\PusherService();
        $config = $pusherService->getFrontendConfig();

        return $this->response->setJSON([
            'success' => true,
            'data' => $config,
            'userId' => $userId, // Needed for private channel subscription
        ]);
    }

    /**
     * Authenticate Pusher private channel subscription
     * POST /api/pusher/auth
     */
    public function pusherAuth(): ResponseInterface
    {
        $userId = session()->get('user_id');
        
        if (!$userId) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Not authenticated',
            ])->setStatusCode(401);
        }

        $socketId = $this->request->getPost('socket_id');
        $channelName = $this->request->getPost('channel_name');

        if (!$socketId || !$channelName) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Missing socket_id or channel_name',
            ])->setStatusCode(400);
        }

        // Verify user can subscribe to this channel
        // Private channel format: private-user-{userId}
        if (strpos($channelName, 'private-user-') === 0) {
            $channelUserId = str_replace('private-user-', '', $channelName);
            
            if ($channelUserId != $userId) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Unauthorized channel access',
                ])->setStatusCode(403);
            }
        }

        // Authenticate with Pusher
        try {
            $pusherService = new \App\Services\PusherService();
            $pusher = $pusherService->getPusherInstance();
            
            $auth = $pusher->socket_auth($channelName, $socketId);
            
            return $this->response->setJSON(json_decode($auth, true));
        } catch (\Exception $e) {
            log_message('error', 'Pusher auth failed: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Authentication failed',
            ])->setStatusCode(500);
        }
    }
}


<?php

namespace App\Models;

use CodeIgniter\Model;

class NotificationModel extends Model
{
    protected $table            = 'notifications';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'user_id',
        'type',
        'title',
        'message',
        'link',
        'is_read',
    ];

    // Dates
    protected $useTimestamps = false; // Disabled because created_at has DEFAULT CURRENT_TIMESTAMP in migration
    protected $dateFormat    = 'datetime';
    protected $createdField  = null; // Null because database handles it with DEFAULT
    protected $updatedField  = null;
    protected $deletedField  = null;

    // Validation
    protected $validationRules      = [];
    protected $validationMessages   = [];
    protected $skipValidation       = true; // Skip validation since we don't have rules defined
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];

    /**
     * Get unread notifications for a user
     */
    public function getUnread(int $userId)
    {
        return $this->where('user_id', $userId)
            ->where('is_read', 0)
            ->orderBy('created_at', 'DESC')
            ->findAll();
    }

    /**
     * Get read notifications for a user
     */
    public function getRead(int $userId, int $limit = 20)
    {
        return $this->where('user_id', $userId)
            ->where('is_read', 1)
            ->orderBy('created_at', 'DESC')
            ->findAll($limit);
    }

    /**
     * Get all notifications for a user
     */
    public function getUserNotifications(int $userId, int $limit = 50)
    {
        return $this->where('user_id', $userId)
            ->orderBy('created_at', 'DESC')
            ->findAll($limit);
    }

    /**
     * Count unread notifications for a user
     */
    public function countUnread(int $userId): int
    {
        return $this->where('user_id', $userId)
            ->where('is_read', 0)
            ->countAllResults();
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(int $notificationId): bool
    {
        return $this->update($notificationId, ['is_read' => 1]);
    }

    /**
     * Mark all notifications as read for a user
     */
    public function markAllAsRead(int $userId): bool
    {
        return $this->where('user_id', $userId)
            ->where('is_read', 0)
            ->set('is_read', 1)
            ->update();
    }

    /**
     * Create notification
     * 
     * @return int|false Notification ID on success, false on failure
     */
    public function createNotification(int $userId, string $type, string $title, string $message, ?string $link = null)
    {
        // Use Query Builder directly to have full control
        // created_at will use DEFAULT CURRENT_TIMESTAMP from database
        $db = \Config\Database::connect();
        
        $data = [
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'link' => $link,
            'is_read' => 0,
            // created_at is not included - database will use DEFAULT CURRENT_TIMESTAMP
        ];

        $builder = $db->table($this->table);
        $builder->insert($data);

        $insertId = $db->insertID();
        
        return $insertId ? (int)$insertId : false;
    }
}


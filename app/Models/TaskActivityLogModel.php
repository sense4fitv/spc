<?php

namespace App\Models;

use CodeIgniter\Model;

class TaskActivityLogModel extends Model
{
    protected $table            = 'task_activity_logs';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'task_id',
        'user_id',
        'action_type',
        'old_value',
        'new_value',
        'description',
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
    protected $skipValidation       = false;
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
     * Log task activity
     */
    public function logActivity(int $taskId, ?int $userId, string $actionType, ?string $oldValue = null, ?string $newValue = null, ?string $description = null): bool
    {
        // Use Query Builder directly to have full control
        // created_at will use DEFAULT CURRENT_TIMESTAMP from database
        $db = \Config\Database::connect();
        
        $data = [
            'task_id' => $taskId,
            'user_id' => $userId,
            'action_type' => $actionType,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'description' => $description,
            // created_at is not included - database will use DEFAULT CURRENT_TIMESTAMP
        ];
        
        // Only include non-null optional fields
        if ($oldValue === null) {
            unset($data['old_value']);
        }
        if ($newValue === null) {
            unset($data['new_value']);
        }
        if ($description === null) {
            unset($data['description']);
        }
        
        // user_id can be null (nullable field)
        // task_id and action_type are required, so they're always included
        
        $builder = $db->table($this->table);
        $builder->insert($data);
        
        return $db->insertID() !== false;
    }

    /**
     * Get logs with user information
     */
    public function findWithUser(int $taskId)
    {
        $db = \Config\Database::connect();
        return $db->table('task_activity_logs')
            ->select('task_activity_logs.*, users.first_name, users.last_name, users.email')
            ->join('users', 'users.id = task_activity_logs.user_id', 'left')
            ->where('task_activity_logs.task_id', $taskId)
            ->orderBy('task_activity_logs.created_at', 'DESC')
            ->get()
            ->getResultArray();
    }
}


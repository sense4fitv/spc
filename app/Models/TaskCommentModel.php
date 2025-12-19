<?php

namespace App\Models;

use CodeIgniter\Model;

class TaskCommentModel extends Model
{
    protected $table            = 'task_comments';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'task_id',
        'user_id',
        'comment',
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
     * Get comment author
     */
    public function getAuthor(int $commentId)
    {
        $comment = $this->find($commentId);
        if (!$comment) {
            return null;
        }
        
        $userModel = new UserModel();
        return $userModel->find($comment['user_id']);
    }

    /**
     * Get comments with author information
     */
    public function findWithAuthors(int $taskId)
    {
        $db = \Config\Database::connect();
        return $db->table('task_comments')
            ->select('task_comments.*, users.first_name, users.last_name, users.email, users.avatar_url')
            ->join('users', 'users.id = task_comments.user_id')
            ->where('task_comments.task_id', $taskId)
            ->orderBy('task_comments.created_at', 'ASC')
            ->get()
            ->getResultArray();
    }
}


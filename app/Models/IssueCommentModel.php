<?php

namespace App\Models;

use CodeIgniter\Model;

class IssueCommentModel extends Model
{
    protected $table            = 'issue_comments';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'issue_id',
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
    public function findWithAuthors(int $issueId)
    {
        $db = \Config\Database::connect();
        return $db->table('issue_comments')
            ->select('issue_comments.*, users.first_name, users.last_name, users.email, users.avatar_url')
            ->join('users', 'users.id = issue_comments.user_id')
            ->where('issue_comments.issue_id', $issueId)
            ->orderBy('issue_comments.created_at', 'ASC')
            ->get()
            ->getResultArray();
    }
}


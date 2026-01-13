<?php

namespace App\Models;

use CodeIgniter\Model;

class IssueFileModel extends Model
{
    protected $table            = 'issue_files';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'issue_id',
        'uploaded_by',
        'filename',
        'filepath',
        'file_type',
        'file_size',
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
     * Get file uploader
     */
    public function getUploader(int $fileId)
    {
        $file = $this->find($fileId);
        if (!$file) {
            return null;
        }
        
        $userModel = new UserModel();
        return $userModel->find($file['uploaded_by']);
    }

    /**
     * Get files with uploader information
     */
    public function findWithUploader(int $issueId)
    {
        $db = \Config\Database::connect();
        return $db->table('issue_files')
            ->select('issue_files.*, users.first_name, users.last_name, users.email')
            ->join('users', 'users.id = issue_files.uploaded_by')
            ->where('issue_files.issue_id', $issueId)
            ->orderBy('issue_files.created_at', 'ASC')
            ->get()
            ->getResultArray();
    }
}


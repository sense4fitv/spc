<?php

namespace App\Models;

use CodeIgniter\Model;

class UserLoginModel extends Model
{
    protected $table            = 'user_logins';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'user_id',
        'ip_address',
        'user_agent',
    ];

    // Dates
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'login_time';
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
     * Log user login
     */
    public function logLogin(int $userId, string $ipAddress, string $userAgent): bool
    {
        return $this->insert([
            'user_id' => $userId,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);
    }

    /**
     * Get user's login history
     */
    public function getUserLogins(int $userId, int $limit = 10)
    {
        return $this->where('user_id', $userId)
            ->orderBy('login_time', 'DESC')
            ->findAll($limit);
    }

    /**
     * Get last login for a user
     */
    public function getLastLogin(int $userId)
    {
        return $this->where('user_id', $userId)
            ->orderBy('login_time', 'DESC')
            ->first();
    }
}


<?php

namespace App\Models;

use CodeIgniter\Model;

class RegionModel extends Model
{
    protected $table            = 'regions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'name',
        'description',
        'manager_id',
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
     * Get region manager
     */
    public function getManager(int $regionId)
    {
        $region = $this->find($regionId);
        if (!$region || !$region['manager_id']) {
            return null;
        }
        
        $userModel = new UserModel();
        return $userModel->find($region['manager_id']);
    }

    /**
     * Get all contracts for a region
     */
    public function getContracts(int $regionId)
    {
        $contractModel = new ContractModel();
        return $contractModel->where('region_id', $regionId)->findAll();
    }

    /**
     * Get all users in a region
     */
    public function getUsers(int $regionId)
    {
        $userModel = new UserModel();
        return $userModel->where('region_id', $regionId)->findAll();
    }

    /**
     * Check if region has dependencies (users or contracts)
     * Returns array with counts for checking before deletion
     * 
     * @param int $regionId Region ID
     * @return array ['has_dependencies' => bool, 'users_count' => int, 'contracts_count' => int]
     */
    public function hasDependencies(int $regionId): array
    {
        $usersCount = $this->getUsers($regionId);
        $contracts = $this->getContracts($regionId);

        $usersCount = count($usersCount);
        $contractsCount = count($contracts);

        return [
            'has_dependencies' => $usersCount > 0 || $contractsCount > 0,
            'users_count' => $usersCount,
            'contracts_count' => $contractsCount,
        ];
    }

    /**
     * Get all regions with manager details
     * 
     * @return array Regions with manager information
     */
    public function getAllRegionsWithDetails(): array
    {
        $db = \Config\Database::connect();

        $regions = $db->table('regions r')
            ->select('r.*, u.first_name as manager_first_name, u.last_name as manager_last_name, u.email as manager_email')
            ->join('users u', 'u.id = r.manager_id', 'left')
            ->orderBy('r.name', 'ASC')
            ->get()
            ->getResultArray();

        // Get counts for each region
        foreach ($regions as &$region) {
            $users = $this->getUsers($region['id']);
            $contracts = $this->getContracts($region['id']);
            
            $region['users_count'] = count($users);
            $region['contracts_count'] = count($contracts);
            
            // Build manager name
            if ($region['manager_first_name'] || $region['manager_last_name']) {
                $region['manager_name'] = trim(($region['manager_first_name'] ?? '') . ' ' . ($region['manager_last_name'] ?? ''));
            } else {
                $region['manager_name'] = null;
            }
        }

        return $regions;
    }
}


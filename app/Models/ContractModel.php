<?php

namespace App\Models;

use CodeIgniter\Model;

class ContractModel extends Model
{
    protected $table            = 'contracts';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'region_id',
        'name',
        'contract_number',
        'client_name',
        'manager_id',
        'start_date',
        'end_date',
        'progress_percentage',
        'status',
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
     * Get contract's region
     */
    public function getRegion(int $contractId)
    {
        $contract = $this->find($contractId);
        if (!$contract) {
            return null;
        }
        
        $regionModel = new RegionModel();
        return $regionModel->find($contract['region_id']);
    }

    /**
     * Get contract manager
     */
    public function getManager(int $contractId)
    {
        $contract = $this->find($contractId);
        if (!$contract || !$contract['manager_id']) {
            return null;
        }
        
        $userModel = new UserModel();
        return $userModel->find($contract['manager_id']);
    }

    /**
     * Get all subdivisions for a contract
     */
    public function getSubdivisions(int $contractId)
    {
        $subdivisionModel = new SubdivisionModel();
        return $subdivisionModel->where('contract_id', $contractId)->findAll();
    }

    /**
     * Get contracts by status
     */
    public function findByStatus(string $status)
    {
        return $this->where('status', $status)->findAll();
    }

    /**
     * Get active contracts
     */
    public function findActive()
    {
        return $this->where('status', 'active')->findAll();
    }

    /**
     * Get all contracts with details (region, manager)
     * 
     * @return array Contracts with joined data
     */
    public function getAllContractsWithDetails(): array
    {
        $db = \Config\Database::connect();

        $contracts = $db->table('contracts c')
            ->select('c.*, r.name as region_name, u.first_name as manager_first_name, u.last_name as manager_last_name, u.email as manager_email')
            ->join('regions r', 'r.id = c.region_id', 'left')
            ->join('users u', 'u.id = c.manager_id', 'left')
            ->orderBy('c.name', 'ASC')
            ->get()
            ->getResultArray();

        foreach ($contracts as &$contract) {
            // Build manager name
            if ($contract['manager_first_name'] || $contract['manager_last_name']) {
                $contract['manager_name'] = trim(($contract['manager_first_name'] ?? '') . ' ' . ($contract['manager_last_name'] ?? ''));
            } else {
                $contract['manager_name'] = null;
            }

            // Get subdivisions count
            $contract['subdivisions_count'] = count($this->getSubdivisions($contract['id']));
        }

        return $contracts;
    }

    /**
     * Get contracts for a region with details
     * 
     * @param int $regionId Region ID
     * @return array Contracts with details
     */
    public function getContractsForRegionWithDetails(int $regionId): array
    {
        $db = \Config\Database::connect();

        $contracts = $db->table('contracts c')
            ->select('c.*, r.name as region_name, u.first_name as manager_first_name, u.last_name as manager_last_name, u.email as manager_email')
            ->join('regions r', 'r.id = c.region_id', 'left')
            ->join('users u', 'u.id = c.manager_id', 'left')
            ->where('c.region_id', $regionId)
            ->orderBy('c.name', 'ASC')
            ->get()
            ->getResultArray();

        foreach ($contracts as &$contract) {
            // Build manager name
            if ($contract['manager_first_name'] || $contract['manager_last_name']) {
                $contract['manager_name'] = trim(($contract['manager_first_name'] ?? '') . ' ' . ($contract['manager_last_name'] ?? ''));
            } else {
                $contract['manager_name'] = null;
            }

            // Get subdivisions count
            $contract['subdivisions_count'] = count($this->getSubdivisions($contract['id']));
        }

        return $contracts;
    }

    /**
     * Get contracts for a manager with details
     * 
     * @param int $managerId Manager user ID
     * @return array Contracts with details
     */
    public function getContractsForManagerWithDetails(int $managerId): array
    {
        $db = \Config\Database::connect();

        $contracts = $db->table('contracts c')
            ->select('c.*, r.name as region_name, u.first_name as manager_first_name, u.last_name as manager_last_name, u.email as manager_email')
            ->join('regions r', 'r.id = c.region_id', 'left')
            ->join('users u', 'u.id = c.manager_id', 'left')
            ->where('c.manager_id', $managerId)
            ->orderBy('c.name', 'ASC')
            ->get()
            ->getResultArray();

        foreach ($contracts as &$contract) {
            // Build manager name
            if ($contract['manager_first_name'] || $contract['manager_last_name']) {
                $contract['manager_name'] = trim(($contract['manager_first_name'] ?? '') . ' ' . ($contract['manager_last_name'] ?? ''));
            } else {
                $contract['manager_name'] = null;
            }

            // Get subdivisions count
            $contract['subdivisions_count'] = count($this->getSubdivisions($contract['id']));
        }

        return $contracts;
    }
}


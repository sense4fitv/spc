<?php

namespace App\Models;

use CodeIgniter\Model;

class SubdivisionModel extends Model
{
    protected $table            = 'subdivisions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'contract_id',
        'code',
        'name',
        'details',
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
     * Get subdivision's contract
     */
    public function getContract(int $subdivisionId)
    {
        $subdivision = $this->find($subdivisionId);
        if (!$subdivision) {
            return null;
        }
        
        $contractModel = new ContractModel();
        return $contractModel->find($subdivision['contract_id']);
    }

    /**
     * Get all tasks for a subdivision
     */
    public function getTasks(int $subdivisionId)
    {
        $taskModel = new TaskModel();
        return $taskModel->where('subdivision_id', $subdivisionId)->findAll();
    }

    /**
     * Get all subdivisions with details (contract, region)
     * 
     * @return array Subdivisions with joined data
     */
    public function getAllSubdivisionsWithDetails(): array
    {
        $db = \Config\Database::connect();

        $subdivisions = $db->table('subdivisions s')
            ->select('s.*, c.name as contract_name, c.contract_number, r.name as region_name, r.id as region_id')
            ->join('contracts c', 'c.id = s.contract_id', 'left')
            ->join('regions r', 'r.id = c.region_id', 'left')
            ->orderBy('c.name', 'ASC')
            ->orderBy('s.code', 'ASC')
            ->get()
            ->getResultArray();

        foreach ($subdivisions as &$subdivision) {
            // Get tasks count
            $subdivision['tasks_count'] = count($this->getTasks($subdivision['id']));
        }

        return $subdivisions;
    }

    /**
     * Get subdivisions for a region with details
     * 
     * @param int $regionId Region ID
     * @return array Subdivisions with details
     */
    public function getSubdivisionsForRegionWithDetails(int $regionId): array
    {
        $db = \Config\Database::connect();

        $subdivisions = $db->table('subdivisions s')
            ->select('s.*, c.name as contract_name, c.contract_number, r.name as region_name, r.id as region_id')
            ->join('contracts c', 'c.id = s.contract_id', 'left')
            ->join('regions r', 'r.id = c.region_id', 'left')
            ->where('r.id', $regionId)
            ->orderBy('c.name', 'ASC')
            ->orderBy('s.code', 'ASC')
            ->get()
            ->getResultArray();

        foreach ($subdivisions as &$subdivision) {
            // Get tasks count
            $subdivision['tasks_count'] = count($this->getTasks($subdivision['id']));
        }

        return $subdivisions;
    }

    /**
     * Get subdivisions for a manager with details
     * 
     * @param int $managerId Manager user ID
     * @return array Subdivisions with details
     */
    public function getSubdivisionsForManagerWithDetails(int $managerId): array
    {
        $db = \Config\Database::connect();

        $subdivisions = $db->table('subdivisions s')
            ->select('s.*, c.name as contract_name, c.contract_number, r.name as region_name, r.id as region_id')
            ->join('contracts c', 'c.id = s.contract_id', 'left')
            ->join('regions r', 'r.id = c.region_id', 'left')
            ->where('c.manager_id', $managerId)
            ->orderBy('c.name', 'ASC')
            ->orderBy('s.code', 'ASC')
            ->get()
            ->getResultArray();

        foreach ($subdivisions as &$subdivision) {
            // Get tasks count
            $subdivision['tasks_count'] = count($this->getTasks($subdivision['id']));
        }

        return $subdivisions;
    }
}


<?php

namespace App\Services;

use App\Models\SubdivisionModel;
use App\Models\ContractModel;
use App\Models\UserModel;
use App\Models\RegionModel;

/**
 * SubdivisionManagementService
 * 
 * Service responsible for subdivision management business logic,
 * including permission checks and data filtering based on user roles.
 */
class SubdivisionManagementService
{
    protected SubdivisionModel $subdivisionModel;
    protected ContractModel $contractModel;
    protected UserModel $userModel;
    protected RegionModel $regionModel;

    /**
     * Role level mappings
     */
    protected array $roleLevels = [
        'admin'      => 100,
        'director'   => 80,
        'manager'    => 50,
        'executant'  => 20,
        'auditor'    => 10,
    ];

    public function __construct()
    {
        $this->subdivisionModel = new SubdivisionModel();
        $this->contractModel = new ContractModel();
        $this->userModel = new UserModel();
        $this->regionModel = new RegionModel();
    }

    /**
     * Get viewable subdivisions based on current user's role
     * 
     * @param int $currentUserId Current user ID
     * @return array Array of subdivisions with additional data
     */
    public function getViewableSubdivisions(int $currentUserId): array
    {
        $currentUser = $this->userModel->find($currentUserId);

        if (!$currentUser) {
            return [];
        }

        $roleLevel = $currentUser['role_level'];

        // Admin sees all subdivisions
        if ($roleLevel >= $this->roleLevels['admin']) {
            return $this->subdivisionModel->getAllSubdivisionsWithDetails();
        }

        // Director sees subdivisions from contracts in his region (region_id is required)
        if ($roleLevel >= $this->roleLevels['director']) {
            $regionId = $currentUser['region_id'];
            if (!$regionId) {
                // Director must have region_id - if missing, return empty
                return [];
            }
            return $this->subdivisionModel->getSubdivisionsForRegionWithDetails($regionId);
        }

        // Manager sees only subdivisions from contracts assigned to him
        if ($roleLevel >= $this->roleLevels['manager']) {
            return $this->subdivisionModel->getSubdivisionsForManagerWithDetails($currentUserId);
        }

        // Executant and Auditor - no access to subdivisions management
        return [];
    }

    /**
     * Check if current user can create a subdivision for a contract
     * 
     * @param int $currentUserId Current user ID
     * @param int $contractId Contract ID
     * @return bool
     */
    public function canCreateSubdivision(int $currentUserId, int $contractId): bool
    {
        $currentUser = $this->userModel->find($currentUserId);
        $contract = $this->contractModel->find($contractId);

        if (!$currentUser || !$contract) {
            return false;
        }

        $roleLevel = $currentUser['role_level'];

        // Admin can create anywhere
        if ($roleLevel >= $this->roleLevels['admin']) {
            return true;
        }

        // Director can create only in contracts from his region (region_id is required)
        if ($roleLevel >= $this->roleLevels['director']) {
            $directorRegionId = $currentUser['region_id'];
            if (!$directorRegionId) {
                // Director must have region_id - cannot create without it
                return false;
            }
            return $contract['region_id'] == $directorRegionId;
        }

        // Manager can create only in contracts assigned to him
        if ($roleLevel >= $this->roleLevels['manager']) {
            return $contract['manager_id'] == $currentUserId;
        }

        return false;
    }

    /**
     * Check if current user can edit a subdivision
     * 
     * @param int $currentUserId Current user ID
     * @param int $subdivisionId Subdivision ID to edit
     * @return bool
     */
    public function canEditSubdivision(int $currentUserId, int $subdivisionId): bool
    {
        $currentUser = $this->userModel->find($currentUserId);
        $subdivision = $this->subdivisionModel->find($subdivisionId);

        if (!$currentUser || !$subdivision) {
            return false;
        }

        // Get the contract for this subdivision
        $contract = $this->contractModel->find($subdivision['contract_id']);
        if (!$contract) {
            return false;
        }

        $roleLevel = $currentUser['role_level'];

        // Admin can edit any subdivision
        if ($roleLevel >= $this->roleLevels['admin']) {
            return true;
        }

        // Director can edit only subdivisions from contracts in his region (region_id is required)
        if ($roleLevel >= $this->roleLevels['director']) {
            $directorRegionId = $currentUser['region_id'];
            if (!$directorRegionId) {
                // Director must have region_id - cannot edit without it
                return false;
            }
            return $contract['region_id'] == $directorRegionId;
        }

        // Manager can edit only subdivisions from contracts assigned to him
        if ($roleLevel >= $this->roleLevels['manager']) {
            return $contract['manager_id'] == $currentUserId;
        }

        return false;
    }

    /**
     * Check if current user can delete a subdivision
     * 
     * @param int $currentUserId Current user ID
     * @param int $subdivisionId Subdivision ID to delete
     * @return bool
     */
    public function canDeleteSubdivision(int $currentUserId, int $subdivisionId): bool
    {
        // Same logic as edit
        return $this->canEditSubdivision($currentUserId, $subdivisionId);
    }

    /**
     * Get allowed contracts for subdivision creation
     * 
     * @param int $currentUserId Current user ID
     * @return array Array of contracts [id => name]
     */
    public function getAllowedContractsForCreate(int $currentUserId): array
    {
        $currentUser = $this->userModel->find($currentUserId);

        if (!$currentUser) {
            return [];
        }

        $roleLevel = $currentUser['role_level'];

        // Admin sees all contracts
        if ($roleLevel >= $this->roleLevels['admin']) {
            $contracts = $this->contractModel->findAll();
            $result = [];
            foreach ($contracts as $contract) {
                $result[$contract['id']] = $contract['name'];
            }
            return $result;
        }

        // Director sees only contracts from his region (region_id is required)
        if ($roleLevel >= $this->roleLevels['director']) {
            $regionId = $currentUser['region_id'];
            if (!$regionId) {
                // Director must have region_id - return empty if missing
                return [];
            }
            $contracts = $this->contractModel->where('region_id', $regionId)->findAll();
            $result = [];
            foreach ($contracts as $contract) {
                $result[$contract['id']] = $contract['name'];
            }
            return $result;
        }

        // Manager sees only contracts assigned to him
        if ($roleLevel >= $this->roleLevels['manager']) {
            $contracts = $this->contractModel->where('manager_id', $currentUserId)->findAll();
            $result = [];
            foreach ($contracts as $contract) {
                $result[$contract['id']] = $contract['name'];
            }
            return $result;
        }

        return [];
    }

    /**
     * Check if subdivision has dependencies (tasks)
     * 
     * @param int $subdivisionId Subdivision ID
     * @return array ['has_dependencies' => bool, 'tasks_count' => int]
     */
    public function hasDependencies(int $subdivisionId): array
    {
        $tasks = $this->subdivisionModel->getTasks($subdivisionId);
        $tasksCount = count($tasks);

        return [
            'has_dependencies' => $tasksCount > 0,
            'tasks_count' => $tasksCount,
        ];
    }
}

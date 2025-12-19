<?php

namespace App\Services;

use App\Models\ContractModel;
use App\Models\UserModel;
use App\Models\RegionModel;

/**
 * ContractManagementService
 * 
 * Service responsible for contract management business logic,
 * including permission checks and data filtering based on user roles.
 */
class ContractManagementService
{
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
        $this->contractModel = new ContractModel();
        $this->userModel = new UserModel();
        $this->regionModel = new RegionModel();
    }

    /**
     * Get viewable contracts based on current user's role
     * 
     * @param int $currentUserId Current user ID
     * @return array Array of contracts with additional data
     */
    public function getViewableContracts(int $currentUserId): array
    {
        $currentUser = $this->userModel->find($currentUserId);
        
        if (!$currentUser) {
            return [];
        }

        $roleLevel = $currentUser['role_level'];

        // Admin sees all contracts
        if ($roleLevel >= $this->roleLevels['admin']) {
            return $this->contractModel->getAllContractsWithDetails();
        }

        // Director sees contracts from his region (region_id is required)
        if ($roleLevel >= $this->roleLevels['director']) {
            $regionId = $currentUser['region_id'];
            if (!$regionId) {
                // Director must have region_id - if missing, return empty
                return [];
            }
            return $this->contractModel->getContractsForRegionWithDetails($regionId);
        }

        // Manager sees only contracts assigned to him
        if ($roleLevel >= $this->roleLevels['manager']) {
            return $this->contractModel->getContractsForManagerWithDetails($currentUserId);
        }

        // Executant and Auditor - no access to contracts
        return [];
    }

    /**
     * Check if current user can create a contract
     * 
     * @param int $currentUserId Current user ID
     * @param int|null $targetRegionId Target region ID (for validation)
     * @return bool
     */
    public function canCreateContract(int $currentUserId, ?int $targetRegionId = null): bool
    {
        $currentUser = $this->userModel->find($currentUserId);
        
        if (!$currentUser) {
            return false;
        }

        $roleLevel = $currentUser['role_level'];

        // Admin can create anywhere
        if ($roleLevel >= $this->roleLevels['admin']) {
            return true;
        }

        // Director can create only in his region (region_id is required)
        if ($roleLevel >= $this->roleLevels['director']) {
            $directorRegionId = $currentUser['region_id'];
            if (!$directorRegionId) {
                // Director must have region_id - cannot create without it
                return false;
            }
            // If targetRegionId is null (in create form), allow if director has region_id
            // If targetRegionId is set (in store), must match director's region
            if ($targetRegionId === null) {
                return true; // Allow access to create form
            }
            // Must match director's region when saving
            return $targetRegionId === $directorRegionId;
        }

        // Manager cannot create contracts
        return false;
    }

    /**
     * Check if current user can edit a contract
     * 
     * @param int $currentUserId Current user ID
     * @param int $contractId Contract ID to edit
     * @return bool
     */
    public function canEditContract(int $currentUserId, int $contractId): bool
    {
        $currentUser = $this->userModel->find($currentUserId);
        $contract = $this->contractModel->find($contractId);

        if (!$currentUser || !$contract) {
            return false;
        }

        $roleLevel = $currentUser['role_level'];

        // Admin can edit any contract
        if ($roleLevel >= $this->roleLevels['admin']) {
            return true;
        }

        // Director can edit only contracts from his region (region_id is required)
        if ($roleLevel >= $this->roleLevels['director']) {
            $directorRegionId = $currentUser['region_id'];
            if (!$directorRegionId) {
                // Director must have region_id - cannot edit without it
                return false;
            }
            // Contract must be in same region
            return $contract['region_id'] === $directorRegionId;
        }

        // Manager can edit only contracts assigned to him
        if ($roleLevel >= $this->roleLevels['manager']) {
            return $contract['manager_id'] === $currentUserId;
        }

        return false;
    }

    /**
     * Check if current user can delete a contract
     * 
     * @param int $currentUserId Current user ID
     * @param int $contractId Contract ID to delete
     * @return bool
     */
    public function canDeleteContract(int $currentUserId, int $contractId): bool
    {
        // Same logic as edit
        return $this->canEditContract($currentUserId, $contractId);
    }

    /**
     * Get allowed regions for contract creation
     * 
     * @param int $currentUserId Current user ID
     * @return array Array of regions [id => name]
     */
    public function getAllowedRegionsForCreate(int $currentUserId): array
    {
        $currentUser = $this->userModel->find($currentUserId);
        
        if (!$currentUser) {
            return [];
        }

        $roleLevel = $currentUser['role_level'];

        // Admin sees all regions
        if ($roleLevel >= $this->roleLevels['admin']) {
            $regions = $this->regionModel->findAll();
            $result = [];
            foreach ($regions as $region) {
                $result[$region['id']] = $region['name'];
            }
            return $result;
        }

        // Director sees only his region (region_id is required)
        if ($roleLevel >= $this->roleLevels['director']) {
            $regionId = $currentUser['region_id'];
            if (!$regionId) {
                // Director must have region_id - return empty if missing
                return [];
            }
            $region = $this->regionModel->find($regionId);
            if ($region) {
                return [$region['id'] => $region['name']];
            }
        }

        return [];
    }

    /**
     * Get all managers (users with role 'manager') for contract assignment
     * 
     * @return array Array of managers [id => full_name]
     */
    public function getAllManagers(): array
    {
        $managers = $this->userModel->where('role', 'manager')
            ->where('active', 1)
            ->orderBy('last_name', 'ASC')
            ->orderBy('first_name', 'ASC')
            ->findAll();

        $result = [];
        foreach ($managers as $manager) {
            $fullName = trim(($manager['first_name'] ?? '') . ' ' . ($manager['last_name'] ?? ''));
            $result[$manager['id']] = $fullName ?: $manager['email'];
        }

        return $result;
    }

    /**
     * Check if contract has dependencies (subdivisions or tasks)
     * 
     * @param int $contractId Contract ID
     * @return array ['has_dependencies' => bool, 'subdivisions_count' => int]
     */
    public function hasDependencies(int $contractId): array
    {
        $subdivisions = $this->contractModel->getSubdivisions($contractId);
        $subdivisionsCount = count($subdivisions);

        return [
            'has_dependencies' => $subdivisionsCount > 0,
            'subdivisions_count' => $subdivisionsCount,
        ];
    }
}


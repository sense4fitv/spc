<?php

namespace App\Services;

use App\Models\IssueModel;
use App\Models\UserModel;
use App\Models\RegionModel;
use App\Models\DepartmentModel;

/**
 * IssueManagementService
 * 
 * Service responsible for issue management business logic,
 * including permission checks and data filtering based on user roles.
 */
class IssueManagementService
{
    protected IssueModel $issueModel;
    protected UserModel $userModel;
    protected RegionModel $regionModel;
    protected DepartmentModel $departmentModel;

    /**
     * Role level mappings
     */
    protected array $roleLevels = [
        'admin'    => 100,
        'director' => 80,
    ];

    public function __construct()
    {
        $this->issueModel = new IssueModel();
        $this->userModel = new UserModel();
        $this->regionModel = new RegionModel();
        $this->departmentModel = new DepartmentModel();
    }

    /**
     * Get viewable issues based on current user's role
     * 
     * @param int $currentUserId Current user ID
     * @return array Array of issues with additional data
     */
    public function getViewableIssues(int $currentUserId): array
    {
        $currentUser = $this->userModel->find($currentUserId);

        if (!$currentUser) {
            return [];
        }

        $roleLevel = $currentUser['role_level'];

        // Admin sees all issues (with and without region)
        if ($roleLevel >= $this->roleLevels['admin']) {
            return $this->issueModel->getAllIssuesWithLastComment();
        }

        // Director sees only issues from his region (region_id is required)
        if ($roleLevel >= $this->roleLevels['director']) {
            $regionId = $currentUser['region_id'];
            if (!$regionId) {
                // Director must have region_id - if missing, return empty
                return [];
            }
            return $this->issueModel->getIssuesForRegionWithLastComment($regionId);
        }

        // Other roles cannot see issues
        return [];
    }

    /**
     * Check if current user can view an issue
     * 
     * @param int $currentUserId Current user ID
     * @param int $issueId Issue ID to view
     * @return bool
     */
    public function canViewIssue(int $currentUserId, int $issueId): bool
    {
        $currentUser = $this->userModel->find($currentUserId);
        $issue = $this->issueModel->find($issueId);

        if (!$currentUser || !$issue) {
            return false;
        }

        $roleLevel = $currentUser['role_level'];

        // Admin can view any issue
        if ($roleLevel >= $this->roleLevels['admin']) {
            return true;
        }

        // Director can view only issues from his region
        if ($roleLevel >= $this->roleLevels['director']) {
            $directorRegionId = $currentUser['region_id'];
            if (!$directorRegionId) {
                // Director must have region_id - cannot view without it
                return false;
            }
            
            // Global issues (region_id NULL) - only Admin can view
            if ($issue['region_id'] === null) {
                return false;
            }
            
            return $issue['region_id'] == $directorRegionId;
        }

        return false;
    }

    /**
     * Check if current user can create an issue
     * 
     * @param int $currentUserId Current user ID
     * @param int|null $regionId Region ID for the issue (null for global)
     * @return bool
     */
    public function canCreateIssue(int $currentUserId, ?int $regionId = null): bool
    {
        $currentUser = $this->userModel->find($currentUserId);

        if (!$currentUser) {
            return false;
        }

        $roleLevel = $currentUser['role_level'];

        // Admin can create issues (with or without region)
        if ($roleLevel >= $this->roleLevels['admin']) {
            return true;
        }

        // Director can create issues only for his region (region_id is required)
        if ($roleLevel >= $this->roleLevels['director']) {
            $directorRegionId = $currentUser['region_id'];
            if (!$directorRegionId) {
                // Director must have region_id - cannot create without it
                return false;
            }
            
            // Director cannot create global issues (region_id NULL)
            if ($regionId === null) {
                return false;
            }
            
            return $regionId == $directorRegionId;
        }

        return false;
    }

    /**
     * Check if current user can edit an issue
     * 
     * @param int $currentUserId Current user ID
     * @param int $issueId Issue ID to edit
     * @return bool
     */
    public function canEditIssue(int $currentUserId, int $issueId): bool
    {
        $currentUser = $this->userModel->find($currentUserId);
        $issue = $this->issueModel->find($issueId);

        if (!$currentUser || !$issue) {
            return false;
        }

        $roleLevel = $currentUser['role_level'];

        // Admin can edit any issue
        if ($roleLevel >= $this->roleLevels['admin']) {
            return true;
        }

        // Creator + Director can edit issues from his region
        if ($roleLevel >= $this->roleLevels['director']) {
            $directorRegionId = $currentUser['region_id'];
            if (!$directorRegionId) {
                return false;
            }
            
            // Global issues (region_id NULL) - only Admin can edit
            if ($issue['region_id'] === null) {
                return false;
            }
            
            // Director can edit if issue is from his region (creator check is optional, but we allow it)
            return $issue['region_id'] == $directorRegionId;
        }

        return false;
    }

    /**
     * Check if current user can archive an issue
     * 
     * @param int $currentUserId Current user ID
     * @param int $issueId Issue ID to archive
     * @return bool
     */
    public function canArchiveIssue(int $currentUserId, int $issueId): bool
    {
        // Only Admin can archive issues
        $currentUser = $this->userModel->find($currentUserId);
        
        if (!$currentUser) {
            return false;
        }

        $roleLevel = $currentUser['role_level'];
        return $roleLevel >= $this->roleLevels['admin'];
    }

    /**
     * Get allowed regions for issue creation
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
        $regions = [];

        // Admin sees all regions
        if ($roleLevel >= $this->roleLevels['admin']) {
            $allRegions = $this->regionModel->findAll();
            foreach ($allRegions as $region) {
                $regions[$region['id']] = $region['name'];
            }
            return $regions;
        }

        // Director sees only his region
        if ($roleLevel >= $this->roleLevels['director']) {
            $regionId = $currentUser['region_id'];
            if ($regionId) {
                $region = $this->regionModel->find($regionId);
                if ($region) {
                    $regions[$region['id']] = $region['name'];
                }
            }
            return $regions;
        }

        return [];
    }

    /**
     * Get allowed departments for issue creation
     * 
     * @param int $currentUserId Current user ID
     * @param int|null $regionId Region ID (optional, for filtering)
     * @return array Array of departments [id => name]
     */
    public function getAllowedDepartmentsForCreate(int $currentUserId, ?int $regionId = null): array
    {
        $currentUser = $this->userModel->find($currentUserId);

        if (!$currentUser) {
            return [];
        }

        $roleLevel = $currentUser['role_level'];
        $departments = [];

        // Admin sees all departments
        if ($roleLevel >= $this->roleLevels['admin']) {
            $allDepartments = $this->departmentModel->findAll();
            foreach ($allDepartments as $dept) {
                $departments[$dept['id']] = $dept['name'];
            }
            return $departments;
        }

        // Director sees all departments (no region filtering needed for departments)
        if ($roleLevel >= $this->roleLevels['director']) {
            $allDepartments = $this->departmentModel->findAll();
            foreach ($allDepartments as $dept) {
                $departments[$dept['id']] = $dept['name'];
            }
            return $departments;
        }

        return [];
    }
}


<?php

namespace App\Models;

use CodeIgniter\Model;

class IssueModel extends Model
{
    protected $table            = 'issues';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'region_id',
        'department_id',
        'created_by',
        'title',
        'description',
        'status',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
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
     * Get issue creator
     */
    public function getCreator(int $issueId)
    {
        $issue = $this->find($issueId);
        if (!$issue) {
            return null;
        }

        $userModel = new UserModel();
        return $userModel->find($issue['created_by']);
    }

    /**
     * Get issue region
     */
    public function getRegion(int $issueId)
    {
        $issue = $this->find($issueId);
        if (!$issue || !$issue['region_id']) {
            return null;
        }

        $regionModel = new RegionModel();
        return $regionModel->find($issue['region_id']);
    }

    /**
     * Get issue department
     */
    public function getDepartment(int $issueId)
    {
        $issue = $this->find($issueId);
        if (!$issue || !$issue['department_id']) {
            return null;
        }

        $departmentModel = new DepartmentModel();
        return $departmentModel->find($issue['department_id']);
    }

    /**
     * Get issue comments
     */
    public function getComments(int $issueId)
    {
        $commentModel = new IssueCommentModel();
        return $commentModel->where('issue_id', $issueId)
            ->orderBy('created_at', 'ASC')
            ->findAll();
    }

    /**
     * Get issue files
     */
    public function getFiles(int $issueId)
    {
        $fileModel = new IssueFileModel();
        return $fileModel->where('issue_id', $issueId)->findAll();
    }

    /**
     * Find issues by status
     */
    public function findByStatus(string $status)
    {
        return $this->where('status', $status)->findAll();
    }

    /**
     * Get all issues with details (region, department, creator) sorted by last comment date
     * 
     * @return array Issues with joined data, sorted by last comment date (DESC)
     */
    public function getAllIssuesWithLastComment(): array
    {
        $db = \Config\Database::connect();

        $issues = $db->table('issues i')
            ->select('i.*,
                r.id as region_id_full, r.name as region_name,
                d.id as department_id_full, d.name as department_name, d.color_code as department_color,
                creator.first_name as creator_first_name, creator.last_name as creator_last_name, creator.email as creator_email,
                MAX(ic.created_at) as last_comment_date,
                COUNT(DISTINCT ic.id) as comments_count,
                COUNT(DISTINCT ifile.id) as files_count')
            ->join('regions r', 'r.id = i.region_id', 'left')
            ->join('departments d', 'd.id = i.department_id', 'left')
            ->join('users creator', 'creator.id = i.created_by', 'left')
            ->join('issue_comments ic', 'ic.issue_id = i.id', 'left')
            ->join('issue_files ifile', 'ifile.issue_id = i.id', 'left')
            ->groupBy('i.id')
            ->orderBy('last_comment_date', 'DESC')
            ->orderBy('i.created_at', 'DESC')
            ->get()
            ->getResultArray();

        foreach ($issues as &$issue) {
            // Build creator name
            if ($issue['creator_first_name'] || $issue['creator_last_name']) {
                $issue['creator_name'] = trim(($issue['creator_first_name'] ?? '') . ' ' . ($issue['creator_last_name'] ?? ''));
            } else {
                $issue['creator_name'] = $issue['creator_email'] ?? 'Necunoscut';
            }

            // Handle last_comment_date (can be NULL if no comments)
            if ($issue['last_comment_date']) {
                $issue['last_comment_date'] = $issue['last_comment_date'];
            } else {
                // If no comments, use created_at as sort key
                $issue['last_comment_date'] = $issue['created_at'];
            }

            // Convert counts to integers
            $issue['comments_count'] = (int)($issue['comments_count'] ?? 0);
            $issue['files_count'] = (int)($issue['files_count'] ?? 0);
        }

        return $issues;
    }

    /**
     * Get issues for a region with details, sorted by last comment date
     * 
     * @param int $regionId Region ID
     * @return array Issues with details
     */
    public function getIssuesForRegionWithLastComment(int $regionId): array
    {
        $db = \Config\Database::connect();

        $issues = $db->table('issues i')
            ->select('i.*,
                r.id as region_id_full, r.name as region_name,
                d.id as department_id_full, d.name as department_name, d.color_code as department_color,
                creator.first_name as creator_first_name, creator.last_name as creator_last_name, creator.email as creator_email,
                MAX(ic.created_at) as last_comment_date,
                COUNT(DISTINCT ic.id) as comments_count,
                COUNT(DISTINCT ifile.id) as files_count')
            ->join('regions r', 'r.id = i.region_id', 'left')
            ->join('departments d', 'd.id = i.department_id', 'left')
            ->join('users creator', 'creator.id = i.created_by', 'left')
            ->join('issue_comments ic', 'ic.issue_id = i.id', 'left')
            ->join('issue_files ifile', 'ifile.issue_id = i.id', 'left')
            ->where('i.region_id', $regionId)
            ->groupBy('i.id')
            ->orderBy('last_comment_date', 'DESC')
            ->orderBy('i.created_at', 'DESC')
            ->get()
            ->getResultArray();

        foreach ($issues as &$issue) {
            // Build creator name
            if ($issue['creator_first_name'] || $issue['creator_last_name']) {
                $issue['creator_name'] = trim(($issue['creator_first_name'] ?? '') . ' ' . ($issue['creator_last_name'] ?? ''));
            } else {
                $issue['creator_name'] = $issue['creator_email'] ?? 'Necunoscut';
            }

            // Handle last_comment_date
            if ($issue['last_comment_date']) {
                $issue['last_comment_date'] = $issue['last_comment_date'];
            } else {
                $issue['last_comment_date'] = $issue['created_at'];
            }

            // Convert counts to integers
            $issue['comments_count'] = (int)($issue['comments_count'] ?? 0);
            $issue['files_count'] = (int)($issue['files_count'] ?? 0);
        }

        return $issues;
    }

    /**
     * Get global issues (region_id IS NULL) with details, sorted by last comment date
     * 
     * @return array Issues with details
     */
    public function getGlobalIssuesWithLastComment(): array
    {
        $db = \Config\Database::connect();

        $issues = $db->table('issues i')
            ->select('i.*,
                d.id as department_id_full, d.name as department_name, d.color_code as department_color,
                creator.first_name as creator_first_name, creator.last_name as creator_last_name, creator.email as creator_email,
                MAX(ic.created_at) as last_comment_date,
                COUNT(DISTINCT ic.id) as comments_count,
                COUNT(DISTINCT ifile.id) as files_count')
            ->join('departments d', 'd.id = i.department_id', 'left')
            ->join('users creator', 'creator.id = i.created_by', 'left')
            ->join('issue_comments ic', 'ic.issue_id = i.id', 'left')
            ->join('issue_files ifile', 'ifile.issue_id = i.id', 'left')
            ->where('i.region_id IS NULL')
            ->groupBy('i.id')
            ->orderBy('last_comment_date', 'DESC')
            ->orderBy('i.created_at', 'DESC')
            ->get()
            ->getResultArray();

        foreach ($issues as &$issue) {
            // Build creator name
            if ($issue['creator_first_name'] || $issue['creator_last_name']) {
                $issue['creator_name'] = trim(($issue['creator_first_name'] ?? '') . ' ' . ($issue['creator_last_name'] ?? ''));
            } else {
                $issue['creator_name'] = $issue['creator_email'] ?? 'Necunoscut';
            }

            // Handle last_comment_date
            if ($issue['last_comment_date']) {
                $issue['last_comment_date'] = $issue['last_comment_date'];
            } else {
                $issue['last_comment_date'] = $issue['created_at'];
            }

            // Convert counts to integers
            $issue['comments_count'] = (int)($issue['comments_count'] ?? 0);
            $issue['files_count'] = (int)($issue['files_count'] ?? 0);
        }

        return $issues;
    }

    /**
     * Get issue with full details (for issue details view)
     * 
     * @param int $issueId Issue ID
     * @return array|null Issue with all related data
     */
    public function getIssueWithFullDetails(int $issueId): ?array
    {
        $issue = $this->find($issueId);

        if (!$issue) {
            return null;
        }

        // Get region
        if ($issue['region_id']) {
            $region = $this->getRegion($issueId);
            $issue['region'] = $region;
        } else {
            $issue['region'] = null;
        }

        // Get department
        if ($issue['department_id']) {
            $department = $this->getDepartment($issueId);
            $issue['department'] = $department;
        } else {
            $issue['department'] = null;
        }

        // Get creator
        $creator = $this->getCreator($issueId);
        $issue['creator'] = $creator;
        if ($creator) {
            $issue['creator_name'] = trim(($creator['first_name'] ?? '') . ' ' . ($creator['last_name'] ?? '')) ?: $creator['email'];
        }

        // Get comments (with authors)
        $commentModel = new IssueCommentModel();
        $comments = $commentModel->findWithAuthors($issueId);
        $issue['comments'] = $comments;

        // Get files (with uploader)
        $fileModel = new IssueFileModel();
        $files = $fileModel->findWithUploader($issueId);
        $issue['files'] = $files;

        return $issue;
    }

    /**
     * Archive issue (set status to 'archived')
     * 
     * @param int $issueId Issue ID
     * @return bool Success status
     */
    public function archive(int $issueId): bool
    {
        return $this->update($issueId, ['status' => 'archived']) !== false;
    }
}


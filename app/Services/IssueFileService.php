<?php

namespace App\Services;

use App\Models\IssueFileModel;
use App\Models\IssueModel;
use App\Models\UserModel;
use App\Models\RegionModel;

/**
 * IssueFileService
 * 
 * Service responsible for file upload, download, and access control for issue files.
 */
class IssueFileService
{
    protected IssueFileModel $issueFileModel;
    protected IssueModel $issueModel;
    protected UserModel $userModel;
    protected RegionModel $regionModel;
    protected IssueManagementService $issueManagementService;

    protected string $uploadPath;
    protected int $maxFileSize = 10485760; // 10MB in bytes

    /**
     * Allowed MIME types for upload
     */
    protected array $allowedMimeTypes = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'text/plain',
        'text/csv',
        'application/zip',
        'application/x-rar-compressed',
    ];

    public function __construct()
    {
        $this->issueFileModel = new IssueFileModel();
        $this->issueModel = new IssueModel();
        $this->userModel = new UserModel();
        $this->regionModel = new RegionModel();
        $this->issueManagementService = new IssueManagementService();

        // Set upload path: writable/uploads/issues/{issue_id}/
        $this->uploadPath = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'issues' . DIRECTORY_SEPARATOR;
        
        // Ensure upload directory exists
        if (!is_dir($this->uploadPath)) {
            mkdir($this->uploadPath, 0755, true);
        }
    }

    /**
     * Upload one or more files for an issue
     * 
     * @param int $issueId Issue ID
     * @param array|\CodeIgniter\HTTP\Files\UploadedFile $files Uploaded file(s)
     * @param int $userId User ID who uploaded the files
     * @return array ['success' => bool, 'files' => array, 'errors' => array]
     */
    public function uploadIssueFiles(int $issueId, $files, int $userId): array
    {
        // Verify issue exists
        $issue = $this->issueModel->find($issueId);
        if (!$issue) {
            return [
                'success' => false,
                'files' => [],
                'errors' => ['Problematicea nu există.'],
            ];
        }

        // Verify user has access to upload files to this issue
        if (!$this->canAccessIssue($issueId, $userId)) {
            return [
                'success' => false,
                'files' => [],
                'errors' => ['Nu ai permisiunea să încarci fișiere pentru această problematică.'],
            ];
        }

        // Normalize files to array if single file
        if (!is_array($files)) {
            $files = [$files];
        }

        $uploadedFiles = [];
        $errors = [];

        // Create issue-specific directory
        $issueDir = $this->uploadPath . $issueId . DIRECTORY_SEPARATOR;
        if (!is_dir($issueDir)) {
            mkdir($issueDir, 0755, true);
        }

        foreach ($files as $file) {
            if (!$file->isValid()) {
                $errors[] = 'Fișier invalid: ' . $file->getName();
                continue;
            }

            // Check file size
            if ($file->getSize() > $this->maxFileSize) {
                $errors[] = 'Fișierul ' . $file->getName() . ' depășește dimensiunea maximă permisă (10MB).';
                continue;
            }

            // Check MIME type
            $mimeType = $file->getMimeType();
            if (!in_array($mimeType, $this->allowedMimeTypes)) {
                $errors[] = 'Tipul de fișier ' . $file->getName() . ' nu este permis.';
                continue;
            }

            // Generate unique filename
            $originalName = $file->getName();
            $extension = $file->getClientExtension();
            $filename = uniqid('issue_' . $issueId . '_') . '.' . $extension;
            $filepath = $issueDir . $filename;

            // Move uploaded file
            if (!$file->move($issueDir, $filename)) {
                $errors[] = 'Eroare la încărcarea fișierului ' . $originalName . '.';
                continue;
            }

            // Save file record in database
            $fileData = [
                'issue_id' => $issueId,
                'uploaded_by' => $userId,
                'filename' => $originalName,
                'filepath' => 'uploads/issues/' . $issueId . '/' . $filename,
                'file_type' => $mimeType,
                'file_size' => $file->getSize(),
            ];

            // Insert file record using Query Builder directly
            // created_at will use DEFAULT CURRENT_TIMESTAMP from database
            $db = \Config\Database::connect();
            $db->table('issue_files')->insert($fileData);
            $fileId = $db->insertID();

            if ($fileId) {
                $fileData['id'] = $fileId;
                $uploadedFiles[] = $fileData;
            } else {
                // Remove uploaded file if database insert failed
                if (file_exists($filepath)) {
                    unlink($filepath);
                }
                $errors[] = 'Eroare la salvarea fișierului ' . $originalName . ' în baza de date.';
            }
        }

        return [
            'success' => !empty($uploadedFiles),
            'files' => $uploadedFiles,
            'errors' => $errors,
        ];
    }

    /**
     * Download file for an issue
     * 
     * @param int $issueId Issue ID
     * @param int $fileId File ID
     * @param int $userId User ID requesting the download
     * @return array|null ['filepath' => string, 'filename' => string] or null if access denied
     */
    public function downloadIssueFile(int $issueId, int $fileId, int $userId): ?array
    {
        // Verify user has access to this issue
        if (!$this->canAccessIssue($issueId, $userId)) {
            return null;
        }

        // Get file record
        $file = $this->issueFileModel->find($fileId);
        if (!$file || $file['issue_id'] != $issueId) {
            return null;
        }

        // Build full file path
        $filepath = WRITEPATH . $file['filepath'];

        // Verify file exists
        if (!file_exists($filepath)) {
            return null;
        }

        return [
            'filepath' => $filepath,
            'filename' => $file['filename'],
            'file_type' => $file['file_type'] ?? 'application/octet-stream',
        ];
    }

    /**
     * Check if user can access an issue (for file operations)
     * 
     * @param int $issueId Issue ID
     * @param int $userId User ID
     * @return bool
     */
    protected function canAccessIssue(int $issueId, int $userId): bool
    {
        return $this->issueManagementService->canViewIssue($userId, $issueId);
    }
}


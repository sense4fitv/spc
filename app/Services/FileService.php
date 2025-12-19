<?php

namespace App\Services;

use App\Models\TaskFileModel;
use App\Models\TaskModel;
use App\Models\SubdivisionModel;
use App\Models\ContractModel;
use App\Models\UserModel;

/**
 * FileService
 * 
 * Service responsible for file upload, download, and access control for task files.
 */
class FileService
{
    protected TaskFileModel $taskFileModel;
    protected TaskModel $taskModel;
    protected SubdivisionModel $subdivisionModel;
    protected ContractModel $contractModel;
    protected UserModel $userModel;
    protected TaskManagementService $taskManagementService;

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
        $this->taskFileModel = new TaskFileModel();
        $this->taskModel = new TaskModel();
        $this->subdivisionModel = new SubdivisionModel();
        $this->contractModel = new ContractModel();
        $this->userModel = new UserModel();
        $this->taskManagementService = new TaskManagementService();

        // Set upload path: writable/uploads/tasks/{task_id}/
        $this->uploadPath = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'tasks' . DIRECTORY_SEPARATOR;
        
        // Ensure upload directory exists
        if (!is_dir($this->uploadPath)) {
            mkdir($this->uploadPath, 0755, true);
        }
    }

    /**
     * Upload one or more files for a task
     * 
     * @param int $taskId Task ID
     * @param array|\CodeIgniter\HTTP\Files\UploadedFile $files Uploaded file(s)
     * @param int $userId User ID who uploaded the files
     * @return array ['success' => bool, 'files' => array, 'errors' => array]
     */
    public function uploadTaskFiles(int $taskId, $files, int $userId): array
    {
        // Verify task exists
        $task = $this->taskModel->find($taskId);
        if (!$task) {
            return [
                'success' => false,
                'files' => [],
                'errors' => ['Task-ul nu există.'],
            ];
        }

        // Verify user has access to upload files to this task
        if (!$this->canAccessTask($taskId, $userId)) {
            return [
                'success' => false,
                'files' => [],
                'errors' => ['Nu ai permisiunea să încarci fișiere pentru acest task.'],
            ];
        }

        // Normalize files to array if single file
        if (!is_array($files)) {
            $files = [$files];
        }

        $uploadedFiles = [];
        $errors = [];

        // Create task-specific directory
        $taskDir = $this->uploadPath . $taskId . DIRECTORY_SEPARATOR;
        if (!is_dir($taskDir)) {
            mkdir($taskDir, 0755, true);
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
            $filename = uniqid('task_' . $taskId . '_') . '.' . $extension;
            $filepath = $taskDir . $filename;

            // Move uploaded file
            if (!$file->move($taskDir, $filename)) {
                $errors[] = 'Eroare la încărcarea fișierului ' . $originalName . '.';
                continue;
            }

            // Save file record in database
            $fileData = [
                'task_id' => $taskId,
                'uploaded_by' => $userId,
                'filename' => $originalName,
                'filepath' => 'uploads/tasks/' . $taskId . '/' . $filename,
                'file_type' => $mimeType,
                'file_size' => $file->getSize(),
            ];

            // Insert file record using Query Builder directly
            // created_at will use DEFAULT CURRENT_TIMESTAMP from database
            $db = \Config\Database::connect();
            $db->table('task_files')->insert($fileData);
            $fileId = $db->insertID();

            if ($fileId) {
                $fileData['id'] = $fileId;
                $uploadedFiles[] = $fileData;
            } else {
                // Remove uploaded file if database insert failed
                if (file_exists($filepath)) {
                    unlink($filepath);
                }
                $errors[] = 'Eroare la salvarea în baza de date pentru ' . $originalName . '.';
            }
        }

        return [
            'success' => count($uploadedFiles) > 0 && count($errors) === 0,
            'files' => $uploadedFiles,
            'errors' => $errors,
        ];
    }

    /**
     * Delete a task file
     * 
     * @param int $fileId File ID
     * @param int $userId User ID who wants to delete
     * @return array ['success' => bool, 'message' => string]
     */
    public function deleteTaskFile(int $fileId, int $userId): array
    {
        $file = $this->taskFileModel->find($fileId);
        
        if (!$file) {
            return [
                'success' => false,
                'message' => 'Fișierul nu există.',
            ];
        }

        // Check if user can access the task
        if (!$this->canAccessTask($file['task_id'], $userId)) {
            return [
                'success' => false,
                'message' => 'Nu ai permisiunea să ștergi acest fișier.',
            ];
        }

        // Delete physical file
        $fullPath = WRITEPATH . $file['filepath'];
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }

        // Delete database record
        if ($this->taskFileModel->delete($fileId)) {
            return [
                'success' => true,
                'message' => 'Fișierul a fost șters cu succes.',
            ];
        }

        return [
            'success' => false,
            'message' => 'Eroare la ștergerea fișierului din baza de date.',
        ];
    }

    /**
     * Check if user can access a file (can view/download)
     * 
     * @param int $fileId File ID
     * @param int $userId User ID
     * @return bool
     */
    public function canAccessFile(int $fileId, int $userId): bool
    {
        $file = $this->taskFileModel->find($fileId);
        
        if (!$file) {
            return false;
        }

        // Check if user can view the task
        return $this->canAccessTask($file['task_id'], $userId);
    }

    /**
     * Check if user can access a task (can view task details)
     * 
     * @param int $taskId Task ID
     * @param int $userId User ID
     * @return bool
     */
    protected function canAccessTask(int $taskId, int $userId): bool
    {
        return $this->taskManagementService->canViewTask($userId, $taskId);
    }

    /**
     * Get file download path and verify access
     * 
     * @param int $fileId File ID
     * @param int $userId User ID
     * @return array ['success' => bool, 'filepath' => string|null, 'filename' => string|null, 'message' => string]
     */
    public function downloadFile(int $fileId, int $userId): array
    {
        $file = $this->taskFileModel->find($fileId);
        
        if (!$file) {
            return [
                'success' => false,
                'filepath' => null,
                'filename' => null,
                'message' => 'Fișierul nu există.',
            ];
        }

        // Check access
        if (!$this->canAccessFile($fileId, $userId)) {
            return [
                'success' => false,
                'filepath' => null,
                'filename' => null,
                'message' => 'Nu ai permisiunea să accesezi acest fișier.',
            ];
        }

        // Build full path
        $fullPath = WRITEPATH . $file['filepath'];
        
        if (!file_exists($fullPath)) {
            return [
                'success' => false,
                'filepath' => null,
                'filename' => null,
                'message' => 'Fișierul nu a fost găsit pe server.',
            ];
        }

        return [
            'success' => true,
            'filepath' => $fullPath,
            'filename' => $file['filename'],
            'mime_type' => $file['file_type'],
            'message' => 'OK',
        ];
    }

    /**
     * Get files for a task with uploader information
     * 
     * @param int $taskId Task ID
     * @return array Array of files with uploader info
     */
    public function getTaskFiles(int $taskId): array
    {
        return $this->taskFileModel->findWithUploader($taskId);
    }
}


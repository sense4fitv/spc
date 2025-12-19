<?php

namespace App\Services;

use App\Models\TaskModel;
use App\Models\TaskActivityLogModel;
use App\Models\TaskCommentModel;
use App\Models\SubdivisionModel;
use App\Models\ContractModel;

/**
 * TaskService
 * 
 * Service responsible for task business logic:
 * - Creating tasks with activity logs
 * - Updating task status with workflow validation
 * - Sending notifications
 */
class TaskService
{
    protected TaskModel $taskModel;
    protected TaskActivityLogModel $activityLogModel;
    protected TaskCommentModel $commentModel;
    protected NotificationService $notificationService;
    protected SubdivisionModel $subdivisionModel;
    protected ContractModel $contractModel;

    /**
     * Valid status transitions based on workflow
     * Format: 'current_status' => ['allowed_next_statuses']
     */
    protected array $statusWorkflow = [
        'new' => ['in_progress', 'blocked'],
        'in_progress' => ['review', 'blocked'],
        'review' => ['completed', 'in_progress', 'blocked'],
        'completed' => [], // No transitions from completed
        'blocked' => ['in_progress'], // Always returns to in_progress
    ];

    public function __construct()
    {
        $this->taskModel = new TaskModel();
        $this->activityLogModel = new TaskActivityLogModel();
        $this->commentModel = new TaskCommentModel();
        $this->notificationService = new NotificationService();
        $this->subdivisionModel = new SubdivisionModel();
        $this->contractModel = new ContractModel();
    }

    /**
     * Create a new task with activity log and notifications
     * 
     * @param array $taskData Task data (subdivision_id, created_by, title, description, status, priority, deadline)
     * @param array $assigneeIds Array of user IDs to assign
     * @param array $departmentIds Array of department IDs (optional)
     * @return array ['success' => bool, 'task_id' => int|null, 'message' => string]
     */
    public function createTask(array $taskData, array $assigneeIds = [], array $departmentIds = []): array
    {
        // Set default status if not provided
        if (!isset($taskData['status'])) {
            $taskData['status'] = 'new';
        }

        // Insert task
        $taskId = $this->taskModel->insert($taskData);

        if (!$taskId) {
            return [
                'success' => false,
                'task_id' => null,
                'message' => 'Eroare la crearea task-ului.',
            ];
        }

        // Log task creation
        $this->activityLogModel->logActivity(
            $taskId,
            $taskData['created_by'],
            'task_created',
            null,
            null,
            'Task creat: ' . ($taskData['title'] ?? '')
        );

        // Assign users
        foreach ($assigneeIds as $userId) {
            $this->taskModel->assignUser($taskId, $userId);
        }

        if (!empty($assigneeIds)) {
            $this->activityLogModel->logActivity(
                $taskId,
                $taskData['created_by'],
                'users_assigned',
                null,
                implode(',', $assigneeIds),
                'Utilizatori asignați task-ului'
            );
        }

        // Assign departments
        foreach ($departmentIds as $departmentId) {
            $this->taskModel->addDepartment($taskId, $departmentId);
        }

        if (!empty($departmentIds)) {
            $this->activityLogModel->logActivity(
                $taskId,
                $taskData['created_by'],
                'departments_assigned',
                null,
                implode(',', $departmentIds),
                'Departamente asignate task-ului'
            );
        }

        // Get task details for notifications
        $task = $this->taskModel->find($taskId);
        $subdivision = $this->subdivisionModel->find($task['subdivision_id']);
        $contract = null;
        $contractManagerId = null;

        if ($subdivision) {
            $contract = $this->contractModel->find($subdivision['contract_id']);
            if ($contract && $contract['manager_id']) {
                $contractManagerId = $contract['manager_id'];
            }
        }

        $creator = service('session')->get('first_name') . ' ' . service('session')->get('last_name');
        $taskTitle = $task['title'];
        $taskLink = '/tasks/view/' . $taskId;

        // Send notifications to assignees
        foreach ($assigneeIds as $userId) {
            // Don't notify if assignee is the creator
            if ($userId != $taskData['created_by']) {
                $this->notificationService->send(
                    $userId,
                    'info',
                    'Task nou asignat',
                    $creator . ' ți-a asignat un task nou: "' . $taskTitle . '"',
                    $taskLink
                );
            }
        }

        // Send notification to contract manager (if exists and different from creator)
        if ($contractManagerId && $contractManagerId != $taskData['created_by']) {
            $this->notificationService->send(
                $contractManagerId,
                'info',
                'Task nou creat',
                'Un task nou a fost creat în contractul "' . ($contract['name'] ?? '') . '": "' . $taskTitle . '"',
                $taskLink
            );
        }

        return [
            'success' => true,
            'task_id' => $taskId,
            'message' => 'Task-ul a fost creat cu succes.',
        ];
    }

    /**
     * Update task status with workflow validation
     * 
     * @param int $taskId Task ID
     * @param string $newStatus New status
     * @param int $userId User ID making the change
     * @return array ['success' => bool, 'message' => string]
     */
    public function updateTaskStatus(int $taskId, string $newStatus, int $userId): array
    {
        $task = $this->taskModel->find($taskId);

        if (!$task) {
            return [
                'success' => false,
                'message' => 'Task-ul nu există.',
            ];
        }

        $currentStatus = $task['status'];

        // Check if status is the same
        if ($currentStatus === $newStatus) {
            return [
                'success' => false,
                'message' => 'Status-ul este deja ' . $this->getStatusLabel($newStatus) . '.',
            ];
        }

        // Validate workflow transition
        if (!$this->isValidStatusTransition($currentStatus, $newStatus)) {
            return [
                'success' => false,
                'message' => 'Tranziția de la "' . $this->getStatusLabel($currentStatus) . '" la "' . $this->getStatusLabel($newStatus) . '" nu este permisă conform workflow-ului.',
            ];
        }

        // Update status
        $updated = $this->taskModel->update($taskId, ['status' => $newStatus]);

        if (!$updated) {
            return [
                'success' => false,
                'message' => 'Eroare la actualizarea status-ului.',
            ];
        }

        // Log status change
        $this->activityLogModel->logActivity(
            $taskId,
            $userId,
            'status_changed',
            $currentStatus,
            $newStatus,
            'Status schimbat de la "' . $this->getStatusLabel($currentStatus) . '" la "' . $this->getStatusLabel($newStatus) . '"'
        );

        // Get task details for notifications
        $task = $this->taskModel->find($taskId);
        $subdivision = $this->subdivisionModel->find($task['subdivision_id']);
        $contract = null;
        $contractManagerId = null;

        if ($subdivision) {
            $contract = $this->contractModel->find($subdivision['contract_id']);
            if ($contract && $contract['manager_id']) {
                $contractManagerId = $contract['manager_id'];
            }
        }

        $changerName = service('session')->get('first_name') . ' ' . service('session')->get('last_name');
        $taskTitle = $task['title'];
        $taskLink = '/tasks/view/' . $taskId;
        $statusLabel = $this->getStatusLabel($newStatus);

        // Get assignees
        $assignees = $this->taskModel->getAssignees($taskId);
        $assigneeIds = [];
        foreach ($assignees as $assignee) {
            $assigneeIds[] = $assignee['user_id'] ?? $assignee['id'];
        }

        // Send notifications to assignees
        foreach ($assigneeIds as $assigneeId) {
            // Don't notify if assignee is the one making the change
            if ($assigneeId != $userId) {
                $this->notificationService->send(
                    $assigneeId,
                    'info',
                    'Status task actualizat',
                    $changerName . ' a schimbat statusul task-ului "' . $taskTitle . '" la "' . $statusLabel . '"',
                    $taskLink
                );
            }
        }

        // Send notification to contract manager (if exists and different from changer)
        if ($contractManagerId && $contractManagerId != $userId) {
            $this->notificationService->send(
                $contractManagerId,
                'info',
                'Status task actualizat',
                'Statusul task-ului "' . $taskTitle . '" din contractul "' . ($contract['name'] ?? '') . '" a fost schimbat la "' . $statusLabel . '"',
                $taskLink
            );
        }

        return [
            'success' => true,
            'message' => 'Status-ul a fost actualizat cu succes.',
        ];
    }

    /**
     * Add a comment to a task
     * 
     * @param int $taskId Task ID
     * @param string $comment Comment text
     * @param int $userId User ID
     * @return array ['success' => bool, 'comment_id' => int|null, 'message' => string]
     */
    public function addComment(int $taskId, string $comment, int $userId): array
    {
        $task = $this->taskModel->find($taskId);

        if (!$task) {
            return [
                'success' => false,
                'comment_id' => null,
                'message' => 'Task-ul nu există.',
            ];
        }

        // Insert comment using Query Builder directly
        // created_at will use DEFAULT CURRENT_TIMESTAMP from database
        $db = \Config\Database::connect();
        $db->table('task_comments')->insert([
            'task_id' => $taskId,
            'user_id' => $userId,
            'comment' => $comment,
            // created_at is not included - database will use DEFAULT CURRENT_TIMESTAMP
        ]);
        $commentId = $db->insertID();

        if (!$commentId) {
            return [
                'success' => false,
                'comment_id' => null,
                'message' => 'Eroare la adăugarea comentariului.',
            ];
        }

        // Log comment addition (optional, based on requirements)
        // We don't log comments in activity log as per user's request

        // Get task details for notifications
        $subdivision = $this->subdivisionModel->find($task['subdivision_id']);
        $contract = null;
        $contractManagerId = null;

        if ($subdivision) {
            $contract = $this->contractModel->find($subdivision['contract_id']);
            if ($contract && $contract['manager_id']) {
                $contractManagerId = $contract['manager_id'];
            }
        }

        $commenterName = service('session')->get('first_name') . ' ' . service('session')->get('last_name');
        $taskTitle = $task['title'];
        $taskLink = '/tasks/view/' . $taskId;

        // Get assignees
        $assignees = $this->taskModel->getAssignees($taskId);
        $assigneeIds = [];
        foreach ($assignees as $assignee) {
            $assigneeIds[] = $assignee['user_id'] ?? $assignee['id'];
        }

        // Send notifications to assignees
        foreach ($assigneeIds as $assigneeId) {
            // Don't notify if assignee is the commenter
            if ($assigneeId != $userId) {
                $this->notificationService->send(
                    $assigneeId,
                    'info',
                    'Comentariu nou',
                    $commenterName . ' a adăugat un comentariu la task-ul "' . $taskTitle . '"',
                    $taskLink
                );
            }
        }

        // Send notification to contract manager (if exists and different from commenter)
        if ($contractManagerId && $contractManagerId != $userId) {
            $this->notificationService->send(
                $contractManagerId,
                'info',
                'Comentariu nou',
                'Un comentariu nou a fost adăugat la task-ul "' . $taskTitle . '" din contractul "' . ($contract['name'] ?? '') . '"',
                $taskLink
            );
        }

        return [
            'success' => true,
            'comment_id' => $commentId,
            'message' => 'Comentariul a fost adăugat cu succes.',
        ];
    }

    /**
     * Check if status transition is valid according to workflow
     * 
     * @param string $currentStatus Current status
     * @param string $newStatus New status
     * @return bool
     */
    public function isValidStatusTransition(string $currentStatus, string $newStatus): bool
    {
        // Blocked can be set from any status (except completed)
        if ($newStatus === 'blocked' && $currentStatus !== 'completed') {
            return true;
        }

        // Check if current status exists in workflow
        if (!isset($this->statusWorkflow[$currentStatus])) {
            return false;
        }

        // Check if transition is allowed
        return in_array($newStatus, $this->statusWorkflow[$currentStatus]);
    }

    /**
     * Get status label in Romanian
     * 
     * @param string $status Status code
     * @return string Status label
     */
    protected function getStatusLabel(string $status): string
    {
        $labels = [
            'new' => 'Nou',
            'in_progress' => 'În progres',
            'blocked' => 'Blocat',
            'review' => 'În revizie',
            'completed' => 'Finalizat',
        ];

        return $labels[$status] ?? $status;
    }
}

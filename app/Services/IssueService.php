<?php

namespace App\Services;

use App\Models\IssueModel;
use App\Models\IssueCommentModel;
use App\Models\UserModel;
use App\Models\RegionModel;

/**
 * IssueService
 * 
 * Service responsible for issue business logic:
 * - Adding comments with notifications
 */
class IssueService
{
    protected IssueModel $issueModel;
    protected IssueCommentModel $commentModel;
    protected NotificationService $notificationService;
    protected UserModel $userModel;
    protected RegionModel $regionModel;
    protected IssueManagementService $issueManagementService;

    public function __construct()
    {
        $this->issueModel = new IssueModel();
        $this->commentModel = new IssueCommentModel();
        $this->notificationService = new NotificationService();
        $this->userModel = new UserModel();
        $this->regionModel = new RegionModel();
        $this->issueManagementService = new IssueManagementService();
    }

    /**
     * Add a comment to an issue
     * 
     * @param int $issueId Issue ID
     * @param string $comment Comment text
     * @param int $userId User ID
     * @return array ['success' => bool, 'comment_id' => int|null, 'message' => string]
     */
    public function addComment(int $issueId, string $comment, int $userId): array
    {
        $issue = $this->issueModel->find($issueId);

        if (!$issue) {
            return [
                'success' => false,
                'comment_id' => null,
                'message' => 'Problematicea nu există.',
            ];
        }

        // Insert comment using Query Builder directly
        // created_at will use DEFAULT CURRENT_TIMESTAMP from database
        $db = \Config\Database::connect();
        $db->table('issue_comments')->insert([
            'issue_id' => $issueId,
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

        // Get issue details for notifications
        $commenter = $this->userModel->find($userId);
        $commenterName = trim(($commenter['first_name'] ?? '') . ' ' . ($commenter['last_name'] ?? '')) ?: $commenter['email'];
        $issueTitle = $issue['title'];
        $issueLink = '/issues/view/' . $issueId;

        // Get users who can view this issue (to send notifications)
        $notifyUserIds = $this->getNotifyUsersForIssue($issueId, $userId);

        // Send notifications to all users who can view this issue (except the commenter)
        foreach ($notifyUserIds as $notifyUserId) {
            if ($notifyUserId != $userId) {
                $this->notificationService->send(
                    $notifyUserId,
                    'info',
                    'Comentariu nou la problematică',
                    $commenterName . ' a adăugat un comentariu la problematică "' . $issueTitle . '"',
                    $issueLink
                );
            }
        }

        return [
            'success' => true,
            'comment_id' => $commentId,
            'message' => 'Comentariul a fost adăugat cu succes.',
        ];
    }

    /**
     * Get user IDs who should be notified for an issue
     * 
     * @param int $issueId Issue ID
     * @param int $excludeUserId User ID to exclude from notifications
     * @return array User IDs to notify
     */
    protected function getNotifyUsersForIssue(int $issueId, int $excludeUserId): array
    {
        $issue = $this->issueModel->find($issueId);
        
        if (!$issue) {
            return [];
        }

        $notifyUserIds = [];

        // Global issue (region_id NULL) - only Admin
        if ($issue['region_id'] === null) {
            $admins = $this->userModel->where('role_level', 100)->findAll();
            foreach ($admins as $admin) {
                if ($admin['id'] != $excludeUserId) {
                    $notifyUserIds[] = $admin['id'];
                }
            }
            return $notifyUserIds;
        }

        // Region-specific issue - Admin + Director of that region
        // Get all admins
        $admins = $this->userModel->where('role_level', 100)->findAll();
        foreach ($admins as $admin) {
            if ($admin['id'] != $excludeUserId) {
                $notifyUserIds[] = $admin['id'];
            }
        }

        // Get director of the region
        $directors = $this->userModel->where('role_level', 80)
            ->where('region_id', $issue['region_id'])
            ->findAll();
        foreach ($directors as $director) {
            if ($director['id'] != $excludeUserId && !in_array($director['id'], $notifyUserIds)) {
                $notifyUserIds[] = $director['id'];
            }
        }

        return array_unique($notifyUserIds);
    }
}


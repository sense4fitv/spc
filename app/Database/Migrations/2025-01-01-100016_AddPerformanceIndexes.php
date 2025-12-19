<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPerformanceIndexes extends Migration
{
    public function up(): void
    {
        // Add performance indexes as specified in schema
        $this->db->query('CREATE INDEX idx_tasks_status ON tasks(status)');
        $this->db->query('CREATE INDEX idx_tasks_deadline ON tasks(deadline)');
        // idx_notifications_user_unread already created in CreateNotifications migration
    }

    public function down(): void
    {
        $this->db->query('DROP INDEX idx_tasks_status ON tasks');
        $this->db->query('DROP INDEX idx_tasks_deadline ON tasks');
    }
}


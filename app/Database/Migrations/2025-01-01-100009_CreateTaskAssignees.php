<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTaskAssignees extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'task_id' => [
                'type'       => 'INT',
                'unsigned'   => true,
            ],
            'user_id' => [
                'type'       => 'INT',
                'unsigned'   => true,
            ],
        ]);

        $this->forge->addPrimaryKey(['task_id', 'user_id']);
        $this->forge->addKey('task_id');
        $this->forge->addKey('user_id');
        $this->forge->addForeignKey('task_id', 'tasks', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('task_assignees');
    }

    public function down(): void
    {
        $this->forge->dropTable('task_assignees');
    }
}


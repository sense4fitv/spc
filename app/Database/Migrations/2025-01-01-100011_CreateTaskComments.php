<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTaskComments extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'task_id' => [
                'type'       => 'INT',
                'unsigned'   => true,
            ],
            'user_id' => [
                'type'       => 'INT',
                'unsigned'   => true,
            ],
            'comment' => [
                'type' => 'TEXT',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('task_id');
        $this->forge->addKey('user_id');
        $this->forge->addForeignKey('task_id', 'tasks', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('task_comments');
        
        $this->db->query("ALTER TABLE task_comments MODIFY created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP");
    }

    public function down(): void
    {
        $this->forge->dropTable('task_comments');
    }
}


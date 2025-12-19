<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTaskFiles extends Migration
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
            'uploaded_by' => [
                'type'       => 'INT',
                'unsigned'   => true,
            ],
            'filename' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
            ],
            'filepath' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
            ],
            'file_type' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
                'null'       => true,
            ],
            'file_size' => [
                'type'       => 'INT',
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('task_id');
        $this->forge->addKey('uploaded_by');
        $this->forge->addForeignKey('task_id', 'tasks', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('uploaded_by', 'users', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('task_files');
        
        $this->db->query("ALTER TABLE task_files MODIFY created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP");
    }

    public function down(): void
    {
        $this->forge->dropTable('task_files');
    }
}


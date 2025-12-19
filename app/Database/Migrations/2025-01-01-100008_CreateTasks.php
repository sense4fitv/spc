<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTasks extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'subdivision_id' => [
                'type'       => 'INT',
                'unsigned'   => true,
            ],
            'created_by' => [
                'type'       => 'INT',
                'unsigned'   => true,
            ],
            'title' => [
                'type'       => 'VARCHAR',
                'constraint' => '200',
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['new', 'in_progress', 'blocked', 'review', 'completed'],
                'default'    => 'new',
            ],
            'priority' => [
                'type'       => 'ENUM',
                'constraint' => ['low', 'medium', 'high', 'critical'],
                'default'    => 'medium',
            ],
            'deadline' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('subdivision_id');
        $this->forge->addKey('created_by');
        $this->forge->addForeignKey('subdivision_id', 'subdivisions', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('created_by', 'users', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('tasks');
        
        $this->db->query("ALTER TABLE tasks MODIFY created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP");
        $this->db->query("ALTER TABLE tasks MODIFY updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
    }

    public function down(): void
    {
        $this->forge->dropTable('tasks');
    }
}


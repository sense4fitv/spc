<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateContracts extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'region_id' => [
                'type'       => 'INT',
                'unsigned'   => true,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => '200',
            ],
            'contract_number' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
                'null'       => true,
                'comment'    => 'Ex: #CN-2024-001',
            ],
            'client_name' => [
                'type'       => 'VARCHAR',
                'constraint' => '150',
                'null'       => true,
            ],
            'manager_id' => [
                'type'       => 'INT',
                'unsigned'   => true,
                'null'       => true,
                'comment'    => 'Manager Contract',
            ],
            'start_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'end_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'progress_percentage' => [
                'type'       => 'TINYINT',
                'default'    => 0,
                'comment'    => '0-100',
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['planning', 'active', 'on_hold', 'completed'],
                'default'    => 'planning',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('region_id');
        $this->forge->addKey('manager_id');
        $this->forge->addForeignKey('region_id', 'regions', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('manager_id', 'users', 'id', 'CASCADE', 'SET NULL');
        $this->forge->createTable('contracts');
        
        $this->db->query("ALTER TABLE contracts MODIFY created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP");
    }

    public function down(): void
    {
        $this->forge->dropTable('contracts');
    }
}


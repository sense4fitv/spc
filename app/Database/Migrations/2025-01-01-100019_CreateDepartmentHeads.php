<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDepartmentHeads extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'user_id' => [
                'type'       => 'INT',
                'unsigned'   => true,
            ],
            'department_id' => [
                'type'       => 'INT',
                'unsigned'   => true,
            ],
            'region_id' => [
                'type'       => 'INT',
                'unsigned'   => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);

        $this->forge->addPrimaryKey(['user_id', 'department_id', 'region_id']);
        $this->forge->addKey('user_id');
        $this->forge->addKey('department_id');
        $this->forge->addKey('region_id');
        
        // Foreign keys
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('department_id', 'departments', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('region_id', 'regions', 'id', 'CASCADE', 'CASCADE');
        
        $this->forge->createTable('department_heads');

        // Set default for created_at using SQL raw
        $this->db->query("ALTER TABLE department_heads MODIFY created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP");

        // Add UNIQUE constraint on (department_id, region_id) to ensure one head per department/region
        // This must be done via SQL raw as CodeIgniter Forge doesn't support composite unique keys directly
        $this->db->query("ALTER TABLE department_heads ADD UNIQUE KEY unique_dept_head_region (department_id, region_id)");
    }

    public function down(): void
    {
        $this->forge->dropTable('department_heads');
    }
}


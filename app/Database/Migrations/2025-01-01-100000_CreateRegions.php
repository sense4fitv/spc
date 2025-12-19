<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRegions extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => '150',
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'manager_id' => [
                'type'       => 'INT',
                'unsigned'   => true,
                'null'       => true,
                'comment'    => 'Director Regional',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('manager_id');
        $this->forge->createTable('regions');

        // Set default for created_at using SQL raw
        $this->db->query("ALTER TABLE regions MODIFY created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP");
    }

    public function down(): void
    {
        $this->forge->dropTable('regions');
    }
}

<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSubdivisions extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'contract_id' => [
                'type'       => 'INT',
                'unsigned'   => true,
            ],
            'code' => [
                'type'       => 'VARCHAR',
                'constraint' => '20',
                'comment'    => 'Ex: SUB-01',
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => '150',
            ],
            'details' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('contract_id');
        $this->forge->addForeignKey('contract_id', 'contracts', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('subdivisions');
        
        $this->db->query("ALTER TABLE subdivisions MODIFY created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP");
    }

    public function down(): void
    {
        $this->forge->dropTable('subdivisions');
    }
}


<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUsers extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'username' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'comment'    => 'Email-ul va fi folosit ca username',
            ],
            'email' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
            ],
            'password_hash' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
            ],
            'role' => [
                'type'       => 'ENUM',
                'constraint' => ['admin', 'director', 'manager', 'executant', 'auditor'],
            ],
            'role_level' => [
                'type'       => 'TINYINT',
                'unsigned'   => true,
                'comment'    => '100=Admin, 80=Director, 50=Manager, 20=Executant, 10=Auditor',
            ],
            'region_id' => [
                'type'       => 'INT',
                'unsigned'   => true,
                'null'       => true,
                'comment'    => 'NULL = super user (acces la toate regiunile)',
            ],
            'first_name' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'null'       => true,
            ],
            'last_name' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'null'       => true,
            ],
            'active' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
            'avatar_url' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
                'comment'    => 'Pentru UI-ul modern',
            ],
            'created_by' => [
                'type'       => 'INT',
                'unsigned'   => true,
                'null'       => true,
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
        $this->forge->addKey('role_level');
        $this->forge->addKey('region_id');
        $this->forge->addUniqueKey('email');
        
        // Foreign key for region_id - regions table exists at this point
        $this->forge->addForeignKey('region_id', 'regions', 'id', 'CASCADE', 'SET NULL');
        
        // Note: Self-referencing FK (created_by -> users.id) added in separate migration
        $this->forge->createTable('users');
        
        // Set defaults for timestamps using SQL raw
        $this->db->query("ALTER TABLE users MODIFY created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP");
        $this->db->query("ALTER TABLE users MODIFY updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
    }

    public function down(): void
    {
        $this->forge->dropTable('users');
    }
}


<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUserLogins extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type'       => 'INT',
                'unsigned'   => true,
            ],
            'ip_address' => [
                'type'       => 'VARCHAR',
                'constraint' => '45',
            ],
            'user_agent' => [
                'type' => 'TEXT',
            ],
            'login_time' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('user_logins');
        
        $this->db->query("ALTER TABLE user_logins MODIFY login_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP");
    }

    public function down(): void
    {
        $this->forge->dropTable('user_logins');
    }
}


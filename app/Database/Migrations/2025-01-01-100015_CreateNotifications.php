<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateNotifications extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type'       => 'INT',
                'unsigned'   => true,
                'comment'    => 'Destinatarul',
            ],
            'type' => [
                'type'       => 'ENUM',
                'constraint' => ['info', 'success', 'warning', 'error'],
                'default'    => 'info',
            ],
            'title' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
            ],
            'message' => [
                'type' => 'TEXT',
            ],
            'link' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
                'comment'    => 'Link catre task/contract (ex: /tasks/view/102)',
            ],
            'is_read' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->addKey(['user_id', 'is_read']);
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('notifications');
        
        $this->db->query("ALTER TABLE notifications MODIFY created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP");
    }

    public function down(): void
    {
        $this->forge->dropTable('notifications');
    }
}


<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUserDepartments extends Migration
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
        ]);

        $this->forge->addPrimaryKey(['user_id', 'department_id']);
        $this->forge->addKey('user_id');
        $this->forge->addKey('department_id');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('department_id', 'departments', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('user_departments');
    }

    public function down(): void
    {
        $this->forge->dropTable('user_departments');
    }
}


<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTaskDepartments extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'task_id' => [
                'type'       => 'INT',
                'unsigned'   => true,
            ],
            'department_id' => [
                'type'       => 'INT',
                'unsigned'   => true,
            ],
        ]);

        $this->forge->addPrimaryKey(['task_id', 'department_id']);
        $this->forge->addKey('task_id');
        $this->forge->addKey('department_id');
        $this->forge->addForeignKey('task_id', 'tasks', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('department_id', 'departments', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('task_departments');
    }

    public function down(): void
    {
        $this->forge->dropTable('task_departments');
    }
}


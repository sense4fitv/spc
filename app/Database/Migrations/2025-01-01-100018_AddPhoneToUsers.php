<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPhoneToUsers extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('users', [
            'phone' => [
                'type'       => 'VARCHAR',
                'constraint' => '20',
                'null'       => true,
                'comment'    => 'Număr de telefon pentru contact',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('users', ['phone']);
    }
}


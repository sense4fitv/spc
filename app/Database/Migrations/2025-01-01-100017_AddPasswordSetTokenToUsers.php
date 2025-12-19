<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPasswordSetTokenToUsers extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('users', [
            'password_set_token' => [
                'type'       => 'VARCHAR',
                'constraint' => '32',
                'null'       => true,
                'comment'    => 'Token pentru setarea parolei (când active = 0)',
            ],
            'token_expires_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => 'Data expirării token-ului',
            ],
        ]);

        $this->forge->addKey('password_set_token');
    }

    public function down(): void
    {
        $this->forge->dropColumn('users', ['password_set_token', 'token_expires_at']);
    }
}


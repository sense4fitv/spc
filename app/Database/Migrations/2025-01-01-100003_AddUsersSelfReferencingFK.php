<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddUsersSelfReferencingFK extends Migration
{
    public function up(): void
    {
        // Add self-referencing foreign key for created_by after users table is created
        $this->db->query('ALTER TABLE users ADD CONSTRAINT users_created_by_fk FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE');
    }

    public function down(): void
    {
        $this->db->query('ALTER TABLE users DROP FOREIGN KEY users_created_by_fk');
    }
}


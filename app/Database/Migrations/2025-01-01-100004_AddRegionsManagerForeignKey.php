<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRegionsManagerForeignKey extends Migration
{
    public function up(): void
    {
        // Add foreign key for regions.manager_id after users table is created
        $this->forge->addForeignKey('manager_id', 'users', 'id', 'CASCADE', 'SET NULL', 'regions_manager_fk');
    }

    public function down(): void
    {
        $this->forge->dropForeignKey('regions', 'regions_manager_fk');
    }
}


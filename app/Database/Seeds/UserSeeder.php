<?php

namespace App\Database\Seeds;

use App\Models\UserModel;
use CodeIgniter\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $userModel = new UserModel();

        // Verifică dacă user-ul există deja
        $existingUser = $userModel->findByEmail('dorin.pirvu@supercom.ro');
        
        if ($existingUser) {
            echo "User-ul cu email-ul dorin.pirvu@supercom.ro există deja. Se omite inserarea.\n";
            return;
        }

        // Hash-uiește parola folosind metoda CodeIgniter 4 (password_hash)
        $passwordHash = password_hash('SuperCom123!@#', PASSWORD_DEFAULT);

        // Datele user-ului admin
        $userData = [
            'username'      => 'dorin.pirvu@supercom.ro',
            'email'         => 'dorin.pirvu@supercom.ro',
            'password_hash' => $passwordHash,
            'role'          => 'admin',
            'role_level'    => 100,
            'region_id'     => null, // NULL pentru admin (super user)
            'first_name'    => 'Dorin',
            'last_name'     => 'Pirvu',
            'active'        => 1,
            'created_by'    => null, // Prima dată, nu are creator
        ];

        // Inserează user-ul
        if ($userModel->insert($userData)) {
            echo "User-ul admin a fost creat cu succes!\n";
            echo "Email: dorin.pirvu@supercom.ro\n";
            echo "Parolă: SuperCom123!@#\n";
        } else {
            echo "Eroare la crearea user-ului: " . implode(', ', $userModel->errors()) . "\n";
        }
    }
}

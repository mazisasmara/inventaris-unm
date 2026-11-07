<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'Super Administrator',
                'email' => 'superadmin@inventaris-unm.test',
                'identity_number' => null,
                'role' => 'superadmin',
            ],
            [
                'name' => 'Administrator Lab',
                'email' => 'admin@inventaris-unm.test',
                'identity_number' => null,
                'role' => 'admin',
            ],
            [
                'name' => 'Mahasiswa Testing',
                'email' => 'mahasiswa@inventaris-unm.test',
                'identity_number' => '23100001',
                'role' => 'mahasiswa',
            ],
            [
                'name' => 'Dosen Testing',
                'email' => 'dosen@inventaris-unm.test',
                'identity_number' => '198501012010011001',
                'role' => 'dosen',
            ],
        ];

        foreach ($users as $data) {
            $user = User::firstOrNew([
                'email' => $data['email'],
            ]);

            $user->name = $data['name'];
            $user->identity_number = $data['identity_number'];
            $user->role = $data['role'];
            $user->is_active = true;

            if (! $user->exists) {
                $user->password = Hash::make('password');
            }

            $user->save();
        }
    }
}

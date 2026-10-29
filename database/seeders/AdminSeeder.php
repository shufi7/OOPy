<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('AdminSeeder hanya untuk pengembangan lokal. Gunakan akun Admin dengan kredensial tersendiri pada server.');
        }

        $created = DB::transaction(function () {
            $existing = User::where('email', 'admin@gmail.com')->lockForUpdate()->first();
            if ($existing) {
                if ($existing->role !== 'admin') {
                    throw new RuntimeException('Email admin@gmail.com sudah dipakai pengguna biasa. Akun tersebut tidak diubah.');
                }

                return false;
            }

            $admin = new User;
            $admin->name = 'Admin OOPy';
            $admin->email = 'admin@gmail.com';
            // The User password cast hashes this local development credential.
            $admin->password = 'admin123';
            $admin->role = 'admin';
            $admin->save();

            return true;
        }, 3);

        $this->command?->info($created
            ? 'Akun Admin lokal admin@gmail.com berhasil dibuat.'
            : 'Akun Admin admin@gmail.com sudah tersedia. Password dan data akun tetap dipertahankan.');
    }
}

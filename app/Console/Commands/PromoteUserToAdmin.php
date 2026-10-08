<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class PromoteUserToAdmin extends Command
{
    protected $signature = 'oopy:promote-admin {email : Email akun yang sudah terdaftar}';

    protected $description = 'Promosikan akun OOPy yang sudah ada menjadi admin melalui terminal tepercaya';

    public function handle(): int
    {
        $email = $this->argument('email');
        if (Validator::make(['email' => $email], ['email' => ['required', 'string', 'email', 'max:255']])->fails()) {
            $this->error('Masukkan alamat email yang valid.');

            return self::FAILURE;
        }

        $user = User::where('email', $email)->first();
        if (! $user) {
            $this->error('Akun tidak ditemukan. Daftarkan akun terlebih dahulu.');

            return self::FAILURE;
        }

        if ($user->role === 'admin') {
            $this->info('Akun ini sudah memiliki role admin.');

            return self::SUCCESS;
        }

        $this->line('Akun: '.$user->name.' <'.$user->email.'>');
        if (! $this->confirm('Promosikan akun ini menjadi admin?', false)) {
            $this->info('Promosi dibatalkan.');

            return self::FAILURE;
        }

        $user->role = 'admin';
        $user->save();
        $this->info('Akun berhasil dipromosikan menjadi admin.');

        return self::SUCCESS;
    }
}

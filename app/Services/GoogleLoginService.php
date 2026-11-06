<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Two\User as GoogleUser;

class GoogleLoginService
{
    public function resolve(GoogleUser $profile): User
    {
        $id = $profile->getId();
        $email = $profile->getEmail();
        if (! is_string($id) || $id === '' || strlen($id) > 255
            || ! is_string($email) || strlen($email) > 255 || ! filter_var($email, FILTER_VALIDATE_EMAIL)
            || ($profile->user['email_verified'] ?? false) !== true) {
            throw ValidationException::withMessages(['google' => 'Akun Google harus memiliki email yang terverifikasi.']);
        }
        $email = Str::lower($email);

        return DB::transaction(function () use ($profile, $id, $email) {
            $linked = User::where('google_id', $id)->lockForUpdate()->first();
            if ($linked) {
                return $linked;
            }

            $user = User::whereRaw('LOWER(email) = ?', [$email])->lockForUpdate()->first();
            if ($user) {
                // Google only remains authoritative for Gmail or verified Workspace addresses.
                $authoritative = Str::endsWith($email, '@gmail.com') || ! empty($profile->user['hd']);
                if ($user->google_id !== null || $user->role !== 'user' || ! $authoritative) {
                    throw ValidationException::withMessages(['google' => 'Email ini sudah terdaftar. Silakan masuk menggunakan password akun OOPy.']);
                }
            } else {
                $name = $profile->getName();
                $user = new User([
                    'name' => is_string($name) && trim($name) !== '' ? Str::limit(trim($name), 255, '') : Str::before($email, '@'),
                    'email' => $email,
                    'password' => Str::password(64),
                ]);
                $user->role = 'user';
            }

            $user->google_id = $id;
            $user->email_verified_at ??= now();
            $user->save();

            return $user;
        }, 3);
    }
}

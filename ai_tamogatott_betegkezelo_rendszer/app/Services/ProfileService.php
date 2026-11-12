<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ProfileService
{
    public function updateProfile(User $user, array $data): User
    {
        $user->update($data);
        ActivityLogger::log('Profil adatok módosítva');
        return $user;
    }

    public function updatePassword(User $user, string $currentPassword, string $newPassword): void
    {
        if (!Hash::check($currentPassword, $user->password_hash)) {
            throw ValidationException::withMessages([
                'current_password' => 'A megadott jelenlegi jelszó helytelen.',
            ]);
        }

        $user->update(['password_hash' => Hash::make($newPassword)]);
        ActivityLogger::log('Jelszó megváltoztatva.');
    }
}

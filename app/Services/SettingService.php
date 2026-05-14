<?php
namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class SettingService
{
    /*
    |--------------------------------------------------------------------------
    | Update Password
    |--------------------------------------------------------------------------
    | Checks current password and updates new password for logged-in user.
    */
    public function updatePassword(string $currentPassword, string $newPassword): array
    {
        $user = Auth::user();

        if (! $user) {
            return [
                'status'  => false,
                'message' => 'User session not found. Please login again.',
            ];
        }

        if (! Hash::check($currentPassword, $user->password)) {
            return [
                'status'  => false,
                'message' => 'Current password is incorrect.',
            ];
        }

        $user->update([
            'password' => Hash::make($newPassword),
        ]);

        return [
            'status'  => true,
            'message' => 'Password updated successfully.',
        ];
    }
}

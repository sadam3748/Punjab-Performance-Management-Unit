<?php
namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    /*
    |--------------------------------------------------------------------------
    | Attempt Login
    |--------------------------------------------------------------------------
    | Supports login by username or email.
    */
    public function attemptLogin(string $login, string $password, bool $remember = false): array
    {
        $user = User::query()
            ->with('role')
            ->where('username', $login)
            ->orWhere('email', $login)
            ->first();

        if (! $user) {
            return [
                'status'  => false,
                'message' => 'Invalid username/email or password.',
            ];
        }

        if (! Hash::check($password, $user->password)) {
            return [
                'status'  => false,
                'message' => 'Invalid username/email or password.',
            ];
        }

        if (! $user->is_active) {
            return [
                'status'  => false,
                'message' => 'Your account is inactive. Please contact administrator.',
            ];
        }

        if (! $user->role || ! $user->role->is_active) {
            return [
                'status'  => false,
                'message' => 'Your assigned role is inactive. Please contact administrator.',
            ];
        }

        Auth::login($user, $remember);

        $user->update([
            'last_login_at' => now(),
        ]);

        return [
            'status'  => true,
            'message' => 'Login successful.',
            'user'    => $user,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Logout
    |--------------------------------------------------------------------------
    */
    public function logout(): void
    {
        Auth::logout();
    }
}

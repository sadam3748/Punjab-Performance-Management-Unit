<?php
namespace App\Http\Controllers;

use App\Services\AuthService;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    protected AuthService $authService;

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    | AuthService handles login checking and session authentication.
    */
    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /*
    |--------------------------------------------------------------------------
    | Show Login Page
    |--------------------------------------------------------------------------
    | Displays the PPMF login form.
    */
    public function showLogin()
    {
        return view('auth.login');
    }

    /*
    |--------------------------------------------------------------------------
    | Login User
    |--------------------------------------------------------------------------
    | Allows login with username or email.
    */
    public function login(Request $request)
    {
        $validated = $request->validate([
            'login'    => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'login.required'    => 'Please enter username or email.',
            'password.required' => 'Please enter password.',
        ]);

        $result = $this->authService->attemptLogin(
            $validated['login'],
            $validated['password'],
            $request->boolean('remember')
        );

        if (! $result['status']) {
            return back()
                ->withErrors([
                    'login' => $result['message'],
                ])
                ->withInput($request->only('login'));
        }

        $request->session()->regenerate();

        return redirect()
            ->route('dashboard')
            ->with('success', 'Login successful.');
    }

    /*
    |--------------------------------------------------------------------------
    | Logout User
    |--------------------------------------------------------------------------
    | Logs out user and clears session.
    */
    public function logout(Request $request)
    {
        $this->authService->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with('success', 'Logged out successfully.');
    }
}

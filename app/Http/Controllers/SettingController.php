<?php
namespace App\Http\Controllers;

use App\Services\SettingService;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    protected SettingService $settingService;

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    | SettingService handles password update and future setting logic.
    */
    public function __construct(SettingService $settingService)
    {
        $this->settingService = $settingService;
    }

    /*
    |--------------------------------------------------------------------------
    | Settings Page
    |--------------------------------------------------------------------------
    | Shows general system/user settings page.
    */
    public function index()
    {
        return view('settings.index');
    }

    /*
    |--------------------------------------------------------------------------
    | Change Password Page
    |--------------------------------------------------------------------------
    | Shows logged-in user password change form.
    */
    public function changePassword()
    {
        return view('settings.change-password');
    }

    /*
    |--------------------------------------------------------------------------
    | Update Password
    |--------------------------------------------------------------------------
    | Validates and updates logged-in user password.
    */
    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'new_password'     => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'current_password.required' => 'Please enter current password.',
            'new_password.required'     => 'Please enter new password.',
            'new_password.min'          => 'New password must be at least 6 characters.',
            'new_password.confirmed'    => 'New password confirmation does not match.',
        ]);

        $result = $this->settingService->updatePassword(
            $validated['current_password'],
            $validated['new_password']
        );

        if (! $result['status']) {
            return back()->withErrors([
                'current_password' => $result['message'],
            ]);
        }

        return redirect()
            ->route('settings.change-password')
            ->with('success', 'Password updated successfully.');
    }

    /*
    |--------------------------------------------------------------------------
    | System Manual Page
    |--------------------------------------------------------------------------
    | Shows static/help manual page.
    */
    public function systemManual()
    {
        return view('settings.system-manual');
    }
}

<?php
namespace App\Http\Controllers;

use App\Services\UserService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    protected UserService $userService;

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    | User-related query and save logic is handled inside UserService.
    */
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /*
    |--------------------------------------------------------------------------
    | User List
    |--------------------------------------------------------------------------
    | Shows all users with role and area filters.
    */
    public function index(Request $request)
    {
        $filters = $request->only([
            'role_id',
            'division_id',
            'district_id',
            'tehsil_id',
            'is_active',
            'search',
        ]);

        $users      = $this->userService->getUserList($filters);
        $filterData = $this->userService->getFormData();

        return view('users.index', [
            'users'     => $users,
            'roles'     => $filterData['roles'],
            'divisions' => $filterData['divisions'],
            'districts' => $filterData['districts'],
            'tehsils'   => $filterData['tehsils'],
            'filters'   => $filters,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Create User Form
    |--------------------------------------------------------------------------
    */
    public function create()
    {
        $formData = $this->userService->getFormData();

        return view('users.create', [
            'roles'     => $formData['roles'],
            'divisions' => $formData['divisions'],
            'districts' => $formData['districts'],
            'tehsils'   => $formData['tehsils'],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Store User
    |--------------------------------------------------------------------------
    | Creates a new portal/mobile API user.
    */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'role_id'     => ['required', 'exists:roles,id'],
            'division_id' => ['nullable', 'exists:divisions,id'],
            'district_id' => ['nullable', 'exists:districts,id'],
            'tehsil_id'   => ['nullable', 'exists:tehsils,id'],

            'name'        => ['required', 'string', 'max:255'],
            'username'    => ['required', 'string', 'max:100', 'unique:users,username'],
            'email'       => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'password'    => ['required', 'string', 'min:6'],

            'phone'       => ['nullable', 'string', 'max:30'],
            'designation' => ['nullable', 'string', 'max:255'],
            'is_active'   => ['nullable', 'boolean'],
        ]);

        $this->userService->createUser($validated);

        return redirect()
            ->route('users.index')
            ->with('success', 'User created successfully.');
    }

    /*
    |--------------------------------------------------------------------------
    | Edit User Form
    |--------------------------------------------------------------------------
    */
    public function edit($id)
    {
        $user     = $this->userService->getUserById($id);
        $formData = $this->userService->getFormData();

        return view('users.edit', [
            'user'      => $user,
            'roles'     => $formData['roles'],
            'divisions' => $formData['divisions'],
            'districts' => $formData['districts'],
            'tehsils'   => $formData['tehsils'],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Update User
    |--------------------------------------------------------------------------
    */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'role_id'     => ['required', 'exists:roles,id'],
            'division_id' => ['nullable', 'exists:divisions,id'],
            'district_id' => ['nullable', 'exists:districts,id'],
            'tehsil_id'   => ['nullable', 'exists:tehsils,id'],

            'name'        => ['required', 'string', 'max:255'],
            'username'    => ['required', 'string', 'max:100', 'unique:users,username,' . $id],
            'email'       => ['nullable', 'email', 'max:255', 'unique:users,email,' . $id],
            'password'    => ['nullable', 'string', 'min:6'],

            'phone'       => ['nullable', 'string', 'max:30'],
            'designation' => ['nullable', 'string', 'max:255'],
            'is_active'   => ['nullable', 'boolean'],
        ]);

        $this->userService->updateUser($id, $validated);

        return redirect()
            ->route('users.index')
            ->with('success', 'User updated successfully.');
    }

    /*
    |--------------------------------------------------------------------------
    | Toggle User Status
    |--------------------------------------------------------------------------
    | Active user can login. Inactive user should be blocked from login.
    */
    public function toggleStatus($id)
    {
        $this->userService->toggleUserStatus($id);

        return redirect()
            ->route('users.index')
            ->with('success', 'User status updated successfully.');
    }
}

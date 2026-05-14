<?php
namespace App\Services;

use App\Models\District;
use App\Models\Division;
use App\Models\Role;
use App\Models\Tehsil;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserService
{
    /*
    |--------------------------------------------------------------------------
    | User List
    |--------------------------------------------------------------------------
    | Returns users with role and administrative area relations.
    */
    public function getUserList(array $filters)
    {
        return User::query()
            ->with([
                'role:id,name,slug,scope_level',
                'division:id,name',
                'district:id,name',
                'tehsil:id,name',
            ])
            ->when(! empty($filters['role_id']), function ($q) use ($filters) {
                $q->where('role_id', $filters['role_id']);
            })
            ->when(! empty($filters['division_id']), function ($q) use ($filters) {
                $q->where('division_id', $filters['division_id']);
            })
            ->when(! empty($filters['district_id']), function ($q) use ($filters) {
                $q->where('district_id', $filters['district_id']);
            })
            ->when(! empty($filters['tehsil_id']), function ($q) use ($filters) {
                $q->where('tehsil_id', $filters['tehsil_id']);
            })
            ->when(isset($filters['is_active']) && $filters['is_active'] !== '', function ($q) use ($filters) {
                $q->where('is_active', (bool) $filters['is_active']);
            })
            ->when(! empty($filters['search']), function ($q) use ($filters) {
                $search = $filters['search'];

                $q->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'ILIKE', "%{$search}%")
                        ->orWhere('username', 'ILIKE', "%{$search}%")
                        ->orWhere('email', 'ILIKE', "%{$search}%")
                        ->orWhere('phone', 'ILIKE', "%{$search}%")
                        ->orWhere('designation', 'ILIKE', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();
    }

    /*
    |--------------------------------------------------------------------------
    | Get User By ID
    |--------------------------------------------------------------------------
    */
    public function getUserById($id): User
    {
        return User::with([
            'role',
            'division',
            'district',
            'tehsil',
        ])->findOrFail($id);
    }

    /*
    |--------------------------------------------------------------------------
    | Create User
    |--------------------------------------------------------------------------
    | Used by Super Admin / PMRU to create web portal or mobile API user.
    */
    public function createUser(array $data): User
    {
        $data = $this->normalizeAreaAccess($data);

        return User::create([
            'role_id'     => $data['role_id'],
            'division_id' => $data['division_id'] ?? null,
            'district_id' => $data['district_id'] ?? null,
            'tehsil_id'   => $data['tehsil_id'] ?? null,

            'name'        => $data['name'],
            'username'    => $data['username'],
            'email'       => $data['email'] ?? null,
            'password'    => Hash::make($data['password']),

            'phone'       => $data['phone'] ?? null,
            'designation' => $data['designation'] ?? null,
            'is_active'   => $data['is_active'] ?? true,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Update User
    |--------------------------------------------------------------------------
    | Password is updated only if new password is entered.
    */
    public function updateUser($id, array $data): User
    {
        $user = User::findOrFail($id);

        $data = $this->normalizeAreaAccess($data);

        $updateData = [
            'role_id'     => $data['role_id'],
            'division_id' => $data['division_id'] ?? null,
            'district_id' => $data['district_id'] ?? null,
            'tehsil_id'   => $data['tehsil_id'] ?? null,

            'name'        => $data['name'],
            'username'    => $data['username'],
            'email'       => $data['email'] ?? null,

            'phone'       => $data['phone'] ?? null,
            'designation' => $data['designation'] ?? null,
            'is_active'   => $data['is_active'] ?? false,
        ];

        if (! empty($data['password'])) {
            $updateData['password'] = Hash::make($data['password']);
        }

        $user->update($updateData);

        return $user;
    }

    /*
    |--------------------------------------------------------------------------
    | Toggle Status
    |--------------------------------------------------------------------------
    */
    public function toggleUserStatus($id): User
    {
        $user = User::findOrFail($id);

        $user->update([
            'is_active' => ! $user->is_active,
        ]);

        return $user;
    }

    /*
    |--------------------------------------------------------------------------
    | Normalize Area Access
    |--------------------------------------------------------------------------
    | This keeps user access clean according to role scope.
    |
    | Punjab level:
    |   Super Admin, Chief Secretary, PMRU User
    |   division_id, district_id, tehsil_id = null
    |
    | Division level:
    |   Commissioner
    |   division_id required, district_id/tehsil_id = null
    |
    | District level:
    |   DC
    |   district_id required, tehsil_id = null
    |
    | Tehsil level:
    |   AC / Field User
    |   tehsil_id required
    */
    private function normalizeAreaAccess(array $data): array
    {
        $role = Role::find($data['role_id']);

        if (! $role) {
            return $data;
        }

        if ($role->scope_level === 'punjab') {
            $data['division_id'] = null;
            $data['district_id'] = null;
            $data['tehsil_id']   = null;

            return $data;
        }

        if ($role->scope_level === 'division') {
            $data['district_id'] = null;
            $data['tehsil_id']   = null;

            return $data;
        }

        if ($role->scope_level === 'district') {
            $district = ! empty($data['district_id'])
                ? District::find($data['district_id'])
                : null;

            if ($district) {
                $data['division_id'] = $district->division_id;
            }

            $data['tehsil_id'] = null;

            return $data;
        }

        if ($role->scope_level === 'tehsil') {
            $tehsil = ! empty($data['tehsil_id'])
                ? Tehsil::with('district')->find($data['tehsil_id'])
                : null;

            if ($tehsil && $tehsil->district) {
                $data['district_id'] = $tehsil->district_id;
                $data['division_id'] = $tehsil->district->division_id;
            }

            return $data;
        }

        return $data;
    }

    /*
    |--------------------------------------------------------------------------
    | Form Dropdown Data
    |--------------------------------------------------------------------------
    */
    public function getFormData(): array
    {
        return [
            'roles'     => Role::where('is_active', true)
                ->orderBy('id')
                ->get(['id', 'name', 'slug', 'scope_level']),

            'divisions' => Division::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),

            'districts' => District::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'division_id', 'name']),

            'tehsils'   => Tehsil::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'district_id', 'name']),
        ];
    }
}

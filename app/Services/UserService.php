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
    public function getFormData(): array
    {
        return [
            'roles'     => Role::orderBy('name')->get(),
            'divisions' => Division::orderBy('name')->get(),
            'districts' => District::orderBy('name')->get(),
            'tehsils'   => Tehsil::orderBy('name')->get(),
        ];
    }

    public function getUserList(array $filters)
    {
        return User::with(['role', 'division', 'district', 'tehsil'])
            ->when(! empty($filters['role_id']), function ($query) use ($filters) {
                $query->where('role_id', $filters['role_id']);
            })
            ->when(! empty($filters['division_id']), function ($query) use ($filters) {
                $query->where('division_id', $filters['division_id']);
            })
            ->when(! empty($filters['district_id']), function ($query) use ($filters) {
                $query->where('district_id', $filters['district_id']);
            })
            ->when(! empty($filters['tehsil_id']), function ($query) use ($filters) {
                $query->where('tehsil_id', $filters['tehsil_id']);
            })
            ->when($filters['is_active'] !== null && $filters['is_active'] !== '', function ($query) use ($filters) {
                $query->where('is_active', $filters['is_active']);
            })
            ->when(! empty($filters['search']), function ($query) use ($filters) {
                $search = $filters['search'];

                $query->where(function ($q) use ($search) {
                    $q->where('name', 'ILIKE', "%{$search}%")
                        ->orWhere('username', 'ILIKE', "%{$search}%")
                        ->orWhere('email', 'ILIKE', "%{$search}%")
                        ->orWhere('phone', 'ILIKE', "%{$search}%")
                        ->orWhere('designation', 'ILIKE', "%{$search}%");
                });
            })
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();
    }

    public function getUserById(int $id): User
    {
        return User::with(['role', 'division', 'district', 'tehsil'])
            ->findOrFail($id);
    }

    public function createUser(array $data): User
    {
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

    public function updateUser(int $id, array $data): User
    {
        $user = User::findOrFail($id);

        $payload = [
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
            $payload['password'] = Hash::make($data['password']);
        }

        $user->update($payload);

        return $user;
    }

    public function toggleUserStatus(int $id): User
    {
        $user = User::findOrFail($id);

        $user->update([
            'is_active' => ! $user->is_active,
        ]);

        return $user;
    }
}

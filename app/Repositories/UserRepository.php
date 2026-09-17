<?php

namespace App\Repositories;

use App\DTO\Create\InstallAdminCreateData;
use App\DTO\Create\UserCreateData;
use App\DTO\Update\UserUpdateData;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class UserRepository extends BaseRepository
{
    /**
     * Initialize administrator persistence with the user model.
     */
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    /**
     * Return user names and logins for selection fields, optionally restricted by role.
     */
    public function getForSelection(?string $role = null): Collection
    {
        return $this->model->newQuery()
            ->when($role !== null, fn ($query) => $query->where('role', $role))
            ->orderBy('name')
            ->get(['id', 'name', 'login']);
    }

    /**
     * Create an administrator from normalized user data.
     *
     * @param UserCreateData $data
     * @return User
     */
    public function createWithMapping(UserCreateData $data): User
    {
        return $this->create($this->mapping($data->toArray()));
    }

    /**
     * Create the initial administrator with a securely hashed installation password.
     *
     * @param InstallAdminCreateData $data
     * @return User
     */
    public function createAdminFromInstall(InstallAdminCreateData $data): User
    {
        return $this->create([
            'name' => $data->name,
            'login' => $data->login,
            'role' => $data->role,
            'password' => Hash::make($data->password),
        ]);
    }

    /**
     * Update an administrator with normalized profile and credential data.
     *
     * @param int $id
     * @param UserUpdateData $data
     * @return bool
     */
    public function update(int $id, UserUpdateData $data): bool
    {
        return DB::transaction(function () use ($id, $data): bool {
            $user = User::query()->lockForUpdate()->find($id);
            if (!$user) {
                return false;
            }

            if ($user->role !== $data->role) {
                // Memberships belong to a role and must be assigned again after a role change.
                $user->projects()->detach();
            }

            return $user->fill($this->mapping($data->toArray()))->save();
        });
    }

    /**
     * Restrict user input to fillable attributes and hash a supplied password.
     *
     * @param array $data
     * @return array
     */
    private function mapping(array $data): array
    {
        $mapped = collect($data)
            ->only($this->model->getFillable())
            ->toArray();

        if (empty($mapped['password'])) {
            unset($mapped['password']);
        } else {
            $mapped['password'] = Hash::make($mapped['password']);
        }

        return $mapped;
    }
}

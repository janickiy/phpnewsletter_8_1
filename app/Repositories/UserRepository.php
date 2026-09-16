<?php

namespace App\Repositories;

use App\DTO\Create\InstallAdminCreateData;
use App\DTO\Create\UserCreateData;
use App\DTO\Update\UserUpdateData;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

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
        return $this->updateModel($id, $this->mapping($data->toArray()));
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

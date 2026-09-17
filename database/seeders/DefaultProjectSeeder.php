<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;

class DefaultProjectSeeder extends Seeder
{
    public const NAME = 'Основной проект';

    public function run(): void
    {
        $administrator = User::query()->where('role', User::ROLE_ADMIN)->orderBy('id')->first();
        if ($administrator) {
            self::forAdministrator($administrator);
        }
    }

    public static function forAdministrator(User $administrator): Project
    {
        return Project::query()->firstOrCreate(
            ['owner_id' => $administrator->id, 'name' => self::NAME],
            ['status' => true]
        );
    }
}

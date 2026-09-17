<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $administrator = User::query()->where('role', User::ROLE_ADMIN)->orderBy('id')->first();
        if (!$administrator) {
            return;
        }
        $project = DefaultProjectSeeder::forAdministrator($administrator);

        foreach ($this->categories() as $name) {
            Category::query()->firstOrCreate(['project_id' => $project->id, 'name' => $name]);
        }
    }

    /**
     * Build English default demo categories.
     *
     * @return array<int, string>
     */
    private function categories(): array
    {
        return ['Category 1', 'Category 2', 'Category 3'];
    }
}

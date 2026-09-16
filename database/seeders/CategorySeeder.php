<?php

namespace Database\Seeders;

use App\Models\Category;
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
        foreach ($this->categories() as $name) {
            Category::query()->firstOrCreate(['name' => $name]);
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

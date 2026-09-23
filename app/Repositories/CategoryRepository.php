<?php

namespace App\Repositories;

use App\DTO\Create\CategoryCreateData;
use App\DTO\Update\CategoryUpdateData;
use App\Models\Category;
use App\Services\ProjectAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CategoryRepository extends BaseRepository
{
    /**
     * Initialize category persistence with the category model.
     * @param Category $model
     */
    public function __construct(Category $model)
    {
        parent::__construct($model);
    }

    /**
     * Create a subscriber category from validated category data.
     *
     * @param CategoryCreateData $data
     * @return Category
     */
    public function add(CategoryCreateData $data): Category
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        return $this->create($data->toArray());
    }

    /**
     * Update an existing subscriber category with validated category data.
     *
     * @param int $id
     * @param CategoryUpdateData $data
     * @return bool
     */
    public function update(int $id, CategoryUpdateData $data): bool
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $category = $this->find($id);

        return $category ? $category->fill($this->mapping($data->toArray()))->save() : false;
    }

    public function all(): Collection
    {
        return $this->managedCategories()->get();
    }

    public function find(int $id): ?Category
    {
        return $this->managedCategories()->find($id);
    }

    public function delete(int $id): bool
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $category = $this->find($id);

        return $category ? (bool) $category->delete() : false;
    }

    /**
     * Build alphabetically ordered category options keyed by identifier.
     *
     * @return array
     */
    public function getOption(): array
    {
        return $this->managedCategories()->orderBy('name')->pluck('name', 'id')->all();
    }

    private function managedCategories(): Builder
    {
        return ProjectAccess::categories($this->model->newQuery(), 'manage');
    }

    /**
     * Restrict category input to attributes allowed for mass assignment.
     */
    private function mapping(array $data): array
    {
        return collect($data)
            ->only($this->model->getFillable())
            ->all();
    }
}

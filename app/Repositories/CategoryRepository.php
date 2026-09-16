<?php

namespace App\Repositories;

use App\DTO\Create\CategoryCreateData;
use App\DTO\Update\CategoryUpdateData;
use App\Models\Category;

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
        return $this->updateModel($id, $this->mapping($data->toArray()));
    }

    /**
     * Build alphabetically ordered category options keyed by identifier.
     *
     * @return array
     */
    public function getOption(): array
    {
        return $this->model->orderBy('name')->get()->pluck('name', 'id')->toArray();
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

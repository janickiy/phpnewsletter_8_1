<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

abstract class BaseRepository implements RepositoryInterface
{

    /**
     * Initialize the repository with the Eloquent model it manages.
     *
     * @param Model $model
     */
    public function __construct(protected Model $model)
    {
    }

    /**
     * Create a model record from the supplied persistence attributes.
     *
     * @param array $data
     * @return Builder|Model
     */
    public function create(array $data): Builder|Model
    {
        return $this->model->create($data);
    }

    /**
     * Fill and save an existing model by identifier, returning false when it is absent.
     */
    protected function updateModel(int $id, array $data): bool
    {
        $model = $this->model->find($id);

        if ($model) {
            return $model->fill($data)->save();
        }

        return false;
    }

    /**
     * Return every record managed by this repository.
     *
     * @return Collection
     */
    public function all(): Collection
    {
        return $this->model->all();
    }

    /**
     * Find one managed model by its primary key.
     *
     * @param int $id
     * @return Model|null
     */
    public function find(int $id): ?Model
    {
        return $this->model->find($id);
    }

    /**
     * Delete one managed model by identifier when it exists.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $model = $this->model->find($id);
        if ($model) {
            $model->delete();
            return true;
        }
        return false;
    }

    /**
     * Delete every record from the managed model table without resetting its sequence.
     */
    public function deleteAll(): void
    {
        $this->model->query()->delete();
    }

    /**
     * Truncate the managed model table and reset its sequence.
     */
    public function truncate(): void
    {
        $this->model->truncate();
    }

}

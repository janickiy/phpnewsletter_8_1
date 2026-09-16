<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

interface RepositoryInterface
{
    /**
     * Return every record managed by the repository.
     */
    public function all(): Collection;

    /**
     * Find one managed model by its primary key.
     */
    public function find(int $id): ?Model;

    /**
     * Create a managed model from persistence attributes.
     */
    public function create(array $data): Builder|Model;

    /**
     * Delete one managed model by its primary key.
     */
    public function delete(int $id): bool;

    /**
     * Delete all records managed by the repository.
     */
    public function deleteAll(): void;

    /**
     * Truncate the managed table and reset its sequence.
     */
    public function truncate(): void;
}

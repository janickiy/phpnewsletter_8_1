<?php

namespace App\Repositories;

use App\DTO\Create\SmtpCreateData;
use App\DTO\Update\SmtpUpdateData;
use App\Models\Smtp;

class SmtpRepository extends BaseRepository
{
    /**
     * Initialize SMTP persistence with the SMTP model.
     */
    public function __construct(Smtp $model)
    {
        parent::__construct($model);
    }

    /**
     * Update an SMTP server with normalized connection settings.
     *
     * @param int $id
     * @param SmtpUpdateData $data
     * @return bool
     */
    public function update(int $id, SmtpUpdateData $data): bool
    {
        return $this->updateModel($id, $this->mapping($data->toArray()));
    }

    /**
     * Create an SMTP server from normalized connection settings.
     */
    public function createWithMapping(SmtpCreateData $data): Smtp
    {
        return $this->create($this->mapping($data->toArray()));
    }

    /**
     * Activate, deactivate, or delete the selected SMTP servers.
     *
     * @param int $action
     * @param array $ids
     * @return void
     */
    public function updateStatus(int $action, array $ids): void
    {
        $ids = array_filter($ids, static fn ($id) => is_numeric($id));

        if (empty($ids)) {
            return;
        }

        match ($action) {
            0, 1 => $this->model
                ->whereIn('id', $ids)
                ->update(['active' => $action]),

            2 => $this->model
                ->whereIn('id', $ids)
                ->delete(),

            default => null,
        };
    }

    /**
     * Normalize SMTP numeric fields and preserve the existing password when omitted.
     *
     * @param array $data
     * @return array
     */
    private function mapping(array $data): array
    {
        $mapped = collect($data)
            ->only($this->model->getFillable())
            ->map(function ($value, $key) {
                return match ($key) {
                    'port', 'timeout' => !is_null($value) ? (int) $value : null,
                    default => $value,
                };
            })
            ->toArray();

        if (array_key_exists('password', $mapped) && empty($mapped['password'])) {
            unset($mapped['password']);
        }

        return $mapped;
    }
}

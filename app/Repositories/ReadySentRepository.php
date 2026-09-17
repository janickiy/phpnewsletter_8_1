<?php

namespace App\Repositories;

use App\DTO\Create\ReadySentCreateData;
use App\DTO\Update\ReadySentReadData;
use App\Models\ReadySent;
use App\Services\ProjectAccess;

class ReadySentRepository extends BaseRepository
{
    /**
     * Initialize delivery-log persistence with the ready-sent model.
     */
    public function __construct(ReadySent $model)
    {
        parent::__construct($model);
    }

    /**
     * Record one message delivery attempt and its result.
     *
     * @param ReadySentCreateData $data
     * @return ReadySent
     */
    public function add(ReadySentCreateData $data): ReadySent
    {
        return ReadySent::query()->create([
            'project_id' => $data->projectId,
            'subscriber_id' => $data->subscriberId,
            'template_id' => $data->templateId,
            'success' => $data->success,
            'schedule_id' => $data->scheduleId,
            'log_id' => $data->logId,
            'email' => $data->email,
            'template' => $data->template,
            'errorMsg' => $data->errorMsg,
            'readMail' => $data->readMail,
        ]);
    }

    /**
     * Mark matching subscriber and template delivery records as read.
     *
     * @param ReadySentReadData $data
     * @return bool
     */
    public function markAsRead(ReadySentReadData $data): bool
    {
        return $this->model->query()
            ->where('template_id', $data->templateId)
            ->where('subscriber_id', $data->subscriberId)
            ->update([
            'readMail' => $data->readMail,
        ]);
    }

    /**
     * Count delivery records in a mailing batch with the requested success state.
     *
     * @param int $logId
     * @param int $success
     * @return int
     */
    public function countStatus(int $logId, int $success): int
    {
        return ProjectAccess::scope(ReadySent::query(), 'manage')->where('log_id', $logId)->where('success', $success)->count();
    }

    /**
     * Build a compact status feed from the most recent logged delivery attempts.
     *
     * @param int $limit
     * @return array|false[]
     */
    public function logOnline(int $limit = 5, ?int $logId = null): array
    {
        $readySent = ProjectAccess::scope(ReadySent::query(), 'manage')
            ->when($logId, fn ($query) => $query->where('log_id', $logId))
            ->orderBy('id', 'desc')
            ->where('log_id', '>', 0)
            ->limit($limit)
            ->get();

        if (!$readySent) {
            return ['result' => false];
        }

        $rows = [];

        foreach ($readySent as $row) {
            $rows[] = [
                'subscriber_id' => $row->subscriber_id,
                "email" => $row->email,
                "status" => $row->success === 1 ? __('frontend.str.sent') : __('frontend.str.not_sent'),
            ];
        }

        return [
            'result' => true,
            'item' => $rows
        ];
    }
}

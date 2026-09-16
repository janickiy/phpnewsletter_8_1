<?php

namespace App\Repositories;

use App\DTO\Create\SubscriberCreateData;
use App\DTO\Update\SubscriberUpdateData;
use App\Models\Subscribers;
use App\Models\Subscriptions;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;

class SubscriberRepository extends BaseRepository
{
    /**
     * Initialize subscriber persistence and its transaction manager.
     */
    public function __construct(
        Subscribers                      $model,
        private readonly DatabaseManager $database
    )
    {
        parent::__construct($model);
    }

    /**
     * Update an existing subscriber's editable identity fields.
     *
     * @param int $id
     * @param SubscriberUpdateData $data
     * @return bool
     */
    public function update(int $id, SubscriberUpdateData $data): bool
    {
        return $this->updateModel($id, $this->mapping($data->toArray()));
    }

    /**
     * Create a subscriber and synchronize all selected category memberships atomically.
     *
     * @param SubscriberCreateData $data
     * @return Subscribers
     * @throws \Throwable
     */
    public function add(SubscriberCreateData $data): Subscribers
    {
        return $this->database->transaction(function () use ($data) {
            $model = $this->model->create($this->mapping($data->toArray()));

            $this->syncSubscriptions($model->id, $data->categoryIds);

            return $model;
        });
    }

    /**
     * Create a public-form subscriber through the transactional subscriber workflow.
     *
     * @param SubscriberCreateData $data
     * @return Subscribers
     * @throws \Throwable
     */
    public function createFrontendSubscriber(SubscriberCreateData $data): Subscribers
    {
        return $this->add($data);
    }

    /**
     * Return active category subscribers not yet processed in the specified manual mailing batch.
     *
     * @param int $logId
     * @param int $templateId
     * @param array $categoryId
     * @param string $order
     * @param int|null $limit
     * @param string|null $interval
     * @return Collection|null
     */
    public function getSubscribers(
        int     $logId,
        int     $templateId,
        array   $categoryId,
        string  $order,
        ?int    $limit = null,
        ?string $interval = null
    ): ?Collection
    {
        $q = $this->model->select('subscribers.email', 'subscribers.token', 'subscribers.id', 'subscribers.name')
            ->distinct()
            ->join('subscriptions', 'subscribers.id', '=', 'subscriptions.subscriber_id')
            ->leftJoin('ready_sent', function ($join) use ($templateId, $logId) {
                $join->on('subscribers.id', '=', 'ready_sent.subscriber_id')
                    ->where('ready_sent.template_id', $templateId)
                    ->where('ready_sent.log_id', $logId)
                    ->where(function ($query) {
                        $query->where('ready_sent.success', 1)
                            ->orWhere('ready_sent.success', 0);
                    });
            })
            ->whereNull('ready_sent.subscriber_id')
            ->whereIn('subscriptions.category_id', $categoryId)
            ->where('subscribers.active', 1);

        if ($interval) {
            $q->whereRaw($interval);
        }

        return $q->orderByRaw($order)
            ->take($limit)
            ->get();
    }

    /**
     * Count distinct active subscribers in selected categories after interval and limit filters.
     *
     * @param array $categoryId
     * @param int|null $limit
     * @param string|null $interval
     * @return int
     */
    public function countSubscriptions(array $categoryId, ?int $limit = null, ?string $interval = null): int
    {
        $q = Subscriptions::query()
            ->select('subscribers.id')
            ->join('subscribers', 'subscriptions.subscriber_id', '=', 'subscribers.id')
            ->where('subscribers.active', 1)
            ->whereIn('subscriptions.category_id', $categoryId);

        if ($interval) {
            $q->whereRaw($interval);
        }

        return $q->groupBy('subscribers.id')
            ->take($limit)
            ->get()
            ->count();
    }

    /**
     * Return active scheduled recipients that do not yet have a delivery attempt for the schedule.
     *
     * @param int $scheduleId
     * @param string $order
     * @param int|null $limit
     * @param string|null $interval
     * @return Collection|null
     */
    public function getSubscribersNotReadySent(
        int     $scheduleId,
        string  $order,
        ?int    $limit = null,
        ?string $interval = null
    ): ?Collection
    {
        $q = $this->model->select([
            'subscribers.email',
            'subscribers.id',
            'subscribers.token',
            'subscribers.name',
        ])
            ->distinct()
            ->join('subscriptions', 'subscribers.id', '=', 'subscriptions.subscriber_id')
            ->join('schedule_category', function ($join) use ($scheduleId) {
                $join->on('subscriptions.category_id', '=', 'schedule_category.category_id')
                    ->where('schedule_category.schedule_id', $scheduleId);
            })
            ->leftJoin('ready_sent', function ($join) use ($scheduleId) {
                $join->on('subscribers.id', '=', 'ready_sent.subscriber_id')
                    ->where('ready_sent.schedule_id', $scheduleId)
                    ->where(function ($query) {
                        $query->where('ready_sent.success', 1)
                            ->orWhere('ready_sent.success', 0);
                    });
            })
            ->whereNull('ready_sent.subscriber_id')
            ->where('subscribers.active', 1);

        if ($interval) {
            $q->whereRaw($interval);
        }

        return $q->orderByRaw($order)
            ->take($limit)
            ->get();
    }

    /**
     * Return active scheduled recipients whose previous delivery attempt failed.
     *
     * @param int $scheduleId
     * @param string $order
     * @param int|null $limit
     * @param string|null $interval
     * @return Collection|null
     */
    public function getSubscribersUnSent(
        int     $scheduleId,
        string  $order,
        ?int    $limit = null,
        ?string $interval = null
    ): ?Collection
    {
        $q = $this->model->select([
            'subscribers.email',
            'subscribers.id',
            'subscribers.token',
            'subscribers.name',
        ])
            ->distinct()
            ->join('subscriptions', 'subscribers.id', '=', 'subscriptions.subscriber_id')
            ->join('schedule_category', function ($join) use ($scheduleId) {
                $join->on('subscriptions.category_id', '=', 'schedule_category.category_id')
                    ->where('schedule_category.schedule_id', $scheduleId);
            })
            ->join('ready_sent', function ($join) use ($scheduleId) {
                $join->on('subscribers.id', '=', 'ready_sent.subscriber_id')
                    ->where('ready_sent.schedule_id', $scheduleId)
                    ->where('ready_sent.success', 0);
            })
            ->where('subscribers.active', 1);

        if ($interval) {
            $q->whereRaw($interval);
        }

        return $q->orderByRaw($order)
            ->limit($limit)
            ->get();
    }

    /**
     * Return all category identifiers assigned to a subscriber.
     *
     * @param int $subscriberId
     * @return array
     */
    public function getSubscriberCategoryIdList(int $subscriberId): array
    {
        return Subscriptions::query()
            ->where('subscriber_id', $subscriberId)
            ->pluck('category_id')
            ->toArray();
    }

    /**
     * Activate, deactivate, or delete selected subscribers and their category memberships.
     *
     * @param int $action
     * @param array $ids
     * @return void
     */
    public function updateStatus(int $action, array $ids = []): void
    {
        switch ($action) {
            case 0:
            case 1:
                $this->model->whereIn('id', $ids)->update(['active' => $action]);
                break;
            case 2:
                Subscriptions::query()->whereIn('subscriber_id', $ids)->delete();
                $this->model->whereIn('id', $ids)->delete();
                break;
        }
    }

    /**
     * Create category membership records for a subscriber.
     *
     * @param int $subscriberId
     * @param array $categoryIds
     * @return void
     */
    private function syncSubscriptions(int $subscriberId, array $categoryIds): void
    {
        foreach ($categoryIds as $categoryId) {
            if (!is_numeric($categoryId)) {
                continue;
            }

            Subscriptions::query()->create([
                'subscriber_id' => $subscriberId,
                'category_id' => (int)$categoryId,
            ]);
        }
    }

    /**
     * Restrict subscriber input to attributes allowed for mass assignment.
     *
     * @param array $data
     * @return array
     */
    private function mapping(array $data): array
    {
        return collect($data)
            ->only($this->model->getFillable())
            ->all();
    }
}

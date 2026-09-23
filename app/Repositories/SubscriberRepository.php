<?php

namespace App\Repositories;

use App\DTO\Create\SubscriberCreateData;
use App\DTO\Update\SubscriberUpdateData;
use App\Models\Subscribers;
use App\Models\Templates;
use App\Models\Schedule;
use App\Models\Subscriptions;
use App\Models\Category;
use App\Models\Project;
use App\Services\ProjectAccess;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Builder;
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
        return $this->database->transaction(function () use ($id, $data): bool {
            $subscriber = ProjectAccess::subscribers($this->model->newQuery())->lockForUpdate()->findOrFail($id);
            $visibleProjectIds = ProjectAccess::projects()->pluck('projects.id')->all();
            $projectIds = $data->projectIds ?? $subscriber->projects()->whereIn('projects.id', $visibleProjectIds)->pluck('projects.id')->all();
            $this->validateAssignments($projectIds, $data->categoryIds ?? []);

            $saved = $subscriber->fill([...$data->toArray(), 'email' => strtolower(trim($data->email))])->save();
            if ($data->projectIds !== null) {
                $removedIds = $subscriber->projects()->whereIn('projects.id', $visibleProjectIds)
                    ->whereNotIn('projects.id', $projectIds)->pluck('projects.id')->all();
                $subscriber->projects()->detach($removedIds);
                $subscriber->projects()->syncWithoutDetaching($projectIds);
            }
            if ($data->categoryIds !== null) {
                $this->removeCategories([$id]);
                $this->syncSubscriptions($id, $data->categoryIds);
            }

            return $saved;
        });
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
        abort_unless(auth()->check(), 403);
        $this->validateAssignments($data->projectIds ?: [Project::DEFAULT_ID], $data->categoryIds);

        return $this->persistSubscriber($data);
    }

    private function persistSubscriber(SubscriberCreateData $data): Subscribers
    {
        return $this->database->transaction(function () use ($data) {
            $model = $this->model->newQuery()->firstOrCreate(
                ['email' => strtolower(trim($data->email))],
                $this->mapping([...$data->toArray(), 'email' => strtolower(trim($data->email))])
            );
            $model->projects()->syncWithoutDetaching($data->projectIds ?: [Project::DEFAULT_ID]);
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
        $this->validateAssignments($data->projectIds, $data->categoryIds, true);

        return $this->persistSubscriber($data);
    }

    public function find(int $id): ?Subscribers
    {
        return ProjectAccess::subscribers($this->model->newQuery())->find($id);
    }

    /**
     * Administrators remove the contact; project users remove only their visible memberships.
     */
    public function delete(int $id): bool
    {
        if (!$this->find($id)) {
            return false;
        }
        $this->updateStatus(2, [$id]);

        return true;
    }

    /**
     * Return active recipients not yet processed in the specified manual mailing batch.
     *
     * @param int $logId
     * @param int $templateId
     * @param array $categoryId
     * @param string $order
     * @param int|null $limit
     * @param string|null $interval
     * @param bool $wholeProject Include every active project member without a category filter.
     * @return Collection|null
     */
    public function getSubscribers(
        int     $logId,
        int     $templateId,
        array   $categoryId,
        string  $order,
        ?int    $limit = null,
        ?string $interval = null,
        bool    $wholeProject = false
    ): ?Collection
    {
        $projectId = Templates::query()->whereKey($templateId)->value('project_id');
        if ($projectId === null) {
            return collect();
        }

        $q = $this->manualRecipientsQuery($categoryId, (int) $projectId, $wholeProject)
            ->select('subscribers.email', 'subscribers.token', 'subscribers.id', 'subscribers.name')
            ->leftJoin('ready_sent', function ($join) use ($templateId, $logId) {
                $join->on('subscribers.id', '=', 'ready_sent.subscriber_id')
                    ->where('ready_sent.template_id', $templateId)
                    ->where('ready_sent.log_id', $logId)
                    ->where(function ($query) {
                        $query->where('ready_sent.success', 1)
                            ->orWhere('ready_sent.success', 0);
                    });
            })
            ->whereNull('ready_sent.subscriber_id');

        if ($interval) {
            $q->whereRaw($interval);
        }

        return $q->orderByRaw($order)
            ->take($limit)
            ->get();
    }

    /**
     * Count distinct active project recipients after category, interval and limit filters.
     *
     * @param array $categoryId
     * @param int|null $limit
     * @param string|null $interval
     * @param int|null $projectId
     * @param bool $wholeProject Include every active project member without a category filter.
     * @return int
     */
    public function countSubscriptions(array $categoryId, ?int $limit = null, ?string $interval = null, ?int $projectId = null, bool $wholeProject = false): int
    {
        $q = $this->manualRecipientsQuery($categoryId, $projectId ?? Project::DEFAULT_ID, $wholeProject)
            ->select('subscribers.id');

        if ($interval) {
            $q->whereRaw($interval);
        }

        return $q->take($limit)
            ->get()
            ->count();
    }

    /** Use the same project membership and category boundaries for sending and progress totals. */
    private function manualRecipientsQuery(array $categoryIds, int $projectId, bool $wholeProject): Builder
    {
        return $this->model->newQuery()
            ->distinct()
            ->join('project_subscriber', 'subscribers.id', '=', 'project_subscriber.subscriber_id')
            ->where('project_subscriber.project_id', $projectId)
            ->where('subscribers.active', 1)
            ->when(!$wholeProject, function ($query) use ($categoryIds) {
                $query->whereExists(function ($subscriptions) use ($categoryIds) {
                    $subscriptions->selectRaw('1')->from('subscriptions')
                        ->join('categories', 'subscriptions.category_id', '=', 'categories.id')
                        ->whereColumn('subscriptions.subscriber_id', 'subscribers.id')
                        ->whereIn('subscriptions.category_id', $categoryIds);
                });
            });
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
        $q = $this->scheduledRecipientsQuery($scheduleId)
            ->leftJoin('ready_sent', function ($join) use ($scheduleId) {
                $join->on('subscribers.id', '=', 'ready_sent.subscriber_id')
                    ->where('ready_sent.schedule_id', $scheduleId)
                    ->where(function ($query) {
                        $query->where('ready_sent.success', 1)
                            ->orWhere('ready_sent.success', 0);
                    });
            })
            ->whereNull('ready_sent.subscriber_id');

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
        $q = $this->scheduledRecipientsQuery($scheduleId)
            ->join('ready_sent', function ($join) use ($scheduleId) {
                $join->on('subscribers.id', '=', 'ready_sent.subscriber_id')
                    ->where('ready_sent.schedule_id', $scheduleId)
                    ->where('ready_sent.success', 0);
            })
            ->whereNotExists(function ($successful) use ($scheduleId) {
                $successful->selectRaw('1')->from('ready_sent as successful_delivery')
                    ->whereColumn('successful_delivery.subscriber_id', 'subscribers.id')
                    ->where('successful_delivery.schedule_id', $scheduleId)
                    ->where('successful_delivery.success', 1);
            });

        if ($interval) {
            $q->whereRaw($interval);
        }

        return $q->orderByRaw($order)
            ->limit($limit)
            ->get();
    }

    /**
     * Target active project members; only the default project is narrowed by selected categories.
     */
    private function scheduledRecipientsQuery(int $scheduleId): Builder
    {
        return $this->model->newQuery()
            ->select('subscribers.email', 'subscribers.id', 'subscribers.token', 'subscribers.name')
            ->distinct()
            ->join('project_subscriber', 'subscribers.id', '=', 'project_subscriber.subscriber_id')
            ->where('project_subscriber.project_id', Schedule::query()->select('project_id')->whereKey($scheduleId))
            ->where('subscribers.active', 1)
            ->where(function ($query) use ($scheduleId) {
                $query->where('project_subscriber.project_id', '<>', Project::DEFAULT_ID)
                    ->orWhereExists(function ($subscriptions) use ($scheduleId) {
                        $subscriptions->selectRaw('1')->from('subscriptions')
                            ->join('categories', 'subscriptions.category_id', '=', 'categories.id')
                            ->join('schedule_category', 'subscriptions.category_id', '=', 'schedule_category.category_id')
                            ->where('schedule_category.schedule_id', $scheduleId)
                            ->whereColumn('subscriptions.subscriber_id', 'subscribers.id');
                    });
            });
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
        $ids = array_values(array_unique(array_map('intval', $ids)));
        $this->database->transaction(function () use ($action, $ids): void {
            $visibleIds = ProjectAccess::subscribers($this->model->newQuery())
                ->whereIn('id', $ids)->lockForUpdate()->pluck('id');
            abort_unless($visibleIds->count() === count($ids), 403);

            if (in_array($action, [0, 1], true)) {
                $this->model->newQuery()->whereIn('id', $ids)->update(['active' => $action]);
            } elseif ($action === 2 && auth()->user()->isAdmin()) {
                $this->model->newQuery()->whereIn('id', $ids)->delete();
            } elseif ($action === 2) {
                $projectIds = ProjectAccess::projects()->pluck('projects.id')->all();
                $this->database->table('project_subscriber')->whereIn('subscriber_id', $ids)
                    ->whereIn('project_id', $projectIds)->delete();
            }
        });
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

            Subscriptions::query()->firstOrCreate([
                'subscriber_id' => $subscriberId,
                'category_id' => (int)$categoryId,
            ]);
        }
    }

    private function removeCategories(array $subscriberIds): void
    {
        Subscriptions::query()->whereIn('subscriber_id', $subscriberIds)
            ->delete();
    }

    private function validateAssignments(array $projectIds, array $categoryIds, bool $public = false): void
    {
        $projects = $public ? Project::query()->includingDefault()->where('status', 1) : ProjectAccess::projects();
        abort_unless((!$public || count($projectIds) === 1)
            && $projects->whereIn('projects.id', $projectIds)->count() === count(array_unique($projectIds)), 403);
        abort_unless(Category::query()->whereIn('id', $categoryIds)->count()
            === count(array_unique($categoryIds)), 422);
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

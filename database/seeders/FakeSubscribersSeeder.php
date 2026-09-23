<?php

namespace Database\Seeders;

use App\Models\Project;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FakeSubscribersSeeder extends Seeder
{
    private const SUBSCRIBERS_COUNT = 5000;
    private const INSERT_CHUNK_SIZE = 500;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $projectId = Project::DEFAULT_ID;
        $categoryIds = DB::table('categories')->pluck('id')->all();

        if (empty($categoryIds)) {
            $this->command?->warn('Категории не найдены. Сначала заполните таблицу categories.');
            return;
        }

        $now = now();
        $subscribersBatch = [];

        for ($i = 1; $i <= self::SUBSCRIBERS_COUNT; $i++) {
            $subscribersBatch[] = [
                'name' => fake()->name(),
                'email' => sprintf('subscriber_%s_%d@example.test', $now->timestamp, $i),
                'active' => 1,
                'token' => Str::random(32),
                'timeSent' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($subscribersBatch) === self::INSERT_CHUNK_SIZE || $i === self::SUBSCRIBERS_COUNT) {
                $this->insertSubscribersWithSubscriptions($subscribersBatch, $categoryIds, (int) $projectId);
                $subscribersBatch = [];
            }
        }

        $this->command?->info('Фейковые подписчики и подписки успешно созданы.');
    }

    /**
     * @param array<int, array<string, mixed>> $subscribersBatch
     * @param array<int, int> $categoryIds
     */
    private function insertSubscribersWithSubscriptions(array $subscribersBatch, array $categoryIds, int $projectId): void
    {
        DB::transaction(function () use ($subscribersBatch, $categoryIds, $projectId) {
            DB::table('subscribers')->insertOrIgnore($subscribersBatch);

            $emails = array_column($subscribersBatch, 'email');

            $insertedSubscribers = DB::table('subscribers')
                ->select(['id', 'email'])
                ->whereIn('email', $emails)
                ->get()
                ->keyBy('email');

            $subscriptionsBatch = [];
            $membershipsBatch = [];
            $now = now();

            foreach ($subscribersBatch as $subscriberData) {
                $subscriber = $insertedSubscribers->get($subscriberData['email']);

                if (!$subscriber) {
                    continue;
                }

                $membershipsBatch[] = [
                    'project_id' => $projectId,
                    'subscriber_id' => $subscriber->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                $randomCategoryIds = collect($categoryIds)
                    ->shuffle()
                    ->take(random_int(1, count($categoryIds)))
                    ->values();

                foreach ($randomCategoryIds as $categoryId) {
                    $subscriptionsBatch[] = [
                        'subscriber_id' => $subscriber->id,
                        'category_id' => $categoryId,
                    ];
                }
            }

            if (!empty($membershipsBatch)) {
                DB::table('project_subscriber')->insertOrIgnore($membershipsBatch);
            }

            if (!empty($subscriptionsBatch)) {
                DB::table('subscriptions')->insertOrIgnore($subscriptionsBatch);
            }
        });
    }
}

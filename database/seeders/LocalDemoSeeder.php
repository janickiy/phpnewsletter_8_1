<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Macros;
use App\Models\Project;
use App\Models\Schedule;
use App\Models\Smtp;
use App\Models\Subscribers;
use App\Models\Templates;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class LocalDemoSeeder extends Seeder
{
    private const SUBSCRIBERS_COUNT = 500;
    private const ADMIN_LOGIN = 'admin';
    private const ADMIN_PASSWORD = '1234567';

    private Project $project;

    /**
     * Fill the local installation with realistic demo data without resetting it.
     */
    public function run(): void
    {
        $this->call(SettingsSeeder::class);

        DB::transaction(function () {
            $this->project = DefaultProjectSeeder::forAdministrator($this->seedAdmin());
            $categories = $this->seedCategories();
            $templates = $this->seedTemplates();
            $subscribers = $this->seedSubscribers($categories);

            $this->seedMacros();
            $this->seedSmtpServers();
            $this->configureLocalMailDelivery();
            $this->seedSchedulesAndDeliveryLog($templates, $subscribers, $categories);
            $this->seedRedirects($subscribers);
        });

        $this->command?->info(sprintf(
            'Local site has been seeded: %d subscribers, 5 templates, admin login "%s".',
            self::SUBSCRIBERS_COUNT,
            self::ADMIN_LOGIN,
        ));
    }

    private function seedAdmin(): User
    {
        return User::query()->updateOrCreate(
            ['login' => self::ADMIN_LOGIN],
            [
                'name' => 'Local Administrator',
                'description' => 'Administrator account for the local Docker environment.',
                'role' => User::ROLE_ADMIN,
                'password' => Hash::make(self::ADMIN_PASSWORD),
            ]
        );
    }

    /**
     * @return \Illuminate\Support\Collection<int, Category>
     */
    private function seedCategories()
    {
        $categories = [
            [
                'name' => 'Category 1',
                'legacy_names' => ['Категория 1', 'Categoría 1', 'Catégorie 1', 'Kategorie 1', '分类 1', 'Categoria 1', 'الفئة 1', 'श्रेणी 1'],
            ],
            [
                'name' => 'Category 2',
                'legacy_names' => ['Категория 2', 'Categoría 2', 'Catégorie 2', 'Kategorie 2', '分类 2', 'Categoria 2', 'الفئة 2', 'श्रेणी 2'],
            ],
            [
                'name' => 'Category 3',
                'legacy_names' => ['Категория 3', 'Categoría 3', 'Catégorie 3', 'Kategorie 3', '分类 3', 'Categoria 3', 'الفئة 3', 'श्रेणी 3'],
            ],
            ['name' => 'Product Updates', 'legacy_names' => ['Новости продукта']],
            ['name' => 'Promotions', 'legacy_names' => ['Промо-акции']],
            ['name' => 'Blog Digest', 'legacy_names' => ['Дайджест блога']],
            ['name' => 'Events', 'legacy_names' => ['События']],
            ['name' => 'VIP Customers', 'legacy_names' => ['VIP клиенты']],
            ['name' => 'New Users', 'legacy_names' => ['Новые пользователи']],
        ];

        foreach ($categories as $category) {
            $this->syncDemoCategory($category['name'], $category['legacy_names']);
        }

        $names = array_column($categories, 'name');

        return Category::query()->where('project_id', $this->project->id)->whereIn('name', $names)->orderBy('id')->get();
    }

    private function syncDemoCategory(string $name, array $legacyNames): Category
    {
        $names = array_values(array_unique(array_merge([$name], $legacyNames)));
        $existing = Category::query()->where('project_id', $this->project->id)->whereIn('name', $names)->orderBy('id')->get();
        $target = $existing->firstWhere('name', $name) ?? $existing->first();

        if (!$target) {
            return Category::query()->create(['project_id' => $this->project->id, 'name' => $name]);
        }

        $target->forceFill(['name' => $name])->save();

        foreach ($existing as $category) {
            if ($category->id === $target->id) {
                continue;
            }

            DB::table('subscriptions')
                ->where('category_id', $category->id)
                ->update(['category_id' => $target->id]);
            DB::table('schedule_category')
                ->where('category_id', $category->id)
                ->update(['category_id' => $target->id]);

            $category->delete();
        }

        return $target->refresh();
    }

    /**
     * @return \Illuminate\Support\Collection<int, Templates>
     */
    private function seedTemplates()
    {
        $templates = [
            [
                'name' => 'Welcome email for new subscribers',
                'legacy_names' => ['Welcome-письмо для новых подписчиков'],
                'prior' => 0,
                'body' => '<h2>Hello, %NAME%!</h2><p>Thanks for subscribing to PHP Newsletter. This email collects the most useful resources to help you get started quickly.</p><p><a href="https://example.test/start">Open the starter guide</a></p>',
            ],
            [
                'name' => 'July product digest',
                'legacy_names' => ['Июльский дайджест продукта'],
                'prior' => 1,
                'body' => '<h2>Main updates this month</h2><p>New templates, improved subscriber imports, and clearer mailing statistics are now available in the control panel.</p>',
            ],
            [
                'name' => 'Promo campaign: summer discount',
                'legacy_names' => ['Промо-кампания: летняя скидка'],
                'prior' => 1,
                'body' => '<h2>Summer promotion</h2><p>A special offer is available for active subscribers until the end of the week. Use promo code <strong>SUMMER25</strong>.</p>',
            ],
            [
                'name' => 'Webinar invitation',
                'legacy_names' => ['Приглашение на вебинар'],
                'prior' => 0,
                'body' => '<h2>Email marketing webinar</h2><p>We will show how to segment your audience, prepare messages, and track results without unnecessary routine work.</p>',
            ],
            [
                'name' => 'Inactive subscriber reactivation',
                'legacy_names' => ['Реактивация неактивных подписчиков'],
                'prior' => 2,
                'body' => '<h2>We missed you</h2><p>We have not seen you among active readers for a while. Here is a short list of resources worth checking out.</p>',
            ],
        ];

        foreach ($templates as $template) {
            $this->syncDemoTemplate($template);
        }

        return Templates::query()
            ->where('project_id', $this->project->id)
            ->whereIn('name', array_column($templates, 'name'))
            ->orderBy('id')
            ->get();
    }

    private function syncDemoTemplate(array $template): Templates
    {
        $names = array_values(array_unique(array_merge([$template['name']], $template['legacy_names'] ?? [])));
        $existing = Templates::query()->where('project_id', $this->project->id)->whereIn('name', $names)->orderBy('id')->get();
        $target = $existing->firstWhere('name', $template['name']) ?? $existing->first();
        $data = [
            'project_id' => $this->project->id,
            'name' => $template['name'],
            'body' => $template['body'],
            'prior' => $template['prior'],
        ];

        if (!$target) {
            return Templates::query()->create($data);
        }

        $target->forceFill($data)->save();

        foreach ($existing as $existingTemplate) {
            if ($existingTemplate->id === $target->id) {
                continue;
            }

            DB::table('schedule')
                ->where('template_id', $existingTemplate->id)
                ->update(['template_id' => $target->id]);
            DB::table('ready_sent')
                ->where('template_id', $existingTemplate->id)
                ->update([
                    'template_id' => $target->id,
                    'template' => $target->name,
                ]);

            $existingTemplate->delete();
        }

        return $target->refresh();
    }

    /**
     * @param \Illuminate\Support\Collection<int, Category> $categories
     * @return \Illuminate\Support\Collection<int, Subscribers>
     */
    private function seedSubscribers($categories)
    {
        $faker = fake('en_US');
        $faker->seed(1200);
        $emails = [];
        $now = now();

        for ($i = 1; $i <= self::SUBSCRIBERS_COUNT; $i++) {
            $emails[] = sprintf('demo.subscriber%03d@phpnewsletter.test', $i);
        }

        foreach ($emails as $index => $email) {
            $createdAt = $now->copy()->subDays(random_int(1, 75))->subMinutes(random_int(0, 720));

            Subscribers::query()->firstOrCreate(
                ['email' => $email],
                [
                    'name' => $faker->name(),
                    'active' => $index % 11 === 0 ? 0 : 1,
                    'token' => Str::random(32),
                    'timeSent' => $index % 5 === 0 ? $createdAt->copy()->addDays(random_int(1, 8)) : null,
                    'created_at' => $createdAt,
                    'updated_at' => $now,
                ]
            );
        }

        $subscribers = Subscribers::query()->whereIn('email', $emails)->orderBy('id')->get();

        DB::table('project_subscriber')->insertOrIgnore($subscribers->map(fn ($subscriber) => [
            'project_id' => $this->project->id,
            'subscriber_id' => $subscriber->id,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all());

        DB::table('subscriptions')
            ->whereIn('subscriber_id', $subscribers->pluck('id'))
            ->whereIn('category_id', Category::query()->where('project_id', $this->project->id)->select('id'))
            ->delete();

        $subscriptions = [];
        $categoryIds = $categories->pluck('id')->values();

        foreach ($subscribers as $index => $subscriber) {
            $take = ($index % 3) + 1;
            $offset = $index % max(1, $categoryIds->count());

            for ($i = 0; $i < $take; $i++) {
                $subscriptions[] = [
                    'subscriber_id' => $subscriber->id,
                    'category_id' => $categoryIds[($offset + $i) % $categoryIds->count()],
                ];
            }
        }

        DB::table('subscriptions')->insertOrIgnore($subscriptions);

        return $subscribers;
    }

    private function seedMacros(): void
    {
        $macros = [
            ['name' => '%NAME%', 'value' => 'Subscriber name', 'type' => Macros::TYPE_TAGS],
            ['name' => '%EMAIL%', 'value' => 'email@example.test', 'type' => Macros::TYPE_EMAIL],
            ['name' => '%UNSUB%', 'value' => 'https://example.test/unsubscribe', 'type' => Macros::TYPE_URL],
            ['name' => '%CONFIRM%', 'value' => 'https://example.test/confirm', 'type' => Macros::TYPE_URL],
            ['name' => '%PROMO%', 'value' => 'SUMMER25 WINBACK10 VIP30', 'type' => Macros::TYPE_WRAP_PHRASE],
        ];

        foreach ($macros as $macro) {
            Macros::query()->updateOrCreate(
                ['name' => $macro['name']],
                [
                    'value' => $macro['value'],
                    'type' => $macro['type'],
                ]
            );
        }
    }

    private function seedSmtpServers(): void
    {
        Smtp::query()->update(['active' => 0]);

        Smtp::query()->updateOrCreate(
            [
                'host' => 'mailpit',
                'username' => 'mailpit',
            ],
            [
                'email' => 'newsletter@example.test',
                'password' => 'mailpit',
                'port' => 1025,
                'authentication' => 'plain',
                'secure' => 'no',
                'timeout' => 5,
                'active' => 1,
            ]
        );
    }

    private function configureLocalMailDelivery(): void
    {
        foreach ([
            'HOW_TO_SEND' => 'smtp',
            'EMAIL' => 'newsletter@example.test',
            'FROM' => 'PHP Newsletter',
            'URL' => config('app.url', 'http://localhost:8081'),
        ] as $name => $value) {
            DB::table('settings')->updateOrInsert(
                ['name' => $name],
                ['value' => $value]
            );
        }
    }

    /**
     * @param \Illuminate\Support\Collection<int, Templates> $templates
     * @param \Illuminate\Support\Collection<int, Subscribers> $subscribers
     * @param \Illuminate\Support\Collection<int, Category> $categories
     */
    private function seedSchedulesAndDeliveryLog($templates, $subscribers, $categories): void
    {
        $scheduleRows = [
            ['event_name' => 'July digest: sent', 'legacy_event_names' => ['Июльский дайджест: отправлено'], 'offset' => -12, 'template' => 1],
            ['event_name' => 'Summer promo campaign: sent', 'legacy_event_names' => ['Летняя промо-кампания: отправлено'], 'offset' => -7, 'template' => 2],
            ['event_name' => 'Welcome series: sent', 'legacy_event_names' => ['Welcome-серия: отправлено'], 'offset' => -3, 'template' => 0],
            ['event_name' => 'Email marketing webinar', 'legacy_event_names' => ['Вебинар по email-маркетингу'], 'offset' => 2, 'template' => 3],
            ['event_name' => 'Inactive subscriber reactivation', 'legacy_event_names' => ['Реактивация неактивных подписчиков'], 'offset' => 5, 'template' => 4],
        ];

        $eventNames = array_column($scheduleRows, 'event_name');
        $legacyEventNames = collect($scheduleRows)
            ->flatMap(fn (array $row) => $row['legacy_event_names'] ?? [])
            ->all();
        $demoEventNames = array_values(array_unique(array_merge($eventNames, $legacyEventNames)));
        $oldScheduleIds = Schedule::query()->where('project_id', $this->project->id)->whereIn('event_name', $demoEventNames)->pluck('id');
        $oldLogIds = DB::table('ready_sent')->whereIn('schedule_id', $oldScheduleIds)->pluck('log_id')->filter()->unique();

        DB::table('ready_sent')->whereIn('schedule_id', $oldScheduleIds)->delete();
        DB::table('schedule_category')->whereIn('schedule_id', $oldScheduleIds)->delete();
        DB::table('logs')->whereIn('id', $oldLogIds)
            ->whereNotExists(fn ($query) => $query->selectRaw('1')->from('ready_sent')->whereColumn('ready_sent.log_id', 'logs.id'))
            ->delete();
        Schedule::query()->whereIn('id', $oldScheduleIds)->delete();

        $templateList = $templates->values();
        $categoryIds = $categories->pluck('id')->values();

        foreach ($scheduleRows as $index => $row) {
            $template = $templateList[$row['template'] % $templateList->count()];
            $start = Carbon::now()->addDays($row['offset'])->setTime(10 + ($index % 6), 0);
            $end = $start->copy()->addMinutes(45);

            $schedule = Schedule::query()->create([
                'project_id' => $this->project->id,
                'event_name' => $row['event_name'],
                'event_start' => $start,
                'event_end' => $end,
                'template_id' => $template->id,
            ]);

            $scheduleCategories = [];
            for ($i = 0; $i < 2; $i++) {
                $scheduleCategories[] = [
                    'schedule_id' => $schedule->id,
                    'category_id' => $categoryIds[($index + $i) % $categoryIds->count()],
                ];
            }
            DB::table('schedule_category')->insertOrIgnore($scheduleCategories);

            if ($row['offset'] < 0) {
                $this->seedDeliveryRows($schedule, $template, $subscribers, $index);
            }
        }
    }

    /**
     * @param \Illuminate\Support\Collection<int, Subscribers> $subscribers
     */
    private function seedDeliveryRows(Schedule $schedule, Templates $template, $subscribers, int $batchIndex): void
    {
        $logId = DB::table('logs')->insertGetId([
            'time' => $schedule->event_start,
        ]);

        $rows = [];
        $sample = $subscribers->slice($batchIndex * 30, 45);

        if ($sample->count() < 45) {
            $sample = $subscribers->take(45);
        }

        foreach ($sample->values() as $index => $subscriber) {
            $success = $index % 9 === 0 ? 0 : 1;
            $createdAt = $schedule->event_start->copy()->addMinutes($index);

            $rows[] = [
                'project_id' => $this->project->id,
                'subscriber_id' => $subscriber->id,
                'email' => $subscriber->email,
                'template_id' => $template->id,
                'template' => $template->name,
                'success' => $success,
                'errorMsg' => $success ? null : 'SMTP timeout during demo delivery',
                'readMail' => $success && $index % 3 !== 0 ? 1 : null,
                'schedule_id' => $schedule->id,
                'log_id' => $logId,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ];
        }

        DB::table('ready_sent')->insert($rows);
    }

    /**
     * @param \Illuminate\Support\Collection<int, Subscribers> $subscribers
     */
    private function seedRedirects($subscribers): void
    {
        $urls = [
            'https://example.test/start',
            'https://example.test/pricing',
            'https://example.test/webinar',
            'https://example.test/blog/deliverability',
            'https://example.test/promo/summer',
        ];

        DB::table('redirect')->where('project_id', $this->project->id)->whereIn('url', $urls)->delete();

        $rows = [];
        $now = now();

        foreach ($subscribers->take(70)->values() as $index => $subscriber) {
            $rows[] = [
                'project_id' => $this->project->id,
                'url' => $urls[$index % count($urls)],
                'email' => $subscriber->email,
                'created_at' => $now->copy()->subHours(random_int(1, 160)),
                'updated_at' => $now,
            ];
        }

        DB::table('redirect')->insert($rows);
    }
}

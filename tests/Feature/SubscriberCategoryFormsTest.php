<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Project;
use App\Models\Subscribers;
use App\Models\Subscriptions;
use App\Models\User;
use App\Services\SubscriberService;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SubscriberCategoryFormsTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;
    private Project $otherProject;
    private Category $category;
    private Category $secondCategory;
    private Category $otherCategory;
    private Category $defaultCategory;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = User::query()->create([
            'name' => 'Category forms administrator', 'login' => 'category-forms-admin',
            'role' => User::ROLE_ADMIN, 'password' => 'password',
        ]);
        $this->actingAs($admin);
        $this->project = Project::query()->create([
            'name' => 'Named project', 'owner_id' => $admin->id, 'status' => 1,
        ]);
        $this->otherProject = Project::query()->create([
            'name' => 'Other project', 'owner_id' => $admin->id, 'status' => 1,
        ]);
        $this->category = Category::query()->create(['name' => 'First global category']);
        $this->secondCategory = Category::query()->create(['name' => 'Second global category']);
        $this->otherCategory = Category::query()->create(['name' => 'Third global category']);
        $this->defaultCategory = Category::query()->create(['name' => 'Fourth global category']);
    }

    public function test_all_subscriber_forms_offer_every_global_category(): void
    {
        $subscriber = $this->subscriber('saved@example.test', [$this->project->id]);
        $this->subscribe($subscriber, $this->category);

        $urls = [
            route('admin.subscribers.create', ['project_id' => $this->project->id]),
            route('admin.subscribers.edit', $subscriber->id),
            route('admin.subscribers.import', ['project_ids' => [$this->project->id]]),
            route('admin.subscribers.export', ['project_ids' => [$this->project->id]]),
        ];

        foreach ($urls as $url) {
            $response = $this->get($url)->assertOk();
            $this->assertCategoryOptions($response, $this->categoryIds());
            $page = $this->page($response);
            $this->assertSelectedOptions($page, 'project_ids', [$this->project->id]);
            $this->assertCategoriesEnabled($page, true);
            $this->assertSame(2, $page->query('//select[(@id="project_ids" or @id="categoryId") and @multiple]')->length);
            $this->assertEqualsCanonicalizing(
                [$this->category->id, $this->secondCategory->id, $this->otherCategory->id, $this->defaultCategory->id],
                $response->viewData('categories')->pluck('id')->all(),
                'Every category is available independently of project selection.'
            );
        }

        $edit = $this->page($this->get(route('admin.subscribers.edit', $subscriber->id))->assertOk());
        $this->assertSame(1, $edit->query('//select[@id="categoryId"]/option[@value="'.$this->category->id.'" and @selected]')->length);
    }

    public function test_forms_preserve_named_project_and_category_selection_after_validation_errors(): void
    {
        $subscriber = $this->subscriber('saved@example.test', [Project::DEFAULT_ID]);
        $this->subscribe($subscriber, $this->defaultCategory);

        foreach ([
            route('admin.subscribers.create'),
            route('admin.subscribers.edit', $subscriber->id),
            route('admin.subscribers.import'),
            route('admin.subscribers.export'),
        ] as $url) {
            $response = $this->withSession(['_old_input' => [
                'project_ids' => [(string) $this->project->id],
                'categoryId' => [(string) $this->category->id],
            ]])->get($url)->assertOk();

            $this->assertCategoryOptions($response, $this->categoryIds());
            $page = $this->page($response);
            $this->assertSelectedOptions($page, 'project_ids', [$this->project->id]);
            $this->assertSelectedOptions($page, 'categoryId', [$this->category->id]);
        }
    }

    public function test_selecting_multiple_projects_offers_each_category_once(): void
    {
        foreach (['create', 'import', 'export'] as $action) {
            $response = $this->get(route('admin.subscribers.'.$action, [
                'project_ids' => [Project::DEFAULT_ID, $this->project->id, $this->otherProject->id],
            ]))->assertOk();

            $this->assertCategoryOptions($response, [
                $this->category->id, $this->secondCategory->id, $this->otherCategory->id, $this->defaultCategory->id,
            ]);
        }
    }

    public function test_forms_keep_categories_available_without_project_memberships_or_project_hints(): void
    {
        $subscriber = $this->subscriber('unassigned@example.test', []);
        $this->subscribe($subscriber, $this->otherCategory);

        foreach ([
            route('admin.subscribers.create'),
            route('admin.subscribers.edit', $subscriber->id),
            route('admin.subscribers.import'),
            route('admin.subscribers.export'),
        ] as $url) {
            $response = $this->withSession(['_old_input' => [
                'project_ids' => [], 'categoryId' => [$this->otherCategory->id],
            ]])->get($url)->assertOk();
            $this->assertCategoryOptions($response, $this->categoryIds());
            $page = $this->page($response);
            $this->assertCategoriesEnabled($page, true);
            $this->assertSelectedOptions($page, 'categoryId', [$this->otherCategory->id]);
            $this->assertSame(0, $page->query('//*[@id="category-project-hint" or @id="category-create-link"]')->length);
        }
    }

    public function test_project_roles_can_select_all_global_categories_without_access_to_other_projects(): void
    {
        $subscriber = $this->subscriber('scoped@example.test', [$this->project->id]);

        foreach ([User::ROLE_PROJECT_ADMIN, User::ROLE_MODERATOR] as $role) {
            $user = User::query()->create([
                'name' => $role, 'login' => 'category-forms-'.$role,
                'role' => $role, 'password' => 'password',
            ]);
            $this->project->members()->attach($user, ['role' => $role]);
            $this->actingAs($user);

            foreach ($this->subscriberFormUrls($this->project, $subscriber) as $url) {
                $response = $this->get($url)->assertOk();
                $this->assertCategoryOptions($response, $this->categoryIds());
                $page = $this->page($response);
                $this->assertCategoriesEnabled($page, true);
                $this->assertSame(0, $page->query('//*[@id="category-project-hint" or @id="category-create-link"]')->length);
                $this->assertSame(0, $page->query('//select[@id="project_ids"]/option[@value="'.$this->otherProject->id.'"]')->length);
                if ($url === route('admin.subscribers.export', ['project_ids' => [$this->project->id]])) {
                    $this->assertSame(1, $page->query('//select[@id="project_ids" and @multiple and @required]')->length);
                }
            }
        }
    }

    public function test_export_preserves_all_selected_categories_independently_of_projects(): void
    {
        $response = $this->withSession(['_old_input' => [
            'project_ids' => [(string) $this->project->id],
            'categoryId' => [(string) $this->category->id, (string) $this->otherCategory->id],
        ]])->get(route('admin.subscribers.export'))->assertOk();
        $page = $this->page($response);

        $this->assertCategoryOptions($response, $this->categoryIds());
        $this->assertSelectedOptions($page, 'categoryId', [$this->category->id, $this->otherCategory->id]);
        $this->assertSame(2, $page->query('//select[(@id="project_ids" or @id="categoryId") and @multiple]')->length);
        $this->assertSame(0, $page->query('//select[@id="project_ids" and @required]')->length);
    }

    #[DataProvider('exportOptions')]
    public function test_export_preserves_format_and_zip_selection_after_validation_errors(string $format, string $compression): void
    {
        $page = $this->page($this->withSession(['_old_input' => [
            'project_ids' => [(string) $this->project->id],
            'categoryId' => [(string) $this->category->id],
            'export_type' => $format,
            'compress' => $compression,
        ]])->get(route('admin.subscribers.export'))->assertOk());

        $this->assertSelectedOptions($page, 'project_ids', [$this->project->id]);
        $this->assertSelectedOptions($page, 'categoryId', [$this->category->id]);
        $this->assertSame(1, $page->query('//input[@type="radio" and @name="export_type" and @checked]')->length);
        $this->assertSame(1, $page->query('//input[@type="radio" and @name="export_type" and @value="'.$format.'" and @checked]')->length);
        $this->assertSame(1, $page->query('//input[@type="hidden" and @name="compress" and @value="none"]')->length);
        $this->assertSame(1, $page->query('//input[@type="checkbox" and @name="compress" and @value="zip"]')->length);
        $this->assertSame($compression === 'zip' ? 1 : 0, $page->query('//input[@type="checkbox" and @name="compress" and @checked]')->length);
    }

    public static function exportOptions(): array
    {
        return [
            'text' => ['text', 'none'],
            'text zip' => ['text', 'zip'],
            'excel' => ['excel', 'none'],
            'excel zip' => ['excel', 'zip'],
        ];
    }

    public function test_global_category_imports_keep_one_identity_and_one_subscription(): void
    {
        foreach (['txt', 'csv'] as $extension) {
            $email = 'import-'.$extension.'@example.test';
            $contents = $extension === 'txt'
                ? $email."\n".strtoupper($email)."\n"
                : "email,name\n".$email.",Imported name\n".strtoupper($email).",Imported name\n";
            $request = Request::create('/import', 'POST', [
                'project_ids' => [$this->project->id], 'categoryId' => [$this->category->id],
            ], [], ['import' => UploadedFile::fake()->createWithContent('subscribers.'.$extension, $contents)]);

            $service = app(SubscriberService::class);
            if ($extension === 'txt') {
                $service->importFromText($request);
            } else {
                $service->importFromExcel($request);
            }

            $subscriber = Subscribers::query()->where('email', $email)->sole();
            $this->assertSame([$this->project->id], $subscriber->projects()->pluck('projects.id')->all());
            $this->assertSame([$this->category->id], $subscriber->subscriptions()->pluck('category_id')->all());
        }
        $this->assertDatabaseCount('subscribers', 2);
    }

    public function test_global_category_export_combines_membership_and_category_filters_without_duplicates(): void
    {
        $matching = $this->subscriber('matching@example.test', [$this->project->id, $this->otherProject->id]);
        $this->subscribe($matching, $this->category, $this->secondCategory, $this->otherCategory);
        $unmatched = $this->subscriber('unmatched@example.test', [$this->project->id]);
        $this->subscribe($unmatched, $this->defaultCategory);
        $inactive = $this->subscriber('inactive@example.test', [$this->project->id]);
        $inactive->update(['active' => 0]);
        $this->subscribe($inactive, $this->category);
        $wrongProject = $this->subscriber('wrong-project@example.test', [$this->otherProject->id]);
        $this->subscribe($wrongProject, $this->category);

        $contents = $this->post(route('admin.subscribers.export_subscribers'), [
            'project_ids' => [$this->project->id],
            'categoryId' => [$this->category->id, $this->otherCategory->id],
            'export_type' => 'text', 'compress' => 'none',
        ])->assertOk()->streamedContent();

        $this->assertSame(1, substr_count($contents, $matching->email));
        foreach ([$unmatched, $inactive, $wrongProject] as $excluded) {
            $this->assertStringNotContainsString($excluded->email, $contents);
        }
    }

    public function test_create_adds_global_categories_without_duplicating_existing_contacts_or_assignments(): void
    {
        $subscriber = $this->subscriber('existing@example.test', [$this->otherProject->id]);
        $subscriber->update(['active' => 0]);
        $this->subscribe($subscriber, $this->otherCategory);

        for ($attempt = 0; $attempt < 2; $attempt++) {
            $this->post(route('admin.subscribers.store'), [
                'email' => ' EXISTING@EXAMPLE.TEST ', 'project_ids' => [$this->project->id],
                'categoryId' => [$this->category->id, $this->otherCategory->id],
            ])->assertRedirect()->assertSessionHasNoErrors()->assertSessionMissing('error');
        }

        $this->assertDatabaseCount('subscribers', 1);
        $this->assertEqualsCanonicalizing([$this->project->id, $this->otherProject->id], $subscriber->projects()->pluck('projects.id')->all());
        $this->assertEqualsCanonicalizing([$this->category->id, $this->otherCategory->id], $subscriber->subscriptions()->pluck('category_id')->all());
        $this->assertSame(0, (int) $subscriber->fresh()->active);
    }

    public function test_edit_can_keep_or_replace_global_categories_after_clearing_project_memberships(): void
    {
        $subscriber = $this->subscriber('edit-global@example.test', [$this->project->id]);
        $this->subscribe($subscriber, $this->category);

        $this->put(route('admin.subscribers.update'), [
            'id' => $subscriber->id, 'email' => $subscriber->email,
            'project_ids' => [], 'categoryId' => [$this->category->id],
        ])->assertRedirect()->assertSessionHasNoErrors()->assertSessionMissing('error');
        $this->assertSame(0, $subscriber->projects()->count());
        $this->assertSame([$this->category->id], $subscriber->subscriptions()->pluck('category_id')->all());

        $this->put(route('admin.subscribers.update'), [
            'id' => $subscriber->id, 'email' => $subscriber->email,
            'project_ids' => [], 'categoryId' => [$this->otherCategory->id],
        ])->assertRedirect()->assertSessionHasNoErrors()->assertSessionMissing('error');
        $this->assertSame([$this->otherCategory->id], $subscriber->subscriptions()->pluck('category_id')->all());

        $this->put(route('admin.subscribers.update'), [
            'id' => $subscriber->id, 'email' => $subscriber->email, 'project_ids' => [],
        ])->assertRedirect()->assertSessionHasNoErrors()->assertSessionMissing('error');
        $this->assertSame(0, $subscriber->subscriptions()->count());
    }

    public function test_repeated_text_and_spreadsheet_imports_add_categories_and_preserve_existing_global_state(): void
    {
        foreach (['txt', 'csv'] as $extension) {
            $subscriber = $this->subscriber('existing-'.$extension.'@example.test', [$this->otherProject->id]);
            $subscriber->update(['active' => 0, 'timeSent' => '2026-01-02 03:04:05']);
            $this->subscribe($subscriber, $this->category, $this->otherCategory);
            $contents = $extension === 'txt'
                ? strtoupper($subscriber->email)."\n".$subscriber->email."\n"
                : "email,name\n".strtoupper($subscriber->email).",Replacement name\n".$subscriber->email.",Replacement name\n";

            for ($attempt = 0; $attempt < 2; $attempt++) {
                $request = Request::create('/import', 'POST', [
                    'project_ids' => [$this->project->id], 'categoryId' => [$this->secondCategory->id],
                ], [], ['import' => UploadedFile::fake()->createWithContent('subscribers.'.$extension, $contents)]);
                $service = app(SubscriberService::class);
                if ($extension === 'txt') {
                    $service->importFromText($request);
                } else {
                    $service->importFromExcel($request);
                }
            }

            $this->assertEqualsCanonicalizing([$this->project->id, $this->otherProject->id], $subscriber->projects()->pluck('projects.id')->all());
            $this->assertEqualsCanonicalizing([$this->category->id, $this->otherCategory->id, $this->secondCategory->id], $subscriber->subscriptions()->pluck('category_id')->all());
            $this->assertSame(0, (int) $subscriber->fresh()->active);
            $this->assertSame($subscriber->name, $subscriber->fresh()->name);
            $this->assertSame($subscriber->token, $subscriber->fresh()->token);
            $this->assertSame('2026-01-02 03:04:05', (string) $subscriber->fresh()->timeSent);
        }
        $this->assertDatabaseCount('subscribers', 2);
    }

    public function test_export_can_filter_unassigned_contacts_by_global_category(): void
    {
        $matching = $this->subscriber('unassigned-match@example.test', []);
        $this->subscribe($matching, $this->category, $this->otherCategory);
        $unmatched = $this->subscriber('unassigned-other@example.test', []);
        $this->subscribe($unmatched, $this->defaultCategory);
        $assigned = $this->subscriber('project-member@example.test', [$this->project->id]);
        $this->subscribe($assigned, $this->category);

        $contents = $this->post(route('admin.subscribers.export_subscribers'), [
            'project_ids' => [], 'categoryId' => [$this->category->id, $this->otherCategory->id],
            'export_type' => 'text', 'compress' => 'none',
        ])->assertOk()->streamedContent();

        $this->assertSame(1, substr_count($contents, $matching->email));
        $this->assertStringNotContainsString($unmatched->email, $contents);
        $this->assertStringNotContainsString($assigned->email, $contents);
    }

    private function categoryIds(): array
    {
        return [$this->category->id, $this->secondCategory->id, $this->otherCategory->id, $this->defaultCategory->id];
    }

    private function assertCategoryOptions(TestResponse $response, array $expectedIds): void
    {
        $actualIds = [];
        $page = $this->page($response);
        foreach ($page->query('//select[@id="categoryId"]/option') as $option) {
            $actualIds[] = (int) $option->getAttribute('value');
        }
        $this->assertEqualsCanonicalizing($expectedIds, $actualIds);
        $this->assertCount(count(array_unique($actualIds)), $actualIds);
    }

    private function assertSelectedOptions(DOMXPath $page, string $field, array $expectedIds): void
    {
        $actualIds = [];
        foreach ($page->query('//select[@id="'.$field.'"]/option[@selected]') as $option) {
            $actualIds[] = (int) $option->getAttribute('value');
        }
        $this->assertEqualsCanonicalizing($expectedIds, $actualIds);
    }

    private function assertCategoriesEnabled(DOMXPath $page, bool $enabled): void
    {
        $this->assertSame(1, $page->query('//select[@id="categoryId"]')->length);
        $this->assertSame($enabled ? 0 : 1, $page->query('//select[@id="categoryId" and @disabled]')->length);
    }

    private function subscriberFormUrls(Project $project, Subscribers $subscriber): array
    {
        return [
            route('admin.subscribers.create', ['project_id' => $project->id]),
            route('admin.subscribers.edit', $subscriber->id),
            route('admin.subscribers.import', ['project_ids' => [$project->id]]),
            route('admin.subscribers.export', ['project_ids' => [$project->id]]),
        ];
    }

    private function page(TestResponse $response): DOMXPath
    {
        $document = new DOMDocument;
        @$document->loadHTML($response->getContent());

        return new DOMXPath($document);
    }

    private function subscriber(string $email, array $projectIds): Subscribers
    {
        return $this->subscriberFixture([
            'email' => $email, 'name' => 'Form subscriber', 'active' => 1,
            'token' => bin2hex(random_bytes(16)),
        ], $projectIds);
    }

    private function subscribe(Subscribers $subscriber, Category ...$categories): void
    {
        foreach ($categories as $category) {
            Subscriptions::query()->create(['subscriber_id' => $subscriber->id, 'category_id' => $category->id]);
        }
    }
}

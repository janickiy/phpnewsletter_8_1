<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Macros;
use App\Models\Smtp;
use App\Models\Subscribers;
use App\Models\Templates;
use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLteTablesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::query()->create([
            'name' => 'Table administrator',
            'login' => 'table-admin',
            'role' => User::ROLE_ADMIN,
            'password' => 'password',
        ]));
    }

    public function test_every_table_page_loads_the_available_bootstrap_five_integrations(): void
    {
        $projectId = $this->testProjectId();
        $template = Templates::query()->create(['project_id' => $projectId, 'name' => 'Report fixture', 'body' => 'Report', 'prior' => 0]);
        $subscriber = $this->subscriberFixture(['email' => 'report@example.test', 'active' => 1, 'token' => str_repeat('a', 32)], [$projectId]);
        $log = \App\Models\Logs::query()->create(['time' => now()]);
        \App\Models\ReadySent::query()->create(['project_id' => $projectId, 'subscriber_id' => $subscriber->id, 'email' => $subscriber->email, 'template_id' => $template->id, 'template' => $template->name, 'success' => 1, 'log_id' => $log->id]);
        \App\Models\Redirect::query()->create(['project_id' => $projectId, 'url' => 'https://example.test', 'email' => $subscriber->email]);

        $routes = [
            'admin.category.index' => [],
            'admin.macros.index' => [],
            'admin.smtp.index' => [],
            'admin.users.index' => [],
            'admin.subscribers.index' => [],
            'admin.templates.index' => [],
            'admin.log.index' => [],
            'admin.log.info' => ['id' => $log->id],
            'admin.redirect.index' => [],
            'admin.redirect.info' => ['url' => rtrim(strtr(base64_encode('https://example.test'), '+/', '-_'), '=')],
        ];
        $assets = [
            'vendor/datatables-bs5/css/dataTables.bootstrap5.min.css',
            'vendor/datatables-responsive-bs5/css/responsive.bootstrap5.min.css',
            'vendor/datatables/js/dataTables.min.js',
            'vendor/datatables-bs5/js/dataTables.bootstrap5.min.js',
            'vendor/datatables-responsive/js/dataTables.responsive.min.js',
            'vendor/datatables-responsive-bs5/js/responsive.bootstrap5.min.js',
        ];

        foreach ($assets as $asset) {
            $this->assertFileExists(public_path($asset));
        }

        foreach ($routes as $route => $parameters) {
            $response = $this->get(route($route, $parameters))->assertOk();
            foreach ($assets as $asset) {
                $response->assertSee(asset($asset), false);
            }
            $response->assertDontSee('bootstrap4', false);
            $this->assertSame(1, $this->parse($response->getContent())->query('//table[@id="itemList"]')->length, $route);
        }
    }

    public function test_mailing_modal_keeps_send_stop_and_bootstrap_five_dismiss_controls(): void
    {
        $response = $this->get(route('admin.templates.index'))->assertOk();
        $page = $this->parse($response->getContent());

        $this->assertSame(2, $page->query('//*[@id="modal-lg"]//button[@data-bs-dismiss="modal"]')->length);
        $this->assertSame(0, $page->query('//*[@id="modal-lg"]//*[@data-dismiss]')->length);
        $this->assertSame('button', $page->evaluate('string(//button[@id="sendout"]/@type)'));
        $this->assertSame('button', $page->evaluate('string(//button[@id="stopsendout"]/@type)'));
        $this->assertSame(1, $page->query('//select[@id="categoryId"][@multiple]')->length);
        $response->assertSee('bootstrap.Modal.getOrCreateInstance(modalElement)', false)
            ->assertSee('modalInstance.show()', false)
            ->assertSee('hidden.bs.modal', false);
    }

    public function test_server_side_rows_keep_working_actions_and_bulk_selection_fields(): void
    {
        $category = Category::query()->create(['project_id' => $this->testProjectId(), 'name' => 'Readers']);
        $macro = Macros::query()->create(['name' => 'Greeting', 'value' => 'Hello', 'type' => 1]);
        $template = Templates::query()->create(['project_id' => $this->testProjectId(), 'name' => 'Newsletter', 'body' => '<p>Hello</p>', 'prior' => 0]);
        $subscriber = $this->subscriberFixture(['email' => 'reader@example.test',
            'active' => 1,
            'token' => md5('reader@example.test'),
        ], [$this->testProjectId()]);
        $smtp = Smtp::query()->create([
            'host' => 'mailpit',
            'username' => 'sender',
            'email' => 'sender@example.test',
            'port' => 1025,
            'authentication' => 'none',
            'secure' => 'none',
            'timeout' => 30,
        ]);

        foreach ([
            ['category', $category->id, 'actions', null],
            ['macros', $macro->id, 'actions', null],
            ['smtp', $smtp->id, 'action', 'activate[]'],
            ['subscribers', $subscriber->id, 'action', 'activate[]'],
            ['templates', $template->id, 'action', 'templateId[]'],
        ] as [$resource, $id, $actionColumn, $checkboxName]) {
            $response = $this->getJson(route('admin.datatable.'.$resource, ['draw' => 1, 'start' => 0, 'length' => 10]))->assertOk();
            $row = collect($response->json('data'))->firstWhere('id', $id);
            $this->assertNotNull($row, $resource);
            $page = $this->parse($row[$actionColumn].($row['checkbox'] ?? ''));
            $this->assertSame(route('admin.'.$resource.'.edit', ['id' => $id]), $page->evaluate('string(//a[contains(@class,"btn-outline-primary")]/@href)'));

            if ($resource !== 'templates') {
                $this->assertSame(1, $page->query('//button[@type="button"][@id="'.$id.'"][contains(@class,"deleteRow")]')->length, $resource);
            }
            if ($checkboxName !== null) {
                $this->assertSame(1, $page->query('//input[@type="checkbox"][@name="'.$checkboxName.'"][@value="'.$id.'"][contains(@class,"form-check-input")]')->length, $resource);
            }
        }

        $response = $this->getJson(route('admin.datatable.users'))->assertOk();
        $this->assertStringNotContainsString('deleteRow', $response->json('data.0.action'));
    }

    private function parse(string $html): DOMXPath
    {
        $document = new DOMDocument;
        $previousState = libxml_use_internal_errors(true);
        $document->loadHTML($html);
        libxml_clear_errors();
        libxml_use_internal_errors($previousState);

        return new DOMXPath($document);
    }
}

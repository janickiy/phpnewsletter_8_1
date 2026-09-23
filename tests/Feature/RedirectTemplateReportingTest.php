<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Redirect;
use App\Models\Templates;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class RedirectTemplateReportingTest extends TestCase
{
    use RefreshDatabase;

    private const URL = 'https://example.test/shared?source=email&offer=1';
    private int $projectId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->projectId = $this->testProjectId();
        $this->actingAs(User::query()->where('role', User::ROLE_ADMIN)->firstOrFail());
    }

    public function test_shared_url_summary_details_and_excel_keep_each_newsletter_snapshot_separate(): void
    {
        $first = $this->template('Current name');
        $second = $this->template('Same original name');
        $groups = [
            [$first->id, 'Original name', ['first@example.test', 'second@example.test']],
            [$first->id, 'Renamed newsletter', ['renamed@example.test']],
            [$second->id, 'Original name', ['other-template@example.test']],
            [null, null, ['legacy@example.test']],
            [$first->id, '', ['empty-snapshot@example.test']],
        ];
        foreach ($groups as [$templateId, $name, $emails]) {
            foreach ($emails as $email) {
                $this->click($templateId, $name, $email);
            }
        }

        $rows = $this->getJson(route('admin.datatable.redirect'))->assertOk()
            ->assertJsonPath('recordsTotal', 5)->json('data');
        foreach ($groups as [$templateId, $name, $emails]) {
            $row = collect($rows)->first(fn (array $row) => $row['template_id'] === $templateId && $row['template'] === ($name ?? '—'));
            $this->assertNotNull($row);
            $this->assertSame((string) count($emails), strip_tags($row['count']));
            $detailLink = $this->link($row['count']);
            $reportLink = $this->link($row['report']);
            parse_str(parse_url($detailLink, PHP_URL_QUERY), $filter);
            $this->assertArrayHasKey('newsletter', $filter);
            $this->get($detailLink)->assertOk()->assertViewHas('newsletter', $filter['newsletter']);
            $details = $this->getJson(route('admin.datatable.info_redirect', [
                'url' => $this->encodedUrl(), ...$filter,
            ]))->assertOk()->assertJsonCount(count($emails), 'data')->json('data');
            $this->assertEqualsCanonicalizing($emails, array_column($details, 'email'));

            $sheet = $this->spreadsheetRows($this->get($reportLink)->assertOk()->streamedContent());
            $this->assertSame(['URL', __('frontend.str.newsletter'), 'Email', 'Time'], $sheet[0]);
            $this->assertEqualsCanonicalizing($emails, array_column(array_slice($sheet, 1), 2));
            foreach (array_slice($sheet, 1) as $click) {
                $this->assertSame(self::URL, $click[0]);
                $this->assertSame($name ?? '—', $click[1]);
            }
        }

        $this->getJson(route('admin.datatable.info_redirect', ['url' => $this->encodedUrl()]))
            ->assertOk()->assertJsonCount(6, 'data');
        $this->assertCount(7, $this->spreadsheetRows(
            $this->get(route('admin.redirect.report', ['url' => $this->encodedUrl()]))->assertOk()->streamedContent()
        ));
    }

    public function test_newsletter_column_follows_url_in_overview_and_details(): void
    {
        $template = $this->template('Newsletter');
        $this->click($template->id, $template->name, 'reader@example.test');
        $this->get(route('admin.redirect.index'))->assertOk()->assertSeeInOrder([
            '<th>URL</th>', '<th>'.__('frontend.str.newsletter').'</th>',
        ], false);
        $this->get(route('admin.redirect.info', ['url' => $this->encodedUrl()]))->assertOk()->assertSeeInOrder([
            '<th>URL</th>', '<th>'.__('frontend.str.newsletter').'</th>', '<th>Email</th>',
        ], false);
    }

    public function test_invalid_group_filters_are_rejected_in_details_and_downloads(): void
    {
        $this->click(null, null, 'legacy@example.test');
        foreach (['not-valid-json', ['unexpected-array'], base64_encode('[1]'), base64_encode('[-1,"Name"]')] as $filter) {
            foreach (['admin.redirect.info', 'admin.redirect.report', 'admin.datatable.info_redirect'] as $route) {
                $this->getJson(route($route, ['url' => $this->encodedUrl(), 'newsletter' => $filter]))->assertStatus(422);
            }
        }
    }

    public function test_unavailable_and_deleted_templates_do_not_leak_to_project_roles(): void
    {
        $own = $this->template('Own newsletter');
        $otherProject = Project::query()->create([
            'name' => 'Other project', 'owner_id' => auth()->id(), 'status' => 1,
        ]);
        $other = Templates::query()->create([
            'project_id' => $otherProject->id, 'name' => 'Secret newsletter', 'body' => 'Body', 'prior' => 0,
        ]);
        $this->click($own->id, $own->name, 'own@example.test');
        $this->click($other->id, $other->name, 'secret@example.test');
        $this->click(null, null, 'legacy@example.test');
        $this->click(999999, 'Deleted newsletter', 'deleted@example.test');
        $administratorRows = $this->getJson(route('admin.datatable.redirect'))->assertOk()
            ->assertJsonPath('recordsTotal', 4)->json('data');

        $moderator = User::query()->create([
            'name' => 'Moderator', 'login' => 'redirect-moderator', 'role' => User::ROLE_MODERATOR, 'password' => 'password',
        ]);
        Project::query()->findOrFail($this->projectId)->members()->attach($moderator, ['role' => User::ROLE_MODERATOR]);
        $this->actingAs($moderator);
        $this->getJson(route('admin.datatable.redirect'))->assertOk()
            ->assertJsonPath('recordsTotal', 1)->assertJsonPath('data.0.template', 'Own newsletter');
        foreach ($administratorRows as $row) {
            if ($row['template_id'] === $own->id) {
                continue;
            }
            $this->get($this->link($row['count']))->assertNotFound();
            $this->get($this->link($row['report']))->assertNotFound();
        }
        $this->getJson(route('admin.datatable.info_redirect', ['url' => $this->encodedUrl()]))
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.email', 'own@example.test');
    }

    private function template(string $name): Templates
    {
        return Templates::query()->create(['project_id' => $this->projectId, 'name' => $name, 'body' => 'Body', 'prior' => 0]);
    }

    private function click(?int $templateId, ?string $name, string $email): void
    {
        Redirect::query()->create([
            'template_id' => $templateId, 'template' => $name, 'url' => self::URL, 'email' => $email,
        ]);
    }

    private function encodedUrl(): string
    {
        return rtrim(strtr(base64_encode(self::URL), '+/', '-_'), '=');
    }

    private function link(string $html): string
    {
        preg_match('/href="([^"]+)"/', $html, $matches);

        return html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8');
    }

    private function spreadsheetRows(string $contents): array
    {
        $path = tempnam(sys_get_temp_dir(), 'redirect_template_report_');
        file_put_contents($path, $contents);
        try {
            $spreadsheet = IOFactory::load($path);
            try {
                return $spreadsheet->getActiveSheet()->toArray();
            } finally {
                $spreadsheet->disconnectWorksheets();
            }
        } finally {
            unlink($path);
        }
    }
}

<?php

namespace Tests\Feature;

use App\Models\Templates;
use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemplateEditFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_edit_form_hides_empty_attachments_and_selects_normal_priority(): void
    {
        $admin = User::query()->create([
            'name' => 'Template admin',
            'login' => 'template-admin',
            'description' => null,
            'role' => User::ROLE_ADMIN,
            'password' => 'password',
        ]);
        $template = Templates::query()->create([
            'name' => 'Template without attachments',
            'body' => '<p>Body</p>',
            'prior' => 0,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.templates.edit', ['id' => $template->id]));

        $response->assertOk();

        $document = new DOMDocument();
        $previousState = libxml_use_internal_errors(true);
        $document->loadHTML($response->getContent());
        libxml_clear_errors();
        libxml_use_internal_errors($previousState);

        $xpath = new DOMXPath($document);
        $normalPriority = $xpath->query('//*[@id="prior_normal"]');

        $this->assertSame(1, $normalPriority->length);
        $this->assertSame('0', $normalPriority->item(0)->getAttribute('value'));
        $this->assertTrue($normalPriority->item(0)->hasAttribute('checked'));
        $this->assertSame(0, $xpath->query('//*[@id="existing-attachments"]')->length);
        $this->assertSame(0, $xpath->query('//label[@for="attachments"]')->length);
    }
}

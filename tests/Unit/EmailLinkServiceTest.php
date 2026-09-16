<?php

namespace Tests\Unit;

use App\Services\EmailLinkService;
use PHPUnit\Framework\TestCase;

class EmailLinkServiceTest extends TestCase
{
    public function test_html_email_url_is_rendered_as_a_clickable_link(): void
    {
        $url = 'http://localhost:8081/subscribe/506/token123';

        $this->assertSame(
            '<a href="http://localhost:8081/subscribe/506/token123">http://localhost:8081/subscribe/506/token123</a>',
            (new EmailLinkService())->render($url, true)
        );
    }

    public function test_plain_text_email_keeps_the_original_url(): void
    {
        $url = 'http://localhost:8081/unsubscribe/506/token123';

        $this->assertSame(
            $url,
            (new EmailLinkService())->render($url, false)
        );
    }

    public function test_html_email_url_is_safely_escaped(): void
    {
        $url = 'https://example.test/unsubscribe?token="unsafe"&id=1';

        $this->assertSame(
            '<a href="https://example.test/unsubscribe?token=&quot;unsafe&quot;&amp;id=1">https://example.test/unsubscribe?token=&quot;unsafe&quot;&amp;id=1</a>',
            (new EmailLinkService())->render($url, true)
        );
    }
}

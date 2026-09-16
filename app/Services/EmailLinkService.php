<?php

namespace App\Services;

class EmailLinkService
{
    /**
     * Render a visible URL as a clickable link for HTML email bodies.
     *
     * @param string $url
     * @param bool $html
     * @return string
     */
    public function render(string $url, bool $html): string
    {
        if (!$html) {
            return $url;
        }

        $escapedUrl = htmlspecialchars(
            $url,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );

        return sprintf(
            '<a href="%1$s">%1$s</a>',
            $escapedUrl
        );
    }
}

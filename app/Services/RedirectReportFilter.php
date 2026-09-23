<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;

/** Keep overview, detail and spreadsheet reports on the same historical newsletter. */
class RedirectReportFilter
{
    public static function encode(?int $templateId, ?string $template): string
    {
        return rtrim(strtr(base64_encode(json_encode([$templateId, $template], JSON_THROW_ON_ERROR)), '+/', '-_'), '=');
    }

    /** A missing filter preserves the URL-wide reports linked from older versions. */
    public static function apply(Builder $query, mixed $newsletter): Builder
    {
        if ($newsletter === null) {
            return $query;
        }

        abort_unless(is_string($newsletter) && strlen($newsletter) <= 4096, 422);
        $decoded = base64_decode(strtr($newsletter, '-_', '+/'), true);
        $snapshot = $decoded === false ? null : json_decode($decoded, true);
        abort_unless(
            is_array($snapshot) && array_is_list($snapshot) && count($snapshot) === 2
            && ($snapshot[0] === null || (is_int($snapshot[0]) && $snapshot[0] > 0))
            && ($snapshot[1] === null || is_string($snapshot[1])),
            422
        );

        return $query->where('redirect.template_id', $snapshot[0])->where('redirect.template', $snapshot[1]);
    }
}

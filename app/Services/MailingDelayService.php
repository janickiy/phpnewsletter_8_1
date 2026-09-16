<?php

namespace App\Services;

use App\Helpers\SettingsHelper;
use Illuminate\Support\Sleep;

class MailingDelayService
{
    /**
     * Pause only between delivery attempts, never before the first email.
     *
     * @param int $completedAttempts
     * @return void
     */
    public function waitBetween(int $completedAttempts): void
    {
        if ($completedAttempts <= 0) {
            return;
        }

        $seconds = max(0, (int) SettingsHelper::getValueForKey('SLEEP'));

        if ($seconds > 0) {
            Sleep::sleep($seconds);
        }
    }
}

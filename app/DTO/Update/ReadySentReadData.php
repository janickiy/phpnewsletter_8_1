<?php

namespace App\DTO\Update;

class ReadySentReadData
{
    /**
     * Identify a delivery record and the read state that should be applied to it.
     */
    public function __construct(
        public readonly int $subscriberId,
        public readonly int $templateId,
        public readonly int $readMail = 1,
    ) {
    }
}

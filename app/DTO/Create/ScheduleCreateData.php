<?php

namespace App\DTO\Create;

final class ScheduleCreateData
{
    /**
     * Capture the timing, template, and categories for a new mailing schedule.
     */
    public function __construct(
        public readonly string $eventName,
        public readonly string $eventStart,
        public readonly string $eventEnd,
        public readonly int $templateId,
        public readonly array $categoryIds = [],
    ) {
    }

    /**
     * Convert the schedule creation data into persistence attributes.
     */
    public function toArray(): array
    {
        return [
            'event_name' => $this->eventName,
            'event_start' => $this->eventStart,
            'event_end' => $this->eventEnd,
            'template_id' => $this->templateId,
        ];
    }
}

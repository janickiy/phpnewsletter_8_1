<?php

namespace App\DTO\Create;

final class AttachCreateData
{
    /**
     * Capture the attachment attributes required to create a template attachment.
     */
    public function __construct(
        public readonly string $name,
        public readonly string $file_name,
        public readonly int    $template_id,
    )
    {
    }

    /**
     * Convert the attachment creation data into persistence attributes.
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'file_name' => $this->file_name,
            'template_id' => $this->template_id,
        ];
    }

}

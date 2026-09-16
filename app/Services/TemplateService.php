<?php

namespace App\Services;

use App\DTO\Create\AttachCreateData;
use App\Helpers\StringHelper;
use App\Models\Attach;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class TemplateService
{

    /**
     * Validate and store uploaded files, then associate them with the specified template.
     *
     * @param Request $request
     * @param int $templateId
     * @return void
     */
    public function storeAttach(Request $request, int $templateId): void
    {
        $attachFiles = $request->file('attachfile');

        if (empty($attachFiles)) {
            return;
        }

        foreach ($attachFiles as $file) {
            if (!$file instanceof UploadedFile || !$file->isValid()) {
                continue;
            }

            $filename = sprintf(
                '%s.%s',
                StringHelper::randomText(10),
                $file->getClientOriginalExtension()
            );

            $stored = Storage::putFileAs(
                Attach::DIRECTORY,
                $file,
                $filename
            );

            if ($stored === false) {
                throw new \RuntimeException(
                    sprintf("Couldn't save %s!", $file->getClientOriginalName())
                );
            }

            Attach::query()->create(
                (new AttachCreateData(
                    name: $file->getClientOriginalName(),
                    file_name: $filename,
                    template_id: $templateId,
                ))->toArray()
            );
        }
    }
}

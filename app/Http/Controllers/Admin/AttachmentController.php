<?php

namespace App\Http\Controllers\Admin;

use App\Models\Attach;
use App\Services\AttachmentImagePreview;
use App\Services\ProjectAccess;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    /**
     * @param int $id
     * @param AttachmentImagePreview $previews
     * @return Response
     */
    public function preview(int $id, AttachmentImagePreview $previews): Response
    {
        $attachment = Attach::query()->with('template')->findOrFail($id);
        abort_unless($attachment->template, 404);
        ProjectAccess::authorizeProject((int) $attachment->template->project_id, 'manage');
        abort_unless($attachment->file_name === basename($attachment->file_name), 404);

        $png = $previews->render($attachment);
        abort_if($png === null, 404);

        return response($png, 200, [
            'Content-Type' => 'image/png',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    /**
     * @param int $id
     * @return StreamedResponse
     */
    public function download(int $id): StreamedResponse
    {
        $attachment = Attach::query()->with('template')->findOrFail($id);
        abort_unless($attachment->template, 404);
        ProjectAccess::authorizeProject((int) $attachment->template->project_id, 'manage');
        abort_unless($attachment->file_name === basename($attachment->file_name), 404);

        $path = Attach::DIRECTORY.'/'.$attachment->file_name;
        $disk = Storage::disk('local');
        abort_unless($disk->exists($path), 404);

        return $disk->download($path, basename($attachment->name), [
            'Content-Type' => 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}

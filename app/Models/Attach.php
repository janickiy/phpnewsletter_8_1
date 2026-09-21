<?php

namespace App\Models;


use App\Http\Traits\StaticTableName;
use App\Services\AttachmentImagePreview;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Attach extends Model
{
    use StaticTableName;

    public const DIRECTORY = 'private/attachments';

    protected $table = 'attach';

    protected $fillable = [
        'name',
        'file_name',
        'template_id'
    ];

    protected $attributes = [
        'name' => 'user',
    ];


    /**
     * Return the template that owns this attachment.
     *
     * @return BelongsTo
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(Templates::class);
    }

    public function isPreviewableImage(): bool
    {
        return app(AttachmentImagePreview::class)->canPreview($this);
    }

    /**
     * Delete the attachment file from storage and remove its database record.
     *
     * @return void
     */
    public function scopeRemove(): void
    {
        if (Storage::disk('local')->exists(Attach::DIRECTORY . '/' . $this->file_name)) {
            Storage::disk('local')->delete(Attach::DIRECTORY . '/' . $this->file_name);
        }

        $this->delete();
    }
}

<?php

namespace App\Models;

use App\Enums\TemplatePriority;
use App\Http\Traits\StaticTableName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Helpers\StringHelper;

class Templates extends Model
{
    use StaticTableName;

    protected $table = 'templates';

    protected $hidden = ['project_reference_id'];

    public function project(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        $relation = $this->belongsTo(Project::class);
        $relation->getQuery()->includingDefault();

        return $relation;
    }

    protected $fillable = [
        'project_id',
        'name',
        'body',
        'prior'
    ];

    /**
     * Return all files attached to this email template.
     *
     * @return HasMany
     */
    public function attach(): hasMany
    {
        return $this->hasMany(Attach::class, 'template_id');
    }

    /**
     * Build a plain-text preview of the template body with a bounded length.
     *
     * @return string
     */
    public function excerpt(): string
    {
        $content = $this->body;
        $content = preg_replace('/(<.*?>)|(&.*?;)/', '', $content);

        return StringHelper::shortText($content, 500);
    }

    /**
     * Build the template options used by selection fields.
     *
     * @return array
     */
    public static function getOption(): array
    {
        return self::orderBy('name')->get()->pluck('name', 'id')->toArray();
    }

    /**
     * Resolve the template priority to its localized label.
     *
     * @return string
     */
    public function getPrior(): string
    {
        return $this->getPriority()->label();
    }

    public function getPriority(): TemplatePriority
    {
        return TemplatePriority::tryFrom((int) $this->prior) ?? TemplatePriority::Normal;
    }

    /**
     * Delete the template after removing each of its stored attachments.
     *
     * @return void
     */
    public function scopeRemove(): void
    {
        foreach ($this->attach ?? [] as $attach) {
            $attach->remove();
        }

        $this->delete();
    }
}

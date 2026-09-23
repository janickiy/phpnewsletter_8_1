<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use LogicException;

class Project extends Model
{
    public const DEFAULT_ID = 0;

    protected $fillable = ['name', 'description', 'status', 'owner_id'];

    protected $casts = ['status' => 'boolean'];

    protected static function booted(): void
    {
        $rejectDefault = function (Project $project): void {
            if ($project->isDefault()) {
                throw new LogicException('The default project is virtual and cannot be persisted or deleted.');
            }
        };

        static::saving($rejectDefault);
        static::deleting($rejectDefault);
    }

    public static function defaultProject(): self
    {
        return (new self())->forceFill([
            'id' => self::DEFAULT_ID,
            'name' => __('frontend.str.projects.default_name'),
            'description' => null,
            'status' => true,
            'owner_id' => null,
            'created_at' => null,
            'updated_at' => null,
        ]);
    }

    /**
     * Include the virtual project in a read query without storing it in projects.
     *
     * @param Builder $query
     * @return Builder
     *
     */
    public function scopeIncludingDefault(Builder $query): Builder
    {
        $columns = ['id', 'name', 'description', 'status', 'owner_id', 'created_at', 'updated_at'];
        $stored = DB::table('projects')->select($columns)->where('id', '<>', self::DEFAULT_ID);
        $virtual = DB::query()->selectRaw(
            '0 AS id, ? AS name, NULL AS description, 1 AS status, NULL AS owner_id, NULL AS created_at, NULL AS updated_at',
            [__('frontend.str.projects.default_name')]
        );

        if ($query->getQuery()->columns === null) {
            $query->select('projects.*');
        }

        return $query->fromSub($stored->unionAll($virtual), 'projects');
    }

    public function isDefault(): bool
    {
        return $this->id === self::DEFAULT_ID;
    }

    /**
     * @return BelongsTo
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * @return BelongsToMany
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('role')->withTimestamps();
    }

    /**
     * @return HasMany
     */
    public function templates(): HasMany
    {
        return $this->hasMany(Templates::class);
    }

    /**
     * @return BelongsToMany
     */
    public function subscribers(): BelongsToMany
    {
        return $this->belongsToMany(Subscribers::class, 'project_subscriber', 'project_id', 'subscriber_id')->withTimestamps();
    }

    /**
     * @return HasMany
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }
}

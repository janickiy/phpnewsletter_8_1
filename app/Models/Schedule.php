<?php

namespace App\Models;

use App\Http\Traits\StaticTableName;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Schedule extends Model
{
    use HasFactory, StaticTableName;

    protected $table = 'schedule';

    protected $fillable = [
        'event_name',
        'event_start',
        'event_end',
        'template_id'
    ];

    /**
     * Return the email template assigned to this schedule.
     *
     * @return BelongsTo
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(Templates::class, 'template_id');
    }

    /**
     * Return the subscriber categories targeted by this schedule.
     *
     * @return HasManyThrough
     */
    public function categories(): HasManyThrough
    {
        return $this->hasManyThrough(Category::class, ScheduleCategory::class,'schedule_id','id','id','category_id');
    }
}

<?php

namespace App\Models;

use App\Http\Traits\StaticTableName;
use Illuminate\Database\Eloquent\Model;

class ScheduleCategory extends Model
{
    use StaticTableName;

    protected $table = 'schedule_category';

    public $timestamps = false;

    protected $fillable = [
        'schedule_id',
        'category_id'
    ];

    protected $primaryKey = ['schedule_id', 'category_id'];

    public $incrementing = false;
}

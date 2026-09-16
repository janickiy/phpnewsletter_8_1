<?php

namespace App\Models;

use App\Http\Traits\StaticTableName;
use Illuminate\Database\Eloquent\Model;

class Logs extends Model
{
    use StaticTableName;

    protected $table = 'logs';

    public $timestamps = false;

    protected $fillable = [
        'time',
    ];
}

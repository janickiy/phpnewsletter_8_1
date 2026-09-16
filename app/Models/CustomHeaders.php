<?php

namespace App\Models;

use App\Http\Traits\StaticTableName;
use Illuminate\Database\Eloquent\Model;

class CustomHeaders extends Model
{
    use StaticTableName;

    protected $table = 'customheaders';

    protected $fillable = [
        'name',
        'value'
    ];

}

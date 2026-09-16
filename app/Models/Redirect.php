<?php

namespace App\Models;

use App\Http\Traits\StaticTableName;
use Illuminate\Database\Eloquent\Model;

class Redirect extends Model
{
    use StaticTableName;

    protected $table = 'redirect';

    protected $fillable = [
        'url',
        'email'
    ];
}

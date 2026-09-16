<?php

namespace App\Models;

use App\Http\Traits\StaticTableName;
use Illuminate\Database\Eloquent\Model;

class Smtp  extends Model
{
    use StaticTableName;

    protected $table = 'smtp';

    protected $fillable = [
        'host',
        'email',
        'username',
        'password',
        'port',
        'authentication',
        'secure',
        'timeout',
        'active'
    ];
}

<?php

namespace App\Models;

use App\Http\Traits\StaticTableName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Process extends Model
{
    use StaticTableName;

    protected $table = 'process';

    protected $fillable = [
        'command',
        'user_id'
    ];

    /**
     * Return the user associated with this background process record.
     *
     * @return HasOne
     */
    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }
}

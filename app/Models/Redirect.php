<?php

namespace App\Models;

use App\Http\Traits\StaticTableName;
use Illuminate\Database\Eloquent\Model;

class Redirect extends Model
{
    use StaticTableName;

    protected $table = 'redirect';

    public function project(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    protected $fillable = [
        'project_id',
        'url',
        'email'
    ];
}

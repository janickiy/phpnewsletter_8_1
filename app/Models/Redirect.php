<?php

namespace App\Models;

use App\Http\Traits\StaticTableName;
use Illuminate\Database\Eloquent\Model;

class Redirect extends Model
{
    use StaticTableName;

    protected $table = 'redirect';

    protected $hidden = ['project_reference_id'];

    public function project(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        $relation = $this->belongsTo(Project::class);
        $relation->getQuery()->includingDefault();

        return $relation;
    }

    protected $fillable = [
        'project_id',
        'url',
        'email'
    ];
}

<?php

namespace App\Models;

use App\Http\Traits\StaticTableName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Subscriptions extends Model
{
    use StaticTableName;

    protected $table = 'subscriptions';

    public $timestamps = false;

    protected $primaryKey = ['subscriber_id', 'category_id'];

    public $incrementing = false;

    protected $fillable = [
        'subscriber_id',
        'category_id'
    ];

    /**
     * Return the subscriber referenced by this category membership.
     *
     * @return HasOne
     */
    public function subscriber(): HasOne
    {
        return $this->hasOne(Subscribers::class,'id','subscriber_id');
    }

    /**
     * Return the category referenced by this subscription membership.
     *
     * @return HasOne
     */
    public function category(): HasOne
    {
        return $this->hasOne(Category::class,'id','category_id');
    }
}

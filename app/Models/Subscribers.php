<?php

namespace App\Models;


use App\Http\Traits\StaticTableName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Subscribers extends Model
{
    use HasFactory, Notifiable, StaticTableName;

    protected $table = 'subscribers';

    public function projects(): BelongsToMany
    {
        $relation = $this->belongsToMany(Project::class, 'project_subscriber', 'subscriber_id', 'project_id')->withTimestamps();
        $relation->getQuery()->includingDefault();

        return $relation;
    }

    protected $fillable = [
        'name',
        'email',
        'active',
        'timeSent',
        'token'
    ];

    protected $hidden = [
        'token',
    ];

    /**
     * Restrict the subscriber query to records awaiting subscription confirmation.
     *
     * @param $query
     * @return mixed
     */
    public function scopeActive($query)
    {
        return $query->where('active', 'true');
    }

    /**
     * Return the category subscription records owned by this subscriber.
     *
     * @return HasMany
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscriptions::class, 'subscriber_id');
    }

    /**
     * Delete this subscriber together with all category subscription records.
     *
     * @return void
     */
    public function scopeRemove(): void
    {
        foreach ($this->subscriptions ?? [] as $subscription) {
            $subscription->delete();
        }

        $this->delete();
    }
}

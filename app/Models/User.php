<?php

namespace App\Models;

use App\Http\Traits\StaticTableName;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, StaticTableName;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_MODERATOR = 'moderator';
    public const ROLE_PROJECT_ADMIN = 'project_admin';
    public const ROLE_EDITOR = self::ROLE_PROJECT_ADMIN;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'login',
        'description',
        'role',
        'password',
    ];

    /**
     * Resolve the user's role code to its localized display label.
     */
    public function getRoleLabelAttribute(): string
    {
        $roles = [
            self::ROLE_ADMIN => __('frontend.str.projects.roles.admin'),
            self::ROLE_PROJECT_ADMIN => __('frontend.str.projects.roles.project_admin'),
            self::ROLE_MODERATOR => __('frontend.str.projects.roles.moderator'),
        ];

        return $roles[$this->role] ?? $this->role;
    }

    /**
     * Return the localized administrator role options used by user forms.
     */
    public static function getOptions(): array
    {
        return [
            self::ROLE_ADMIN => __('frontend.str.projects.roles.admin'),
            self::ROLE_PROJECT_ADMIN => __('frontend.str.projects.roles.project_admin'),
            self::ROLE_MODERATOR => __('frontend.str.projects.roles.moderator'),
        ];
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isProjectAdmin(): bool
    {
        return $this->role === self::ROLE_PROJECT_ADMIN;
    }

    public function isModerator(): bool
    {
        return $this->role === self::ROLE_MODERATOR;
    }

    public function projects(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Project::class)->withPivot('role')->withTimestamps();
    }

    public function ownedProjects(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Project::class, 'owner_id');
    }

    public function canManageProjects(): bool
    {
        return $this->isAdmin() || $this->isProjectAdmin() || $this->ownedProjects()->exists();
    }

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
}

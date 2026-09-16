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
    public const ROLE_EDITOR = 'editor';

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
            self::ROLE_ADMIN => __('frontend.str.admin'),
            self::ROLE_MODERATOR => __('frontend.str.moderator'),
            self::ROLE_EDITOR => __('frontend.str.editor'),
        ];

        return $roles[$this->role] ?? $this->role;
    }

    /**
     * Return the localized administrator role options used by user forms.
     */
    public static function getOptions(): array
    {
        return [
            self::ROLE_ADMIN => __('frontend.str.admin'),
            self::ROLE_MODERATOR => __('frontend.str.moderator'),
            self::ROLE_EDITOR => __('frontend.str.editor'),
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

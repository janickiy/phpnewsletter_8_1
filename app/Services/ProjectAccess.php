<?php

namespace App\Services;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

class ProjectAccess
{
    public static function projects(string $ability = 'view', ?User $user = null): Builder
    {
        $user ??= auth()->user();
        $query = Project::query()->includingDefault();

        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->isAdmin()) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($user, $ability): void {
            $query->where('projects.id', Project::DEFAULT_ID)
                ->orWhere('projects.owner_id', $user->id);

            if ($user->isProjectAdmin() || ($ability === 'view' && $user->isModerator())) {
                $query->orWhereHas('members', function (Builder $members) use ($user): void {
                    $members->where('users.id', $user->id)->where('project_user.role', $user->role);
                });
            }
        });
    }

    public static function scope(Builder|QueryBuilder $query, string $ability = 'view', ?string $column = null, ?User $user = null): Builder|QueryBuilder
    {
        $column ??= $query instanceof Builder
            ? $query->getModel()->qualifyColumn('project_id')
            : $query->from.'.project_id';

        return $query->whereIn($column, self::projects($ability, $user)->select('projects.id'));
    }

    public static function can(Project|int $project, string $ability = 'view', ?User $user = null): bool
    {
        return self::projects($ability, $user)->whereKey($project instanceof Project ? $project->id : $project)->exists();
    }

    /**
     * Limit shared contacts to visible project memberships; only administrators see orphans.
     */
    public static function subscribers(Builder|QueryBuilder $query, ?User $user = null): Builder|QueryBuilder
    {
        $user ??= auth()->user();
        if (!$user) {
            return $query->whereRaw('1 = 0');
        }
        if ($user->isAdmin()) {
            return $query;
        }

        return $query->whereExists(function (QueryBuilder $membership) use ($user): void {
            $membership->selectRaw('1')->from('project_subscriber')
                ->whereColumn('project_subscriber.subscriber_id', 'subscribers.id')
                ->whereIn('project_subscriber.project_id', self::projects('view', $user)->select('projects.id'));
        });
    }

    public static function authorizeProject(int $id, string $ability = 'view', ?User $user = null): Project
    {
        return self::projects($ability, $user)->findOrFail($id);
    }
}

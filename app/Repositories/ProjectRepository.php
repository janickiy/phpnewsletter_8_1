<?php

namespace App\Repositories;

use App\DTO\Create\ProjectCreateData;
use App\DTO\Update\ProjectUpdateData;
use App\Models\Attach;
use App\Models\Project;
use App\Models\User;
use App\Services\ProjectAccess;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ProjectRepository extends BaseRepository
{
    /**
     * Initialize project persistence and its transaction manager.
     */
    public function __construct(
        Project $model,
        private readonly DatabaseManager $database,
    ) {
        parent::__construct($model);
    }

    /**
     * Return projects the current user can manage.
     */
    public function all(): Collection
    {
        return $this->managedProjects()->orderBy('name')->get();
    }


    /**
     * Find an accessible project with its owner and current assignments for editing.
     *
     * @param int $id
     * @return Project|null
     */
    public function find(int $id): ?Project
    {
        abort_if($id === Project::DEFAULT_ID, 403);

        return $this->managedProjects()->with(['owner', 'members'])->find($id);
    }

    /**
     * Build the project list query with owner details and membership counts.
     */
    public function getForDataTable(): Builder
    {
        return $this->managedProjects()->with('owner')->withCount('members');
    }

    /**
     * Create a project and its assignments in one transaction.
     *
     * @param ProjectCreateData $data
     * @return Project
     * @throws \Throwable
     */
    public function add(ProjectCreateData $data): Project
    {
        $user = auth()->user();
        abort_unless($user && ($user->isAdmin() || $user->isProjectAdmin()), 403);
        abort_unless($user->isAdmin() || $data->ownerId === $user->id, 403);

        return $this->database->transaction(function () use ($data): Project {
            $project = $this->create($data->toArray());
            $this->syncMembers($project, $data->projectAdminIds, $data->moderatorIds);

            return $project;
        });
    }

    /**
     * Update a project's attributes and the submitted membership groups atomically.
     *
     * @param int $id
     * @param ProjectUpdateData $data
     * @return bool
     * @throws \Throwable
     */
    public function update(int $id, ProjectUpdateData $data): bool
    {
        abort_if($id === Project::DEFAULT_ID, 403);

        return $this->database->transaction(function () use ($id, $data): bool {
            $project = $this->storedManagedProjects()->lockForUpdate()->findOrFail($id);
            $user = auth()->user();
            abort_unless($data->ownerId === null || $user->isAdmin(), 403);
            abort_unless($data->projectAdminIds === null || $user->isAdmin() || $project->owner_id === $user->id, 403);

            $saved = $project->fill($data->toArray())->save();
            $this->syncMembers($project, $data->projectAdminIds, $data->moderatorIds);

            return $saved;
        });
    }

    /**
     * Delete an accessible project and its data atomically, then remove its files.
     *
     * @param int $id
     * @return bool
     * @throws \Throwable
     */
    public function delete(int $id): bool
    {
        abort_if($id === Project::DEFAULT_ID, 403);

        return $this->database->transaction(function () use ($id): bool {
            $project = $this->storedManagedProjects()->lockForUpdate()->find($id);
            if (!$project) {
                return false;
            }

            $this->ensureReferencesBelongToProject($project);

            $attachmentNames = Attach::query()
                ->whereIn('template_id', $project->templates()->select('id'))
                ->pluck('file_name')->unique()->all();
            $logIds = $this->database->table('ready_sent')
                ->where('project_id', $project->id)->whereNotNull('log_id')
                ->distinct()->pluck('log_id');

            // Foreign keys remove attachments and schedule junctions with their parents.
            // Click snapshots retain their template ID and name after its deletion.
            foreach (['ready_sent', 'schedule', 'templates'] as $table) {
                $this->database->table($table)->where('project_id', $project->id)->delete();
            }
            $project->subscribers()->detach();

            // A mailing run can contain delivery results from several projects.
            $this->database->table('logs')->whereIn('id', $logIds)
                ->whereNotIn('id', $this->database->table('ready_sent')->whereNotNull('log_id')->select('log_id'))
                ->delete();

            $project->members()->detach();
            if (!$project->delete()) {
                throw new RuntimeException('Project deletion was cancelled.');
            }

            $this->database->afterCommit(fn () => $this->deleteAttachmentFiles($attachmentNames));

            return true;
        });
    }

    /**
     * Prevent malformed cross-project references from cascading into another project's data.
     */
    private function ensureReferencesBelongToProject(Project $project): void
    {
        $foreignSchedule = $this->database->table('schedule')
            ->where('project_id', '!=', $project->id)
            ->whereIn('template_id', $project->templates()->select('id'))->exists();
        $foreignDelivery = $this->database->table('ready_sent')
            ->where('project_id', '!=', $project->id)
            ->where(function ($query) use ($project): void {
                $query->whereIn('template_id', $project->templates()->select('id'))
                    ->orWhereIn('schedule_id', $project->schedules()->select('id'));
            })->exists();

        if ($foreignSchedule || $foreignDelivery) {
            throw new RuntimeException('Project data has inconsistent references from another project.');
        }
    }

    /**
     * Remove files only after commit and only when no remaining attachment uses them.
     */
    private function deleteAttachmentFiles(array $fileNames): void
    {
        if ($fileNames === []) {
            return;
        }

        try {
            $remainingNames = Attach::query()->whereIn('file_name', $fileNames)->pluck('file_name');
            $paths = collect($fileNames)->diff($remainingNames)->filter(function (string $name): bool {
                return $name === basename($name)
                    && !str_contains($name, '\\')
                    && !preg_match('/[\x00-\x1F\x7F]/', $name)
                    && !in_array($name, ['', '.', '..'], true);
            })->map(fn (string $name) => Attach::DIRECTORY.'/'.$name)->values()->all();

            if ($paths !== [] && !Storage::disk('local')->delete($paths)) {
                report(new RuntimeException('Could not remove all files belonging to a deleted project.'));
            }
        } catch (\Throwable $e) {
            // The database is committed; a storage failure must not report the project as undeleted.
            report($e);
        }
    }

    /**
     * Apply the same project access boundary to repository reads and mutations.
     */
    private function managedProjects(): Builder
    {
        return ProjectAccess::projectsForManagement();
    }

    /** Lock the stored project row itself, not the derived list containing the virtual project. */
    private function storedManagedProjects(): Builder
    {
        return Project::query()->whereIn('projects.id', $this->managedProjects()->select('projects.id'));
    }

    /**
     * Replace each submitted role's assignments while leaving omitted groups intact.
     *
     * @param Project $project
     * @param array|null $projectAdminIds
     * @param array|null $moderatorIds
     * @return void
     */
    private function syncMembers(Project $project, ?array $projectAdminIds, ?array $moderatorIds): void
    {
        foreach ([User::ROLE_PROJECT_ADMIN => $projectAdminIds, User::ROLE_MODERATOR => $moderatorIds] as $role => $ids) {
            if ($ids === null) {
                continue;
            }

            $ids = array_unique(array_map('intval', $ids));
            $existingIds = $project->members()->wherePivot('role', $role)->pluck('users.id')->all();
            $project->members()->detach(array_diff($existingIds, $ids));
            $project->members()->syncWithoutDetaching(array_fill_keys($ids, ['role' => $role]));
        }
    }
}

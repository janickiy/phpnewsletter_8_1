<?php

namespace App\Http\Controllers\Admin;

use App\DTO\Create\ProjectCreateData;
use App\DTO\Update\ProjectUpdateData;
use App\Http\Requests\Admin\Projects\StoreRequest;
use App\Http\Requests\Admin\Projects\UpdateRequest;
use App\Models\Project;
use App\Models\User;
use App\Repositories\ProjectRepository;
use App\Repositories\UserRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ProjectController extends Controller
{
    /**
     * Inject repositories used to manage projects and populate user selections.
     */
    public function __construct(
        private readonly ProjectRepository $projectRepository,
        private readonly UserRepository $userRepository,
    ) {
        parent::__construct();
    }

    /**
     * Show the project management page where projects are listed through DataTables.
     *
     * @return View
     */
    public function index(): View
    {
        abort_unless(auth()->user()->canManageProjects(), 403);

        return view('admin.projects.index', [
            'title' => __('frontend.str.projects.index'),
            'infoAlert' => __('frontend.str.projects.introduction'),
        ]);
    }

    /**
     * Show the form used to create a project and assign its members.
     *
     * @return View
     */
    public function create(): View
    {
        abort_unless(auth()->user()->isAdmin() || auth()->user()->isProjectAdmin(), 403);

        return view('admin.projects.create_edit', $this->formData());
    }

    /**
     * Validate and persist a project through its creation DTO.
     */
    public function store(StoreRequest $request): RedirectResponse
    {
        try {
            $data = $request->validated();

            $this->projectRepository->add(
                new ProjectCreateData(
                    name: $data['name'],
                    status: (bool) $data['status'],
                    ownerId: (int) ($request->user()->isAdmin() ? ($data['owner_id'] ?? $request->user()->id) : $request->user()->id),
                    description: $data['description'] ?? null,
                    projectAdminIds: array_map('intval', $data['project_admin_ids'] ?? []),
                    moderatorIds: array_map('intval', $data['moderator_ids'] ?? []),
                )
            );
        } catch (HttpExceptionInterface|ModelNotFoundException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', $e->getMessage())->withInput();
        }

        return to_route('admin.projects.index')->with('success', __('message.information_successfully_added'));
    }

    /**
     * Show an accessible project with its current owner and member assignments
     *
     * @param int $id
     * @return View
     */
    public function edit(int $id): View
    {
        $project = $this->projectRepository->find($id);

        abort_if(!$project, 404);

        return view('admin.projects.create_edit', $this->formData($project));
    }


    /**
     * Validate and save project changes through its update DTO.
     *
     * @param UpdateRequest $request
     * @return RedirectResponse
     * @throws HttpExceptionInterface
     */
    public function update(UpdateRequest $request): RedirectResponse
    {
        try {
            $data = $request->validated();

            $this->projectRepository->update(
                (int) $data['id'],
                new ProjectUpdateData(
                    name: $data['name'],
                    status: (bool) $data['status'],
                    description: $data['description'] ?? null,
                    ownerId: $request->user()->isAdmin() && array_key_exists('owner_id', $data) ? (int) $data['owner_id'] : null,
                    projectAdminIds: $this->submittedMemberIds($data, 'project_admin_ids'),
                    moderatorIds: $this->submittedMemberIds($data, 'moderator_ids'),
                )
            );
        } catch (HttpExceptionInterface|ModelNotFoundException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', $e->getMessage())->withInput();
        }

        return to_route('admin.projects.index')->with('success', __('message.data_updated'));
    }

    /**
     * Delete an accessible project with its data and return the result to the table action.
     *
     * @param int $id
     * @return Response
     * @throws \Throwable
     */
    public function destroy(int $id): Response
    {
        abort_unless($this->projectRepository->delete($id), 404);

        return response()->noContent();
    }

    /**
     * Prepare shared create/edit view data without querying models in the controller.
     *
     * @param Project|null $project
     * @return array
     */
    private function formData(?Project $project = null): array
    {
        $user = auth()->user();
        $canAssignAdministrators = !$project || $user->isAdmin() || $project->owner_id === $user->id;

        return [
            'title' => __($project ? 'frontend.str.projects.edit' : 'frontend.str.projects.create'),
            'row' => $project,
            'canAssignAdministrators' => $canAssignAdministrators,
            'owners' => $user->isAdmin() ? $this->userRepository->getForSelection() : collect(),
            'administrators' => $canAssignAdministrators ? $this->userRepository->getForSelection(User::ROLE_PROJECT_ADMIN) : collect(),
            'moderators' => $this->userRepository->getForSelection(User::ROLE_MODERATOR),
            'assignedAdministrators' => $project ? $project->members->where('pivot.role', User::ROLE_PROJECT_ADMIN)->values() : collect(),
            'assignedModerators' => $project ? $project->members->where('pivot.role', User::ROLE_MODERATOR)->values() : collect(),
        ];
    }

    /**
     * Distinguish an omitted membership field from an explicitly cleared selection.
     *
     * @param array $data
     * @param string $field
     * @return array|null
     */
    private function submittedMemberIds(array $data, string $field): ?array
    {
        if (!array_key_exists($field, $data) && !array_key_exists($field.'_present', $data)) {
            return null;
        }

        return array_map('intval', $data[$field] ?? []);
    }
}

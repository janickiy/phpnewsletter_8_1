<?php

namespace App\Http\Controllers\Admin;


use App\DTO\Create\UserCreateData;
use App\DTO\Update\UserUpdateData;
use App\Http\Requests\Admin\Users\StoreRequest;
use App\Http\Requests\Admin\Users\UpdateRequest;
use App\Models\User;
use App\Models\Project;
use App\Repositories\UserRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class UsersController extends Controller
{
    /**
     * Inject the user repository used by all admin user management actions.
     */
    public function __construct(
        private readonly UserRepository $userRepository
    ) {
        parent::__construct();
    }

    /**
     * Show the admin user management page.
     *
     * @return View
     */
    public function index(): View
    {
        return view('admin.users.index', [
            'infoAlert' => __('frontend.hint.users_index'),
            'title' => __('frontend.title.users_index'),
        ]);
    }

    /**
     * Show the form used to create a new admin user.
     *
     * @return View
     */
    public function create(): View
    {
        return view('admin.users.create_edit', [
            'options' => User::getOptions(),
            'infoAlert' => __('frontend.hint.users_create'),
            'title' => __('frontend.title.users_create'),
        ]);
    }

    /**
     * Validate and persist a new admin user account.
     *
     * @param StoreRequest $request
     * @return RedirectResponse
     */
    public function store(StoreRequest $request): RedirectResponse
    {
        try {
            $data = $request->validated();

            $this->userRepository->createWithMapping(
                new UserCreateData(
                    name: $data['name'],
                    login: $data['login'],
                    role: $data['role'],
                    password: $data['password'],
                    description: $data['description'] ?? null,
                )
            );
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->with('error', $e->getMessage())
                ->withInput();
        }

        return to_route('admin.users.index')->with('success', __('message.information_successfully_added'));
    }

    /**
     * Show the edit form for an existing admin user.
     *
     * @param int $id
     * @return View
     */
    public function edit(int $id): View
    {
        $row = $this->userRepository->find($id);

        abort_if(!$row, 404);

        return view('admin.users.create_edit', [
            'row' => $row,
            'options' => User::getOptions(),
            'infoAlert' => __('frontend.hint.users_edit'),
            'title' => __('frontend.title.users_edit'),
        ]);
    }

    /**
     * Validate and save changes to an existing admin user account.
     *
     * @param UpdateRequest $request
     * @return RedirectResponse
     */
    public function update(UpdateRequest $request): RedirectResponse
    {
        try {
            $data = $request->validated();

            $this->userRepository->update(
                (int) $data['id'],
                new UserUpdateData(
                    name: $data['name'],
                    login: $data['login'],
                    role: $data['role'],
                    description: $data['description'] ?? null,
                    password: $data['password'] ?? null,
                )
            );
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->with('error', $e->getMessage())
                ->withInput();
        }

        return to_route('admin.users.index')->with('success', __('message.data_updated'));
    }

    /**
     * Delete an admin user unless it is the currently authenticated account.
     *
     * @param int $id
     * @return void
     */
    public function destroy(int $id): void
    {
        if ($id !== (int) Auth::id()) {
            abort_if(Project::query()->where('owner_id', $id)->exists(), 422, __('frontend.str.projects.owner_has_projects'));
            $this->userRepository->delete($id);
        }
    }
}

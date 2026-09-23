<?php

namespace App\Http\Controllers\Admin;


use App\DTO\Create\CategoryCreateData;
use App\DTO\Update\CategoryUpdateData;
use App\Http\Requests\Admin\Category\EditRequest;
use App\Http\Requests\Admin\Category\StoreRequest;
use App\Repositories\CategoryRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CategoryController extends Controller
{
    /**
     * Inject the category repository used by all category management actions.
     */
    public function __construct(private readonly CategoryRepository $categoryRepository)
    {
        parent::__construct();
    }

    /**
     * Show the category management page where categories are listed through DataTables.
     *
     * @return View
     */
    public function index(): View
    {
        return view('admin.category.index', [
            'infoAlert' => __('frontend.hint.category_index'),
            'title' => __('frontend.title.category_index'),
        ]);
    }

    /**
     * Show the form used to create a new subscriber category.
     *
     * @return View
     */
    public function create(): View
    {
        return view('admin.category.create_edit', [
            'infoAlert' => __('frontend.hint.category_create'),
            'title' => __('frontend.title.category_create'),
        ]);
    }

    /**
     * Validate and persist a new subscriber category, then return to the category list.
     *
     * @param StoreRequest $request
     * @return RedirectResponse
     */
    public function store(StoreRequest $request): RedirectResponse
    {
        try {
            $data = $request->validated();

            $this->categoryRepository->add(
                new CategoryCreateData(
                    name: $data['name'],
                )
            );
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->with('error', $e->getMessage())
                ->withInput();
        }

        return to_route('admin.category.index')
            ->with('success', __('message.information_successfully_added'));
    }

    /**
     * Show the edit form for an existing subscriber category.
     *
     * @param int $id
     * @return View
     */
    public function edit(int $id): View
    {
        $row = $this->categoryRepository->find($id);
        abort_if(!$row, 404);

        return view('admin.category.create_edit', [
            'row' => $row,
            'infoAlert' => __('frontend.hint.category_create'),
            'title' => __('frontend.title.category_edit'),
        ]);
    }

    /**
     * Validate and save changes to an existing subscriber category.
     *
     * @param EditRequest $request
     * @return RedirectResponse
     */
    public function update(EditRequest $request): RedirectResponse
    {
        try {
            $data = $request->validated();

            $this->categoryRepository->update(
                (int) $data['id'],
                new CategoryUpdateData(
                    name: $data['name'],
                )
            );
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->with('error', $e->getMessage())
                ->withInput();
        }

        return to_route('admin.category.index')
            ->with('success', __('message.data_updated'));
    }

    /**
     * Delete a subscriber category and return the user to the category list.
     *
     * @param int $id
     * @return RedirectResponse
     */
    public function destroy(int $id): RedirectResponse
    {
        abort_unless($this->categoryRepository->find($id), 404);
        try {
            $this->categoryRepository->delete($id);
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->with('error', $e->getMessage());
        }

        return to_route('admin.category.index')
            ->with('success', __('frontend.msg.data_successfully_deleted'));
    }
}

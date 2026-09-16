<?php

namespace App\Http\Controllers\Admin;


use App\DTO\Create\MacrosCreateData;
use App\DTO\Update\MacrosUpdateData;
use App\Http\Requests\Admin\Macros\EditRequest;
use App\Http\Requests\Admin\Macros\StoreRequest;
use App\Models\Macros;
use App\Repositories\MacrosRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MacrosController extends Controller
{
    /**
     * Inject the macro repository used by all macro management actions.
     */
    public function __construct(
        private readonly MacrosRepository $macrosRepository
    ) {
        parent::__construct();
    }

    /**
     * Show the macro management page where available macros are listed.
     *
     * @return View
     */
    public function index(): View
    {
        return view('admin.macros.index', [
            'infoAlert' => __('frontend.hint.macros_index'),
            'title' => __('frontend.title.macros_index'),
        ]);
    }

    /**
     * Show the form used to create a new template macro.
     *
     * @return View
     */
    public function create(): View
    {
        return view('admin.macros.create_edit', [
            'infoAlert' => __('frontend.hint.macros_create'),
            'options' => Macros::getOption(),
            'title' => __('frontend.title.macros_create'),
        ]);
    }

    /**
     * Validate and persist a new template macro.
     *
     * @param StoreRequest $request
     * @return RedirectResponse
     */
    public function store(StoreRequest $request): RedirectResponse
    {
        try {
            $data = $request->validated();

            $this->macrosRepository->add(
                new MacrosCreateData(
                    name: $data['name'],
                    value: $data['value'],
                    type: (int) $data['type'],
                )
            );
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->with('error', $e->getMessage())
                ->withInput();
        }

        return to_route('admin.macros.index')->with('success', __('message.information_successfully_added'));
    }

    /**
     * Show the edit form for an existing template macro.
     *
     * @param int $id
     * @return View
     */
    public function edit(int $id): View
    {
        $row = $this->macrosRepository->find($id);

        abort_if(!$row, 404);

        return view('admin.macros.create_edit', [
            'row' => $row,
            'infoAlert' => __('frontend.hint.macros_create'),
            'options' => Macros::getOption(),
            'title' => __('frontend.title.macros_edit'),
        ]);
    }

    /**
     * Validate and save changes to an existing template macro.
     *
     * @param EditRequest $request
     * @return RedirectResponse
     */
    public function update(EditRequest $request): RedirectResponse
    {
        try {
            $data = $request->validated();

            $this->macrosRepository->update(
                (int) $data['id'],
                new MacrosUpdateData(
                    name: $data['name'],
                    value: $data['value'],
                    type: (int) $data['type'],
                )
            );
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->with('error', $e->getMessage())
                ->withInput();
        }

        return to_route('admin.macros.index')->with('success', __('message.data_updated'));
    }

    /**
     * Delete a template macro for AJAX-driven grid actions.
     *
     * @param int $id
     * @return void
     */
    public function destroy(int $id): void
    {
        $this->macrosRepository->delete($id);
    }
}

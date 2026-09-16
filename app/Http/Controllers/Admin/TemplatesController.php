<?php

namespace App\Http\Controllers\Admin;


use App\DTO\Create\TemplatesCreateData;
use App\DTO\Update\TemplatesUpdateData;
use App\Http\Requests\Admin\Templates\StoreRequest;
use App\Http\Requests\Admin\Templates\DeleteRequest;
use App\Http\Requests\Admin\Templates\UpdateRequest;
use App\Models\Macros;
use App\Repositories\CategoryRepository;
use App\Repositories\TemplateRepository;
use App\Services\TemplateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;


class TemplatesController extends Controller
{
    /**
     * Inject repositories and services required to manage email templates and attachments.
     *
     * @param TemplateRepository $templateRepository
     * @param CategoryRepository $categoryRepository
     * @param TemplateService $templateService
     */
    public function __construct(
        private readonly TemplateRepository $templateRepository,
        private readonly CategoryRepository $categoryRepository,
        private readonly TemplateService $templateService,
    ) {
        parent::__construct();
    }

    /**
     * Show the template management page with category filters.
     *
     * @return View
     */
    public function index(): View
    {
        return view('admin.templates.index', [
            'categoryOptions' => $this->categoryRepository->getOption(),
            'infoAlert' => __('frontend.hint.template_index'),
            'title' => __('frontend.title.template_index'),
        ]);
    }

    /**
     * Show the form used to create a new email template.
     *
     * @return View
     */
    public function create(): View
    {
        return view('admin.templates.create_edit', [
            'infoAlert' => __('frontend.hint.template_create'),
            'macrosList' => $this->getMacros(),
            'title' => __('frontend.title.template_create'),
        ]);
    }

    /**
     * Show a read-only card with template details and rendered email body.
     *
     * @param int $id
     * @return View
     */
    public function show(int $id): View
    {
        $template = $this->templateRepository->find($id);

        abort_if(!$template, 404);

        $template->load('attach');

        return view('admin.templates.show', [
            'template' => $template,
            'title' => $template->name,
        ]);
    }

    /**
     * Validate and persist a new email template together with uploaded attachments.
     *
     * @param StoreRequest $request
     * @return RedirectResponse
     */
    public function store(StoreRequest $request): RedirectResponse
    {
        try {
            $data = $request->validated();
            $template = $this->templateRepository->add(
                new TemplatesCreateData(
                    name: $data['name'],
                    body: $data['body'],
                    prior: (int) $data['prior'],
                )
            );

            $this->templateService->storeAttach($request, $template->id);
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->with('error', $e->getMessage())
                ->withInput();
        }

        return to_route('admin.templates.index')->with('success', __('message.information_successfully_added'));
    }

    /**
     * Show the edit form for an existing email template and its attachments.
     *
     * @param int $id
     * @return View
     */
    public function edit(int $id): View
    {
        $template = $this->templateRepository->find($id);

        abort_if(!$template, 404);

        return view('admin.templates.create_edit', [
            'template' => $template,
            'attachment' => $template->attach,
            'infoAlert' => __('frontend.hint.template_edit'),
            'macrosList' => $this->getMacros(),
            'title' => __('frontend.title.template_edit'),
        ]);
    }

    /**
     * Validate and save changes to an email template and its attachments.
     *
     * @param UpdateRequest $request
     * @return RedirectResponse
     */
    public function update(UpdateRequest $request): RedirectResponse
    {
        try {
            $data = $request->validated();

            $this->templateRepository->update(
                (int) $data['id'],
                new TemplatesUpdateData(
                    name: $data['name'],
                    body: $data['body'],
                    prior: (int) $data['prior'],
                )
            );

            $this->templateService->storeAttach($request, (int) $data['id']);
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->with('error', $e->getMessage())
                ->withInput();
        }

        return to_route('admin.templates.index')->with('success', __('message.data_updated'));
    }

    /**
     * Delete an email template and related resources.
     *
     * @param int $id
     * @return RedirectResponse
     */
    public function destroy(int $id): RedirectResponse
    {
        try {
            $this->templateRepository->remove($id);
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', $e->getMessage());
        }

        return to_route('admin.templates.index')->with('success', __('frontend.msg.data_successfully_deleted'));
    }

    /**
     * Delete selected email templates from the bulk action form.
     *
     * @param DeleteRequest $request
     * @return RedirectResponse
     */
    public function delete(DeleteRequest $request): RedirectResponse
    {
        try {
            $this->templateRepository->updateStatus(
                $request->templateId,
                (int) $request->action
            );
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', $e->getMessage());
        }

        return to_route('admin.templates.index')->with('success', __('message.actions_completed'));
    }

    /**
     * Build the human-readable macro reference string displayed in the template editor.
     *
     * @return string
     */
    private function getMacros(): string
    {
        return Macros::query()
            ->get()
            ->map(fn ($macro) => '{{' . $macro->name . '}} - ' . $macro->getType())
            ->implode(', ');
    }
}

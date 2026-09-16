<?php

namespace App\Http\Controllers\Admin;


use App\DTO\Create\SmtpCreateData;
use App\DTO\Update\SmtpUpdateData;
use App\Http\Requests\Admin\Smtp\EditRequest;
use App\Http\Requests\Admin\Smtp\StatusRequest;
use App\Http\Requests\Admin\Smtp\StoreRequest;
use App\Repositories\SmtpRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SmtpController extends Controller
{
    /**
     * Inject the SMTP repository used by all SMTP account management actions.
     */
    public function __construct(
        private readonly SmtpRepository $smtpRepository
    ) {
        parent::__construct();
    }

    /**
     * Show the SMTP account management page.
     *
     * @return View
     */
    public function index(): View
    {
        return view('admin.smtp.index', [
            'infoAlert' => __('frontend.hint.smtp_index'),
            'title' => __('frontend.title.smtp_index'),
        ]);
    }

    /**
     * Show the form used to create a new SMTP account.
     *
     * @return View
     */
    public function create(): View
    {
        return view('admin.smtp.create_edit', [
            'infoAlert' => __('frontend.hint.smtp_create'),
            'title' => __('frontend.title.smtp_create'),
        ]);
    }

    /**
     * Show SMTP account details.
     *
     * @param int $id
     * @return View
     */
    public function show(int $id): View
    {
        $row = $this->smtpRepository->find($id);

        abort_if(!$row, 404);

        return view('admin.smtp.show', [
            'row' => $row,
            'title' => __('frontend.str.smtp_server'),
        ]);
    }

    /**
     * Validate and persist a new SMTP account configuration.
     *
     * @param StoreRequest $request
     * @return RedirectResponse
     */
    public function store(StoreRequest $request): RedirectResponse
    {
        try {
            $data = $request->validated();

            $this->smtpRepository->createWithMapping(
                new SmtpCreateData(
                    host: $data['host'],
                    username: $data['username'],
                    email: $data['email'],
                    password: $data['password'],
                    port: (int) $data['port'],
                    authentication: $data['authentication'],
                    secure: $data['secure'],
                    timeout: (int) $data['timeout'],
                )
            );
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->with('error', $e->getMessage())
                ->withInput();
        }

        return to_route('admin.smtp.index')->with('success', __('message.information_successfully_added'));
    }

    /**
     * Show the edit form for an existing SMTP account.
     *
     * @param int $id
     * @return View
     */
    public function edit(int $id): View
    {
        $row = $this->smtpRepository->find($id);

        abort_if(!$row, 404);

        return view('admin.smtp.create_edit', [
            'row' => $row,
            'infoAlert' => __('frontend.hint.smtp_edit'),
            'title' => __('frontend.title.smtp_edit'),
        ]);
    }

    /**
     * Validate and save changes to an existing SMTP account.
     *
     * @param EditRequest $request
     * @return RedirectResponse
     */
    public function update(EditRequest $request): RedirectResponse
    {
        try {
            $data = $request->validated();

            $this->smtpRepository->update(
                (int) $data['id'],
                new SmtpUpdateData(
                    host: $data['host'],
                    username: $data['username'],
                    email: $data['email'],
                    password: $data['password'] ?? null,
                    port: (int) $data['port'],
                    authentication: $data['authentication'],
                    secure: $data['secure'],
                    timeout: (int) $data['timeout'],
                )
            );
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->with('error', $e->getMessage())
                ->withInput();
        }

        return to_route('admin.smtp.index')->with('success', __('message.data_updated'));
    }

    /**
     * Delete an SMTP account for AJAX-driven grid actions.
     *
     * @param int $id
     * @return void
     */
    public function destroy(int $id): void
    {
        $this->smtpRepository->delete($id);
    }

    /**
     * Activate or deactivate selected SMTP accounts from the bulk action form.
     *
     * @param StatusRequest $request
     * @return RedirectResponse
     */
    public function status(StatusRequest $request): RedirectResponse
    {
        try {
            $this->smtpRepository->updateStatus(
                (int) $request->action,
                $request->activate
            );
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', $e->getMessage());
        }

        return to_route('admin.smtp.index')->with('success', __('message.actions_completed'));
    }
}

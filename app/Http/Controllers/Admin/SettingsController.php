<?php

namespace App\Http\Controllers\Admin;

use App\DTO\Update\SettingsUpdateData;
use App\Models\CustomHeaders;
use App\Repositories\SettingsRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    /**
     * Inject the settings repository used to persist application configuration.
     */
    public function __construct(
        private readonly SettingsRepository $settingsRepository
    ) {
        parent::__construct();
    }

    /**
     * Show the application settings page with custom header options.\
     *
     * @return View
     */
    public function index(): View
    {
        return view('admin.settings.index', [
            'customHeaders' => CustomHeaders::get(),
            'infoAlert' => __('frontend.hint.settings_index'),
            'title' => __('frontend.title.settings_index'),
        ]);
    }

    /**
     * Persist application settings submitted from the settings form.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function update(Request $request): RedirectResponse
    {
        try {
            $this->settingsRepository->setSettings(
                new SettingsUpdateData(
                    $request->except(['_token', '_method'])
                )
            );
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->with('error', $e->getMessage())
                ->withInput();
        }

        return to_route('admin.settings.index')->with('success', __('message.data_updated'));
    }
}

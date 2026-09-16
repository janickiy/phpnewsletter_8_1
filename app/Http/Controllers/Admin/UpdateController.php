<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\UpdateHelper;
use App\Services\UpdateService;
use Illuminate\View\View;

class UpdateController extends Controller
{
    /**
     * Initialize the update page controller with the application update service.
     */
    public function __construct(private readonly UpdateService $updateService)
    {
        parent::__construct();
    }

    /**
     * Show update availability information and prepare the update action message.
     *
     * @return View
     */
    public function index(): View
    {
        $update = new UpdateHelper(app()->getLocale(), config('app.version'));

        $buttonUpdate = '';
        $msgNoUpdate = '';

        if ($update->checkUpgrade() && $update->checkTree()) {
            $buttonUpdate = str_replace(
                ['%NEW_VERSION%', '%SCRIPT_NAME%'],
                [$update->getUpgradeVersion(), __('frontend.str.script_name')],
                __('frontend.str.button_update')
            );
        } else {
            $msgNoUpdate = str_replace(
                ['%SCRIPT_NAME%', '%NEW_VERSION%'],
                [__('frontend.str.script_name'), config('app.version')],
                __('frontend.msg.no_updates')
            );
        }

        return view('admin.update.index', [
            'button_update' => $buttonUpdate,
            'msg_no_update' => $msgNoUpdate,
            'infoAlert' => __('frontend.hint.update_index'),
            'title' => __('frontend.title.update'),
            'update_steps' => $this->updateService->getClientSteps(),
        ]);
    }
}

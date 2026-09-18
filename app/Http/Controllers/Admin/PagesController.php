<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\StringHelper;
use App\Models\Project;
use App\Services\ProjectAccess;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PagesController extends Controller
{
    /**
     * Show the frequently asked questions page.
     *
     * @return View
     */
    public function faq(): View
    {
        return view('admin.pages.faq', [
            'title' => 'FAQ',
            'infoAlert' => __('frontend.hint.faq_index'),
        ]);
    }

    /**
     * Show example cron commands for manual server scheduling.
     *
     * @return View
     */
    public function cronJobList(): View
    {
        $path = base_path() . '/artisan';

        return view('admin.pages.cron_job_list', [
            'cronJob' => [

                [
                    'description' => __('frontend.str.emails_send'),
                    'cron' => '/usr/bin/php -q ' . $path . ' emails:send',
                ],

                [
                    'description' => __('frontend.str.emails_unsent'),
                    'cron' => '/usr/bin/php -q ' . $path . ' emails:unsent',
                ],

                [
                    'description' => __('frontend.str.remove_subscriber'),
                    'cron' => '/usr/bin/php -q ' . $path . ' emails:remove-unconfirmed-subscriber',
                ],
            ],
            'infoAlert' => __('frontend.hint.cron_job_list'),
            'title' => 'Crontab',
        ]);
    }

    /**
     * Show formatted PHP runtime information for diagnostics.
     *
     * @return View
     */
    public function phpinfo(): View
    {
        return view('admin.pages.phpinfo', [
            'phpinfo' => StringHelper::phpinfoArray(),
            'infoAlert' => __('frontend.hint.phpinfo'),
            'title' => 'PHP Info',
        ]);
    }

    /**
     * Show the embeddable subscription form preview and copyable source code.
     *
     * @param Request $request
     * @return View
     * @throws \Throwable
     */
    public function subscriptionForm(Request $request): View
    {
        $projects = ProjectAccess::projects()->where('status', 1)
            ->orderByRaw('projects.id = ? DESC', [Project::DEFAULT_ID])->orderBy('name')->get();
        $project = $request->filled('project_id')
            ? ProjectAccess::authorizeProject((int) $request->input('project_id'))
            : $projects->first();
        abort_if($project && !$project->status, 404);
        $subform = view('include.subform', compact('project'))->render();
        $subformJs = view('include.subform_js', compact('project'))->render();

        $subform = preg_replace('/<input name="_token" type="hidden"[^>]*>\s*/si', "\n\n    ", $subform);
        $embedCode = trim($subform) . "\n"
            . '<script src="//ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>' . "\n"
            . trim($subformJs);

        return view('admin.pages.subscription_form', [
            'infoAlert' => __('frontend.hint.subscription_form'),
            'embedCode' => $embedCode,
            'project' => $project,
            'projects' => $projects,
            'title' => __('frontend.title.subscription_form'),
        ]);
    }
}

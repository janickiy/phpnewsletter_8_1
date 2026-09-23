<?php

namespace App\Http\Controllers\Admin;

use App\Models\Category;
use App\Models\Macros;
use App\Models\ReadySent;
use App\Models\Redirect;
use App\Models\Schedule;
use App\Models\Subscribers;
use App\Models\Templates;
use App\Models\User;
use App\Services\ProjectAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Show the AdminLTE dashboard landing page.
     */
    public function index(): View
    {
        $isAdmin = auth()->user()->isAdmin();
        $canManage = auth()->user()->canManageProjects();
        $sentTotal = ProjectAccess::scope(ReadySent::query())->count();
        $sentSuccess = ProjectAccess::scope(ReadySent::query())->where('success', 1)->count();
        $sentFailed = ProjectAccess::scope(ReadySent::query())->where('success', 0)->count();
        $readTotal = ProjectAccess::scope(ReadySent::query())->where('readMail', 1)->count();

        $stats = [
            'projects' => ProjectAccess::projects('manage')->count(),
            'templates' => ProjectAccess::scope(Templates::query(), 'manage')->count(),
            'subscribers' => ProjectAccess::subscribers(Subscribers::query())->count(),
            'activeSubscribers' => ProjectAccess::subscribers(Subscribers::query())->where('active', 1)->count(),
            'categories' => ($isAdmin ? ProjectAccess::categories(Category::query(), 'manage')->count() : 0),
            'schedule' => ProjectAccess::scope(Schedule::query(), 'manage')->count(),
            'upcomingSchedule' => ProjectAccess::scope(Schedule::query(), 'manage')->where('event_start', '>=', now())->count(),
            'clicks' => ProjectAccess::redirects(Redirect::query())->count(),
            'macros' => ($isAdmin ? Macros::query()->count() : 0),
            'users' => ($isAdmin ? User::query()->count() : 0),
            'sentTotal' => $sentTotal,
            'sentSuccess' => $sentSuccess,
            'sentFailed' => $sentFailed,
            'readTotal' => $readTotal,
            'deliveryRate' => $sentTotal > 0 ? round($sentSuccess / $sentTotal * 100) : 0,
            'openRate' => $sentSuccess > 0 ? round($readTotal / $sentSuccess * 100) : 0,
        ];

        $latestMailings = ProjectAccess::scope(Schedule::query(), 'view', 'ready_sent.project_id')
            ->selectRaw(
                'ready_sent.log_id AS id, schedule.event_name, schedule.event_start, schedule.event_end, ' .
                'COUNT(ready_sent.id) AS count, ' .
                'SUM(ready_sent.success = 1) AS sent, ' .
                'SUM(ready_sent.success = 0) AS failed, ' .
                'SUM(ready_sent.readMail = 1) AS read_mail, ' .
                'MAX(ready_sent.created_at) AS last_sent_at'
            )
            ->join('ready_sent', 'schedule.id', '=', 'ready_sent.schedule_id')
            ->whereNotNull('ready_sent.log_id')
            ->groupBy('ready_sent.log_id', 'schedule.id', 'schedule.event_name', 'schedule.event_start', 'schedule.event_end')
            ->orderByDesc(DB::raw('MAX(ready_sent.created_at)'))
            ->limit(5)
            ->get();

        return view('admin.dashboard.index', [
            'title' => __('frontend.title.dashboard_index'),
            'isAdmin' => $isAdmin,
            'canManage' => $canManage,
            'stats' => $stats,
            'latestTemplates' => ProjectAccess::scope(Templates::query(), 'manage')->latest()->limit(5)->get(),
            'latestSubscribers' => ProjectAccess::subscribers(Subscribers::query())->latest()->limit(5)->get(),
            'latestMailings' => $latestMailings,
            'upcomingSchedules' => ProjectAccess::scope(Schedule::query(), 'manage')
                ->with('template')
                ->where('event_start', '>=', now())
                ->orderBy('event_start')
                ->limit(5)
                ->get(),
        ]);
    }
}

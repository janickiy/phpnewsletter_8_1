<?php

namespace App\Http\Controllers\Admin;

use App\Models\Category;
use App\Models\Macros;
use App\Models\ReadySent;
use App\Models\Redirect;
use App\Models\Schedule;
use App\Models\Smtp;
use App\Models\Subscribers;
use App\Models\Templates;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Show the AdminLTE dashboard landing page.
     */
    public function index(): View
    {
        $sentTotal = ReadySent::query()->count();
        $sentSuccess = ReadySent::query()->where('success', 1)->count();
        $sentFailed = ReadySent::query()->where('success', 0)->count();
        $readTotal = ReadySent::query()->where('readMail', 1)->count();
        $smtpTotal = Smtp::query()->count();

        $stats = [
            'templates' => Templates::query()->count(),
            'subscribers' => Subscribers::query()->count(),
            'activeSubscribers' => Subscribers::query()->where('active', 1)->count(),
            'categories' => Category::query()->count(),
            'schedule' => Schedule::query()->count(),
            'upcomingSchedule' => Schedule::query()->where('event_start', '>=', now())->count(),
            'smtp' => $smtpTotal,
            'activeSmtp' => Smtp::query()->where('active', 1)->count(),
            'macros' => Macros::query()->count(),
            'users' => User::query()->count(),
            'sentTotal' => $sentTotal,
            'sentSuccess' => $sentSuccess,
            'sentFailed' => $sentFailed,
            'readTotal' => $readTotal,
            'clicks' => Redirect::query()->count(),
            'deliveryRate' => $sentTotal > 0 ? round($sentSuccess / $sentTotal * 100) : 0,
            'openRate' => $sentSuccess > 0 ? round($readTotal / $sentSuccess * 100) : 0,
        ];

        $latestMailings = Schedule::query()
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
            'stats' => $stats,
            'latestTemplates' => Templates::query()->latest()->limit(5)->get(),
            'latestSubscribers' => Subscribers::query()->latest()->limit(5)->get(),
            'latestMailings' => $latestMailings,
            'upcomingSchedules' => Schedule::query()
                ->with('template')
                ->where('event_start', '>=', now())
                ->orderBy('event_start')
                ->limit(5)
                ->get(),
        ]);
    }
}

@extends('admin.app')

@section('title', $title)

@section('css')
    <style>
        .dashboard-small-box {
            min-height: 154px;
        }

        .dashboard-small-box .inner {
            min-height: 118px;
        }

        .dashboard-small-box h3 {
            font-size: 2.15rem;
            line-height: 1.1;
        }

        .dashboard-small-box p {
            margin-bottom: .25rem;
        }

        .dashboard-small-box .dashboard-note {
            display: block;
            min-height: 18px;
            opacity: .9;
        }

        .dashboard-table td,
        .dashboard-table th {
            vertical-align: middle;
        }

        .dashboard-subscribers-table {
            table-layout: fixed;
            width: 100%;
        }

        .dashboard-subscribers-table th,
        .dashboard-subscribers-table td {
            overflow-wrap: anywhere;
            padding-left: .5rem;
            padding-right: .5rem;
        }

        .dashboard-subscribers-table th:nth-child(1) {
            width: 19%;
        }

        .dashboard-subscribers-table th:nth-child(2) {
            width: 35%;
        }

        .dashboard-subscribers-table th:nth-child(3) {
            width: 27%;
        }

        .dashboard-subscribers-table th:nth-child(4) {
            width: 19%;
        }

        .dashboard-subscribers-table .badge {
            max-width: 100%;
            white-space: normal;
        }

        .dashboard-empty {
            color: #6c757d;
            padding: 1.5rem 0;
            text-align: center;
        }

        .dashboard-progress-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: .35rem;
        }
    </style>
@endsection

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-info dashboard-small-box">
                        <div class="inner">
                            <h3>{{ number_format($stats['templates']) }}</h3>
                            <p>{{ __('frontend.menu.templates') }}</p>
                            <span class="dashboard-note">{{ __('frontend.str.add_template') }}</span>
                        </div>
                        <div class="icon">
                            <i class="fas fa-envelope-open-text"></i>
                        </div>
                        <a href="{{ route('admin.templates.index') }}" class="small-box-footer">
                            {{ __('frontend.dashboard.open_section') }} <i class="fas fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </div>

                <div class="col-lg-3 col-6">
                    <div class="small-box bg-success dashboard-small-box">
                        <div class="inner">
                            <h3>{{ number_format($stats['subscribers']) }}</h3>
                            <p>{{ __('frontend.menu.subscribers') }}</p>
                            <span class="dashboard-note">{{ __('frontend.dashboard.active_count', ['count' => number_format($stats['activeSubscribers'])]) }}</span>
                        </div>
                        <div class="icon">
                            <i class="fas fa-user-friends"></i>
                        </div>
                        <a href="{{ route('admin.subscribers.index') }}" class="small-box-footer">
                            {{ __('frontend.dashboard.open_section') }} <i class="fas fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </div>

                <div class="col-lg-3 col-6">
                    <div class="small-box bg-warning dashboard-small-box">
                        <div class="inner">
                            <h3>{{ number_format($stats['schedule']) }}</h3>
                            <p>{{ __('frontend.menu.schedule') }}</p>
                            <span class="dashboard-note">{{ __('frontend.dashboard.active_count', ['count' => number_format($stats['upcomingSchedule'])]) }}</span>
                        </div>
                        <div class="icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <a href="{{ route('admin.schedule.index') }}" class="small-box-footer">
                            {{ __('frontend.dashboard.open_section') }} <i class="fas fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </div>

                <div class="col-lg-3 col-6">
                    <div class="small-box bg-danger dashboard-small-box">
                        <div class="inner">
                            <h3>{{ number_format($stats['sentTotal']) }}</h3>
                            <p>{{ __('frontend.str.sent') }}</p>
                            <span class="dashboard-note">{{ number_format($stats['sentFailed']) }} {{ __('frontend.str.error') }}</span>
                        </div>
                        <div class="icon">
                            <i class="fas fa-paper-plane"></i>
                        </div>
                        <a href="{{ route('admin.log.index') }}" class="small-box-footer">
                            {{ __('frontend.dashboard.open_section') }} <i class="fas fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-primary dashboard-small-box">
                        <div class="inner">
                            <h3>{{ number_format($stats['categories']) }}</h3>
                            <p>{{ __('frontend.menu.subscribers_category') }}</p>
                            <span class="dashboard-note">{{ __('frontend.str.category') }}</span>
                        </div>
                        <div class="icon">
                            <i class="fas fa-list"></i>
                        </div>
                        <a href="{{ route('admin.category.index') }}" class="small-box-footer">
                            {{ __('frontend.dashboard.open_section') }} <i class="fas fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </div>

                <div class="col-lg-3 col-6">
                    <div class="small-box bg-secondary dashboard-small-box">
                        <div class="inner">
                            <h3>{{ number_format($stats['smtp']) }}</h3>
                            <p>{{ __('frontend.str.smtp_server') }}</p>
                            <span class="dashboard-note">{{ __('frontend.dashboard.active_count', ['count' => number_format($stats['activeSmtp'])]) }}</span>
                        </div>
                        <div class="icon">
                            <i class="fas fa-inbox"></i>
                        </div>
                        <a href="{{ route('admin.smtp.index') }}" class="small-box-footer">
                            {{ __('frontend.dashboard.open_section') }} <i class="fas fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </div>

                <div class="col-lg-3 col-6">
                    <div class="small-box bg-purple dashboard-small-box">
                        <div class="inner">
                            <h3>{{ number_format($stats['clicks']) }}</h3>
                            <p>{{ __('frontend.str.redirect') }}</p>
                            <span class="dashboard-note">{{ __('frontend.str.redirect_number') }}</span>
                        </div>
                        <div class="icon">
                            <i class="fas fa-mouse-pointer"></i>
                        </div>
                        <a href="{{ route('admin.redirect.index') }}" class="small-box-footer">
                            {{ __('frontend.dashboard.open_section') }} <i class="fas fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </div>

                <div class="col-lg-3 col-6">
                    <div class="small-box bg-dark dashboard-small-box">
                        <div class="inner">
                            <h3>{{ number_format($stats['users']) }}</h3>
                            <p>{{ __('frontend.menu.users') }}</p>
                            <span class="dashboard-note">{{ number_format($stats['macros']) }} {{ __('frontend.menu.macros') }}</span>
                        </div>
                        <div class="icon">
                            <i class="fas fa-users-cog"></i>
                        </div>
                        <a href="{{ route('admin.users.index') }}" class="small-box-footer">
                            {{ __('frontend.dashboard.open_section') }} <i class="fas fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">{{ __('frontend.dashboard.quick_actions') }}</h3>
                        </div>
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush">
                                <a href="{{ route('admin.templates.create') }}" class="list-group-item list-group-item-action">
                                    <i class="fas fa-plus text-info mr-2"></i>{{ __('frontend.str.add_template') }}
                                    <i class="fas fa-angle-right float-right mt-1"></i>
                                </a>
                                @if(PermissionsHelper::has_permission('admin|moderator'))
                                    <a href="{{ route('admin.subscribers.import') }}" class="list-group-item list-group-item-action">
                                        <i class="fas fa-file-import text-success mr-2"></i>{{ __('frontend.str.import_subscribers') }}
                                        <i class="fas fa-angle-right float-right mt-1"></i>
                                    </a>
                                @endif
                                <a href="{{ route('admin.schedule.create') }}" class="list-group-item list-group-item-action">
                                    <i class="fas fa-calendar-plus text-warning mr-2"></i>{{ __('frontend.str.add_schedule') }}
                                    <i class="fas fa-angle-right float-right mt-1"></i>
                                </a>
                                @if(PermissionsHelper::has_permission('admin'))
                                    <a href="{{ route('admin.settings.index') }}" class="list-group-item list-group-item-action">
                                        <i class="fas fa-cogs text-secondary mr-2"></i>{{ __('frontend.menu.settings') }}
                                        <i class="fas fa-angle-right float-right mt-1"></i>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">{{ __('frontend.dashboard.delivery_overview') }}</h3>
                        </div>
                        <div class="card-body">
                            <div class="dashboard-progress-label">
                                <span>{{ __('frontend.str.sent') }}</span>
                                <strong>{{ $stats['deliveryRate'] }}%</strong>
                            </div>
                            <div class="progress mb-3">
                                <div class="progress-bar bg-success" style="width: {{ $stats['deliveryRate'] }}%"></div>
                            </div>

                            <div class="dashboard-progress-label">
                                <span>{{ __('frontend.str.read') }}</span>
                                <strong>{{ $stats['openRate'] }}%</strong>
                            </div>
                            <div class="progress mb-3">
                                <div class="progress-bar bg-info" style="width: {{ $stats['openRate'] }}%"></div>
                            </div>

                            <div class="d-flex justify-content-between">
                                <span>{{ __('frontend.str.good') }}</span>
                                <strong>{{ number_format($stats['sentSuccess']) }}</strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>{{ __('frontend.str.bad') }}</span>
                                <strong>{{ number_format($stats['sentFailed']) }}</strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>{{ __('frontend.str.read') }}</span>
                                <strong>{{ number_format($stats['readTotal']) }}</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">{{ __('frontend.str.newsletter') }}</h3>
                            <div class="card-tools">
                                <a href="{{ route('admin.log.index') }}" class="btn btn-tool" title="{{ __('frontend.menu.mailing_log') }}">
                                    <i class="fas fa-external-link-alt"></i>
                                </a>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-striped dashboard-table">
                                <thead>
                                <tr>
                                    <th>{{ __('frontend.str.newsletter') }}</th>
                                    <th>{{ __('frontend.str.total') }}</th>
                                    <th>{{ __('frontend.str.sent') }}</th>
                                    <th>{{ __('frontend.str.read') }}</th>
                                    <th>{{ __('frontend.str.date') }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse($latestMailings as $mailing)
                                    <tr>
                                        <td>
                                            <a href="{{ route('admin.log.info', ['id' => $mailing->id]) }}">
                                                {{ $mailing->event_name }}
                                            </a>
                                        </td>
                                        <td>{{ number_format($mailing->count) }}</td>
                                        <td>
                                            <span class="badge badge-success">{{ number_format($mailing->sent ?? 0) }}</span>
                                            <span class="badge badge-danger">{{ number_format($mailing->failed ?? 0) }}</span>
                                        </td>
                                        <td>{{ number_format($mailing->read_mail ?? 0) }}</td>
                                        <td>{{ optional(\Illuminate\Support\Carbon::parse($mailing->last_sent_at))->format('d.m.Y H:i') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="dashboard-empty">{{ __('frontend.dashboard.no_mailings') }}</td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">{{ __('frontend.menu.templates') }}</h3>
                            <div class="card-tools">
                                <a href="{{ route('admin.templates.index') }}" class="btn btn-tool" title="{{ __('frontend.menu.templates') }}">
                                    <i class="fas fa-external-link-alt"></i>
                                </a>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-striped dashboard-table">
                                <thead>
                                <tr>
                                    <th>{{ __('frontend.str.template') }}</th>
                                    <th>{{ __('frontend.str.importance') }}</th>
                                    <th>{{ __('frontend.str.date') }}</th>
                                    <th class="text-right">{{ __('frontend.str.action') }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse($latestTemplates as $template)
                                    <tr>
                                        <td>{{ $template->name }}</td>
                                        <td>{{ $template->getPrior() }}</td>
                                        <td>{{ optional($template->created_at)->format('d.m.Y H:i') }}</td>
                                        <td class="text-right">
                                            <a href="{{ route('admin.templates.edit', ['id' => $template->id]) }}" class="btn btn-outline-info btn-sm">
                                                {{ __('frontend.str.edit') }}
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="dashboard-empty">{{ __('frontend.dashboard.no_templates') }}</td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">{{ __('frontend.menu.subscribers') }}</h3>
                            <div class="card-tools">
                                <a href="{{ route('admin.subscribers.index') }}" class="btn btn-tool" title="{{ __('frontend.menu.subscribers') }}">
                                    <i class="fas fa-external-link-alt"></i>
                                </a>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-striped dashboard-table dashboard-subscribers-table">
                                <thead>
                                <tr>
                                    <th>{{ __('frontend.str.name') }}</th>
                                    <th>{{ __('frontend.str.email') }}</th>
                                    <th>{{ __('frontend.str.status') }}</th>
                                    <th>{{ __('frontend.str.added') }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse($latestSubscribers as $subscriber)
                                    <tr>
                                        <td>{{ $subscriber->name ?: '-' }}</td>
                                        <td>{{ $subscriber->email }}</td>
                                        <td>
                                            @if($subscriber->active)
                                                <span class="badge badge-success">{{ __('frontend.str.activate') }}</span>
                                            @else
                                                <span class="badge badge-secondary">{{ __('frontend.str.deactivate') }}</span>
                                            @endif
                                        </td>
                                        <td>{{ optional($subscriber->created_at)->format('d.m.Y H:i') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="dashboard-empty">{{ __('frontend.dashboard.no_subscribers') }}</td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">{{ __('frontend.menu.schedule') }}</h3>
                            <div class="card-tools">
                                <a href="{{ route('admin.schedule.index') }}" class="btn btn-tool" title="{{ __('frontend.menu.schedule') }}">
                                    <i class="fas fa-external-link-alt"></i>
                                </a>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-striped dashboard-table">
                                <thead>
                                <tr>
                                    <th>{{ __('frontend.str.newsletter') }}</th>
                                    <th>{{ __('frontend.str.template') }}</th>
                                    <th>{{ __('frontend.str.date') }}</th>
                                    <th class="text-right">{{ __('frontend.str.action') }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse($upcomingSchedules as $schedule)
                                    <tr>
                                        <td>{{ $schedule->event_name }}</td>
                                        <td>{{ $schedule->template?->name ?: '-' }}</td>
                                        <td>{{ optional(\Illuminate\Support\Carbon::parse($schedule->event_start))->format('d.m.Y H:i') }}</td>
                                        <td class="text-right">
                                            <a href="{{ route('admin.schedule.edit', ['id' => $schedule->id]) }}" class="btn btn-outline-info btn-sm">
                                                {{ __('frontend.str.edit') }}
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="dashboard-empty">{{ __('frontend.dashboard.no_schedules') }}</td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@extends('admin.app')

@section('title', $title)

@section('css')
    <style>
        .dashboard-small-box {
            min-height: 154px;
            height: 100%;
            width: 100%;
            display: flex;
            flex-direction: column;
            margin-bottom: 0;
        }

        .dashboard-small-box > .inner {
            min-height: 128px;
            flex: 1 1 auto;
            padding: 1rem;
            position: relative;
            z-index: 1;
        }

        .dashboard-page .dashboard-small-box h3 {
            font-size: 2.25rem;
            line-height: 1.1;
            margin-bottom: .5rem;
        }

        .dashboard-small-box p {
            margin-bottom: .25rem;
            max-width: calc(100% - 3rem);
        }

        .dashboard-small-box .dashboard-note {
            display: block;
            min-height: 18px;
            opacity: .9;
            font-size: .875rem;
        }

        .dashboard-small-box .small-box-footer {
            font-weight: 600;
        }

        .dashboard-panels {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 2fr);
            grid-template-areas: "actions mailings" "delivery templates";
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .dashboard-quick-actions { grid-area: actions; }
        .dashboard-mailings { grid-area: mailings; }
        .dashboard-delivery { grid-area: delivery; }
        .dashboard-templates { grid-area: templates; }

        .dashboard-page .card {
            min-width: 0;
            margin-bottom: 0;
        }

        .dashboard-page .card-header {
            display: flex;
            align-items: center;
            gap: .5rem;
        }

        .dashboard-page .card-title {
            font-size: 1.125rem;
            font-weight: 600;
        }

        .dashboard-page .card-tools {
            margin-left: auto;
        }

        .dashboard-page .table {
            margin-bottom: 0;
        }

        .dashboard-table td,
        .dashboard-table th {
            vertical-align: middle;
        }

        .dashboard-subscribers-table {
            table-layout: fixed;
            min-width: 30rem;
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
            color: var(--bs-body-color);
            text-align: center;
        }

        .dashboard-progress-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: .35rem;
        }

        @media (max-width: 991.98px) {
            .dashboard-panels {
                grid-template-columns: minmax(0, 1fr);
                grid-template-areas: "actions" "delivery" "mailings" "templates";
            }
        }

        @media (max-width: 575.98px) {
            .dashboard-page .dashboard-small-box p {
                max-width: none;
                font-size: 1rem;
            }
        }
    </style>
@endsection

@section('content')

    <div class="container-fluid dashboard-page">
        <div class="row g-3 mb-3">
            <div class="col-xl-3 col-sm-6 d-flex">
                <div class="small-box text-bg-info dashboard-small-box">
                    <div class="inner">
                        <h3>{{ number_format($stats['templates']) }}</h3>
                        <p>{{ __('frontend.menu.templates') }}</p>
                        <span class="dashboard-note">{{ __('frontend.str.add_template') }}</span>
                    </div>
                    <i class="small-box-icon fa-solid fa-envelope-open-text" aria-hidden="true"></i>
                    <a href="{{ route('admin.templates.index') }}" class="small-box-footer link-dark link-underline-opacity-0 link-underline-opacity-50-hover">
                        {{ __('frontend.dashboard.open_section') }} <i class="fa-solid fa-circle-arrow-right"></i>
                    </a>
                </div>
            </div>

            <div class="col-xl-3 col-sm-6 d-flex">
                <div class="small-box text-bg-success dashboard-small-box">
                    <div class="inner">
                        <h3>{{ number_format($stats['subscribers']) }}</h3>
                        <p>{{ __('frontend.menu.subscribers') }}</p>
                        <span class="dashboard-note">{{ __('frontend.dashboard.active_count', ['count' => number_format($stats['activeSubscribers'])]) }}</span>
                    </div>
                    <i class="small-box-icon fa-solid fa-user-group" aria-hidden="true"></i>
                    <a href="{{ route('admin.subscribers.index') }}" class="small-box-footer link-light link-underline-opacity-0 link-underline-opacity-50-hover">
                        {{ __('frontend.dashboard.open_section') }} <i class="fa-solid fa-circle-arrow-right"></i>
                    </a>
                </div>
            </div>

            <div class="col-xl-3 col-sm-6 d-flex">
                <div class="small-box text-bg-warning dashboard-small-box">
                    <div class="inner">
                        <h3>{{ number_format($stats['schedule']) }}</h3>
                        <p>{{ __('frontend.menu.schedule') }}</p>
                        <span class="dashboard-note">{{ __('frontend.dashboard.active_count', ['count' => number_format($stats['upcomingSchedule'])]) }}</span>
                    </div>
                    <i class="small-box-icon fa-solid fa-calendar-days" aria-hidden="true"></i>
                    <a href="{{ route('admin.schedule.index') }}" class="small-box-footer link-dark link-underline-opacity-0 link-underline-opacity-50-hover">
                        {{ __('frontend.dashboard.open_section') }} <i class="fa-solid fa-circle-arrow-right"></i>
                    </a>
                </div>
            </div>

            <div class="col-xl-3 col-sm-6 d-flex">
                <div class="small-box text-bg-danger dashboard-small-box">
                    <div class="inner">
                        <h3>{{ number_format($stats['sentTotal']) }}</h3>
                        <p>{{ __('frontend.menu.mailing_log') }}</p>
                        <span class="dashboard-note">{{ number_format($stats['sentFailed']) }} {{ __('frontend.str.error') }}</span>
                    </div>
                    <i class="small-box-icon fa-solid fa-paper-plane" aria-hidden="true"></i>
                    <a href="{{ route('admin.log.index') }}" class="small-box-footer link-light link-underline-opacity-0 link-underline-opacity-50-hover">
                        {{ __('frontend.dashboard.open_section') }} <i class="fa-solid fa-circle-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-xl-3 col-sm-6 d-flex">
                <div class="small-box text-bg-primary dashboard-small-box">
                    <div class="inner">
                        <h3>{{ number_format($stats['categories']) }}</h3>
                        <p>{{ __('frontend.menu.subscribers_category') }}</p>
                        <span class="dashboard-note">{{ __('frontend.str.category') }}</span>
                    </div>
                    <i class="small-box-icon fa-solid fa-list" aria-hidden="true"></i>
                    <a href="{{ route('admin.category.index') }}" class="small-box-footer link-light link-underline-opacity-0 link-underline-opacity-50-hover">
                        {{ __('frontend.dashboard.open_section') }} <i class="fa-solid fa-circle-arrow-right"></i>
                    </a>
                </div>
            </div>

            <div class="col-xl-3 col-sm-6 d-flex">
                <div class="small-box text-bg-secondary dashboard-small-box">
                    <div class="inner">
                        <h3>{{ number_format($stats['smtp']) }}</h3>
                        <p>{{ __('frontend.str.smtp_server') }}</p>
                        <span class="dashboard-note">{{ __('frontend.dashboard.active_count', ['count' => number_format($stats['activeSmtp'])]) }}</span>
                    </div>
                    <i class="small-box-icon fa-solid fa-inbox" aria-hidden="true"></i>
                    <a href="{{ route('admin.smtp.index') }}" class="small-box-footer link-light link-underline-opacity-0 link-underline-opacity-50-hover">
                        {{ __('frontend.dashboard.open_section') }} <i class="fa-solid fa-circle-arrow-right"></i>
                    </a>
                </div>
            </div>

            <div class="col-xl-3 col-sm-6 d-flex">
                <div class="small-box text-bg-dark dashboard-small-box">
                    <div class="inner">
                        <h3>{{ number_format($stats['clicks']) }}</h3>
                        <p>{{ __('frontend.str.redirect') }}</p>
                        <span class="dashboard-note">{{ __('frontend.str.redirect_number') }}</span>
                    </div>
                    <i class="small-box-icon fa-solid fa-link" aria-hidden="true"></i>
                    <a href="{{ route('admin.redirect.index') }}" class="small-box-footer link-light link-underline-opacity-0 link-underline-opacity-50-hover">
                        {{ __('frontend.dashboard.open_section') }} <i class="fa-solid fa-circle-arrow-right"></i>
                    </a>
                </div>
            </div>

            <div class="col-xl-3 col-sm-6 d-flex">
                <div class="small-box text-bg-light dashboard-small-box">
                    <div class="inner">
                        <h3>{{ number_format($stats['users']) }}</h3>
                        <p>{{ __('frontend.menu.users') }}</p>
                        <span class="dashboard-note">{{ number_format($stats['macros']) }} {{ __('frontend.menu.macros') }}</span>
                    </div>
                    <i class="small-box-icon fa-solid fa-users-gear" aria-hidden="true"></i>
                    <a href="{{ route('admin.users.index') }}" class="small-box-footer link-dark link-underline-opacity-0 link-underline-opacity-50-hover">
                        {{ __('frontend.dashboard.open_section') }} <i class="fa-solid fa-circle-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="dashboard-panels">
            <div class="card card-outline card-primary dashboard-quick-actions">
                <div class="card-header">
                    <h3 class="card-title"><i class="fa-solid fa-bolt text-primary me-2" aria-hidden="true"></i>{{ __('frontend.dashboard.quick_actions') }}</h3>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <a href="{{ route('admin.templates.create') }}" class="list-group-item list-group-item-action">
                            <i class="fa-solid fa-plus text-info me-2"></i>{{ __('frontend.str.add_template') }}
                            <i class="fa-solid fa-angle-right float-end mt-1"></i>
                        </a>
                        @if(PermissionsHelper::has_permission('admin|moderator'))
                            <a href="{{ route('admin.subscribers.import') }}" class="list-group-item list-group-item-action">
                                <i class="fa-solid fa-file-import text-success me-2"></i>{{ __('frontend.str.import_subscribers') }}
                                <i class="fa-solid fa-angle-right float-end mt-1"></i>
                            </a>
                        @endif
                        <a href="{{ route('admin.schedule.create') }}" class="list-group-item list-group-item-action">
                            <i class="fa-solid fa-calendar-plus text-warning me-2"></i>{{ __('frontend.str.add_schedule') }}
                            <i class="fa-solid fa-angle-right float-end mt-1"></i>
                        </a>
                        @if(PermissionsHelper::has_permission('admin'))
                            <a href="{{ route('admin.settings.index') }}" class="list-group-item list-group-item-action">
                                <i class="fa-solid fa-gears text-secondary me-2"></i>{{ __('frontend.menu.settings') }}
                                <i class="fa-solid fa-angle-right float-end mt-1"></i>
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card card-outline card-success dashboard-delivery">
                <div class="card-header">
                    <h3 class="card-title"><i class="fa-solid fa-chart-line text-success me-2" aria-hidden="true"></i>{{ __('frontend.dashboard.delivery_overview') }}</h3>
                </div>
                <div class="card-body">
                    <div class="dashboard-progress-label">
                        <span>{{ __('frontend.str.sent') }}</span>
                        <strong>{{ $stats['deliveryRate'] }}%</strong>
                    </div>
                    <div class="progress mb-3" role="progressbar" aria-label="{{ __('frontend.str.sent') }}" aria-valuenow="{{ $stats['deliveryRate'] }}" aria-valuemin="0" aria-valuemax="100">
                        <div class="progress-bar bg-success" style="width: {{ $stats['deliveryRate'] }}%"></div>
                    </div>

                    <div class="dashboard-progress-label">
                        <span>{{ __('frontend.str.read') }}</span>
                        <strong>{{ $stats['openRate'] }}%</strong>
                    </div>
                    <div class="progress mb-3" role="progressbar" aria-label="{{ __('frontend.str.read') }}" aria-valuenow="{{ $stats['openRate'] }}" aria-valuemin="0" aria-valuemax="100">
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

            <div class="card card-outline card-info dashboard-mailings">
                <div class="card-header">
                    <h3 class="card-title"><i class="fa-solid fa-paper-plane text-info me-2" aria-hidden="true"></i>{{ __('frontend.str.newsletter') }}</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.log.index') }}" class="btn btn-tool" title="{{ __('frontend.menu.mailing_log') }}">
                            <i class="fa-solid fa-up-right-from-square"></i>
                        </a>
                    </div>
                </div>
                <div class="card-body p-0 table-responsive">
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
                                    <span class="badge text-bg-success">{{ number_format($mailing->sent ?? 0) }}</span>
                                    <span class="badge text-bg-danger">{{ number_format($mailing->failed ?? 0) }}</span>
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

            <div class="card card-outline card-primary dashboard-templates">
                <div class="card-header">
                    <h3 class="card-title"><i class="fa-solid fa-envelope-open-text text-primary me-2" aria-hidden="true"></i>{{ __('frontend.menu.templates') }}</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.templates.index') }}" class="btn btn-tool" title="{{ __('frontend.menu.templates') }}">
                            <i class="fa-solid fa-up-right-from-square"></i>
                        </a>
                    </div>
                </div>
                <div class="card-body p-0 table-responsive">
                    <table class="table table-striped dashboard-table">
                        <thead>
                        <tr>
                            <th>{{ __('frontend.str.template') }}</th>
                            <th>{{ __('frontend.str.importance') }}</th>
                            <th>{{ __('frontend.str.date') }}</th>
                            <th class="text-end">{{ __('frontend.str.action') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($latestTemplates as $template)
                            <tr>
                                <td>{{ $template->name }}</td>
                                <td>{{ $template->getPrior() }}</td>
                                <td>{{ optional($template->created_at)->format('d.m.Y H:i') }}</td>
                                <td class="text-end">
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

        <div class="row g-3 mb-3">
            <div class="col-md-6 d-flex">
                <div class="card card-outline card-success w-100">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fa-solid fa-user-group text-success me-2" aria-hidden="true"></i>{{ __('frontend.menu.subscribers') }}</h3>
                        <div class="card-tools">
                            <a href="{{ route('admin.subscribers.index') }}" class="btn btn-tool" title="{{ __('frontend.menu.subscribers') }}">
                                <i class="fa-solid fa-up-right-from-square"></i>
                            </a>
                        </div>
                    </div>
                    <div class="card-body p-0 table-responsive">
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
                                            <span class="badge text-bg-success">{{ __('frontend.str.activate') }}</span>
                                        @else
                                            <span class="badge text-bg-secondary">{{ __('frontend.str.deactivate') }}</span>
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

            <div class="col-md-6 d-flex">
                <div class="card card-outline card-warning w-100">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fa-solid fa-calendar-days text-warning me-2" aria-hidden="true"></i>{{ __('frontend.menu.schedule') }}</h3>
                        <div class="card-tools">
                            <a href="{{ route('admin.schedule.index') }}" class="btn btn-tool" title="{{ __('frontend.menu.schedule') }}">
                                <i class="fa-solid fa-up-right-from-square"></i>
                            </a>
                        </div>
                    </div>
                    <div class="card-body p-0 table-responsive">
                        <table class="table table-striped dashboard-table">
                            <thead>
                            <tr>
                                <th>{{ __('frontend.str.newsletter') }}</th>
                                <th>{{ __('frontend.str.template') }}</th>
                                <th>{{ __('frontend.str.date') }}</th>
                                <th class="text-end">{{ __('frontend.str.action') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($upcomingSchedules as $schedule)
                                <tr>
                                    <td>{{ $schedule->event_name }}</td>
                                    <td>{{ $schedule->template?->name ?: '-' }}</td>
                                    <td>{{ optional(\Illuminate\Support\Carbon::parse($schedule->event_start))->format('d.m.Y H:i') }}</td>
                                    <td class="text-end">
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

@endsection

<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('frontend.str.admin_panel') }} | @yield('title')</title>

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ url('favicon.ico') }}" type="image/x-icon">

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">

    <!-- Font Awesome -->
    {!! Html::style('/plugins/fontawesome-free/css/all.min.css') !!}

    {!! Html::style('/plugins/sweetalert2/sweetalert2.min.css') !!}

    <!-- Theme style -->
    {!! Html::style('/dist/css/adminlte.min.css?v=2') !!}

    {!! Html::style('/plugins/toastr/toastr.min.css') !!}

    {!! Html::style('/plugins/flag-icon-css/css/flag-icon.min.css') !!}

    <!-- Custom style -->
    {!! Html::style('/dist/css/admin.css?v=12') !!}

    @yield('css')

    <script type="text/javascript">
        let SITE_URL = "{{ URL::to('/') }}";
    </script>
</head>
<body class="hold-transition sidebar-mini">
<!-- Site wrapper -->
<div class="wrapper">
    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <!-- Left navbar links -->
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>

            <li class="nav-item">
                <a class="nav-link" data-widget="fullscreen" title="{{ __('frontend.str.expand_full_screen') }}"
                   href="#" role="button">
                    <i class="fas fa-expand-arrows-alt"></i>
                </a>
            </li>
        </ul>

        <!-- Right navbar links -->
        <ul class="navbar-nav ml-auto">
            <li class="nav-item dropdown">

                @php
                    $currentLocale = app()->getLocale();
                    $flags = config('app.flags', []);
                    $languages = config('app.languages', []);
                    $currentFlag = $flags[$currentLocale] ?? 'us';
                @endphp

                <a class="nav-link" data-toggle="dropdown" href="javascript:void(0);">
                    <i class="flag-icon flag-icon-{{ $currentFlag }}"></i>
                </a>

                <div class="dropdown-menu dropdown-menu-right p-0">
                    @foreach($languages as $code => $languageName)
                        <a data-id="{{ $code }}" href="javascript:void(0);" class="dropdown-item select-lang">
                            <i class="flag-icon flag-icon-{{ $flags[$code] ?? 'us' }} mr-2"></i> {{ $languageName }}
                        </a>
                    @endforeach
                </div>
            </li>

            <li class="nav-item">
                <a class="nav-link navbar-user-link"
                   href="{{ route('admin.users.edit', ['id' => Auth::user()->id ]) }}">
                    {{ Auth::user()->login }} @if(!empty(Auth::user()->name))
                        ({{ Auth::user()->name }})
                    @endif
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" title="{{ __('frontend.str.signout') }}" href="{{ route('logout') }}"
                   role="button">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </li>
        </ul>
    </nav>
    <!-- /.navbar -->

    <!-- Main Sidebar Container -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <!-- Brand Logo -->
        <a href="{{ route('admin.dashboard.index') }}" class="brand-link">
            <img src="{{ url('/dist/img/logo-sidebar-icon.png') }}?v={{ filemtime(public_path('dist/img/logo-sidebar-icon.png')) }}" alt="PHP Newsletter" class="brand-icon">
            <span class="brand-wordmark">
                <span class="brand-wordmark-main"><span>PHP</span>Newsletter</span>
                <span class="brand-wordmark-sub">Mailing System</span>
            </span>
        </a>

        <!-- Sidebar -->
        <div class="sidebar">
            <!-- Sidebar Menu -->
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu"
                    data-accordion="false">
                    <!-- Add icons to the links using the .nav-icon class
                         with font-awesome or any other icon font library -->

                    <li class="nav-item">
                        <a href="{{ route('admin.dashboard.index') }}" class="nav-link{{ Request::is('/') ? ' active' : '' }}"
                           title="{{ __('frontend.menu.dashboard') }}">
                            <i class="nav-icon fas fa-tachometer-alt"></i>
                            <p>{{ __('frontend.menu.dashboard') }}</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="{{ route('admin.templates.index') }}" class="nav-link{{ Request::is('templates*') || Request::is('template*') ? ' active' : '' }}"
                           title="{{ __('frontend.menu.templates') }}">
                            <i class="nav-icon fas fa-envelope"></i>
                            <p>{{ __('frontend.menu.templates') }}</p>
                        </a>
                    </li>

                    @if(PermissionsHelper::has_permission('admin|moderator'))

                        <li class="nav-item">
                            <a href="{{ route('admin.subscribers.index') }}" class="nav-link{{ Request::is('subscribers*') ? ' active' : '' }}"
                               title="{{ __('frontend.menu.subscribers') }}">
                                <i class="nav-icon fas fa-user-friends"></i>
                                <p>{{ __('frontend.menu.subscribers') }}</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('admin.macros.index') }}" class="nav-link{{ Request::is('macros*') ? ' active' : '' }}"
                               title="{{ __('frontend.menu.macros') }}">
                                <i class="nav-icon fas fa-scroll"></i>
                                <p>{{ __('frontend.menu.macros') }}</p>
                            </a>
                        </li>

                    @endif

                    <li class="nav-item">
                        <a href="{{ route('admin.schedule.index') }}" class="nav-link{{ Request::is('schedule*') ? ' active' : '' }}"
                           title="{{ __('frontend.menu.schedule') }}">
                            <i class="nav-icon fas fa-calendar-alt"></i>
                            <p>{{ __('frontend.menu.schedule') }}</p>
                        </a>
                    </li>

                    @if(PermissionsHelper::has_permission('admin|moderator'))

                        <li class="nav-item">
                            <a href="{{ route('admin.category.index') }}" class="nav-link{{ Request::is('category*') ? ' active' : '' }}"
                               title="{{ __('frontend.menu.subscribers') }}">
                                <i class="nav-icon fas fa-list"></i>
                                <p>{{ __('frontend.menu.subscribers_category') }}</p>
                            </a>
                        </li>

                    @endif

                    @if(PermissionsHelper::has_permission('admin'))

                        <li class="nav-item">
                            <a href="{{ route('admin.smtp.index') }}" class="nav-link{{ Request::is('smtp*') ? ' active' : '' }}" title="{{ __('frontend.str.smtp_server') }}">
                                <i class="nav-icon fas fa-inbox"></i>
                                <p>{{ __('frontend.str.smtp_server') }}</p>
                            </a>
                        </li>

                    @endif

                    <li class="nav-item{{ Request::is('log*') || Request::is('redirect*') ? ' menu-open' : '' }}">
                        <a href="#" class="nav-link{{ Request::is('log*') || Request::is('redirect*') ? ' active' : '' }}" title="{{ __('frontend.menu.logs') }}">
                            <i class="nav-icon fas fa-chart-area"></i>
                            <p>
                                {{ __('frontend.menu.logs') }}
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>

                        <ul class="nav nav-treeview">

                            <li class="nav-item">
                                <a href="{{ route('admin.log.index') }}" class="nav-link{{ Request::is('log*') ? ' active' : '' }}"
                                   title="{{ __('frontend.menu.mailing_log') }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>{{ __('frontend.menu.mailing_log') }}</p>
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="{{ route('admin.redirect.index') }}" class="nav-link{{ Request::is('redirect*') ? ' active' : '' }}"
                                   title="{{ __('frontend.menu.referrens_log') }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>{{ __('frontend.menu.referrens_log') }}</p>
                                </a>
                            </li>

                        </ul>
                    </li>

                    @if(PermissionsHelper::has_permission(Auth::user()->role,'admin'))

                        <li class="nav-item">
                            <a href="{{ route('admin.settings.index') }}" class="nav-link{{  Request::is('settings*') ? ' active' : '' }}"
                               title="{{ __('frontend.menu.settings') }}">
                                <i class="nav-icon fa fa-cogs"></i>
                                <p>{{ __('frontend.menu.settings') }}</p>
                            </a>
                        </li>

                    @endif

                    @if(PermissionsHelper::has_permission(Auth::user()->role,'admin'))

                        <li class="nav-item">
                            <a href="{{ route('admin.users.index') }}" class="nav-link{{ Request::is('users*') ? ' active' : '' }}"
                               title="{{ __('frontend.menu.users') }}">
                                <i class="nav-icon fas fa-users"></i>
                                <p>{{ __('frontend.menu.users') }}</p>
                            </a>
                        </li>

                    @endif

                    @if(PermissionsHelper::has_permission(Auth::user()->role,'admin'))

                        <li class="nav-item">
                            <a href="{{ route('admin.update.index') }}" class="nav-link{{ Request::is('update*') ? ' active' : '' }}" title="{{ __('frontend.menu.update') }}">
                                <i class="nav-icon fas fa-sync-alt"></i>
                                <p>{{ __('frontend.menu.update') }}</p>
                            </a>
                        </li>

                    @endif

                    <li class="nav-item">
                        <a href="{{ route('admin.faq') }}" class="nav-link{{ Request::is('faq*') ? ' active' : '' }}" title="FAQ">
                            <i class="nav-icon fas fa-question-circle"></i>
                            <p>FAQ</p>
                        </a>
                    </li>

                    <li class="nav-item{{ Request::is('pages*') ? ' menu-open' : '' }}">
                        <a href="#" class="nav-link{{ Request::is('pages*') ? ' active' : '' }}" title="{{ __('frontend.menu.miscellaneous') }}">
                            <i class="nav-icon fas fa-bookmark"></i>
                            <p>
                                {{ __('frontend.menu.miscellaneous') }}
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>

                        <ul class="nav nav-treeview">

                            <li class="nav-item">
                                <a href="{{ route('admin.pages.subscription_form') }}" class="nav-link{{ Request::is('pages/subscription-form*') ? ' active' : '' }}"
                                   title="{{ __('frontend.menu.subscription_form') }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>{{ __('frontend.menu.subscription_form') }}</p>
                                </a>
                            </li>

                            @if(PermissionsHelper::has_permission(Auth::user()->role,'admin|moderator'))

                                <li class="nav-item">
                                    <a href="{{ route('admin.pages.cron_job_list') }}" class="nav-link{{ Request::is('pages/cron-job-list*') ? ' active' : '' }}"
                                       title="{{ __('frontend.menu.cron_job_list') }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>{{ __('frontend.menu.cron_job_list') }}</p>
                                    </a>
                                </li>

                                <li class="nav-item">
                                    <a href="{{ route('admin.pages.phpinfo') }}" class="nav-link{{ Request::is('pages/phpinfo*') ? ' active' : '' }}" title="PHP Info">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>PHP Info</p>
                                    </a>
                                </li>

                            @endif

                        </ul>
                    </li>
                </ul>
            </nav>
            <!-- /.sidebar-menu -->
        </div>
        <!-- /.sidebar -->
    </aside>

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1>{{ $title }}</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{{ url('/') }}">{{ __('frontend.str.admin_panel') }}</a></li>
                            @hasSection('breadcrumbs')
                                @yield('breadcrumbs')
                            @else
                                <li class="breadcrumb-item active">{{ $title }}</li>
                            @endif
                        </ol>
                    </div>
                </div>

                @include('admin.notifications')

            </div><!-- /.container-fluid -->
        </section>

        @yield('content')

    </div>
    <!-- /.content-wrapper -->

    <footer class="main-footer">
        <div class="float-right d-none d-sm-inline">
            {{ config('app.version') }}
        </div>
        &copy; 2006-{{ date('Y') }} <a href="https://janickiy.com">PHP Newsletter</a>, {{ __('frontend.str.author') }}
    </footer>

    <!-- Control Sidebar -->
    <aside class="control-sidebar control-sidebar-dark">
        <!-- Control sidebar content goes here -->
    </aside>
    <!-- /.control-sidebar -->
</div>
<!-- ./wrapper -->

<!-- jQuery -->
{!! Html::script('/plugins/jquery/jquery.min.js') !!}
<!-- Bootstrap 4 -->
{!! Html::script('/plugins/bootstrap/js/bootstrap.bundle.min.js') !!}

{!! Html::script('/plugins/sweetalert2/sweetalert2.min.js') !!}
{!! Html::script('/plugins/toastr/toastr.min.js') !!}

<!-- Cookie -->
{!! Html::script('/plugins/cookie/jquery.cookie.js') !!}

<!-- AdminLTE App -->
{!! Html::script('/dist/js/adminlte.min.js') !!}

<script>

    $(function () {
        $.ajax({
            cache: false,
            url: '{{ route('admin.ajax.action') }}',
            method: "POST",
            data: {
                action: "alert_update",
            },
            headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
            dataType: "json",
            success: function (data) {
                if (
                    typeof data.msg === 'string'
                    && data.msg.trim() !== ''
                    && $.cookie('alertshow') !== 'no'
                ) {
                    $('#alert_msg_block').fadeIn('700');
                    $("#alert_warning_msg").append(data.msg);
                }
                console.log(data);
            },
            error: function(xhr, textStatus, error) {
                console.log(textStatus);
                console.log(error);
            }
        });

        $('a.select-lang').on('click', function () {
          //  $(this).parent().find('li.active').removeClass('active');
          //  $(this).addClass('active');

            let Lng = $(this).attr('data-id');

            let request = $.ajax({
                url: '{{ route('admin.ajax.action') }}',
                method: "POST",
                data: {
                    action: "change_lng",
                    locale: Lng,
                },
                headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                dataType: "json"
            });

            request.done(function (data) {
                if (data.result != null && data.result === true) {
                    location.reload();
                }
            });
        });

        $(document).on("click", "a.opislink:not(.active)", function () {
            $(this).addClass('active');
            $(this).parent().find('div.opis').slideDown(760);
            return false;
        });

        $(document).on("click", "a.opislink.active", function () {
            $(this).removeClass('active');
            $(this).parent().find('div.opis').slideUp(760);
            return false;
        });

        setTimeout(function () {
            setTimeout(function () {
                $('.alert-success').fadeOut('700')
            }, 5000);
        });
    });

</script>

@yield('js')

</body>
</html>

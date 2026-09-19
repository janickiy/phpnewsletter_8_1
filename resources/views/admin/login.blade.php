<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === "ar" ? "rtl" : "ltr" }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PHP Newsletter | {{ __('frontend.title.auth') }}</title>

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ url('favicon.ico') }}" type="image/x-icon">

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome7/css/all.min.css') }}">

    <!-- Theme style -->
    <link rel="stylesheet" href="{{ asset('vendor/adminlte4/css/adminlte.min.css') }}">

    <style>
        .auth-logo {
            width: min(300px, 100%);
            height: auto;
        }

        .login-box .card-header {
            padding: 1.25rem 1.25rem 1rem;
        }

        .login-remember .form-check-input {
            width: 1.25rem;
            height: 1.25rem;
        }

        .login-remember .form-check-input,
        .login-remember .form-check-label {
            cursor: pointer;
        }
    </style>

</head>
<body class="login-page bg-body-secondary">
<div class="login-box">
    <!-- /.login-logo -->
    <div class="card card-outline card-primary">
        <div class="card-header text-center">

            <img src="{{ url('/assets/img/logo-auth-install.png') }}?v={{ filemtime(public_path('assets/img/logo-auth-install.png')) }}" alt="PHP Newsletter" class="auth-logo">
        </div>
        <div class="card-body">

            <form method="POST" action="{{ route('login') }}" accept-charset="UTF-8">
                @csrf

                <div class="input-group mb-3">

                    <input type="text" name="login" autocomplete="username" aria-label="{{ __('frontend.form.login') }}" value="{{ old('login') }}" placeholder="{{ __('frontend.form.login') }}" class="form-control">

                    <span class="input-group-text"><i class="fas fa-user" aria-hidden="true"></i></span>

                    @if ($errors->has('login'))
                        <p class="text-danger w-100 mb-0 mt-1">{{ $errors->first('login') }}</p>
                    @endif

                </div>
                <div class="input-group mb-3">

                    <input type="password" name="password" autocomplete="current-password" aria-label="{{ __('frontend.form.password') }}" class="form-control" placeholder="{{ __('frontend.form.password') }}">

                    <span class="input-group-text"><i class="fas fa-lock" aria-hidden="true"></i></span>

                    @if ($errors->has('password'))
                        <p class="text-danger w-100 mb-0 mt-1">{{ $errors->first('password') }}</p>
                    @endif
                </div>
                <div class="row align-items-center">
                    <div class="col-8">
                        <div class="form-check login-remember d-flex align-items-center gap-2 p-0 mb-0">

                            <input type="checkbox" name="remember" class="form-check-input float-none m-0 flex-shrink-0" value="1" id="remember" @checked(old('remember'))>

                            <label for="remember" class="form-check-label">
                                {{ __('frontend.str.remember_me') }}
                            </label>
                        </div>
                    </div>
                    <!-- /.col -->
                    <div class="col-4">
                        <input type="submit" value="{{ __('frontend.str.singin') }}" class="btn btn-primary w-100">
                    </div>
                    <!-- /.col -->
                </div>

            </form>

        </div>
        <!-- /.card-body -->
    </div>
    <!-- /.card -->
</div>
<!-- /.login-box -->

<!-- jQuery -->
<script src="{{ asset('plugins/jquery/jquery.min.js') }}"></script>
<!-- Bootstrap 5 -->
<script src="{{ asset('vendor/bootstrap5/js/bootstrap.bundle.min.js') }}"></script>
<!-- AdminLTE App -->
<script src="{{ asset('vendor/adminlte4/js/adminlte.min.js') }}"></script>
</body>
</html>

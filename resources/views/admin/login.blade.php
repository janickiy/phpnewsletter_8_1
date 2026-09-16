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
    {!! Html::style('plugins/fontawesome-free/css/all.min.css') !!}

    <!-- icheck bootstrap -->
    {!! Html::style('plugins/icheck-bootstrap/icheck-bootstrap.min.css') !!}

    <!-- Theme style -->
    {!! Html::style('dist/css/adminlte.min.css?v=2') !!}

    <style>
        .auth-logo {
            width: min(300px, 100%);
            height: auto;
        }

        .login-box .card-header {
            padding: 1.25rem 1.25rem 1rem;
        }
    </style>

</head>
<body class="hold-transition login-page">
<div class="login-box">
    <!-- /.login-logo -->
    <div class="card card-outline card-primary">
        <div class="card-header text-center">

            <img src="{{ url('/dist/img/logo-auth-install.png') }}?v={{ filemtime(public_path('dist/img/logo-auth-install.png')) }}" alt="PHP Newsletter" class="auth-logo">
        </div>
        <div class="card-body">

            {!! Form::open(['url' => route('login'), 'method' => 'post']) !!}

                <div class="input-group mb-3">

                    {!! Form::text('login', old('login'), [ 'placeholder' => __('frontend.form.login'), 'class' => 'form-control']) !!}

                    <div class="input-group-append">
                        <div class="input-group-text">
                            <span class="fas fa-user"></span>
                        </div>
                    </div>

                    @if ($errors->has('login'))
                        <p class="text-danger">{{ $errors->first('login') }}</p>
                    @endif

                </div>
                <div class="input-group mb-3">

                    {!! Form::password('password',['class' => 'form-control', 'placeholder' => __('frontend.form.password'), 'type' => 'password']) !!}

                    <div class="input-group-append">
                        <div class="input-group-text">
                            <span class="fas fa-lock"></span>
                        </div>
                    </div>

                    @if ($errors->has('password'))
                        <p class="text-danger">{{ $errors->first('password') }}</p>
                    @endif
                </div>
                <div class="row">
                    <div class="col-8">
                        <div class="icheck-primary">

                            {!! Form::checkbox('remember', 1, old('remember') ? true : false , ['id' => "remember"]) !!}

                            <label for="remember">
                                {{ __('frontend.str.remember_me') }}
                            </label>
                        </div>
                    </div>
                    <!-- /.col -->
                    <div class="col-4">
                        {!! Form::submit(__('frontend.str.singin'), ['class' => 'btn btn-primary btn-block']) !!}
                    </div>
                    <!-- /.col -->
                </div>

            {!! Form::close() !!}

        </div>
        <!-- /.card-body -->
    </div>
    <!-- /.card -->
</div>
<!-- /.login-box -->

<!-- jQuery -->
{!! Html::script('plugins/jquery/jquery.min.js') !!}
<!-- Bootstrap 4 -->
{!! Html::script('plugins/bootstrap/js/bootstrap.bundle.min.js') !!}
<!-- AdminLTE App -->
{!! Html::script('dist/js/adminlte.min.js') !!}
</body>
</html>

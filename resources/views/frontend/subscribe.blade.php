<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>PHP Newsletter</title>

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ url('favicon.ico') }}" type="image/x-icon">

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">

    {!! Html::style('/css/bootstrap.min.css') !!}

    <style>
        html,
        body {
            min-height: 100%;
        }

        body {
            margin: 0;
        }

        .subscribe-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            text-align: center;
        }

        .subscribe-message {
            width: 100%;
            max-width: 1200px;
            overflow-wrap: anywhere;
        }

        .subscribe-message h1,
        .subscribe-message h2 {
            margin-left: auto;
            margin-right: auto;
        }
    </style>
</head>
<body>
<main class="subscribe-page">
    <div class="subscribe-message error-box">
        <h1 class="error-text-2 bounceInDown animated"> {{ trans('frontend.str.subscription_activation') }}
            <span class="particle particle--c"></span>
            <span class="particle particle--a"></span>
            <span class="particle particle--b"></span>
        </h1>
        <h2 class="font-xl">
            <strong>
                <i class="fa fa-fw fa-warning fa-lg text-warning"></i>
                {{ trans('frontend.str.subscription_activation_was_successful') }}
            </strong>
        </h2>
    </div>
</main>
</body>
</html>

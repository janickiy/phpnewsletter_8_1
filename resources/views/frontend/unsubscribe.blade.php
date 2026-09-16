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

        .unsubscribe-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            text-align: center;
        }

        .unsubscribe-message {
            width: 100%;
            max-width: 1200px;
            overflow-wrap: anywhere;
        }

        .unsubscribe-message h2,
        .unsubscribe-message h3 {
            margin-left: auto;
            margin-right: auto;
        }
    </style>

</head>
<body>
<main class="unsubscribe-page">
    <div class="unsubscribe-message error-box">
        <h2 class="error-text-2 bounceInDown animated"> {{ trans('frontend.str.unsubscribe') }}
            <span class="particle particle--c"></span>
            <span class="particle particle--a"></span>
            <span class="particle particle--b"></span>
        </h2>
        <h3 class="font-xl">
            <strong>
                <i class="fa fa-fw fa-warning fa-lg text-warning"></i>
                {!! $msg !!}
            </strong>
        </h3>
    </div>
</main>
</body>
</html>

@extends('layouts.install')

@section('content')

    @include('install.steps', ['steps' => [
        'welcome' => 'selected done',
        'requirements' => 'selected done',
        'permissions' => 'selected done',
        'database' => 'selected done',
        'installation' => 'selected'
    ]])

    <form method="POST" action="{{ route('install.install') }}" accept-charset="UTF-8">
        @csrf

    <div class="step-content">
        <h3>{{ __('install.str.install') }}</h3>
        <hr>
        <strong>{{ __('install.str.ready_to_install') }}</strong>
        <p>{{ __('install.str.administration') }}:</p>
        <div class="form-group">
            <label for="login">{{ __('install.str.login') }}</label>

            <input type="text" name="login" value="{{ old('login') }}" class="form-control" id="login">

            @if ($errors->has('login'))
                <span class="text-danger">{{ $errors->first('login') }}</span>
            @endif

        </div>
        <div class="form-group">

            <label for="password">{{ __('install.str.password') }}</label>

            <input type="password" name="password" class="form-control" id="password">

            @if ($errors->has('password'))
                <span class="text-danger">{{ $errors->first('password') }}</span>
            @endif
        </div>
        <div class="form-group">

            <label for="confirm_password">{{ __('install.str.confirm_password') }}</label>

            <input type="password" name="confirm_password" class="form-control" id="confirm_password">

            @if ($errors->has('confirm_password'))
                <span class="text-danger">{{ $errors->first('confirm_password') }}</span>
            @endif
        </div>
        <button class="btn btn-green pull-right" data-toggle="loader" data-loading-text="Installing" type="submit">
            <i class="fa fa-play"></i>
            {{ __('install.button.install') }}
        </button>
        <div class="clearfix"></div>
    </div>

    </form>

@endsection

@section('js')

@endsection

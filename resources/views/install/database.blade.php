@extends('layouts.install')

@section('content')

    @include('install.steps', ['steps' => [
        'welcome' => 'selected done',
        'requirements' => 'selected done',
        'permissions' => 'selected done',
        'database' => 'selected'
    ]])

    @include('layouts.notifications')

    <form method="POST" action="{{ route('install.installation') }}" accept-charset="UTF-8">
        @csrf

    <div class="step-content">
        <h3>{{ __('install.str.database_information') }}</h3>
        <hr>
        <div class="form-group">

            <label for="host">{{ __('install.str.database_host') }}</label>

            <input type="text" name="host" value="{{ old('host') }}" class="form-control" placeholder="" id="host">

            <small>{{ __('install.hint.database_host') }}</small>
            @if ($errors->has('host'))
                <p class="text-danger">{{ $errors->first('host') }}</p>
            @endif
        </div>
        <div class="form-group">

            <label for="username">{{ __('install.str.database_username') }}</label>

            <input type="text" name="username" value="{{ old('username') }}" class="form-control" placeholder="" id="username">

            <small>{{ __('install.hint.database_username') }}</small>
            @if ($errors->has('username'))
                <p class="text-danger">{{ $errors->first('username') }}</p>
            @endif
        </div>
        <div class="form-group">

            <label for="password">{{ __('install.str.password') }}</label>

            <input type="password" name="password" class="form-control" id="password">

            <small>{{ __('install.hint.database_password') }}</small>
            @if ($errors->has('password'))
                <p class="text-danger">{{ $errors->first('password') }}</p>
            @endif
        </div>
        <div class="form-group">
            <label for="database">{{ __('install.str.database_name') }}</label>

            <input type="text" name="database" value="{{ old('database') }}" class="form-control" placeholder="" id="database">

            <small>{{ __('install.hint.database_name') }}</small>
            @if ($errors->has('database'))
                <p class="text-danger">{{ $errors->first('database') }}</p>
            @endif
        </div>

        <button class="btn btn-primary float-right mt-3">
            {{ __('install.button.next') }}
            <i class="fa fa-arrow-right"></i>
        </button>
        <div class="clearfix"></div>
    </div>

    </form>

@endsection

@section('js')

@endsection

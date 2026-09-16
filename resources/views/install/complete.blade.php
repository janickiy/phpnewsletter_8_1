@extends('layouts.install')

@section('content')

    @include('install.steps', ['steps' => [
        'welcome' => 'selected done',
        'requirements' => 'selected done',
        'permissions' => 'selected done',
        'database' => 'selected done',
        'installation' => 'selected done',
        'complete' => 'selected'
    ]])

    <div class="step-content">
        <h3>{{ __('install.str.complete') }}!</h3>
        <hr>
        <p><strong>{{ __('install.str.well_done') }}!</strong></p>
        <p>{{ __('install.str.app_is_successfully_installed') }}</p>

        @if (is_writable(base_path()))
            <p>{!! __('install.str.important') !!}</p>
        @endif

        <a class="btn btn-primary float-right" href="{{ url('login') }}">
            <i class="fa fa-sign-in"></i>
            {{ __('install.str.log_in') }}
        </a>
        <div class="clearfix"></div>
    </div>

@endsection

@section('js')

@endsection

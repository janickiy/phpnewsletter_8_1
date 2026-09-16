@extends('layouts.install')

@section('content')

    @include('install.steps', ['steps' => [
        'welcome' => 'selected done',
        'requirements' => 'selected done',
        'permissions' => 'selected'
    ]])

    <div class="step-content">
        <h3>{{ __('install.str.access_rights') }}</h3>
        <hr>
        <ul class="list-group mb-4">
            @foreach($folders as $path => $isWritable)
                <li class="list-group-item">
                    {{ $path }}
                    @if ($isWritable)
                        <span class="badge badge-secondary float-right ml-2">775</span>
                        <span class="badge badge-success float-right"><i class="fa fa-check"></i></span>
                    @else
                        <span class="badge badge-secondary float-right ml-2">775</span>
                        <span class="badge badge-danger float-right"><i class="fa fa-times"></i></span>
                    @endif
                </li>
            @endforeach
        </ul>
        <a class="btn btn-primary float-right" href="{{ route('install.database') }}">
            {{ __('install.button.next') }}
            <i class="fa fa-arrow-right"></i>
        </a>
        <div class="clearfix"></div>
    </div>

@endsection

@section('js')

@endsection

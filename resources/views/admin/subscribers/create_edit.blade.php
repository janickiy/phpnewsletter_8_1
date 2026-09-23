@extends('admin.app')

@section('title', $title)

@section('breadcrumbs')
    <li class="breadcrumb-item">
        <a href="{{ route('admin.subscribers.index') }}">{{ __('frontend.menu.subscribers') }}</a>
    </li>
    <li class="breadcrumb-item active">{{ $title }}</li>
@endsection

@section('css')


@endsection

@section('content')

    <div class="container-fluid">
        <div class="row">
            <div class="col-12">

                <!-- general form elements -->
                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fa-solid {{ isset($row) ? 'fa-pen-to-square' : 'fa-plus' }} me-2" aria-hidden="true"></i>{{ $title }}</h3>
                    </div>

                    <!-- form start -->
                    <form action="{{ isset($row) ? route('admin.subscribers.update') : route('admin.subscribers.store') }}" method="POST">
                    @csrf
                    @if(isset($row))
                        @method('PUT')
                        <input type="hidden" name="id" value="{{ $row->id }}">
                    @endif

                    <div class="card-body">

                        <p>*-{{ __('frontend.form.required_fields') }}</p>

                        @include('admin.subscribers.project_field')

                        <p class="text-muted small">{{ __('frontend.str.projects.subscriber_shared_status') }}</p>

                        <div class="mb-3">
                            <label for="name" class="form-label">{{ __('frontend.form.name') }}</label>

                            <input type="text" name="name" id="name" value="{{ old('name', $row->name ?? '') }}" class="form-control">

                            @if ($errors->has('name'))
                                <p class="text-danger">{{ $errors->first('name') }}</p>
                            @endif
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email*</label>

                            <input type="text" name="email" id="email" value="{{ old('email', $row->email ?? '') }}" class="form-control">

                            @if ($errors->has('email'))
                                <p class="text-danger">{{ $errors->first('email') }}</p>
                            @endif
                        </div>

                        @include('admin.subscribers.category_field')

                    </div>
                    <!-- /.card-body -->

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            {{ isset($row) ? __('frontend.form.edit') : __('frontend.form.add') }}
                        </button>
                        <a class="btn btn-outline-secondary float-sm-end" href="{{ route('admin.subscribers.index') }}">
                            <i class="fa-solid fa-arrow-left me-1"></i>
                            {{ __('frontend.form.back') }}
                        </a>
                    </div>

                    </form>

                </div>

            </div>
            <!-- /.card -->
        </div>
    </div>

@endsection

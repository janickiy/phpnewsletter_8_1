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
                        <h3 class="card-title"><i class="fa-solid fa-user-group me-2" aria-hidden="true"></i>{{ $title }}</h3>
                    </div>

                    <!-- form start -->
                    <form action="{{ route('admin.subscribers.import_subscribers') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="card-body">

                        <p>*-{{ __('frontend.form.required_fields') }}</p>

                        @include('admin.subscribers.project_field')

                        <div class="mb-3">

                            <label for="import" class="form-label">{{ __('frontend.form.attach_files') }}*</label>

                            <input type="file" name="import" id="import" class="form-control" accept=".csv,.xlsx,.xls,.ods,.txt">

                            @if ($errors->has('import'))
                                <p class="text-danger">{{ $errors->first('import') }}</p>
                            @endif

                            <div class="form-text">
                                <small>{{ __('frontend.form.maximum_size') }}: <cite
                                        title="Source Title">{{ $maxUploadFileSize }}</cite></small>
                            </div>

                        </div>

                        @include('admin.subscribers.category_field')

                    </div>
                    <!-- /.card-body -->

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            {{ __('frontend.form.send') }}
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

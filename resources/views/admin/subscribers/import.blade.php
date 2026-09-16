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

                    <!-- form start -->
                    <form action="{{ route('admin.subscribers.import_subscribers') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="card-body">

                        <p>*-{{ __('frontend.form.required_fields') }}</p>

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

                        <div class="mb-3">

                            <label for="categoryId" class="form-label">{{ __('frontend.form.subscribers_category') }}</label>

                            @php
                                $selectedCategoryIds = array_map('strval', (array) old('categoryId', []));
                            @endphp
                            <select name="categoryId[]" id="categoryId" multiple class="form-select">
                                <option value="">{{ __('frontend.form.select_category') }}</option>
                                @foreach($category_options as $categoryValue => $categoryLabel)
                                    <option value="{{ $categoryValue }}" @selected(in_array((string) $categoryValue, $selectedCategoryIds, true))>{{ $categoryLabel }}</option>
                                @endforeach
                            </select>

                            @if ($errors->has('categoryId'))
                                <p class="text-danger">{{ $errors->first('categoryId') }}</p>
                            @endif

                        </div>

                    </div>
                    <!-- /.card-body -->

                    <div class="card-footer d-flex flex-wrap justify-content-between gap-2">
                        <button type="submit" class="btn btn-primary">
                            {{ __('frontend.form.send') }}
                        </button>
                        <a class="btn btn-secondary" href="{{ route('admin.subscribers.index') }}">
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

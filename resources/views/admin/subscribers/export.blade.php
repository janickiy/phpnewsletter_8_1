@extends('admin.app')

@section('title', $title)

@section('breadcrumbs')
    <li class="breadcrumb-item">
        <a href="{{ route('admin.subscribers.index') }}">{{ __('frontend.menu.subscribers') }}</a>
    </li>
    <li class="breadcrumb-item active">{{ __('frontend.str.export') }}</li>
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
                    <form action="{{ route('admin.subscribers.export_subscribers') }}" method="POST">
                    @csrf

                    <div class="card-body">

                        <p>*-{{ __('frontend.form.required_fields') }}</p>

                        <fieldset class="mb-3">
                            <legend class="fs-6 form-label">{{ __('frontend.form.format') }}</legend>

                            <div class="form-check form-check-inline">
                                <input type="radio" class="form-check-input" name="export_type" id="export_type" value="text" @checked(old('export_type', 'text') === 'text')>
                                <label class="form-check-label" for="export_type">{{ __('frontend.form.text') }}</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input type="radio" class="form-check-input" name="export_type" id="export_type_excel" value="excel" @checked(old('export_type', 'text') === 'excel')>
                                <label class="form-check-label" for="export_type_excel">MS Excel</label>
                            </div>

                            @if ($errors->has('export_type'))
                                <p class="text-danger">{{ $errors->first('export_type') }}</p>
                            @endif
                        </fieldset>

                        <fieldset class="mb-3">
                            <legend class="fs-6 form-label">{{ __('frontend.form.compress') }}</legend>

                            <div class="form-check form-check-inline">
                                <input type="radio" class="form-check-input" name="compress" id="compress" value="none" @checked(old('compress', 'none') === 'none')>
                                <label class="form-check-label" for="compress">{{ __('frontend.str.no') }}</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input type="radio" class="form-check-input" name="compress" id="compress_zip" value="zip" @checked(old('compress', 'none') === 'zip')>
                                <label class="form-check-label" for="compress_zip">zip</label>
                            </div>
                        </fieldset>

                        <div class="mb-3">

                            <label for="categoryId" class="form-label">{{ __('frontend.form.subscribers_category') }}</label>

                            @php
                                $selectedCategoryIds = array_map('strval', (array) old('categoryId', []));
                            @endphp
                            <select name="categoryId[]" id="categoryId" multiple class="form-select">
                                <option value="">{{ __('frontend.form.select_category') }}</option>
                                @foreach($options as $categoryValue => $categoryLabel)
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

@section('js')


@endsection

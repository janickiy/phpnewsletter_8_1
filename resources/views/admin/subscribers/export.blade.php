@extends('admin.app')

@section('title', $title)

@section('breadcrumbs')
    <li class="breadcrumb-item">
        <a href="{{ route('admin.subscribers.index') }}">{{ __('frontend.menu.subscribers') }}</a>
    </li>
    <li class="breadcrumb-item active">{{ __('frontend.str.export') }}</li>
@endsection

@section('css')
    <style>
        .subscriber-export [hidden] { display: none !important; }
        .subscriber-export fieldset { border: 0; margin: 0; padding: 0; min-width: 0; }
        .subscriber-export .form-label { margin-bottom: .75rem; font-weight: 600; }
        .subscriber-export .export-audience > .mb-3 { margin-bottom: 0 !important; }
        .subscriber-export .export-format:has(input:checked) { background: rgba(var(--bs-primary-rgb), .08); }
        .subscriber-export .form-check-input { float: none; flex-shrink: 0; margin: 0; }
        .subscriber-export .export-format { display: flex; align-items: center; gap: .625rem; min-height: 44px; padding: .5rem .875rem; border: 1px solid var(--bs-border-color); border-radius: var(--bs-border-radius); cursor: pointer; }
        .subscriber-export .export-format:has(input:checked) { border-color: var(--bs-primary); }
        .subscriber-export .export-zip { display: flex; align-items: center; gap: .625rem; min-height: 44px; cursor: pointer; }
        .subscriber-export .card-footer .btn { min-height: 40px; }
    </style>
@endsection

@section('content')
    <div class="container-fluid">
        <div class="card card-outline card-primary subscriber-export" id="subscriber-export">
            <div class="card-header px-3 px-sm-4 py-3">
                <h3 class="card-title"><i class="fa-solid fa-user-group me-2" aria-hidden="true"></i>{{ __('frontend.str.export_subscribers') }}</h3>
            </div>
            <form action="{{ route('admin.subscribers.export_subscribers') }}" method="POST">
                @csrf
                <div class="card-body p-3 p-sm-4">
                    <div class="row g-4">
                        <div class="col-md-6 export-audience">
                            @include('admin.subscribers.project_field')
                        </div>
                        <div class="col-md-6 export-audience">
                            @include('admin.subscribers.category_field')
                            <p class="form-text mb-0 mt-2">{{ __('frontend.str.export_categories_hint') }}</p>
                        </div>
                    </div>

                    <section class="border-top mt-4 pt-3" aria-labelledby="export-file-heading">
                        <h4 id="export-file-heading" class="fs-6 fw-semibold mb-3">{{ __('frontend.str.export_file_options') }}</h4>
                        <div class="d-flex flex-wrap align-items-center gap-3 gap-sm-4">
                            <fieldset aria-label="{{ __('frontend.form.format') }}" class="d-flex flex-wrap gap-2">
                                <label class="export-format" for="export_type">
                                    <input type="radio" class="form-check-input" name="export_type" id="export_type" value="text" @checked(old('export_type', 'text') === 'text')>
                                    <span>{{ __('frontend.form.text') }} <small class="text-body-secondary">.txt</small></span>
                                </label>
                                <label class="export-format" for="export_type_excel">
                                    <input type="radio" class="form-check-input" name="export_type" id="export_type_excel" value="excel" @checked(old('export_type', 'text') === 'excel')>
                                    <span>Excel <small class="text-body-secondary">.xlsx</small></span>
                                </label>
                            </fieldset>
                            <input type="hidden" name="compress" value="none">
                            <label class="export-zip" for="compress_zip">
                                <input type="checkbox" class="form-check-input" name="compress" id="compress_zip" value="zip" @checked(old('compress', 'none') === 'zip')>
                                <span>{{ __('frontend.str.export_zip') }}</span>
                            </label>
                        </div>
                        @error('export_type')<p class="text-danger mt-2 mb-0">{{ $message }}</p>@enderror
                        @error('compress')<p class="text-danger mt-2 mb-0">{{ $message }}</p>@enderror
                    </section>
                </div>
                <div class="card-footer d-flex flex-wrap align-items-center justify-content-between gap-2 px-3 px-sm-4 py-3">
                    <a class="btn btn-outline-secondary" href="{{ route('admin.subscribers.index') }}">
                        <i class="fa-solid fa-arrow-left me-1" aria-hidden="true"></i>{{ __('frontend.form.back') }}
                    </a>
                    <button type="submit" class="btn btn-primary ms-auto">
                        <i class="fa-solid fa-download me-1" aria-hidden="true"></i>{{ __('frontend.str.download_file') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

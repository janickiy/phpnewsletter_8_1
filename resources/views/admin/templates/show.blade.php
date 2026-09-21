@extends('admin.app')

@section('title', $title)

@section('breadcrumbs')
    <li class="breadcrumb-item">
        <a href="{{ route('admin.templates.index') }}">{{ __('frontend.str.template') }}</a>
    </li>
    <li class="breadcrumb-item active">{{ $title }}</li>
@endsection

@section('css')
    <style>
        .template-preview-body {
            overflow-x: auto;
        }

        .template-preview-body img {
            max-width: 100%;
            height: auto;
        }
    </style>
@endsection

@section('content')

    <section class="template-preview-page">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card card-primary card-outline">


                        <div class="card-body p-0">
                            <div class="border-bottom p-3">
                                <h5>
                                    <i class="fa-solid fa-envelope me-2" aria-hidden="true"></i>
                                    {{ $template->name }}
                                </h5>
                                <p class="mb-0">{{ __('frontend.str.projects.project') }}: {{ $template->project->name }}</p>
                                <h6 class="mt-2 mb-0">
                                    {{ __('frontend.str.importance') }}: {{ $template->getPrior() }}
                                    <span class="text-body-secondary float-sm-end">
                                        {{ optional($template->created_at)->format('Y-m-d H:i:s') }}
                                    </span>
                                </h6>
                            </div>

                            <div class="p-3 template-preview-body">
                                <iframe class="w-100 border-0" style="min-height: 32rem" title="{{ $template->name }}"
                                        sandbox="" credentialless referrerpolicy="no-referrer"
                                        srcdoc="{{ $template->body }}"></iframe>
                            </div>
                        </div>

                        @if($template->attach->isNotEmpty())
                            <div class="card-footer">
                                <p class="mb-2">
                                    <i class="fas fa-paperclip me-1"></i>
                                    {{ __('frontend.str.attachments') }}
                                </p>

                                <ul class="list-unstyled row g-2 mb-0">
                                    @foreach($template->attach as $attach)
                                        <li class="col-12 col-md-6 col-xl-4">
                                            @include('admin.templates.partials.attachment', ['attachment' => $attach])
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="card-footer">
                            <a class="btn btn-primary" href="{{ route('admin.templates.edit', ['id' => $template->id]) }}">
                                <i class="fas fa-edit me-1"></i>
                                {{ __('frontend.str.edit') }}
                            </a>
                            <a class="btn btn-outline-secondary float-sm-end" href="{{ route('admin.templates.index') }}">
                                <i class="fas fa-arrow-left me-1"></i>
                                {{ __('frontend.form.back') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

@endsection

@extends('admin.app')

@section('title', $title)

@section('breadcrumbs')
    <li class="breadcrumb-item">
        <a href="{{ route('admin.templates.index') }}">{{ __('frontend.str.template') }}</a>
    </li>
    <li class="breadcrumb-item active">{{ $title }}</li>
@endsection

@section('content')

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card card-primary card-outline">


                        <div class="card-body p-0">
                            <div class="mailbox-read-info">
                                <h5>{{ $template->name }}</h5>
                                <h6 class="mt-2 mb-0">
                                    {{ __('frontend.str.importance') }}: {{ $template->getPrior() }}
                                    <span class="mailbox-read-time float-right">
                                        {{ optional($template->created_at)->format('Y-m-d H:i:s') }}
                                    </span>
                                </h6>
                            </div>

                            <div class="mailbox-read-message clearfix">
                                {!! $template->body !!}
                            </div>
                        </div>

                        @if($template->attach->isNotEmpty())
                            <div class="card-footer bg-white">
                                <p class="mb-2">
                                    <i class="fas fa-paperclip mr-1"></i>
                                    {{ __('frontend.str.attachments') }}
                                </p>

                                <ul class="mailbox-attachments clearfix">
                                    @foreach($template->attach as $attach)
                                        <li>
                                            <span class="mailbox-attachment-icon">
                                                <i class="far fa-file"></i>
                                            </span>
                                            <div class="mailbox-attachment-info">
                                                <span class="mailbox-attachment-name text-truncate"
                                                      title="{{ $attach->file_name }}">
                                                    <i class="fas fa-paperclip mr-1"></i>{{ $attach->file_name }}
                                                </span>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="card-footer">
                            <a class="btn btn-primary" href="{{ route('admin.templates.edit', ['id' => $template->id]) }}">
                                <i class="fas fa-edit mr-1"></i>
                                {{ __('frontend.str.edit') }}
                            </a>
                            <a class="btn btn-default bg-white float-right" href="{{ route('admin.templates.index') }}">
                                <i class="fas fa-arrow-left mr-1"></i>
                                {{ __('frontend.form.back') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

@endsection

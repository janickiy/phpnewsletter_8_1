@extends('admin.app')

@section('title', $title)

@section('breadcrumbs')
    <li class="breadcrumb-item">
        <a href="{{ route('admin.templates.index') }}">{{ __('frontend.str.template') }}</a>
    </li>
    <li class="breadcrumb-item active">{{ $title }}</li>
@endsection

@section('css')

    <!-- summernote -->
    <link rel="stylesheet" href="{{ asset('/plugins/summernote/summernote-bs5.min.css') }}">
    <!-- CodeMirror -->
    <link rel="stylesheet" href="{{ asset('/plugins/codemirror/codemirror.css') }}">
    <link rel="stylesheet" href="{{ asset('/plugins/codemirror/theme/monokai.css') }}">

@endsection

@section('content')

    <!-- Main content -->
    <section class="template-editor-page">

        <div class="container-fluid">
            <div class="row">
                <div class="col-12">

                    <form action="{{ isset($template) ? route('admin.templates.update') : route('admin.templates.store') }}" method="POST" enctype="multipart/form-data" id="tmplForm">
                    @csrf
                    @if(isset($template))
                        @method('PUT')
                        <input type="hidden" name="id" value="{{ $template->id }}">
                    @endif

                    @php
                        $priorValue = (int) old('prior', $template->prior ?? 0);
                        $priorValue = in_array($priorValue, [0, 1, 2], true) ? $priorValue : 0;
                    @endphp

                    <div class="card card-outline card-primary">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-envelope-open-text me-1"></i>
                                {{ $title }}
                            </h3>
                        </div>

                        <div class="card-body">

                            <p class="text-body-secondary small mb-3">*-{{ __('frontend.form.required_fields') }}</p>

                            <div class="mb-3">

                                <label for="name" class="form-label">{{ __('frontend.form.name') }}*</label>

                                <input type="text" name="name" id="name" value="{{ old('name', $template->name ?? '') }}" class="form-control" placeholder="{{ __('frontend.form.name') }}">

                                @if ($errors->has('name'))
                                    <p class="text-danger">{{ $errors->first('name') }}</p>
                                @endif
                            </div>

                            <div class="mb-3">

                                <label for="body" class="form-label">{{ __('frontend.form.template') }}*</label>

                                <textarea name="body" id="body" rows="8" placeholder="{{ __('frontend.form.template') }}" class="form-control">{{ old('body', $template->body ?? '') }}</textarea>

                                @if ($errors->has('body'))
                                    <p class="text-danger">{{ $errors->first('body') }}</p>
                                @endif

                                <div class="callout callout-info py-2 mt-3 mb-2">
                                    <small>{!! __('frontend.note.personalization') !!}</small>
                                </div>

                                @if($macrosList)
                                    <div class="callout callout-info py-2 mb-0">
                                        <small>{!! __('frontend.note.macros') !!} {!! $macrosList !!}</small>
                                    </div>
                                @endif

                            </div>

                            <div class="mb-3">

                                <label for="attachfile" class="form-label">{{ __('frontend.form.attach_files') }}</label>

                                <input type="file" name="attachfile[]" id="attachfile" multiple class="form-control">

                                @if ($errors->has('attachfile'))
                                    <p class="text-danger">{{ $errors->first('attachfile') }}</p>
                                @endif

                            </div>

                            @if(isset($attachment) && $attachment->isNotEmpty())
                                <div id="existing-attachments" class="mb-3">

                                    <label for="attachments" class="form-label">{{ __('frontend.str.attachments') }}</label>

                                    <div class="d-flex flex-wrap">
                                        @foreach($attachment as $a)
                                            <span id="attach_{{ $a->id }}" class="badge text-bg-light border me-2 mb-2 p-2">
                                                {{ $a->file_name }}
                                                <a href="#" data-num="{{ $a->id }}" class="remove_attach text-danger ms-1" title="{{ __('frontend.str.remove') }}">X</a>
                                            </span>
                                        @endforeach
                                    </div>

                                </div>
                            @endif

                            <div class="mb-3">

                                <label for="prior" class="form-label">{{ __('frontend.form.prior') }}</label>

                                <div>
                                    <div class="form-check form-check-inline">
                                        <input type="radio" name="prior" value="0" class="form-check-input" id="prior_normal" @checked($priorValue === 0)>

                                        <label class="form-check-label" for="prior_normal">{{ __('frontend.form.normal') }}</label>
                                    </div>

                                    <div class="form-check form-check-inline">
                                        <input type="radio" name="prior" value="2" class="form-check-input" id="prior_low" @checked($priorValue === 2)>

                                        <label class="form-check-label" for="prior_low">{{ __('frontend.form.low') }}</label>
                                    </div>

                                    <div class="form-check form-check-inline">
                                        <input type="radio" name="prior" value="1" class="form-check-input" id="prior_high" @checked($priorValue === 1)>

                                        <label class="form-check-label" for="prior_high">{{ __('frontend.form.high') }}</label>
                                    </div>

                                    @if ($errors->has('prior'))
                                        <p class="text-danger">{{ $errors->first('prior') }}</p>
                                    @endif

                                </div>

                            </div>

                        </div>
                        <!-- /.card-body -->

                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">
                                {{ isset($template) ? __('frontend.form.edit') : __('frontend.form.add') }}
                            </button>
                            <a class="btn btn-outline-secondary float-sm-end" href="{{ route('admin.templates.index') }}">
                                <i class="fas fa-arrow-left me-1"></i>
                                {{ __('frontend.form.back') }}
                            </a>

                        </div>
                    </div>

                    <div class="card card-outline card-info mt-4">
                        <div class="card-header">
                            <h3 class="card-title">{{ __('frontend.str.send_test_letter') }}<span id="process"></span></h3>
                        </div>
                        <div class="card-body">

                            <div id="resultSend"></div>

                            <div class="input-group mb-3">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>

                                <input type="text" name="email" id="email" value="{{ old('email', '') }}" class="form-control" placeholder="Email">

                                <button type="button" id="send_test" class="btn btn-info">{{ __('frontend.str.send') }}</button>

                            </div>
                        </div>
                    </div>

                    </form>

                </div>
                <!-- /.card -->
            </div>
        </div>

    </section>
    <!-- /.content -->

@endsection

@section('js')

    <!-- Summernote -->
    <script src="{{ asset('/plugins/summernote/summernote-bs5.min.js') }}"></script>

    <!-- CodeMirror -->
    <script src="{{ asset('/plugins/codemirror/codemirror.js') }}"></script>
    <script src="{{ asset('/plugins/codemirror/mode/css/css.js') }}"></script>
    <script src="{{ asset('/plugins/codemirror/mode/xml/xml.js') }}"></script>
    <script src="{{ asset('/plugins/codemirror/mode/htmlmixed/htmlmixed.js') }}"></script>

    <!-- Page specific script -->
    <script>
        $(function () {
            // Summernote
            $('#body').summernote({
                height: 300,
                codemirror: {theme: 'monokai'},
            });

            $('#tmplForm').on('submit', function () {
                $('#body').val($('#body').summernote('code'));
            });

            $(document).on("click", ".remove_attach", function (event) {
                event.preventDefault();
                let idAttach = $(this).attr('data-num');

                let request = $.ajax({
                    url: '{{ route('admin.ajax.action') }}',
                    method: "POST",
                    headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                    data: {
                        action: "remove_attach",
                        id: idAttach,
                    },

                    dataType: "json"
                });

                request.done(function (data) {
                    if (data.result != null && data.result === true) {
                        $("#attach_" + idAttach).remove();

                        if ($("#existing-attachments .badge").length === 0) {
                            $("#existing-attachments").remove();
                        }
                    }
                });
            });

            $(document).on("click", "#send_test", function () {
                let bodyContent = $('#body').summernote('code');
                let arr = $("#tmplForm").serializeArray();
                let aParams = [];
                let sParam;

                $("#process").removeClass().addClass('showprocess');
                $("#send_test").attr('disabled', 'disabled');

                for (let i = 0, count = arr.length; i < count; i++) {
                    sParam = encodeURIComponent(arr[i].name);

                    if (sParam == 'body') {
                        sParam += "=";
                        sParam += encodeURIComponent(bodyContent);
                    } else {
                        sParam += "=";
                        sParam += encodeURIComponent(arr[i].value);
                    }

                    aParams.push(sParam);
                }

                sParam = 'action';
                sParam += "=";
                sParam += encodeURIComponent('send_test_email');
                aParams.push(sParam);

                let sendData = aParams.join("&");
                let request = $.ajax({
                    url: '{{ route('admin.ajax.action') }}',
                    method: "POST",
                    data: sendData,
                    dataType: "json"
                });

                request.done(function (data) {
                    if (data.result != null) {
                        let alert_msg = '';

                        if (data.result === true) {
                            alert_msg += '<div class="alert alert-success alert-dismissible fade show">';
                            alert_msg += '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
                            alert_msg += data.msg;
                            alert_msg += '</div>';
                        } else {
                            alert_msg += '<div class="alert alert-danger alert-dismissible fade show">';
                            alert_msg += '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
                            alert_msg += data.msg;
                            alert_msg += '</div>';
                        }

                        $("#resultSend").html(alert_msg);
                    }
                }).always(function () {
                    $("#process").removeClass();
                    $("#send_test").prop('disabled', false);
                });
            });
        })

    </script>

@endsection

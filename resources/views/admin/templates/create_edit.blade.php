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
    {!! Html::style('/plugins/summernote/summernote-bs4.min.css') !!}
    <!-- CodeMirror -->
    {!! Html::style('/plugins/codemirror/codemirror.css') !!}
    {!! Html::style('/plugins/codemirror/theme/monokai.css') !!}

@endsection

@section('content')

    <!-- Main content -->
    <section class="content">

        <div class="container-fluid">
            <div class="row">
                <div class="col-12">

                    {!! Form::open(['url' => isset($template) ? route('admin.templates.update') : route('admin.templates.store'), 'files' => true, 'method' => isset($template) ? 'put' : 'post', 'id' => 'tmplForm']) !!}

                    {!! isset($template) ? Form::hidden('id', $template->id) : '' !!}

                    @php
                        $priorValue = (int) old('prior', $template->prior ?? 0);
                        $priorValue = in_array($priorValue, [0, 1, 2], true) ? $priorValue : 0;
                    @endphp

                    <div class="card card-primary">

                        <div class="card-body">

                            <p>*-{{ __('frontend.form.required_fields') }}</p>

                            <div class="form-group">

                                {!! Form::label('name', __('frontend.form.name') . '*') !!}

                                {!! Form::text('name', old('name', $template->name ?? null), ['class' => 'form-control', 'placeholder' => __('frontend.form.name')]) !!}

                                @if ($errors->has('name'))
                                    <p class="text-danger">{{ $errors->first('name') }}</p>
                                @endif
                            </div>

                            <div class="form-group">

                                {!! Form::label('body', __('frontend.form.template') . '*') !!}

                                {!! Form::textarea('body', old('name', $template->body ?? null), ['rows' => "3", 'placeholder' => __('frontend.form.template'), 'class' => 'form-control']) !!}

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

                            <div class="form-group">

                                {!! Form::label('attachfile[]', __('frontend.form.attach_files')) !!}

                                <div class="input-group">
                                    <div class="custom-file">

                                        {!! Form::file('attachfile[]', ['id' => 'attachfile', 'multiple' => true, 'class' => 'custom-file-input']) !!}

                                        {!! Form::label('attachfile', __('frontend.form.browse'), ['class' => 'custom-file-label']) !!}

                                    </div>
                                </div>

                                @if ($errors->has('attachfile'))
                                    <p class="text-danger">{{ $errors->first('attachfile') }}</p>
                                @endif

                            </div>

                            @if(isset($attachment) && $attachment->isNotEmpty())
                                <div id="existing-attachments" class="form-group">

                                    {!! Form::label('attachments', __('frontend.str.attachments')) !!}

                                    <div class="d-flex flex-wrap">
                                        @foreach($attachment as $a)
                                            <span id="attach_{{ $a->id }}" class="badge badge-light border mr-2 mb-2 p-2">
                                                {{ $a->file_name }}
                                                <a href="#" data-num="{{ $a->id }}" class="remove_attach text-danger ml-1" title="{{ __('frontend.str.remove') }}">X</a>
                                            </span>
                                        @endforeach
                                    </div>

                                </div>
                            @endif

                            <div class="form-group">

                                {!! Form::label('prior', __('frontend.form.prior')) !!}

                                <div>
                                    <div class="custom-control custom-radio custom-control-inline">
                                        {!! Form::radio('prior', 0, $priorValue === 0, ['class' => 'custom-control-input', 'id' => 'prior_normal']) !!}

                                        <label class="custom-control-label" for="prior_normal">{{ __('frontend.form.normal') }}</label>
                                    </div>

                                    <div class="custom-control custom-radio custom-control-inline">
                                        {!! Form::radio('prior', 2, $priorValue === 2, ['class' => 'custom-control-input', 'id' => 'prior_low']) !!}

                                        <label class="custom-control-label" for="prior_low">{{ __('frontend.form.low') }}</label>
                                    </div>

                                    <div class="custom-control custom-radio custom-control-inline">
                                        {!! Form::radio('prior', 1, $priorValue === 1, ['class' => 'custom-control-input', 'id' => 'prior_high']) !!}

                                        <label class="custom-control-label" for="prior_high">{{ __('frontend.form.high') }}</label>
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
                            <a class="btn btn-default bg-white float-right" href="{{ route('admin.templates.index') }}">
                                <i class="fas fa-arrow-left mr-1"></i>
                                {{ __('frontend.form.back') }}
                            </a>

                        </div>
                    </div>

                    <div class="card card-info">
                        <div class="card-header">
                            <h3 class="card-title">{{ __('frontend.str.send_test_letter') }}<span id="process"></span></h3>
                        </div>
                        <div class="card-body">

                            <div id="resultSend"></div>

                            <div class="input-group mb-3">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                </div>

                                {!! Form::text('email', null, ['class' => 'form-control', 'placeholder' => 'Email', 'id' => 'email']) !!}

                                <span class="input-group-append">
                                    <button type="button" id="send_test" class="btn btn-info">{{ __('frontend.str.send') }}</button>
                                </span>

                            </div>
                        </div>
                    </div>

                    {!! Form::close() !!}

                </div>
                <!-- /.card -->
            </div>
        </div>

    </section>
    <!-- /.content -->

@endsection

@section('js')

    <!-- Summernote -->
    {!! Html::script('/plugins/summernote/summernote-bs4.min.js') !!}

    <!-- CodeMirror -->
    {!! Html::script('/plugins/codemirror/codemirror.js') !!}
    {!! Html::script('/plugins/codemirror/mode/css/css.js') !!}
    {!! Html::script('/plugins/codemirror/mode/xml/xml.js') !!}
    {!! Html::script('/plugins/codemirror/mode/htmlmixed/htmlmixed.js') !!}
    {!! Html::script('/plugins/bs-custom-file-input/bs-custom-file-input.min.js') !!}

    <!-- Page specific script -->
    <script>
        $(function () {
            // Summernote
            $('#body').summernote();
            bsCustomFileInput.init();

            $(document).on("click", ".remove_attach", function () {
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
                let bodyContent = $('#body').val();
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
                            alert_msg += '<div class="alert alert-success alert-dismissible">';
                            alert_msg += '<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>';
                            alert_msg += data.msg;
                            alert_msg += '</div>';
                        } else {
                            alert_msg += '<div class="alert alert-danger alert-dismissible">';
                            alert_msg += '<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>';
                            alert_msg += data.msg;
                            alert_msg += '</div>';
                        }

                        console.log(data.msg);

                        $("#resultSend").html(alert_msg);
                        $("#process").removeClass();
                        $("#send_test").removeAttr('disabled');
                    }
                });
            });
        })

    </script>

@endsection

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

    <style>
        .template-editor-page .template-editor-hint {
            padding: .75rem 1rem;
            border-inline-start: 3px solid var(--bs-primary);
            border-radius: var(--bs-border-radius);
            background: var(--bs-tertiary-bg);
            color: var(--bs-secondary-color);
            font-size: .875rem;
            line-height: 1.6;
            overflow-wrap: anywhere;
        }

        .template-editor-page .note-editor.note-frame {
            border-color: var(--bs-border-color);
            border-radius: var(--bs-border-radius);
        }
    </style>

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
                                <i class="fa-solid {{ isset($template) ? 'fa-pen-to-square' : 'fa-plus' }} me-2" aria-hidden="true"></i>
                                {{ $title }}
                            </h3>
                        </div>

                        <div class="card-body">

                            <p class="text-body-secondary small mb-3">*-{{ __('frontend.form.required_fields') }}</p>

                            <div class="mb-3">
                                <label for="project_id" class="form-label">{{ __('frontend.str.projects.project') }}*</label>
                                <select name="project_id" id="project_id" class="form-select @error('project_id') is-invalid @enderror" required @disabled(isset($template))>
                                    @foreach($projects as $project)
                                        <option value="{{ $project->id }}" @selected((string) old('project_id', $template->project_id ?? \App\Models\Project::DEFAULT_ID) === (string) $project->id)>{{ $project->name }}</option>
                                    @endforeach
                                </select>
                                @if(isset($template))
                                    <input type="hidden" name="project_id" value="{{ $template->project_id }}">
                                @endif
                                @error('project_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="mb-3">

                                <label for="name" class="form-label">{{ __('frontend.form.name') }}*</label>

                                <input type="text" name="name" id="name" value="{{ old('name', $template->name ?? '') }}" class="form-control" placeholder="{{ __('frontend.form.name') }}">

                                @if ($errors->has('name'))
                                    <p class="text-danger">{{ $errors->first('name') }}</p>
                                @endif
                            </div>

                            <div class="mb-3">

                                <label for="body" class="form-label">{{ __('frontend.form.template') }}*</label>

                                <textarea name="body" id="body" rows="3" placeholder="{{ __('frontend.form.template') }}" class="form-control">{{ old('body', $template->body ?? '') }}</textarea>

                                @if ($errors->has('body'))
                                    <p class="text-danger">{{ $errors->first('body') }}</p>
                                @endif

                                <div class="template-editor-hint mt-3">
                                    {!! __('frontend.note.personalization') !!}
                                </div>

                                @if($macrosList)
                                    <div class="template-editor-hint mt-3">
                                        {!! __('frontend.note.macros') !!} {!! $macrosList !!}
                                    </div>
                                @endif

                            </div>

                            <section class="border rounded p-3 mb-3" aria-labelledby="template-priority-title">
                                <h4 class="h6 fw-semibold mb-3" id="template-priority-title">
                                    <i class="fa-solid fa-sliders me-2" aria-hidden="true"></i>{{ __('frontend.form.prior') }}
                                </h4>

                                <div role="radiogroup" aria-labelledby="template-priority-title">
                                    <div class="form-check mb-2">
                                        <input type="radio" name="prior" value="0" class="form-check-input" id="prior_normal" @checked($priorValue === 0)>

                                        <label class="form-check-label" for="prior_normal">{{ __('frontend.form.normal') }}</label>
                                    </div>

                                    <div class="form-check mb-2">
                                        <input type="radio" name="prior" value="2" class="form-check-input" id="prior_low" @checked($priorValue === 2)>

                                        <label class="form-check-label" for="prior_low">{{ __('frontend.form.low') }}</label>
                                    </div>

                                    <div class="form-check mb-0">
                                        <input type="radio" name="prior" value="1" class="form-check-input" id="prior_high" @checked($priorValue === 1)>

                                        <label class="form-check-label" for="prior_high">{{ __('frontend.form.high') }}</label>
                                    </div>

                                    @if ($errors->has('prior'))
                                        <p class="text-danger">{{ $errors->first('prior') }}</p>
                                    @endif

                                </div>

                            </section>

                            <section class="border rounded p-3 mb-3" aria-labelledby="template-attachments-title">
                                <h4 class="h6 fw-semibold mb-3" id="template-attachments-title">
                                    <i class="fa-solid fa-paperclip me-2" aria-hidden="true"></i>{{ __('frontend.str.attachments') }}
                                </h4>

                                <label for="attachfile" class="form-label">{{ __('frontend.form.attach_files') }}</label>
                                <input type="file" name="attachfile[]" id="attachfile" multiple class="form-control">

                                @if ($errors->has('attachfile'))
                                    <p class="text-danger">{{ $errors->first('attachfile') }}</p>
                                @endif

                                @if(isset($attachment) && $attachment->isNotEmpty())
                                    <div id="existing-attachments" class="mt-3">
                                        <div class="d-flex flex-wrap gap-2">
                                            @foreach($attachment as $a)
                                                <span id="attach_{{ $a->id }}" class="badge text-bg-light border p-2">
                                                    {{ $a->file_name }}
                                                    <a href="#" data-num="{{ $a->id }}" class="remove_attach text-danger ms-1" title="{{ __('frontend.str.remove') }}">X</a>
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                <p id="attachments-empty" @class(['text-body-secondary mt-3 mb-0', 'd-none' => isset($attachment) && $attachment->isNotEmpty()])>{{ __('frontend.str.no') }}</p>
                            </section>

                            <section class="border rounded bg-body-tertiary p-3" aria-labelledby="template-test-title">
                                <h4 class="h6 fw-semibold mb-3" id="template-test-title">
                                    <i class="fa-solid fa-paper-plane me-2" aria-hidden="true"></i>{{ __('frontend.str.send_test_letter') }}<span id="process"></span>
                                </h4>

                                <div id="resultSend"></div>

                                <div class="input-group">
                                    <span class="input-group-text"><i class="fa-solid fa-envelope" aria-hidden="true"></i></span>
                                    <input type="text" name="email" id="email" value="{{ old('email', '') }}" class="form-control" placeholder="Email" aria-label="Email">
                                    <button type="button" id="send_test" class="btn btn-info">{{ __('frontend.str.send') }}</button>
                                </div>
                            </section>

                        </div>
                        <!-- /.card-body -->

                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">
                                {{ isset($template) ? __('frontend.form.edit') : __('frontend.form.add') }}
                            </button>
                            <a class="btn btn-outline-secondary float-sm-end" href="{{ route('admin.templates.index') }}">
                                <i class="fa-solid fa-arrow-left me-1"></i>
                                {{ __('frontend.form.back') }}
                            </a>

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

    @php
        $editorLocale = match (app()->getLocale()) {
            'ru' => 'ru-RU',
            'es' => 'es-ES',
            'fr' => 'fr-FR',
            'de' => 'de-DE',
            'zh-cn' => 'zh-CN',
            'pt' => 'pt-PT',
            'ar' => 'ar-AR',
            'hi' => 'hi-IN',
            default => 'en-US',
        };
    @endphp

    <script src="{{ asset('/plugins/dompurify/purify.min.js') }}"></script>
    <!-- Summernote -->
    <script src="{{ asset('/plugins/summernote/summernote-bs5.min.js') }}"></script>
    <script src="{{ asset('/plugins/summernote/lang/summernote-' . $editorLocale . '.js') }}"></script>

    <!-- CodeMirror -->
    <script src="{{ asset('/plugins/codemirror/codemirror.js') }}"></script>
    <script src="{{ asset('/plugins/codemirror/mode/css/css.js') }}"></script>
    <script src="{{ asset('/plugins/codemirror/mode/xml/xml.js') }}"></script>
    <script src="{{ asset('/plugins/codemirror/mode/htmlmixed/htmlmixed.js') }}"></script>

    <!-- Page specific script -->
    <script>
        $(function () {
            const cleanTemplateHtml = (html) => DOMPurify.sanitize(html, {
                USE_PROFILES: {html: true},
                FORBID_TAGS: ['style', 'form', 'input', 'button', 'select', 'textarea'],
                SANITIZE_NAMED_PROPS: true,
            });
            const SafeCodeview = class extends $.summernote.options.modules.codeview {
                purify(html) { return cleanTemplateHtml(html); }
            };
            const SafeEditor = class extends $.summernote.options.modules.editor {
                constructor(context) {
                    super(context);
                    const pasteHTML = this.pasteHTML;
                    this.pasteHTML = (html) => pasteHTML.call(this, cleanTemplateHtml(html));
                }
            };

            // Sanitize before Summernote places stored HTML into the administration page.
            $('#body').val(cleanTemplateHtml($('#body').val())).summernote({
                lang: @json($editorLocale),
                modules: {...$.summernote.options.modules, codeview: SafeCodeview, editor: SafeEditor},
                disableDragAndDrop: true,
                callbacks: {
                    onPaste: function (event) {
                        const clipboard = (event.originalEvent || event).clipboardData;
                        const html = clipboard && clipboard.getData('text/html');
                        if (html) {
                            event.preventDefault();
                            $('#body').summernote('pasteHTML', cleanTemplateHtml(html));
                        }
                    },
                },
                height: 60,
                minHeight: 60,
                codemirror: {theme: 'monokai'},
            });

            $('#tmplForm').on('submit', function () {
                $('#body').val(cleanTemplateHtml($('#body').summernote('code')));
            });

            function updateAttachmentEmptyState() {
                const hasAttachments = $('#existing-attachments .badge').length > 0 || $('#attachfile')[0].files.length > 0;
                $('#attachments-empty').toggleClass('d-none', hasAttachments);
            }

            $('#attachfile').on('change', updateAttachmentEmptyState);

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

                        updateAttachmentEmptyState();
                    }
                });
            });

            $(document).on("click", "#send_test", function () {
                let bodyContent = cleanTemplateHtml($('#body').summernote('code'));
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

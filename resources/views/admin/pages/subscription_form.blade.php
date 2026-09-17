@extends('admin.app')

@section('title', $title)

@section('css')

    <link rel="stylesheet" href="{{ asset('/plugins/highlightjs/styles/github-dark.css') }}">

    <style>

        .subscription-form-preview {
            width: 100%;
            max-width: 720px;
            padding-top: 1rem;
        }

        .subscription-form-preview > .mb-3 {
            margin-bottom: 0 !important;
        }

        .subscription-form-page .card-title {
            font-size: 1.125rem;
        }

        .subscription-form-page pre {
            position: relative;
            border: 1px solid #30363d !important;
            border-radius: 8px;
            background: #0d1117 !important;
            padding: 0 !important;
            margin-bottom: 0;
            font-size: 14px !important;
            overflow: auto;
        }

        .subscription-form-page pre code {
            background: #0d1117 !important;
            font-size: 13.5px !important;
            white-space: pre;
        }

        .subscription-form-page .hljs {
            background: #0d1117 !important;
        }

        .subscription-form-page .hljs-ln {
            width: 100%;
        }

        .subscription-form-page .hljs-ln td {
            padding: 0;
        }

        .subscription-form-page .hljs-ln-numbers {
            background: #010409;
            border-right: 1px solid #30363d;
            color: #6e7681;
            min-width: 42px;
            padding-right: 12px !important;
            text-align: right;
            user-select: none;
            vertical-align: top;
        }

        .subscription-form-page .hljs-ln-code {
            padding-left: 14px !important;
        }

    </style>

@endsection


@section('content')

    <div class="container-fluid subscription-form-page">
        <div class="row">
            <div class="col-12">

                <div class="card card-outline card-primary mb-3">
                    <div class="card-header">
                        <h3 class="card-title mb-0">
                            <i class="fa-solid fa-envelope-open-text me-2" aria-hidden="true"></i>{{ $title }}
                        </h3>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="mb-3">
                            <label for="subscription-project" class="form-label">{{ __('frontend.str.projects.project') }}</label>
                            <select name="project_id" id="subscription-project" class="form-select" onchange="this.form.submit()">
                                @forelse($projects as $option)
                                    <option value="{{ $option->id }}" @selected($project?->id === $option->id)>{{ $option->name }}</option>
                                @empty
                                    <option value="">{{ __('frontend.str.projects.no_projects') }}</option>
                                @endforelse
                            </select>
                        </form>
                        <div class="subscription-form-preview">
                            @include('include.subform')
                        </div>
                    </div>
                </div>

                <div class="card card-outline card-secondary mb-3">
                    <div class="card-header d-flex align-items-center flex-wrap gap-2">
                        <h3 class="card-title mb-0">
                            <i class="fa-solid fa-code me-2" aria-hidden="true"></i>HTML
                        </h3>
                        <button type="button" class="btn btn-outline-primary btn-sm ms-auto" id="copy-embed-code">
                            <i class="fa-solid fa-copy me-2" aria-hidden="true"></i>{{ __('frontend.str.copy_to_clipboard') }}
                        </button>
                    </div>
                    <div class="card-body">
                        <pre><code class="language-html" id="codebox">{{ $embedCode }}</code></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('js')

    <script src="{{ asset('/plugins/highlightjs/highlight.js') }}"></script>
    <script src="{{ asset('/plugins/highlightjs/highlightjs-line-numbers.js') }}"></script>

    <script>
        const codebox = document.getElementById('codebox');
        // Keep the original line breaks before the highlighter creates its line-number table.
        const subscriptionEmbedCode = codebox.textContent;

        hljs.highlightElement(codebox);
        hljs.lineNumbersBlockSync(codebox);

        document.getElementById('copy-embed-code').addEventListener('click', async function () {
            if (navigator.clipboard) {
                try {
                    await navigator.clipboard.writeText(subscriptionEmbedCode);
                    return;
                } catch (error) {
                    // Use the selection-based fallback when clipboard access is unavailable.
                }
            }

            const $temp = $('<textarea readonly>').css({ position: 'fixed', opacity: 0 });
            $("body").append($temp);
            $temp.val(subscriptionEmbedCode).select();
            document.execCommand("copy");
            $temp.remove();
        });
    </script>

@endsection

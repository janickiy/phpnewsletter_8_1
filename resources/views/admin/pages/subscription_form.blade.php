@extends('admin.app')

@section('title', $title)

@section('css')

    {!! Html::style('/plugins/highlightjs/styles/github-dark.css') !!}

    <style>

        pre {
            position: relative;
            border: 1px solid #30363d !important;
            border-radius: 8px;
            background: #0d1117 !important;
            padding: 0 !important;
            margin-bottom: 15px !important;
            font-size: 14px !important;
            overflow: auto;
        }

        pre code {
            background: #0d1117 !important;
            font-size: 13.5px !important;
            white-space: pre;
        }

        .hljs {
            background: #0d1117 !important;
        }

        .hljs-ln {
            width: 100%;
        }

        .hljs-ln td {
            padding: 0;
        }

        .hljs-ln-numbers {
            background: #010409;
            border-right: 1px solid #30363d;
            color: #6e7681;
            min-width: 42px;
            padding-right: 12px !important;
            text-align: right;
            user-select: none;
            vertical-align: top;
        }

        .hljs-ln-code {
            padding-left: 14px !important;
        }

        .copy-code-button {
            margin-bottom: 10px;
        }

    </style>

@endsection


@section('content')

    <!-- Main content -->
    <section class="content">

        <div class="container-fluid">
            <div class="row">
                <div class="col-12">

                    <div class="card">
                        <!-- /.card-header -->
                        <div class="card-body">

                            @include('include.subform')

                            <div class="form-group">

                                <button type="button" class="btn btn-primary copy-code-button"
                                        onclick="copyToClipboard('#codebox')">
                                    <span id="myTooltip">{{ __('frontend.str.copy_to_clipboard') }}</span>
                                </button>

                                <pre><code class="language-html" id="codebox">{{ $embedCode }}</code></pre>

                            </div>

                            <!-- /.card-body -->
                        </div>
                        <!-- /.card -->
                    </div>
                    <!-- /.col -->
                </div>
                <!-- /.row -->
            </div>
            <!-- /.container-fluid -->

    </section>
    <!-- /.content -->

@endsection

@section('js')

    <!-- {!! Html::script('/plugins/highlightjs/highlight.js') !!} -->
    <!-- {!! Html::script('/plugins/highlightjs/highlightjs-line-numbers.js') !!} -->

    {!! Html::script('https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js') !!}
    {!! Html::script('https://cdnjs.cloudflare.com/ajax/libs/highlightjs-line-numbers.js/2.6.0/highlightjs-line-numbers.min.js') !!}

    <script>hljs.highlightAll();</script>
    <script>hljs.initLineNumbersOnLoad();</script>

    <script>
        async function copyToClipboard(element) {
            const content = $(element).text().trim();

            if (navigator.clipboard) {
                await navigator.clipboard.writeText(content);
                return;
            }

            let $temp = $("<textarea>");
            $("body").append($temp);
            $temp.val(content).select();
            document.execCommand("copy");
            $temp.remove();
        }
    </script>

@endsection

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

    <!-- Main content -->
    <section class="content">

        <div class="container-fluid">
            <div class="row">
                <div class="col-12">

                    <!-- general form elements -->
                    <header class="card card-primary">

                        <!-- form start -->
                        {!! Form::open(['url' => route('admin.subscribers.import_subscribers'), 'files' => true, 'method' => 'post']) !!}

                        <div class="card-body">

                            <p>*-{{ __('frontend.form.required_fields') }}</p>

                            <div class="form-group">

                                {!! Form::label('import', __('frontend.form.attach_files') . '*') !!}

                                <div class="input-group">
                                    <div class="custom-file">

                                        {!! Form::file('import',  ['id' => 'import', 'class' => "custom-file-input", 'accept' => '.csv,.xlsx,.xls,.ods,.txt']) !!}

                                        {!! Form::label('import', __('frontend.form.browse'),  ['class' => "custom-file-label"]) !!}

                                    </div>
                                </div>

                                @if ($errors->has('import'))
                                    <p class="text-danger">{{ $errors->first('import') }}</p>
                                @endif

                                <blockquote class="quote-secondary">
                                    <small>{{ __('frontend.form.maximum_size') }}: <cite
                                            title="Source Title">{{ $maxUploadFileSize }}</cite></small>
                                </blockquote>

                            </div>

                            <div class="form-group">

                                {!! Form::label('categoryId[]', __('frontend.form.charset')) !!}

                                {!! Form::select('charset', $charsets, null, ['placeholder' => '--' . __('frontend.form.select') . '--', 'class' => 'form-control']) !!}

                                @if ($errors->has('charset'))
                                    <p class="text-danger">{{ $errors->first('charset') }}</p>
                                @endif

                            </div>

                            <div class="form-group">

                                {!! Form::label('categoryId[]', __('frontend.form.subscribers_category')) !!}

                                {!! Form::select('categoryId[]', $category_options, null, ['multiple' => 'multiple', 'placeholder' => __('frontend.form.select_category'), 'class' => 'form-control']) !!}

                                @if ($errors->has('categoryId'))
                                    <p class="text-danger">{{ $errors->first('categoryId') }}</p>
                                @endif

                            </div>

                        </div>
                        <!-- /.card-body -->

                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">
                                {{ __('frontend.form.send') }}
                            </button>
                            <a class="btn btn-default float-sm-right" href="{{ route('admin.subscribers.index') }}">
                                <i class="fas fa-arrow-left mr-1"></i>
                                {{ __('frontend.form.back') }}
                            </a>
                        </div>

                        {!! Form::close() !!}

                    </header>
                </div>
                <!-- /.card -->
            </div>
        </div>

    </section>
    <!-- /.content -->

@endsection

@section('js')

    {!! Html::script('/plugins/bs-custom-file-input/bs-custom-file-input.min.js') !!}

    <script>

        $(function () {
            bsCustomFileInput.init();
        });

    </script>

@endsection

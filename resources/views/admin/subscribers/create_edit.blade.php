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
                        {!! Form::open(['url' => isset($row) ? route('admin.subscribers.update') : route('admin.subscribers.store'), 'method' => isset($row) ? 'put' : 'post']) !!}

                        {!! isset($row) ? Form::hidden('id', $row->id) : '' !!}

                        <div class="card-body">

                            <p>*-{{ __('frontend.form.required_fields') }}</p>

                            <div class="form-group">
                                {!! Form::label('name', __('frontend.form.name')) !!}

                                {!! Form::text('name', old('name', $row->name ?? null), ['class' => 'form-control']) !!}

                                @if ($errors->has('name'))
                                    <p class="text-danger">{{ $errors->first('name') }}</p>
                                @endif
                            </div>

                            <div class="form-group">
                                {!! Form::label('email', 'Email*') !!}

                                {!! Form::text('email', old('email', $row->email ?? null), ['class' => 'form-control']) !!}

                                @if ($errors->has('email'))
                                    <p class="text-danger">{{ $errors->first('email') }}</p>
                                @endif
                            </div>

                            <div class="form-group">

                                {!! Form::label('categoryId',  __('frontend.form.subscribers_category')) !!}

                                @php
                                    $selectedCategoryIds = collect((array) old('categoryId', $subscriberCategoryIds ?? []))
                                        ->map(fn ($value) => (string) $value)
                                        ->all();
                                @endphp

                                <select name="categoryId[]" id="categoryId" multiple class="form-control">
                                    @foreach($options as $categoryValue => $categoryLabel)
                                        <option value="{{ $categoryValue }}" @selected(in_array((string) $categoryValue, $selectedCategoryIds, true))>
                                            {{ $categoryLabel }}
                                        </option>
                                    @endforeach
                                </select>

                                @if ($errors->has('categoryId'))
                                    <p class="text-danger">{{ $errors->first('categoryId') }}</p>
                                @endif
                            </div>

                        </div>
                        <!-- /.card-body -->

                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">
                                {{ isset($row) ? __('frontend.form.edit') : __('frontend.form.add') }}
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


@endsection

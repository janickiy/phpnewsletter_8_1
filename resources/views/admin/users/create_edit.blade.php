@extends('admin.app')

@section('title', $title)

@section('breadcrumbs')
    <li class="breadcrumb-item">
        <a href="{{ route('admin.users.index') }}">{{ __('frontend.menu.users') }}</a>
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
                        {!! Form::open(['url' => isset($row) ? route('admin.users.update') : route('admin.users.store'), 'method' => isset($row) ? 'put' : 'post']) !!}

                        {!! isset($row) ? Form::hidden('id', $row->id) : '' !!}

                        <div class="card-body">

                            <p>*-{{ __('frontend.form.required_fields') }}</p>

                            <div class="form-group">

                                {!! Form::label('name', __('frontend.form.name')) !!}

                                {!! Form::text('name', old('name', $row->name ?? null), ['class' => 'form-control', 'placeholder' => __('frontend.form.name')]) !!}

                                @if ($errors->has('name'))
                                    <p class="text-danger">{{ $errors->first('name') }}</p>
                                @endif
                            </div>

                            <div class="form-group">

                                {!! Form::label('login', __('frontend.form.login')) !!}

                                {!! Form::text('login', old('login', $row->login ?? null), [ 'placeholder' => __('frontend.form.login'), 'class' => 'form-control']) !!}

                                @if ($errors->has('login'))
                                    <p class="text-danger">{{ $errors->first('login') }}</p>
                                @endif

                            </div>

                            <div class="form-group">

                                {!! Form::label('description', __('frontend.form.description')) !!}

                                {!! Form::textarea('description', old('description', $row->description ?? null), [ 'placeholder' => __('frontend.form.description'), 'rows' => 3, 'class' => 'form-control']) !!}

                                @if ($errors->has('description'))
                                    <p class="text-danger">{{ $errors->first('description') }}</p>
                                @endif

                            </div>

                            @if ((isset($row->id) && $row->id != Auth::user()->id) || !isset($row->id))

                                <div class="form-group">

                                    {!! Form::label('role', __('frontend.form.role')) !!}

                                    {!! Form::select('role', $options, $row->role ?? 'admin', ['placeholder' => __('frontend.form.select_role'), 'class' => 'custom-select']) !!}

                                    @if ($errors->has('role'))
                                        <p class="text-danger">{{ $errors->first('role') }}</p>
                                    @endif

                                </div>

                            @else
                                {!! Form::hidden('role', $row->role) !!}
                            @endif

                            <div class="form-group">

                                {!! Form::label('password', __('frontend.form.password')) !!}

                                {!! Form::password('password', ['class' => 'form-control', 'autocomplete' => 'new-password']) !!}

                                @if (isset($row))
                                    <small class="form-text text-muted">
                                        {{ __('frontend.form.leave_blank_password') }}
                                    </small>
                                @endif

                                @if ($errors->has('password'))
                                    <p class="text-danger">{{ $errors->first('password') }}</p>
                                @endif

                            </div>

                            <div class="form-group">

                                {!! Form::label('password_again', __('frontend.form.password_again')) !!}

                                {!! Form::password('password_again', ['class' => 'form-control', 'autocomplete' => 'new-password']) !!}

                                @if ($errors->has('password_again'))
                                    <p class="text-danger">{{ $errors->first('password_again') }}</p>
                                @endif

                            </div>

                        </div>
                        <!-- /.card-body -->

                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">
                                {{ isset($row) ? __('frontend.form.edit') : __('frontend.form.add') }}
                            </button>
                            <a class="btn btn-default float-sm-right" href="{{ route('admin.users.index') }}">
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

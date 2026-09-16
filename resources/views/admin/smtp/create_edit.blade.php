@extends('admin.app')

@section('title', $title)

@section('breadcrumbs')
    <li class="breadcrumb-item">
        <a href="{{ route('admin.smtp.index') }}">{{ __('frontend.title.smtp_index') }}</a>
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

                    <div class="card card-primary">

                        <!-- form start -->
                        {!! Form::open(['url' => isset($row) ? route('admin.smtp.update') : route('admin.smtp.store'), 'method' => isset($row) ? 'put' : 'post']) !!}

                        <div class="card-header">
                            <h3 class="card-title">{{ $title }}</h3>
                        </div>

                        {!! isset($row) ? Form::hidden('id', $row->id) : '' !!}

                        <div class="card-body">

                            <p>*-{{ __('frontend.form.required_fields') }}</p>

                            @php
                                $secureValue = old('secure', $row->secure ?? 'no');
                                $authenticationValue = old('authentication', $row->authentication ?? 'no');
                            @endphp

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        {!! Form::label('host', __('frontend.form.smtp_server') . '*') !!}

                                        {!! Form::text('host', old('host', $row->host ?? null), [ 'placeholder' => __('frontend.form.smtp_server'), 'class' => 'form-control']) !!}

                                        @if ($errors->has('host'))
                                            <p class="text-danger">{{ $errors->first('host') }}</p>
                                        @endif
                                    </div>

                                    <div class="form-group">
                                        {!! Form::label('email', 'E-mail*', ['class' => 'label']) !!}

                                        {!! Form::text('email', old('email', $row->email ?? null), [ 'placeholder' => 'E-mail', 'class' => 'form-control']) !!}

                                        @if ($errors->has('email'))
                                            <p class="text-danger">{{ $errors->first('email') }}</p>
                                        @endif
                                    </div>

                                    <div class="form-group">
                                        {!! Form::label('username', __('frontend.form.login') . '*') !!}

                                        {!! Form::text('username', old('username', $row->username ?? null), [ 'placeholder' => __('frontend.form.login'), 'class' => 'form-control']) !!}

                                        @if ($errors->has('username'))
                                            <p class="text-danger">{{ $errors->first('username') }}</p>
                                        @endif
                                    </div>

                                    <div class="form-group">

                                        {!! Form::label('password', __('frontend.form.password')) !!}

                                        {!! Form::text('password', old('password', $row->password ?? null), [ 'placeholder' => __('frontend.form.password'), 'class' => 'form-control']) !!}

                                        @if ($errors->has('password'))
                                            <p class="text-danger">{{ $errors->first('password') }}</p>
                                        @endif
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        {!! Form::label('port', __('frontend.form.port') . '*') !!}

                                        {!! Form::text('port', old('port', $row->port ?? 25), [ 'placeholder' => __('frontend.form.port'), 'class' => 'form-control']) !!}

                                        @if ($errors->has('port'))
                                            <p class="text-danger">{{ $errors->first('port') }}</p>
                                        @endif
                                    </div>

                                    <div class="form-group">
                                        {!! Form::label('timeout', __('frontend.form.timeout') . '*') !!}

                                        {!! Form::text('timeout', old('timeout', $row->timeout ?? 5), [ 'placeholder' => __('frontend.form.timeout'), 'class' => 'form-control']) !!}

                                        @if ($errors->has('timeout'))
                                            <p class="text-danger">{{ $errors->first('timeout') }}</p>
                                        @endif
                                    </div>

                                    <div class="form-group">
                                        {!! Form::label('secure', __('frontend.form.secure_connection')) !!}

                                        <div>
                                            <div class="custom-control custom-radio custom-control-inline">
                                                {!! Form::radio('secure', 'no', $secureValue === 'no', ['class' => 'custom-control-input', 'id' => 'secure_no']) !!}
                                                <label class="custom-control-label" for="secure_no">{{ __('frontend.str.no') }}</label>
                                            </div>

                                            <div class="custom-control custom-radio custom-control-inline">
                                                {!! Form::radio('secure', 'ssl', $secureValue === 'ssl', ['class' => 'custom-control-input', 'id' => 'secure_ssl']) !!}
                                                <label class="custom-control-label" for="secure_ssl">ssl</label>
                                            </div>

                                            <div class="custom-control custom-radio custom-control-inline">
                                                {!! Form::radio('secure', 'tls', $secureValue === 'tls', ['class' => 'custom-control-input', 'id' => 'secure_tls']) !!}
                                                <label class="custom-control-label" for="secure_tls">tls</label>
                                            </div>
                                        </div>

                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="form-group">

                                        {!! Form::label('authentication', __('frontend.form.authentication_method')) !!}

                                        <div>
                                            <div class="custom-control custom-radio custom-control-inline">
                                                {!! Form::radio('authentication', 'no', $authenticationValue === 'no', ['class' => 'custom-control-input', 'id' => 'authentication_no']) !!}
                                                <label class="custom-control-label" for="authentication_no">LOGIN ({{ __('frontend.form.low_secrecy') }})</label>
                                            </div>

                                            <div class="custom-control custom-radio custom-control-inline">
                                                {!! Form::radio('authentication', 'plain', $authenticationValue === 'plain', ['class' => 'custom-control-input', 'id' => 'authentication_plain']) !!}
                                                <label class="custom-control-label" for="authentication_plain">PLAIN ({{ __('frontend.form.medium_secrecy') }})</label>
                                            </div>

                                            <div class="custom-control custom-radio custom-control-inline">
                                                {!! Form::radio('authentication', 'crammd5', $authenticationValue === 'crammd5', ['class' => 'custom-control-input', 'id' => 'authentication_crammd5']) !!}
                                                <label class="custom-control-label" for="authentication_crammd5">CRAM-MD5 ({{ __('frontend.form.high_secrecy') }})</label>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>

                        </div>
                        <!-- /.card-body -->

                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">
                                {{ isset($row) ? __('frontend.form.edit') : __('frontend.form.add') }}
                            </button>
                            <a class="btn btn-default bg-white float-right" href="{{ route('admin.smtp.index') }}">
                                <i class="fas fa-arrow-left mr-1"></i>
                                {{ __('frontend.form.back') }}
                            </a>
                        </div>

                    {!! Form::close() !!}

                    </div>

                </div>
                <!-- /.card -->
            </div>
        </div>

    </section>
    <!-- /.content -->

@endsection

@section('js')


@endsection

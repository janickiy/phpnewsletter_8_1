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

    <div class="container-fluid">
        <div class="row">
            <div class="col-12">

                <div class="card card-outline card-primary">

                    <!-- form start -->
                    <form action="{{ isset($row) ? route('admin.smtp.update') : route('admin.smtp.store') }}" method="POST">
                        @csrf
                        @if (isset($row))
                            @method('PUT')
                        @endif

                    <div class="card-header">
                        <h3 class="card-title">{{ $title }}</h3>
                    </div>

                    @if (isset($row))
                        <input type="hidden" name="id" value="{{ $row->id }}">
                    @endif

                    <div class="card-body">

                        <p>*-{{ __('frontend.form.required_fields') }}</p>

                        @php
                            $secureValue = old('secure', $row->secure ?? 'no');
                            $authenticationValue = old('authentication', $row->authentication ?? 'no');
                        @endphp

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="host" class="form-label">{{ __('frontend.form.smtp_server') . '*' }}</label>

                                    <input type="text" name="host" value="{{ old('host', $row->host ?? null) }}" placeholder="{{ __('frontend.form.smtp_server') }}" class="form-control" id="host">

                                    @if ($errors->has('host'))
                                        <p class="text-danger">{{ $errors->first('host') }}</p>
                                    @endif
                                </div>

                                <div class="mb-3">
                                    <label for="email" class="form-label">E-mail*</label>

                                    <input type="text" name="email" value="{{ old('email', $row->email ?? null) }}" placeholder="E-mail" class="form-control" id="email">

                                    @if ($errors->has('email'))
                                        <p class="text-danger">{{ $errors->first('email') }}</p>
                                    @endif
                                </div>

                                <div class="mb-3">
                                    <label for="username" class="form-label">{{ __('frontend.form.login') . '*' }}</label>

                                    <input type="text" name="username" value="{{ old('username', $row->username ?? null) }}" placeholder="{{ __('frontend.form.login') }}" class="form-control" id="username">

                                    @if ($errors->has('username'))
                                        <p class="text-danger">{{ $errors->first('username') }}</p>
                                    @endif
                                </div>

                                <div class="mb-3">

                                    <label for="password" class="form-label">{{ __('frontend.form.password') }}</label>

                                    <input type="text" name="password" value="{{ old('password', $row->password ?? null) }}" placeholder="{{ __('frontend.form.password') }}" class="form-control" id="password">

                                    @if ($errors->has('password'))
                                        <p class="text-danger">{{ $errors->first('password') }}</p>
                                    @endif
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="port" class="form-label">{{ __('frontend.form.port') . '*' }}</label>

                                    <input type="text" name="port" value="{{ old('port', $row->port ?? 25) }}" placeholder="{{ __('frontend.form.port') }}" class="form-control" id="port">

                                    @if ($errors->has('port'))
                                        <p class="text-danger">{{ $errors->first('port') }}</p>
                                    @endif
                                </div>

                                <div class="mb-3">
                                    <label for="timeout" class="form-label">{{ __('frontend.form.timeout') . '*' }}</label>

                                    <input type="text" name="timeout" value="{{ old('timeout', $row->timeout ?? 5) }}" placeholder="{{ __('frontend.form.timeout') }}" class="form-control" id="timeout">

                                    @if ($errors->has('timeout'))
                                        <p class="text-danger">{{ $errors->first('timeout') }}</p>
                                    @endif
                                </div>

                                <div class="mb-3">
                                    <label for="secure_no" class="form-label">{{ __('frontend.form.secure_connection') }}</label>

                                    <div>
                                        <div class="form-check form-check-inline">
                                            <input type="radio" name="secure" value="no" class="form-check-input" id="secure_no" @checked($secureValue === 'no')>
                                            <label class="form-check-label" for="secure_no">{{ __('frontend.str.no') }}</label>
                                        </div>

                                        <div class="form-check form-check-inline">
                                            <input type="radio" name="secure" value="ssl" class="form-check-input" id="secure_ssl" @checked($secureValue === 'ssl')>
                                            <label class="form-check-label" for="secure_ssl">ssl</label>
                                        </div>

                                        <div class="form-check form-check-inline">
                                            <input type="radio" name="secure" value="tls" class="form-check-input" id="secure_tls" @checked($secureValue === 'tls')>
                                            <label class="form-check-label" for="secure_tls">tls</label>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <div class="col-12">
                                <div class="mb-3">

                                    <label for="authentication_no" class="form-label">{{ __('frontend.form.authentication_method') }}</label>

                                    <div>
                                        <div class="form-check form-check-inline">
                                            <input type="radio" name="authentication" value="no" class="form-check-input" id="authentication_no" @checked($authenticationValue === 'no')>
                                            <label class="form-check-label" for="authentication_no">LOGIN ({{ __('frontend.form.low_secrecy') }})</label>
                                        </div>

                                        <div class="form-check form-check-inline">
                                            <input type="radio" name="authentication" value="plain" class="form-check-input" id="authentication_plain" @checked($authenticationValue === 'plain')>
                                            <label class="form-check-label" for="authentication_plain">PLAIN ({{ __('frontend.form.medium_secrecy') }})</label>
                                        </div>

                                        <div class="form-check form-check-inline">
                                            <input type="radio" name="authentication" value="crammd5" class="form-check-input" id="authentication_crammd5" @checked($authenticationValue === 'crammd5')>
                                            <label class="form-check-label" for="authentication_crammd5">CRAM-MD5 ({{ __('frontend.form.high_secrecy') }})</label>
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
                        <a class="btn btn-outline-secondary float-sm-end" href="{{ route('admin.smtp.index') }}">
                            <i class="fa-solid fa-arrow-left me-1"></i>
                            {{ __('frontend.form.back') }}
                        </a>
                    </div>

                </form>

                </div>

            </div>
            <!-- /.card -->
        </div>
    </div>

@endsection

@section('js')


@endsection

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
                        <h3 class="card-title">
                            <i class="fa-solid {{ isset($row) ? 'fa-pen-to-square' : 'fa-plus' }} me-2" aria-hidden="true"></i>
                            {{ $title }}
                        </h3>
                    </div>

                    @if (isset($row))
                        <input type="hidden" name="id" value="{{ $row->id }}">
                    @endif

                    <div class="card-body">

                        <p class="text-body-secondary small mb-3">*-{{ __('frontend.form.required_fields') }}</p>

                        @php
                            $secureValue = old('secure', $row->secure ?? 'no');
                            $authenticationValue = old('authentication', $row->authentication ?? 'no');
                        @endphp

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="host" class="form-label">{{ __('frontend.form.smtp_server') . '*' }}</label>

                                <input type="text" name="host" value="{{ old('host', $row->host ?? null) }}" placeholder="{{ __('frontend.form.smtp_server') }}" class="form-control" id="host">

                                @if ($errors->has('host'))
                                    <p class="text-danger">{{ $errors->first('host') }}</p>
                                @endif
                            </div>

                            <div class="col-md-6">
                                <label for="email" class="form-label">E-mail*</label>

                                <input type="text" name="email" value="{{ old('email', $row->email ?? null) }}" placeholder="mail@example.com" class="form-control" id="email">

                                @if ($errors->has('email'))
                                    <p class="text-danger">{{ $errors->first('email') }}</p>
                                @endif
                            </div>

                            <div class="col-md-6">
                                <label for="username" class="form-label">{{ __('frontend.form.login') . '*' }}</label>

                                <input type="text" name="username" value="{{ old('username', $row->username ?? null) }}" placeholder="{{ __('frontend.form.login') }}" class="form-control" id="username">

                                @if ($errors->has('username'))
                                    <p class="text-danger">{{ $errors->first('username') }}</p>
                                @endif
                            </div>

                            <div class="col-md-6">
                                <label for="password" class="form-label">{{ __('frontend.form.password') }}*</label>

                                <input type="text" name="password" value="{{ old('password', $row->password ?? null) }}" placeholder="{{ __('frontend.form.password') }}" class="form-control" id="password">

                                @if ($errors->has('password'))
                                    <p class="text-danger">{{ $errors->first('password') }}</p>
                                @endif
                            </div>

                            <div class="col-md-6">
                                <label for="port" class="form-label">{{ __('frontend.form.port') . '*' }}</label>

                                <input type="text" name="port" value="{{ old('port', $row->port ?? 25) }}" placeholder="{{ __('frontend.form.port') }}" class="form-control" id="port">

                                @if ($errors->has('port'))
                                    <p class="text-danger">{{ $errors->first('port') }}</p>
                                @endif
                            </div>

                            <div class="col-md-6">
                                <label for="timeout" class="form-label">{{ __('frontend.form.timeout') . '*' }}</label>

                                <input type="text" name="timeout" value="{{ old('timeout', $row->timeout ?? 5) }}" placeholder="{{ __('frontend.form.timeout') }}" class="form-control" id="timeout">

                                @if ($errors->has('timeout'))
                                    <p class="text-danger">{{ $errors->first('timeout') }}</p>
                                @endif
                            </div>

                            <div class="col-md-6">
                                <label for="secure" class="form-label">{{ __('frontend.form.secure_connection') }}</label>
                                <select name="secure" id="secure" class="form-select">
                                    <option value="no" @selected($secureValue === 'no')>{{ __('frontend.str.no') }}</option>
                                    <option value="ssl" @selected($secureValue === 'ssl')>ssl</option>
                                    <option value="tls" @selected($secureValue === 'tls')>tls</option>
                                </select>

                                @if ($errors->has('secure'))
                                    <p class="text-danger">{{ $errors->first('secure') }}</p>
                                @endif
                            </div>

                            <div class="col-md-6">
                                <label for="authentication" class="form-label">{{ __('frontend.form.authentication_method') }}</label>
                                <select name="authentication" id="authentication" class="form-select">
                                    <option value="no" @selected($authenticationValue === 'no')>LOGIN ({{ __('frontend.form.low_secrecy') }})</option>
                                    <option value="plain" @selected($authenticationValue === 'plain')>PLAIN ({{ __('frontend.form.medium_secrecy') }})</option>
                                    <option value="crammd5" @selected($authenticationValue === 'crammd5')>CRAM-MD5 ({{ __('frontend.form.high_secrecy') }})</option>
                                </select>

                                @if ($errors->has('authentication'))
                                    <p class="text-danger">{{ $errors->first('authentication') }}</p>
                                @endif
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

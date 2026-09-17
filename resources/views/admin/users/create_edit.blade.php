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

    <div class="container-fluid">
        <div class="row">
            <div class="col-12">

                <!-- general form elements -->
                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fa-solid fa-users me-2" aria-hidden="true"></i>{{ $title }}</h3>
                    </div>

                    <!-- form start -->
                    <form action="{{ isset($row) ? route('admin.users.update') : route('admin.users.store') }}" method="POST">
                        @csrf
                        @if (isset($row))
                            @method('PUT')
                        @endif

                    @if (isset($row))
                        <input type="hidden" name="id" value="{{ $row->id }}">
                    @endif

                    <div class="card-body">

                        <p>*-{{ __('frontend.form.required_fields') }}</p>

                        <div class="mb-3">

                            <label for="name" class="form-label">{{ __('frontend.form.name') }}</label>

                            <input type="text" name="name" value="{{ old('name', $row->name ?? null) }}" class="form-control" placeholder="{{ __('frontend.form.name') }}" id="name">

                            @if ($errors->has('name'))
                                <p class="text-danger">{{ $errors->first('name') }}</p>
                            @endif
                        </div>

                        <div class="mb-3">

                            <label for="login" class="form-label">{{ __('frontend.form.login') }}</label>

                            <input type="text" name="login" value="{{ old('login', $row->login ?? null) }}" placeholder="{{ __('frontend.form.login') }}" class="form-control" id="login">

                            @if ($errors->has('login'))
                                <p class="text-danger">{{ $errors->first('login') }}</p>
                            @endif

                        </div>

                        <div class="mb-3">

                            <label for="description" class="form-label">{{ __('frontend.form.description') }}</label>

                            <textarea name="description" placeholder="{{ __('frontend.form.description') }}" rows="3" class="form-control" id="description" cols="50">{{ old('description', $row->description ?? null) }}</textarea>

                            @if ($errors->has('description'))
                                <p class="text-danger">{{ $errors->first('description') }}</p>
                            @endif

                        </div>

                        @if ((isset($row->id) && $row->id != Auth::user()->id) || !isset($row->id))

                            <div class="mb-3">

                                <label for="role" class="form-label">{{ __('frontend.form.role') }}</label>

                                <select name="role" class="form-select" id="role">
                                    <option value="" @selected((string) old('role', $row->role ?? 'admin') === '')>{{ __('frontend.form.select_role') }}</option>
                                    @foreach ($options as $value => $label)
                                        <option value="{{ $value }}" @selected((string) old('role', $row->role ?? 'admin') === (string) $value)>{{ $label }}</option>
                                    @endforeach
                                </select>

                                @if ($errors->has('role'))
                                    <p class="text-danger">{{ $errors->first('role') }}</p>
                                @endif

                            </div>

                        @else
                            <input type="hidden" name="role" value="{{ old('role', $row->role) }}" id="role">
                        @endif

                        <div class="mb-3">

                            <label for="password" class="form-label">{{ __('frontend.form.password') }}</label>

                            <input type="password" name="password" class="form-control" autocomplete="new-password" id="password">

                            @if (isset($row))
                                <small class="form-text text-muted">
                                    {{ __('frontend.form.leave_blank_password') }}
                                </small>
                            @endif

                            @if ($errors->has('password'))
                                <p class="text-danger">{{ $errors->first('password') }}</p>
                            @endif

                        </div>

                        <div class="mb-3">

                            <label for="password_again" class="form-label">{{ __('frontend.form.password_again') }}</label>

                            <input type="password" name="password_again" class="form-control" autocomplete="new-password" id="password_again">

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
                        <a class="btn btn-outline-secondary float-sm-end" href="{{ route('admin.users.index') }}">
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

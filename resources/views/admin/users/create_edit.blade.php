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
                        <h3 class="card-title"><i class="fa-solid {{ isset($row) ? 'fa-pen-to-square' : 'fa-plus' }} me-2" aria-hidden="true"></i>{{ $title }}</h3>
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
                        <p class="text-body-secondary small mb-3">*-{{ __('frontend.form.required_fields') }}</p>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label">{{ __('frontend.form.name') }}*</label>
                                <input type="text" name="name" value="{{ old('name', $row->name ?? null) }}" class="form-control" placeholder="{{ __('frontend.form.name') }}" id="name" required>
                                @error('name')
                                    <p class="text-danger mb-0">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="login" class="form-label">{{ __('frontend.form.login') }}*</label>
                                <input type="text" name="login" value="{{ old('login', $row->login ?? null) }}" placeholder="{{ __('frontend.form.login') }}" class="form-control" id="login" required>
                                @error('login')
                                    <p class="text-danger mb-0">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="password" class="form-label">{{ __('frontend.form.password') }}{{ isset($row) ? '' : '*' }}</label>
                                <input type="password" name="password" class="form-control" autocomplete="new-password" id="password" @required(!isset($row))>
                                @if (isset($row))
                                    <small class="form-text text-body-secondary">{{ __('frontend.form.leave_blank_password') }}</small>
                                @endif
                                @error('password')
                                    <p class="text-danger mb-0">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="role" class="form-label">{{ __('frontend.form.role') }}*</label>
                                @if (!isset($row) || $row->id !== Auth::id())
                                    <select name="role" class="form-select" id="role" required aria-describedby="role-description">
                                        @foreach ($options as $value => $label)
                                            <option value="{{ $value }}" @selected((string) old('role', $row->role ?? \App\Enums\UserRole::Admin->value) === (string) $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    <input type="hidden" name="role" value="{{ $row->role }}">
                                    <input type="text" class="form-control" id="role" value="{{ $row->role_label }}" readonly aria-describedby="role-description">
                                @endif
                                @error('role')
                                    <p class="text-danger mb-0">{{ $message }}</p>
                                @enderror
                                <div id="role-description" class="text-body-secondary small mt-2">
                                    <strong>{{ __('frontend.str.projects.roles_note') }}</strong>
                                    <ul class="mb-0 ps-3">
                                        @foreach (\App\Enums\UserRole::descriptions() as $description)
                                            <li>{{ $description }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label for="password_again" class="form-label">{{ __('frontend.form.password_again') }}{{ isset($row) ? '' : '*' }}</label>
                                <input type="password" name="password_again" class="form-control" autocomplete="new-password" id="password_again" @required(!isset($row))>
                                @error('password_again')
                                    <p class="text-danger mb-0">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="description" class="form-label">{{ __('frontend.form.description') }}</label>
                                <textarea name="description" placeholder="{{ __('frontend.form.description') }}" rows="5" class="form-control" id="description">{{ old('description', $row->description ?? null) }}</textarea>
                                @error('description')
                                    <p class="text-danger mb-0">{{ $message }}</p>
                                @enderror
                            </div>
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

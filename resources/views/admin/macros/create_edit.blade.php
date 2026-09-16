@extends('admin.app')

@section('title', $title)

@section('breadcrumbs')
    <li class="breadcrumb-item">
        <a href="{{ route('admin.macros.index') }}">{{ __('frontend.menu.macros') }}</a>
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

                    <!-- form start -->
                    <form method="POST" action="{{ isset($row) ? route('admin.macros.update') : route('admin.macros.store') }}" accept-charset="UTF-8">
                        @csrf
                        @if(isset($row))
                            @method('PUT')
                        @endif

                    @if(isset($row))
                        <input type="hidden" name="id" value="{{ $row->id }}">
                    @endif

                    <div class="card-body">

                        <p>*-{{ __('frontend.form.required_fields') }}</p>

                        <div class="mb-3">
                            <label for="name" class="form-label">{{ __('frontend.form.macros_name') }}*</label>

                            <input type="text" name="name" id="name" value="{{ old('name', $row->name ?? null) }}" class="form-control" placeholder="{{ __('frontend.form.name') }}">

                            @if ($errors->has('name'))
                                <p class="text-danger">{{ $errors->first('name') }}</p>
                            @endif
                        </div>

                        <div class="mb-3">

                            <label for="value" class="form-label">{{ __('frontend.form.value') }}*</label>

                            <textarea name="value" id="value" placeholder="{{ __('frontend.form.value') }}" rows="3" cols="50" class="form-control">{{ old('value', $row->value ?? null) }}</textarea>

                            @if ($errors->has('value'))
                                <p class="text-danger">{{ $errors->first('value') }}</p>
                            @endif

                        </div>

                        <div class="mb-3">

                            <label for="type" class="form-label">{{ __('frontend.form.macros_type') }}*</label>

                            <select name="type" id="type" class="form-select">
                                <option value="" @selected((string) old('type', $row->type ?? '') === '')>{{ __('frontend.form.macros_type') }}</option>
                                @foreach($options as $value => $label)
                                    <option value="{{ $value }}" @selected((string) old('type', $row->type ?? '') === (string) $value)>{{ $label }}</option>
                                @endforeach
                            </select>

                            @if ($errors->has('type'))
                                <p class="text-danger">{{ $errors->first('type') }}</p>
                            @endif
                        </div>

                    </div>
                    <!-- /.card-body -->

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            {{ isset($row) ? __('frontend.form.edit') : __('frontend.form.add') }}
                        </button>
                        <a class="btn btn-outline-secondary float-sm-end" href="{{ route('admin.macros.index') }}">
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

    <script>
        $(function () {
            $('#type').on('change', function () {
                let sampleMacros = getValue(this.value);
                $('#value').val(sampleMacros);
            });
        });

        function getValue(value) {
            switch (value) {
                case '1':
                    return '{{ __('frontend.form.sample_macros_type_url') }}';
                case '2':
                    return '{{ __('frontend.form.sample_macros_type_email') }}';
                case '3':
                    return '{{ __('frontend.form.sample_macros_type_hash_tags') }}';
                case '4':
                    return '{{ __('frontend.form.sample_macros_type_tags') }}';
                case '5':
                    return '{{ __('frontend.form.sample_macros_type_wrap_phrase') }}';
            }
        }

    </script>

@endsection

@extends('admin.app')

@section('title', $title)

@section('breadcrumbs')
    <li class="breadcrumb-item">
        <a href="{{ route('admin.category.index') }}">{{ __('frontend.title.category_index') }}</a>
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
                    <form method="POST" action="{{ isset($row) ? route('admin.category.update') : route('admin.category.store') }}" accept-charset="UTF-8">
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
                            <label for="project_id" class="form-label">{{ __('frontend.str.projects.project') }}{{ !isset($row) || $row->project_id !== null ? '*' : '' }}</label>
                            @if(isset($row))
                                <input type="hidden" name="project_id" value="{{ $row->project_id }}">
                                <input id="project_id" class="form-control" value="{{ $projects->firstWhere('id', $row->project_id)?->name ?? __('frontend.str.projects.subscriber_unassigned') }}" readonly>
                            @else
                                <select name="project_id" id="project_id" class="form-select" required>
                                    <option value="">{{ __('frontend.str.projects.select') }}</option>
                                    @foreach($projects as $projectOption)
                                        <option value="{{ $projectOption->id }}" @selected((int) old('project_id', $projects->first()?->id) === $projectOption->id)>{{ $projectOption->name }}</option>
                                    @endforeach
                                </select>
                            @endif
                            @error('project_id')<p class="text-danger">{{ $message }}</p>@enderror
                        </div>

                        <div class="mb-3">
                            <label for="name" class="form-label">{{ __('frontend.form.name') }}*</label>

                            <input type="text" name="name" id="name" value="{{ old('name', $row->name ?? null) }}" class="form-control" placeholder="{{ __('frontend.form.name') }}">

                            @if ($errors->has('name'))
                                <p class="text-danger">{{ $errors->first('name') }}</p>
                            @endif
                        </div>

                    </div>
                    <!-- /.card-body -->

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            {{ isset($row) ? __('frontend.form.edit') : __('frontend.form.add') }}
                        </button>
                        <a class="btn btn-outline-secondary float-sm-end" href="{{ route('admin.category.index') }}">
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

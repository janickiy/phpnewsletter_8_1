@extends('admin.app')

@section('title', $title)

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.projects.index') }}">{{ __('frontend.str.projects.index') }}</a></li>
    <li class="breadcrumb-item active">{{ $title }}</li>
@endsection

@section('content')
    <div class="container-fluid">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fa-solid {{ isset($row) ? 'fa-pen-to-square' : 'fa-plus' }} me-2" aria-hidden="true"></i>{{ $title }}</h3>
            </div>
            <form method="POST" action="{{ isset($row) ? route('admin.projects.update') : route('admin.projects.store') }}">
                @csrf
                @if(isset($row))
                    @method('PUT')
                    <input type="hidden" name="id" value="{{ $row->id }}">
                @endif
                <div class="card-body">
                    <p class="small text-body-secondary">*-{{ __('frontend.form.required_fields') }}</p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">{{ __('frontend.str.projects.name') }}*</label>
                            <input type="text" class="form-control" name="name" id="name" maxlength="255" value="{{ old('name', $row->name ?? '') }}" required>
                            @error('name')<p class="text-danger mb-0">{{ $message }}</p>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="status" class="form-label">{{ __('frontend.str.projects.status') }}</label>
                            <select class="form-select" name="status" id="status">
                                <option value="1" @selected((string) old('status', isset($row) ? (int) $row->status : 1) === '1')>{{ __('frontend.str.projects.active') }}</option>
                                <option value="0" @selected((string) old('status', isset($row) ? (int) $row->status : 1) === '0')>{{ __('frontend.str.projects.inactive') }}</option>
                            </select>
                            @error('status')<p class="text-danger mb-0">{{ $message }}</p>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="description" class="form-label">{{ __('frontend.str.projects.description') }}</label>
                            <textarea class="form-control" name="description" id="description" rows="4" maxlength="10000">{{ old('description', $row->description ?? '') }}</textarea>
                            @error('description')<p class="text-danger mb-0">{{ $message }}</p>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="owner_id" class="form-label">{{ __('frontend.str.projects.owner') }}</label>
                            @if(auth()->user()->isAdmin())
                                <select class="form-select" name="owner_id" id="owner_id" required>
                                    @foreach($owners as $owner)
                                        <option value="{{ $owner->id }}" @selected((string) old('owner_id', $row->owner_id ?? auth()->id()) === (string) $owner->id)>{{ $owner->name }} ({{ $owner->login }})</option>
                                    @endforeach
                                </select>
                            @else
                                <input type="text" class="form-control" id="owner_id" value="{{ $row?->owner?->name ?? auth()->user()->name }}" readonly>
                            @endif
                            <div class="form-text">{{ __('frontend.str.projects.owner_help') }}</div>
                            @error('owner_id')<p class="text-danger mb-0">{{ $message }}</p>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="project_admin_ids" class="form-label">{{ __('frontend.str.projects.administrators') }}</label>
                            @if($canAssignAdministrators)
                                <input type="hidden" name="project_admin_ids_present" value="1">
                                @php($selectedAdministrators = collect(session()->hasOldInput() ? old('project_admin_ids', []) : $assignedAdministrators->pluck('id'))->map(fn ($id) => (string) $id)->all())
                                <select class="form-select" name="project_admin_ids[]" id="project_admin_ids" multiple size="5" aria-describedby="administrators-help">
                                    @foreach($administrators as $administrator)
                                        <option value="{{ $administrator->id }}" @selected(in_array((string) $administrator->id, $selectedAdministrators, true))>{{ $administrator->name }} ({{ $administrator->login }})</option>
                                    @endforeach
                                </select>
                                @if($administrators->isEmpty())<p class="form-text mb-0">{{ __('frontend.str.projects.no_administrators') }}</p>@endif
                            @else
                                <div id="project_admin_ids" class="border rounded p-2 bg-body-tertiary">
                                    {{ $assignedAdministrators->pluck('name')->join(', ') ?: __('frontend.str.projects.not_assigned') }}
                                </div>
                            @endif
                            <div id="administrators-help" class="form-text">{{ __('frontend.str.projects.administrators_help') }}</div>
                            @foreach($errors->get('project_admin_ids*') as $messages)
                                @foreach($messages as $message)<p class="text-danger mb-0">{{ $message }}</p>@endforeach
                            @endforeach
                        </div>
                        <div class="col-md-6">
                            <label for="moderator_ids" class="form-label">{{ __('frontend.str.projects.moderators') }}</label>
                            <input type="hidden" name="moderator_ids_present" value="1">
                            @php($selectedModerators = collect(session()->hasOldInput() ? old('moderator_ids', []) : $assignedModerators->pluck('id'))->map(fn ($id) => (string) $id)->all())
                            <select class="form-select" name="moderator_ids[]" id="moderator_ids" multiple size="5" aria-describedby="moderators-help">
                                @foreach($moderators as $moderator)
                                    <option value="{{ $moderator->id }}" @selected(in_array((string) $moderator->id, $selectedModerators, true))>{{ $moderator->name }} ({{ $moderator->login }})</option>
                                @endforeach
                            </select>
                            @if($moderators->isEmpty())<p class="form-text mb-0">{{ __('frontend.str.projects.no_moderators') }}</p>@endif
                            <div id="moderators-help" class="form-text">{{ __('frontend.str.projects.moderators_help') }}</div>
                            @foreach($errors->get('moderator_ids*') as $messages)
                                @foreach($messages as $message)<p class="text-danger mb-0">{{ $message }}</p>@endforeach
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">{{ isset($row) ? __('frontend.form.edit') : __('frontend.form.add') }}</button>
                    <a class="btn btn-outline-secondary float-sm-end" href="{{ route('admin.projects.index') }}"><i class="fa-solid fa-arrow-left me-1" aria-hidden="true"></i>{{ __('frontend.form.back') }}</a>
                </div>
            </form>
        </div>
    </div>
@endsection

<div class="mb-3">
    <label for="project_ids" class="form-label">{{ __('frontend.str.projects.index') }}{{ $projectSelectionRequired ? '*' : '' }}</label>
    <select name="project_ids[]" id="project_ids" class="form-select" multiple size="{{ min(max($projects->count(), 2), 6) }}" @required($projectSelectionRequired)>
        @foreach($projects as $projectOption)
            <option value="{{ $projectOption->id }}" @selected(in_array($projectOption->id, $selectedProjectIds))>{{ $projectOption->name }}</option>
        @endforeach
    </select>
    @if(auth()->user()->isAdmin())
        <div class="form-text">{{ __('frontend.str.projects.subscriber_projects_hint') }}</div>
    @endif
    @if($errors->has('project_ids') || $errors->has('project_ids.*'))
        <p class="text-danger">{{ $errors->first('project_ids') ?: $errors->first('project_ids.*') }}</p>
    @endif
</div>

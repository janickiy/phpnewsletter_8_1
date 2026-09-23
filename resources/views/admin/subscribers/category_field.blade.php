@php
    $selectedCategoryIds = array_map('strval', (array) (session()->hasOldInput()
        ? old('categoryId', [])
        : ($subscriberCategoryIds ?? [])));
@endphp

<div class="mb-3">
    <label for="categoryId" class="form-label">{{ __('frontend.form.subscribers_category') }}</label>
    <select name="categoryId[]" id="categoryId" multiple class="form-select">
        @foreach($options as $categoryValue => $categoryLabel)
            <option value="{{ $categoryValue }}" @selected(in_array((string) $categoryValue, $selectedCategoryIds, true))>{{ $categoryLabel }}</option>
        @endforeach
    </select>
    @if ($errors->has('categoryId') || $errors->has('categoryId.*'))
        <p class="text-danger">{{ $errors->first('categoryId') ?: $errors->first('categoryId.*') }}</p>
    @endif
</div>

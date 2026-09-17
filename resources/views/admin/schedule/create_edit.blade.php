@extends('admin.app')

@section('title', $title)

@section('breadcrumbs')
    <li class="breadcrumb-item">
        <a href="{{ route('admin.schedule.index') }}">{{ __('frontend.menu.schedule') }}</a>
    </li>
    <li class="breadcrumb-item active">{{ $title }}</li>
@endsection

@section('css')

    <link rel="stylesheet" href="{{ asset('/plugins/daterangepicker/daterangepicker.css') }}">

@endsection

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fa-solid fa-calendar-days me-2" aria-hidden="true"></i>
                            {{ $title }}
                        </h3>
                    </div>

                    <form method="POST" action="{{ isset($row) ? route('admin.schedule.update') : route('admin.schedule.store') }}" accept-charset="UTF-8">
                        @csrf
                        @if(isset($row))
                            @method('PUT')
                            <input type="hidden" name="id" value="{{ $row->id }}">
                        @endif

                        <div class="card-body">
                            <p class="text-body-secondary small mb-3">*-{{ __('frontend.form.required_fields') }}</p>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="event_name" class="form-label">{{ __('frontend.form.name') }}*</label>
                                    <input type="text" name="event_name" id="event_name" value="{{ old('event_name', $row->event_name ?? null) }}" class="form-control @error('event_name') is-invalid @enderror" placeholder="{{ __('frontend.form.name') }}">
                                    @error('event_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="template_id" class="form-label">{{ __('frontend.form.template') }}</label>
                                    <select name="template_id" id="template_id" class="form-select @error('template_id') is-invalid @enderror">
                                        <option value="" @selected((string) old('template_id', $row->template_id ?? '') === '')>{{ __('frontend.form.select') }}</option>
                                        @foreach($options as $value => $label)
                                            <option value="{{ $value }}" @selected((string) old('template_id', $row->template_id ?? '') === (string) $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('template_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="date_interval" class="form-label">{{ __('frontend.str.date') }}</label>
                                    <div class="input-group has-validation">
                                        <span class="input-group-text"><i class="far fa-calendar-alt"></i></span>
                                        <input type="text" name="date_interval" value="{{ old('date_interval', $date_interval ?? null) }}" placeholder="DD.MM.YYYY HH:MM - DD.MM.YYYY HH:MM" class="form-control @error('date_interval') is-invalid @enderror" id="date_interval">
                                        @error('date_interval')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label for="categoryId" class="form-label">{{ __('frontend.form.subscribers_category') }}</label>
                                    @php
                                        $selectedCategoryIds = collect(session()->hasOldInput() ? old('categoryId', []) : ($categoryId ?? []))
                                            ->map(fn ($value) => (string) $value)
                                            ->all();
                                    @endphp
                                    <select name="categoryId[]" id="categoryId" multiple class="form-select @error('categoryId') is-invalid @enderror">
                                        @foreach($category_options as $categoryValue => $categoryLabel)
                                            <option value="{{ $categoryValue }}" @selected(in_array((string) $categoryValue, $selectedCategoryIds, true))>
                                                {{ $categoryLabel }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('categoryId')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">
                                {{ isset($row) ? __('frontend.form.edit') : __('frontend.form.add') }}
                            </button>
                            <a class="btn btn-outline-secondary float-sm-end" href="{{ route('admin.schedule.index') }}">
                                <i class="fas fa-arrow-left me-1"></i>
                                {{ __('frontend.form.back') }}
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')

    <!-- moment -->
    <script src="{{ asset('/plugins/moment/moment.min.js') }}"></script>

    {{-- Динамическое подключение locale --}}
    @php
        $localeMap = [
            'ru' => 'ru',
            'en' => 'en-gb', // важно: у moment нет просто "en"
            'uk' => 'uk',
            'de' => 'de',
            'fr' => 'fr',
            'es' => 'es',
            'it' => 'it',
            'hi' => 'hi',
            'ar' => 'ar',
            'pt' => 'pt',
            'pt-br' => 'pt-br',
            'zh-cn' => 'zh-cn',
            'zh-tw' => 'zh-tw',
        ];

        $momentLocale = $localeMap[strtolower(app()->getLocale())] ?? 'en-gb';
    @endphp

    <script src="{{ asset('/plugins/moment/locale/' . $momentLocale . '.js') }}"></script>

    <!-- daterangepicker -->
    <script src="{{ asset('/plugins/daterangepicker/daterangepicker.js') }}"></script>

    <script>
        $(function () {

            let locale = @json($momentLocale);

            moment.locale(locale);

            let localeData = moment.localeData();

            // Keep submitted dates in ASCII digits while translating calendar labels.
            moment.locale('en');

            const dateInput = $('#date_interval');
            const dateFormat = 'DD.MM.YYYY HH:mm';
            const initialValue = dateInput.val();
            const freshForm = @json(!isset($row) && !session()->hasOldInput());
            const defaultStart = moment().add(1, 'days').startOf('hour').add(1, 'hours');
            const defaultEnd = defaultStart.clone().add(1, 'hours');
            const initialDates = initialValue.split(' - ');
            const savedStart = moment(initialDates[0], dateFormat, true);
            const savedEnd = moment(initialDates[1], dateFormat, true);
            const validSavedRange = initialDates.length === 2 && savedStart.isValid()
                && savedEnd.isValid() && savedEnd.isAfter(savedStart);

            const pickerOptions = {
                autoUpdateInput: false,
                startDate: validSavedRange ? savedStart : defaultStart,
                endDate: validSavedRange ? savedEnd : defaultEnd,
                buttonClasses: 'btn btn-sm',
                applyButtonClasses: 'btn-primary',
                cancelButtonClasses: 'btn-outline-secondary',
                timePicker: true,
                timePickerIncrement: 30,
                timePicker24Hour: true,
                locale: {
                    format: dateFormat,
                    separator: ' - ',
                    applyLabel: @json(__('frontend.str.apply')),
                    cancelLabel: @json(__('frontend.str.cancel')),
                    daysOfWeek: localeData.weekdaysMin(),
                    monthNames: localeData.months(),
                    firstDay: localeData.firstDayOfWeek()
                },
            };

            if (freshForm && initialValue === '') {
                pickerOptions.minDate = moment().add(1, 'days');
                pickerOptions.maxDate = moment().add(359, 'days');
                dateInput.val(defaultStart.format(dateFormat) + ' - ' + defaultEnd.format(dateFormat));
            }

            dateInput.daterangepicker(pickerOptions);
            dateInput.on('apply.daterangepicker', function (event, picker) {
                $(this).val(picker.startDate.format(dateFormat) + ' - ' + picker.endDate.format(dateFormat));
            });

        });
    </script>

@endsection

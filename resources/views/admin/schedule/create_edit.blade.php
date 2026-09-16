@extends('admin.app')

@section('title', $title)

@section('breadcrumbs')
    <li class="breadcrumb-item">
        <a href="{{ route('admin.schedule.index') }}">{{ __('frontend.menu.schedule') }}</a>
    </li>
    <li class="breadcrumb-item active">{{ $title }}</li>
@endsection

@section('css')

    {!! Html::style('/plugins/daterangepicker/daterangepicker.css') !!}

@endsection

@section('content')
    <!-- Main content -->
    <section class="content">

        <div class="container-fluid">
            <div class="row">
                <div class="col-12">

                    <!-- general form elements -->
                    <header class="card card-primary">

                        <!-- form start -->

                        {!! Form::open(['url' => isset($row) ? route('admin.schedule.update') : route('admin.schedule.store'), 'method' => isset($row) ? 'put' : 'post']) !!}

                        {!! isset($row) ? Form::hidden('id', $row->id) : '' !!}

                        <div class="card-body">

                            <p>*-{{ __('frontend.form.required_fields') }}</p>

                            <div class="form-group">
                                {!! Form::label('event_name', __('frontend.form.name') . '*') !!}

                                {!! Form::text('event_name', old('event_name', $row->event_name ?? null), ['class' => 'form-control', 'placeholder' => __('frontend.form.name')]) !!}

                                @if ($errors->has('event_name'))
                                    <p class="text-danger">{{ $errors->first('event_name') }}</p>
                                @endif
                            </div>

                            <div class="form-group">

                                {!! Form::label('template_id',  __('frontend.form.template')) !!}

                                {!! Form::select('template_id', $options, old('template_id', $row->template_id ?? null), ['placeholder' => __('frontend.form.select'), 'class' => 'custom-select']) !!}

                                @if ($errors->has('template_id'))
                                    <p class="text-danger">{{ $errors->first('template_id') }}</p>
                                @endif

                            </div>

                            <div class="form-group">
                                <div class="row">
                                    <div class="col-3">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">
                                                    <i class="far fa-calendar-alt"></i>
                                                </span>
                                            </div>

                                            {!! Form::text('date_interval', old('date_interval', $date_interval ?? null), ['placeholder' => 'DD.MM.YYYY HH:MM - DD.MM.YYYY HH:MM', 'class' => 'form-control', 'id' => 'date_interval']) !!}
                                        </div>
                                        @if ($errors->has('date_interval'))
                                            <p class="text-danger">{{ $errors->first('date_interval') }}</p>
                                        @endif
                                    </div>

                                </div>
                            </div>

                            <div class="form-group">

                                {!! Form::label('categoryId',  __('frontend.form.subscribers_category')) !!}

                                @php
                                    $selectedCategoryIds = collect(old('categoryId', $categoryId ?? []))
                                        ->map(fn ($value) => (string) $value)
                                        ->all();
                                @endphp

                                <select name="categoryId[]" id="categoryId" multiple class="form-control">
                                    @foreach($category_options as $categoryValue => $categoryLabel)
                                        <option value="{{ $categoryValue }}" @selected(in_array((string) $categoryValue, $selectedCategoryIds, true))>
                                            {{ $categoryLabel }}
                                        </option>
                                    @endforeach
                                </select>

                                @if ($errors->has('categoryId'))
                                    <p class="text-danger">{{ $errors->first('categoryId') }}</p>
                                @endif
                            </div>

                            <!-- /.card-body -->
                        </div>

                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">
                                {{ isset($row) ? __('frontend.form.edit') : __('frontend.form.add') }}
                            </button>
                            <a class="btn btn-default float-sm-right" href="{{ route('admin.schedule.index') }}">
                                <i class="fas fa-arrow-left mr-1"></i>
                                {{ __('frontend.form.back') }}
                            </a>
                        </div>

                        {!! Form::close() !!}

                    </header>

                </div>
                <!-- /.card -->
            </div>
        </div>

    </section>
    <!-- /.content -->

@endsection

@section('js')

    <!-- moment -->
    {!! Html::script('/plugins/moment/moment.min.js') !!}

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
            'pt' => 'pt',
            'pt-BR' => 'pt-br',
            'zh-CN' => 'zh-cn',
            'zh-TW' => 'zh-tw',
        ];

        $momentLocale = $localeMap[app()->getLocale()] ?? 'en-gb';
    @endphp

    {!! Html::script('/plugins/moment/locale/' . $momentLocale . '.js') !!}

    <!-- daterangepicker -->
    {!! Html::script('/plugins/daterangepicker/daterangepicker.js') !!}

    <script>
        $(function () {

            let locale = @json($momentLocale);

            moment.locale(locale);

            let localeData = moment.localeData();

            $('#date_interval').daterangepicker({
                timePicker: true,
                timePickerIncrement: 30,
                timePicker24Hour: true,
                locale: {
                    format: 'DD.MM.YYYY HH:mm',
                    separator: ' - ',
                    applyLabel: @json(__('frontend.str.apply')),
                    cancelLabel: @json(__('frontend.str.cancel')),
                    daysOfWeek: localeData.weekdaysMin(),
                    monthNames: localeData.months(),
                    firstDay: localeData.firstDayOfWeek()
                },
                minDate: moment().add(1, 'days'),
                maxDate: moment().add(359, 'days'),
            });

        });
    </script>

@endsection

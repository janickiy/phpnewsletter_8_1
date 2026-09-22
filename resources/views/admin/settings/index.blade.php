@extends('admin.app')

@section('title', $title)

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fa-solid fa-gears me-2" aria-hidden="true"></i>{{ $title }}
                        </h3>
                    </div>
                    <div class="p-3 bg-body-tertiary border-bottom">
                        <ul class="nav nav-pills flex-column flex-lg-row gap-2" role="tablist">
                            <li class="nav-item border-0" role="presentation">
                                <button type="button" class="nav-link active" id="s1-tab" data-bs-target="#s1" data-bs-toggle="tab" role="tab" aria-controls="s1" aria-selected="true"><i class="fa-solid fa-sliders me-2" aria-hidden="true"></i>{{ __('frontend.str.settings_form.general') }}</button>
                            </li>
                            <li class="nav-item border-0" role="presentation">
                                <button type="button" class="nav-link" id="s2-tab" data-bs-target="#s2" data-bs-toggle="tab" role="tab" aria-controls="s2" aria-selected="false"><i class="fa-solid fa-paper-plane me-2" aria-hidden="true"></i>{{ __('frontend.str.mailing_options') }}</button>
                            </li>
                            <li class="nav-item border-0" role="presentation">
                                <button type="button" class="nav-link" id="s3-tab" data-bs-target="#s3" data-bs-toggle="tab" role="tab" aria-controls="s3" aria-selected="false"><i class="fa-solid fa-list me-2" aria-hidden="true"></i>{{ __('frontend.str.additional_headers') }}</button>
                            </li>
                        </ul>
                    </div>
                    <form action="{{ route('admin.settings.update') }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="card-body">
                            <div class="tab-content">
                                <div class="tab-pane fade show active" id="s1" role="tabpanel" aria-labelledby="s1-tab" tabindex="0">
                                    @include('admin.settings.general')
                                </div>
                                <!-- /.tab-pane -->
                                <div class="tab-pane fade" id="s2" role="tabpanel" aria-labelledby="s2-tab" tabindex="0">
                                    @include('admin.settings.mailing')
                                </div>
                                <!-- /.tab-pane -->
                                <div class="tab-pane fade" id="s3" role="tabpanel" aria-labelledby="s3-tab" tabindex="0">
                                    <div id="headerslist">
                                        @php
                                            $headerNames = session()->hasOldInput()
                                                ? old('header_name', [])
                                                : collect($customHeaders ?? [])->pluck('name')->all();
                                            $headerValues = session()->hasOldInput()
                                                ? old('header_value', [])
                                                : collect($customHeaders ?? [])->pluck('value')->all();
                                        @endphp
                                        @foreach ($headerNames as $headerIndex => $headerName)
                                            <div class="header-row border rounded bg-body-tertiary p-3 mb-3">
                                                <div class="row g-3 align-items-end">
                                                    <div class="col-md-5">
                                                        <label for="header_name_{{ $loop->index }}" class="form-label">{{ __('frontend.form.name') }}</label>
                                                        <input type="text" name="header_name[]" value="{{ $headerName }}" class="form-control" id="header_name_{{ $loop->index }}">
                                                    </div>
                                                    <div class="col-md-5">
                                                        <label for="header_value_{{ $loop->index }}" class="form-label">{{ __('frontend.form.value') }}</label>
                                                        <input type="text" name="header_value[]" value="{{ $headerValues[$headerIndex] ?? '' }}" class="form-control" id="header_value_{{ $loop->index }}">
                                                    </div>
                                                    <div class="col-md-2 d-flex justify-content-md-end">
                                                        <button type="button" class="btn btn-outline-danger removeBlock" title="{{ __('frontend.form.remove') }}" aria-label="{{ __('frontend.form.remove') }}"><i class="fa-solid fa-trash-can" aria-hidden="true"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                    <button type="button" class="btn btn-outline-primary" id="add_field"><i class="fa-solid fa-plus me-1" aria-hidden="true"></i>{{ __('frontend.form.add') }}</button>
                                </div>
                                <!-- /.tab-pane -->
                            </div>
                            <!-- /.tab-content -->
                        </div><!-- /.card-body -->
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa-solid fa-check me-1" aria-hidden="true"></i>{{ __('frontend.str.settings_form.save') }}
                            </button>
                        </div>
                    </form>
                    <!-- /.card-body -->
                </div>
                <!-- /.card -->
            </div>
            <!-- /.col -->
        </div>
        <!-- /.row -->
    </div>
    <!-- /.container-fluid -->
@endsection

@section('js')
    <script>
        $(function () {
            const $confirmationToggle = $('#REQUIRE_SUB_CONFIRMATION');
            const $confirmationFields = $('#settings-confirmation-fields');
            const confirmationHasErrors = @json($errors->hasAny(['SUBJECT_TEXT_CONFIRM', 'TEXT_CONFIRMATION']));

            $confirmationToggle.on('change', function () {
                const expanded = this.checked || confirmationHasErrors;
                $confirmationFields.prop('hidden', !expanded);
                $confirmationToggle.attr('aria-expanded', String(expanded));
            });

            const mailingErrors = {
                sendmail: @json($errors->has('SENDMAIL_PATH')),
                interval: @json($errors->has('INTERVAL_NUMBER')),
                limit: @json($errors->has('LIMIT_NUMBER')),
                cleanup: @json($errors->has('DAYS_FOR_REMOVE_SUBSCRIBER'))
            };

            function updateMailingFields() {
                $('#settings-sendmail-field').prop('hidden', $('#HOW_TO_SEND').val() !== 'sendmail' && !mailingErrors.sendmail);
                $('#settings-interval-value').prop('hidden', $('#INTERVAL_TYPE').val() === 'no' && !mailingErrors.interval);

                const showLimit = $('#LIMIT_SEND').prop('checked') || mailingErrors.limit;
                $('#settings-limit-fields').prop('hidden', !showLimit);
                $('#LIMIT_SEND').attr('aria-expanded', String(showLimit));

                const showCleanup = $('#REMOVE_SUBSCRIBER').prop('checked') || mailingErrors.cleanup;
                $('#settings-cleanup-fields').prop('hidden', !showCleanup);
                $('#REMOVE_SUBSCRIBER').attr('aria-expanded', String(showCleanup));
            }

            $('#HOW_TO_SEND, #INTERVAL_TYPE, #LIMIT_SEND, #REMOVE_SUBSCRIBER').on('change', updateMailingFields);
            updateMailingFields();

            let headerIndex = $('#headerslist .header-row').length;
            const headerLabels = {
                name: @json(__('frontend.form.name')),
                value: @json(__('frontend.form.value')),
                remove: @json(__('frontend.form.remove'))
            };

            $('#add_field').on('click', function () {
                const $row = $('<div>', {class: 'row g-3 align-items-end'});

                ['name', 'value'].forEach(function (field) {
                    const id = 'header_' + field + '_' + headerIndex;
                    $row.append($('<div>', {class: 'col-md-5'}).append(
                        $('<label>', {class: 'form-label', for: id}).text(headerLabels[field]),
                        $('<input>', {class: 'form-control', type: 'text', name: 'header_' + field + '[]', id: id})
                    ));
                });

                $row.append($('<div>', {class: 'col-md-2 d-flex justify-content-md-end'}).append(
                    $('<button>', {type: 'button', class: 'btn btn-outline-danger removeBlock', title: headerLabels.remove, 'aria-label': headerLabels.remove}).append(
                        $('<i>', {class: 'fa-solid fa-trash-can', 'aria-hidden': 'true'})
                    )
                ));

                const $header = $('<div>', {class: 'header-row border rounded bg-body-tertiary p-3 mb-3'}).append($row);
                $('#headerslist').append($header);
                $header.find('input').first().trigger('focus');
                headerIndex++;
            });

            $('#headerslist').on('click', '.removeBlock', function () {
                $(this).closest('.header-row').remove();
                $('#add_field').trigger('focus');
            });
        });
    </script>
@endsection

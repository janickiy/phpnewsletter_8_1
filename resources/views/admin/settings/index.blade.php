@extends('admin.app')

@section('title', $title)

@section('css')


@endsection

@section('content')

    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header p-2">
                        <ul class="nav nav-pills flex-column flex-sm-row gap-1" role="tablist">
                            <li class="nav-item border-0" role="presentation">
                                <button type="button" class="nav-link active" id="s1-tab" data-bs-target="#s1" data-bs-toggle="tab" role="tab" aria-controls="s1" aria-selected="true">{{ __('frontend.str.interface_settings') }}</button>
                            </li>
                            <li class="nav-item border-0" role="presentation">
                                <button type="button" class="nav-link" id="s2-tab" data-bs-target="#s2" data-bs-toggle="tab" role="tab" aria-controls="s2" aria-selected="false">{{ __('frontend.str.mailing_options') }}</button>
                            </li>
                            <li class="nav-item border-0" role="presentation">
                                <button type="button" class="nav-link" id="s3-tab" data-bs-target="#s3" data-bs-toggle="tab" role="tab" aria-controls="s3" aria-selected="false">{{ __('frontend.str.additional_headers') }}</button>
                            </li>
                        </ul>
                    </div><!-- /.card-header -->

                    <form action="{{ route('admin.settings.update') }}" method="POST">
                        @csrf
                        @method('PUT')

                    <div class="card-body">
                        <div class="tab-content">
                            <div class="tab-pane fade show active" id="s1" role="tabpanel" aria-labelledby="s1-tab" tabindex="0">
                                <div class="mb-3 row">

                                    <label for="EMAIL" class="col-sm-2 col-form-label">{{ __('frontend.str.sender_email') }}</label>

                                    <div class="col-sm-10">

                                        <input type="text" name="EMAIL" value="{{ old('EMAIL', SettingsHelper::getInstance()->getValueForKey('EMAIL')) }}" placeholder="Email" class="form-control" id="EMAIL">

                                        @if ($errors->has('EMAIL'))
                                            <span class="text-danger">{{ $errors->first('EMAIL') }}</span>
                                        @endif

                                    </div>

                                </div>

                                <div class="mb-3 row">

                                    <label for="FROM" class="col-sm-2 col-form-label">{{ __('frontend.str.sender_name') }}</label>

                                    <div class="col-sm-10">

                                        <input type="text" name="FROM" value="{{ old('FROM', SettingsHelper::getInstance()->getValueForKey('FROM')) }}" placeholder="{{ __("frontend.str.sender_name") }}" class="form-control" id="FROM">

                                        @if ($errors->has('FROM'))
                                            <span class="text-danger">{{ $errors->first('FROM') }}</span>
                                        @endif

                                    </div>
                                </div>

                                <div class="mb-3 row">

                                    <label for="RETURN_PATH" class="col-sm-2 col-form-label">{{ __('frontend.form.return_path') }}</label>

                                    <div class="col-sm-10">

                                        <input type="text" name="RETURN_PATH" value="{{ old('RETURN_PATH', SettingsHelper::getInstance()->getValueForKey('RETURN_PATH')) }}" placeholder="{{ __("frontend.form.return_path") }}" class="form-control" id="RETURN_PATH">

                                        @if ($errors->has('RETURN_PATH'))
                                            <span class="text-danger">{{ $errors->first('RETURN_PATH') }}</span>
                                        @endif

                                    </div>

                                </div>

                                <div class="mb-3 row">

                                    <label for="LIST_OWNER" class="col-sm-2 col-form-label">{{ __('frontend.form.list_owner') }}</label>

                                    <div class="col-sm-10">

                                        <input type="text" name="LIST_OWNER" value="{{ old('LIST_OWNER', SettingsHelper::getInstance()->getValueForKey('LIST_OWNER')) }}" placeholder="{{ __("frontend.form.list_owner") }}" class="form-control" id="LIST_OWNER">

                                        @if ($errors->has('LIST_OWNER'))
                                            <span class="text-danger">{{ $errors->first('LIST_OWNER') }}</span>
                                        @endif

                                    </div>

                                </div>

                                <div class="mb-3 row">

                                    <label for="ORGANIZATION" class="col-sm-2 col-form-label">{{ __('frontend.form.organization') }}</label>

                                    <div class="col-sm-10">

                                        <input type="text" name="ORGANIZATION" value="{{ old('ORGANIZATION', SettingsHelper::getInstance()->getValueForKey('ORGANIZATION')) }}" placeholder="{{ __("frontend.form.organization") }}" class="form-control" id="ORGANIZATION">

                                        @if ($errors->has('ORGANIZATION'))
                                            <span class="text-danger">{{ $errors->first('ORGANIZATION') }}</span>
                                        @endif

                                    </div>

                                </div>

                                <div class="mb-3 row">

                                    <label for="SUBJECT_TEXT_CONFIRM" class="col-sm-2 col-form-label">{{ __('frontend.form.subject_text_confirm') }}</label>

                                    <div class="col-sm-10">

                                        <input type="text" name="SUBJECT_TEXT_CONFIRM" value="{{ old('SUBJECT_TEXT_CONFIRM', SettingsHelper::getInstance()->getValueForKey('SUBJECT_TEXT_CONFIRM')) }}" placeholder="{{ __("frontend.form.subject_text_confirm") }}" class="form-control" id="SUBJECT_TEXT_CONFIRM">

                                        @if ($errors->has('SUBJECT_TEXT_CONFIRM'))
                                            <span
                                                class="text-danger">{{ $errors->first('SUBJECT_TEXT_CONFIRM') }}</span>
                                        @endif

                                    </div>

                                </div>

                                <div class="mb-3 row">

                                    <label for="TEXT_CONFIRMATION" class="col-sm-2 col-form-label">{{ __('frontend.form.text_confirmation') }}</label>

                                    <div class="col-sm-10">

                                        <textarea name="TEXT_CONFIRMATION" rows="4" placeholder="{{ __("frontend.form.text_confirmation") }}" class="form-control" id="TEXT_CONFIRMATION" cols="50">{{ old('TEXT_CONFIRMATION', SettingsHelper::getInstance()->getValueForKey('TEXT_CONFIRMATION')) }}</textarea>

                                        @if ($errors->has('TEXT_CONFIRMATION'))
                                            <span
                                                class="text-danger">{{ $errors->first('TEXT_CONFIRMATION') }}</span>
                                        @endif

                                    </div>

                                </div>

                                <div class="mb-3 row">

                                    <div class="offset-sm-2 col-sm-10">
                                        <div class="form-check">

                                            <input type="checkbox" name="REQUIRE_SUB_CONFIRMATION" value="1" class="form-check-input" id="REQUIRE_SUB_CONFIRMATION" @checked((session()->hasOldInput() ? old('REQUIRE_SUB_CONFIRMATION') : SettingsHelper::getInstance()->getValueForKey('REQUIRE_SUB_CONFIRMATION')) == 1)>

                                            <label for="REQUIRE_SUB_CONFIRMATION" class="form-check-label">{{ __('frontend.form.require_subscription_confirmation') }}</label>

                                        </div>
                                    </div>

                                </div>

                                <div class="mb-3 row">

                                    <label for="UNSUBLINK" class="col-sm-2 col-form-label">{{ __('frontend.form.unsublink_text') }}</label>

                                    <div class="col-sm-10">

                                        <textarea name="UNSUBLINK" rows="4" placeholder="{{ __("frontend.form.unsublink_text") }}" class="form-control" id="UNSUBLINK" cols="50">{{ old('UNSUBLINK', SettingsHelper::getInstance()->getValueForKey('UNSUBLINK')) }}</textarea>

                                        @if ($errors->has('UNSUBLINK'))
                                            <span class="text-danger">{{ $errors->first('UNSUBLINK') }}</span>
                                        @endif

                                    </div>

                                </div>

                            </div>
                            <!-- /.tab-pane -->
                            <div class="tab-pane fade" id="s2" role="tabpanel" aria-labelledby="s2-tab" tabindex="0">

                                <div class="mb-3 row">

                                    <div class="offset-sm-2 col-sm-10">
                                        <div class="form-check">

                                            <input type="checkbox" name="SHOW_UNSUBSCRIBE_LINK" value="1" class="form-check-input" id="SHOW_UNSUBSCRIBE_LINK" @checked((session()->hasOldInput() ? old('SHOW_UNSUBSCRIBE_LINK') : SettingsHelper::getInstance()->getValueForKey('SHOW_UNSUBSCRIBE_LINK')) == 1)>

                                            <label for="SHOW_UNSUBSCRIBE_LINK" class="form-check-label">{{ __('frontend.form.show_unsubscribe_link') }}</label>

                                        </div>
                                    </div>

                                </div>

                                <div class="mb-3 row">

                                    <div class="offset-sm-2 col-sm-10">
                                        <div class="form-check">

                                            <input type="checkbox" name="REQUEST_REPLY" value="1" class="form-check-input" id="REQUEST_REPLY" @checked((session()->hasOldInput() ? old('REQUEST_REPLY') : SettingsHelper::getInstance()->getValueForKey('REQUEST_REPLY')) == 1)>

                                            <label for="REQUEST_REPLY" class="form-check-label">{{ __('frontend.form.request_reply') }}</label>

                                        </div>
                                    </div>

                                </div>

                                <div class="mb-3 row">

                                    <div class="offset-sm-2 col-sm-10">
                                        <div class="form-check">

                                            <input type="checkbox" name="NEW_SUBSCRIBER_NOTIFY" value="1" class="form-check-input" id="NEW_SUBSCRIBER_NOTIFY" @checked((session()->hasOldInput() ? old('NEW_SUBSCRIBER_NOTIFY') : SettingsHelper::getInstance()->getValueForKey('NEW_SUBSCRIBER_NOTIFY')) == 1)>

                                            <label for="NEW_SUBSCRIBER_NOTIFY" class="form-check-label">{{ __('frontend.form.new_subscriber_notify') }}</label>

                                        </div>
                                    </div>

                                </div>

                                <div class="mb-3 row">

                                    <label for="INTERVAL_NUMBER" class="col-sm-2 col-form-label">{{ __('frontend.form.interval_number') }}</label>

                                    <div class="col-md-7">

                                        <input type="text" name="INTERVAL_NUMBER" value="{{ old('INTERVAL_NUMBER', SettingsHelper::getInstance()->getValueForKey('INTERVAL_NUMBER')) }}" class="form-control" id="INTERVAL_NUMBER">

                                        @if ($errors->has('INTERVAL_NUMBER'))
                                            <span class="text-danger">{{ $errors->first('INTERVAL_NUMBER') }}</span>
                                        @endif

                                    </div>

                                    <div class="col-md-3">

                                        <select name="INTERVAL_TYPE" class="form-select" id="INTERVAL_TYPE">
                                            @foreach ([ 'no' => __('frontend.str.no'), 'minute' => __('frontend.form.minute'), 'hour' => __('frontend.form.hour'), 'day' => __('frontend.form.day'), ] as $value => $label)
                                                <option value="{{ $value }}" @selected((string) old('INTERVAL_TYPE', SettingsHelper::getInstance()->getValueForKey('INTERVAL_TYPE') ?: 'no') === (string) $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>

                                        @if ($errors->has('INTERVAL_TYPE'))
                                            <span class="text-danger">{{ $errors->first('INTERVAL_TYPE') }}</span>
                                        @endif

                                    </div>

                                </div>

                                <div class="mb-3 row">

                                    <label for="LIMIT_NUMBER" id="limit-number-label" class="col-sm-2 col-form-label">{{ __('frontend.form.limit_number') }}</label>

                                    <div class="col-sm-10">

                                        <div class="input-group">
                                            <span class="input-group-text">
                                                    <input type="checkbox" name="LIMIT_SEND" value="1" id="LIMIT_SEND" class="form-check-input mt-0" aria-labelledby="limit-number-label" @checked((session()->hasOldInput() ? old('LIMIT_SEND') : SettingsHelper::getInstance()->getValueForKey('LIMIT_SEND')) == 1)>
                                                </span>

                                            <input type="text" name="LIMIT_NUMBER" value="{{ old('LIMIT_NUMBER', SettingsHelper::getInstance()->getValueForKey('LIMIT_NUMBER')) }}" class="form-control" id="LIMIT_NUMBER">

                                            @if ($errors->has('LIMIT_NUMBER'))
                                                <span
                                                    class="text-danger">{{ $errors->first('LIMIT_NUMBER') }}</span>
                                            @endif

                                        </div>

                                    </div>

                                </div>

                                <div class="mb-3 row">

                                    <label for="SLEEP" class="col-sm-2 col-form-label">{{ __('frontend.form.sleep') }}</label>

                                    <div class="col-sm-10">

                                            <input type="text" name="SLEEP" value="{{ old('SLEEP', SettingsHelper::getInstance()->getValueForKey('SLEEP') ?: 0) }}" placeholder="{{ __("frontend.form.sleep") }}" class="form-control" id="SLEEP">

                                        @if ($errors->has('SLEEP'))
                                            <span class="text-danger">{{ $errors->first('SLEEP') }}</span>
                                        @endif

                                    </div>

                                </div>

                                <div class="mb-3 row">

                                    <label for="DAYS_FOR_REMOVE_SUBSCRIBER" id="remove-subscriber-label" class="col-sm-2 col-form-label">{{ __('frontend.form.days_for_remove_subscriber') }}</label>

                                    <div class="col-sm-10">

                                        <div class="input-group">
                                            <span class="input-group-text">
                                                    <input type="checkbox" name="REMOVE_SUBSCRIBER" value="1" id="REMOVE_SUBSCRIBER" class="form-check-input mt-0" aria-labelledby="remove-subscriber-label" @checked((session()->hasOldInput() ? old('REMOVE_SUBSCRIBER') : SettingsHelper::getInstance()->getValueForKey('REMOVE_SUBSCRIBER')) == 1)>
                                                </span>

                                            <input type="text" name="DAYS_FOR_REMOVE_SUBSCRIBER" value="{{ old('DAYS_FOR_REMOVE_SUBSCRIBER', SettingsHelper::getInstance()->getValueForKey('DAYS_FOR_REMOVE_SUBSCRIBER')) }}" class="form-control" id="DAYS_FOR_REMOVE_SUBSCRIBER">

                                            @if ($errors->has('DAYS_FOR_REMOVE_SUBSCRIBER'))
                                                <span
                                                    class="text-danger">{{ $errors->first('DAYS_FOR_REMOVE_SUBSCRIBER') }}</span>
                                            @endif

                                        </div>

                                    </div>

                                </div>

                                <div class="mb-3 row">

                                    <label for="PRECEDENCE" class="col-sm-2 col-form-label">{{ __('frontend.form.precedence') }}</label>

                                    <div class="col-sm-10">

                                        <select name="PRECEDENCE" class="form-select" id="PRECEDENCE">
                                            @foreach ([ 'no' => __('frontend.str.no'), 'bulk' => 'bulk', 'junk' => 'junk', 'list' => 'list', ] as $value => $label)
                                                <option value="{{ $value }}" @selected((string) old('PRECEDENCE', SettingsHelper::getInstance()->getValueForKey('PRECEDENCE') ?: 'no') === (string) $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>

                                        @if ($errors->has('PRECEDENCE'))
                                            <span class="text-danger">{{ $errors->first('PRECEDENCE') }}</span>
                                        @endif

                                    </div>

                                </div>

                                <div class="mb-3 row">

                                    <label for="CONTENT_TYPE_html" class="col-sm-2 col-form-label">{{ __('frontend.form.content_type') }}</label>

                                    <div class="col-sm-10">

                                        <!-- radio -->
                                        <div class="mb-3">
                                            <div class="form-check">

                                                <input type="radio" name="CONTENT_TYPE" value="html" class="form-check-input" id="CONTENT_TYPE_html" @checked(old('CONTENT_TYPE', SettingsHelper::getInstance()->getValueForKey('CONTENT_TYPE') ?: 'html') === 'html')>

                                                <label class="form-check-label" for="CONTENT_TYPE_html">HTML</label>
                                            </div>
                                            <div class="form-check">

                                                <input type="radio" name="CONTENT_TYPE" value="plain" class="form-check-input" id="CONTENT_TYPE_plain" @checked(old('CONTENT_TYPE', SettingsHelper::getInstance()->getValueForKey('CONTENT_TYPE') ?: 'html') === 'plain')>

                                                <label class="form-check-label" for="CONTENT_TYPE_plain">Plain</label>
                                            </div>
                                        </div>

                                    </div>

                                </div>

                                <div class="mb-3 row">

                                    <label for="HOW_TO_SEND_php" class="col-sm-2 col-form-label">{{ __('frontend.form.how_to_send') }}</label>

                                    <div class="col-sm-10">

                                        <!-- radio -->
                                        <div class="mb-3">
                                            <div class="form-check">

                                                <input type="radio" name="HOW_TO_SEND" value="php" class="form-check-input" id="HOW_TO_SEND_php" @checked(old('HOW_TO_SEND', SettingsHelper::getInstance()->getValueForKey('HOW_TO_SEND') ?: 'php') === 'php')>

                                                <label class="form-check-label" for="HOW_TO_SEND_php">PHP Mail</label>
                                            </div>
                                            <div class="form-check">

                                                <input type="radio" name="HOW_TO_SEND" value="smtp" class="form-check-input" id="HOW_TO_SEND_smtp" @checked(old('HOW_TO_SEND', SettingsHelper::getInstance()->getValueForKey('HOW_TO_SEND') ?: 'php') === 'smtp')>

                                                <label class="form-check-label" for="HOW_TO_SEND_smtp">SMTP</label>
                                            </div>
                                            <div class="form-check">

                                                <input type="radio" name="HOW_TO_SEND" value="sendmail" class="form-check-input" id="HOW_TO_SEND_sendmail" @checked(old('HOW_TO_SEND', SettingsHelper::getInstance()->getValueForKey('HOW_TO_SEND') ?: 'php') === 'sendmail')>

                                                <label class="form-check-label" for="HOW_TO_SEND_sendmail">Sendmail</label>
                                            </div>
                                        </div>

                                    </div>
                                </div>

                                <div class="mb-3 row">

                                    <label for="SENDMAIL_PATH" class="col-sm-2 col-form-label">{{ __('frontend.form.sendmail_path') }}</label>

                                    <div class="col-sm-10">

                                        <input type="text" name="SENDMAIL_PATH" value="{{ old('SENDMAIL_PATH', SettingsHelper::getInstance()->getValueForKey('SENDMAIL_PATH')) }}" placeholder="{{ __('frontend.form.sendmail_path') }}" class="form-control" id="SENDMAIL_PATH">

                                        @if ($errors->has('SENDMAIL_PATH'))
                                            <span class="text-danger">{{ $errors->first('SENDMAIL_PATH') }}</span>
                                        @endif

                                    </div>

                                </div>

                                <div class="mb-3 row">

                                    <label for="URL" class="col-sm-2 col-form-label">URL</label>

                                    <div class="col-sm-10">

                                        <input type="text" name="URL" value="{{ old('URL', SettingsHelper::getInstance()->getValueForKey('URL')) }}" placeholder="URL" class="form-control" id="URL">

                                        @if ($errors->has('URL'))
                                            <span class="text-danger">{{ $errors->first('URL') }}</span>
                                        @endif

                                    </div>

                                </div>
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

                                        <div class="header-row">
                                            <div class="mb-3 row">

                                                <label for="header_name_{{ $loop->index }}" class="col-sm-2 col-form-label">{{ __('frontend.form.name') }}</label>

                                                <div class="col-md-3">

                                                    <input type="text" name="header_name[]" value="{{ $headerName }}" class="form-control" id="header_name_{{ $loop->index }}">

                                                </div>

                                                <label for="header_value_{{ $loop->index }}" class="col-sm-2 col-form-label">{{ __('frontend.form.value') }}</label>

                                                <div class="col-md-3">

                                                    <input type="text" name="header_value[]" value="{{ $headerValues[$headerIndex] ?? '' }}" class="form-control" id="header_value_{{ $loop->index }}">

                                                </div>

                                                <div class="col-md-2">
                                                    <button type="button" class="btn btn-outline-danger removeBlock" title="{{ __('frontend.form.remove') }}"> - </button>
                                                </div>
                                            </div>
                                        </div>

                                    @endforeach

                                    <div class="mb-3">
                                        <div class="col-lg-12">
                                            <input class="btn btn-secondary" id="add_field" type="button" value="+ {{ __('frontend.form.add') }}">
                                        </div>
                                    </div>

                                </div>
                            </div>
                            <!-- /.tab-pane -->
                        </div>
                        <!-- /.tab-content -->
                    </div><!-- /.card-body -->

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            {{ __('frontend.str.apply') }}
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
            $(document).on("click", '#add_field', function () {
                let html = '<div class="header-row"><div class="mb-3 row">';

                html += '<label class="col-sm-2 col-form-label">{{ __('frontend.str.name') }}</label>';
                html += '<div class="col-md-3"><input class="form-control" type="text" value="" name="header_name[]"></div>';
                html += '<label class="col-sm-2 col-form-label">{{ __('frontend.str.value') }}</label>';
                html += '<div class="col-md-3"><input class="form-control" type="text" value="" name="header_value[]"></div>';
                html += '<div class="col-md-2"><button type="button" class="btn btn-outline-danger removeBlock" title="{{ __('frontend.form.remove') }}"> - </button></div>';
                html += '</div></div>';

                $('#headerslist').prepend(html);
            });

            $(document).on("click", '.removeBlock', function () {
                let parent = $(this).parents('div[class^="header-row"]').first();
                parent.remove();
            });
        });
    </script>

@endsection

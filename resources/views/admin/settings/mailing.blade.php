@php
    $sendMethod = old('HOW_TO_SEND', SettingsHelper::getInstance()->getValueForKey('HOW_TO_SEND') ?: 'php');
    $intervalType = old('INTERVAL_TYPE', SettingsHelper::getInstance()->getValueForKey('INTERVAL_TYPE') ?: 'no');
    $limitSending = (session()->hasOldInput() ? old('LIMIT_SEND') : SettingsHelper::getInstance()->getValueForKey('LIMIT_SEND')) == 1;
    $removeSubscribers = (session()->hasOldInput() ? old('REMOVE_SUBSCRIBER') : SettingsHelper::getInstance()->getValueForKey('REMOVE_SUBSCRIBER')) == 1;
    $showSendmailPath = $sendMethod === 'sendmail' || $errors->has('SENDMAIL_PATH');
    $showInterval = $intervalType !== 'no' || $errors->has('INTERVAL_NUMBER');
    $showLimit = $limitSending || $errors->has('LIMIT_NUMBER');
    $showCleanup = $removeSubscribers || $errors->has('DAYS_FOR_REMOVE_SUBSCRIBER');
@endphp

<div class="settings-mailing">
    <section class="settings-section" aria-labelledby="settings-delivery-title">
        <h4 class="settings-section-title mb-3" id="settings-delivery-title">
            <i class="fa-solid fa-envelope text-primary me-2" aria-hidden="true"></i>{{ __('frontend.str.settings_form.mailing.delivery') }}
        </h4>
        <div class="row g-3">
            <div class="col-md-6">
                <label for="HOW_TO_SEND" class="form-label">{{ __('frontend.str.settings_form.mailing.method') }}</label>
                <select name="HOW_TO_SEND" class="form-select" id="HOW_TO_SEND" aria-controls="settings-sendmail-field">
                    @foreach (['php' => 'PHP Mail', 'smtp' => 'SMTP', 'sendmail' => 'Sendmail'] as $value => $label)
                        <option value="{{ $value }}" @selected($sendMethod === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('HOW_TO_SEND')
                    <span class="text-danger">{{ $message }}</span>
                @enderror
            </div>
            <div class="col-md-6">
                <label for="CONTENT_TYPE" class="form-label">{{ __('frontend.str.settings_form.mailing.format') }}</label>
                <select name="CONTENT_TYPE" class="form-select" id="CONTENT_TYPE">
                    @foreach (['html' => 'HTML', 'plain' => __('frontend.str.settings_form.mailing.plain')] as $value => $label)
                        <option value="{{ $value }}" @selected(old('CONTENT_TYPE', SettingsHelper::getInstance()->getValueForKey('CONTENT_TYPE') ?: 'html') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('CONTENT_TYPE')
                    <span class="text-danger">{{ $message }}</span>
                @enderror
            </div>
            <div class="col-12" id="settings-sendmail-field" @if(!$showSendmailPath) hidden @endif>
                <label for="SENDMAIL_PATH" class="form-label">{{ __('frontend.form.sendmail_path') }}</label>
                <input type="text" name="SENDMAIL_PATH" value="{{ old('SENDMAIL_PATH', SettingsHelper::getInstance()->getValueForKey('SENDMAIL_PATH')) }}" placeholder="{{ __('frontend.form.sendmail_path') }}" class="form-control" id="SENDMAIL_PATH">
                @error('SENDMAIL_PATH')
                    <span class="text-danger">{{ $message }}</span>
                @enderror
            </div>
        </div>
        <details class="settings-additional-options mt-4" @if($errors->hasAny(['PRECEDENCE', 'URL'])) open @endif>
            <summary class="text-primary">{{ __('frontend.str.settings_form.mailing.additional') }}</summary>
            <div class="row g-3 mt-1">
                <div class="col-md-6">
                    <label for="PRECEDENCE" class="form-label">{{ __('frontend.str.settings_form.mailing.precedence') }}</label>
                    <select name="PRECEDENCE" class="form-select" id="PRECEDENCE">
                        @foreach (['no' => __('frontend.str.settings_form.mailing.no_precedence'), 'bulk' => 'bulk', 'junk' => 'junk', 'list' => 'list'] as $value => $label)
                            <option value="{{ $value }}" @selected((string) old('PRECEDENCE', SettingsHelper::getInstance()->getValueForKey('PRECEDENCE') ?: 'no') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('PRECEDENCE')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label for="URL" class="form-label">{{ __('frontend.str.settings_form.mailing.site_url') }}</label>
                    <input type="text" name="URL" value="{{ old('URL', SettingsHelper::getInstance()->getValueForKey('URL')) }}" placeholder="URL" class="form-control" id="URL" aria-describedby="settings-url-help">
                    <div class="form-text" id="settings-url-help">{{ __('frontend.str.settings_form.mailing.site_url_help') }}</div>
                    @error('URL')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </details>
    </section>

    <section class="settings-section" aria-labelledby="settings-speed-title">
        <h4 class="settings-section-title mb-3" id="settings-speed-title">
            <i class="fa-solid fa-gauge-high text-primary me-2" aria-hidden="true"></i>{{ __('frontend.str.settings_form.mailing.speed') }}
        </h4>
        <div class="row g-3">
            <div class="col-md-6">
                <label for="INTERVAL_NUMBER" class="form-label" id="interval-number-label">{{ __('frontend.str.settings_form.mailing.interval') }}</label>
                <div class="row g-2">
                    <div class="col-6" id="settings-interval-value" @if(!$showInterval) hidden @endif>
                        <input type="text" inputmode="numeric" name="INTERVAL_NUMBER" value="{{ old('INTERVAL_NUMBER', SettingsHelper::getInstance()->getValueForKey('INTERVAL_NUMBER')) }}" class="form-control" id="INTERVAL_NUMBER" aria-describedby="settings-interval-help">
                        @error('INTERVAL_NUMBER')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col">
                        <select name="INTERVAL_TYPE" class="form-select" id="INTERVAL_TYPE" aria-label="{{ __('frontend.str.settings_form.mailing.interval_unit') }}" aria-describedby="settings-interval-help" aria-controls="settings-interval-value">
                            @foreach (['no' => __('frontend.str.settings_form.mailing.no_interval'), 'minute' => __('frontend.str.settings_form.mailing.minutes'), 'hour' => __('frontend.str.settings_form.mailing.hours'), 'day' => __('frontend.str.settings_form.mailing.days')] as $value => $label)
                                <option value="{{ $value }}" @selected($intervalType === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('INTERVAL_TYPE')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="form-text" id="settings-interval-help">{{ __('frontend.str.settings_form.mailing.interval_help') }}</div>
            </div>
            <div class="col-md-6">
                <label for="SLEEP" class="form-label">{{ __('frontend.str.settings_form.mailing.pause') }}</label>
                <div class="settings-field-with-unit">
                    <input type="text" inputmode="numeric" name="SLEEP" value="{{ old('SLEEP', SettingsHelper::getInstance()->getValueForKey('SLEEP') ?: 0) }}" class="form-control" id="SLEEP" aria-describedby="settings-sleep-unit settings-sleep-help">
                    <span class="text-body-secondary" id="settings-sleep-unit">{{ __('frontend.str.settings_form.mailing.seconds') }}</span>
                </div>
                <div class="form-text" id="settings-sleep-help">{{ __('frontend.str.settings_form.mailing.pause_help') }}</div>
                @error('SLEEP')
                    <span class="text-danger">{{ $message }}</span>
                @enderror
            </div>
        </div>
        <div class="rounded bg-body-tertiary p-3 mt-4">
            <div class="form-check form-switch mb-0">
                <input type="checkbox" name="LIMIT_SEND" value="1" class="form-check-input" id="LIMIT_SEND" role="switch" aria-controls="settings-limit-fields" aria-expanded="{{ $showLimit ? 'true' : 'false' }}" aria-describedby="settings-limit-help" @checked($limitSending)>
                <label for="LIMIT_SEND" class="form-check-label">{{ __('frontend.str.settings_form.mailing.limit') }}</label>
                <div class="form-text mt-1" id="settings-limit-help">{{ __('frontend.str.settings_form.mailing.limit_help') }}</div>
            </div>
        </div>
        <div class="mt-3" id="settings-limit-fields" @if(!$showLimit) hidden @endif>
            <label for="LIMIT_NUMBER" class="form-label">{{ __('frontend.str.settings_form.mailing.limit_number') }}</label>
            <div class="settings-field-with-unit">
                <input type="text" inputmode="numeric" name="LIMIT_NUMBER" value="{{ old('LIMIT_NUMBER', SettingsHelper::getInstance()->getValueForKey('LIMIT_NUMBER')) }}" class="form-control" id="LIMIT_NUMBER" aria-describedby="settings-limit-unit">
                <span class="text-body-secondary" id="settings-limit-unit">{{ __('frontend.str.settings_form.mailing.emails') }}</span>
            </div>
            @error('LIMIT_NUMBER')
                <span class="text-danger">{{ $message }}</span>
            @enderror
        </div>
    </section>

    <section class="settings-section" aria-labelledby="settings-notifications-title">
        <h4 class="settings-section-title mb-3" id="settings-notifications-title">
            <i class="fa-solid fa-bell text-primary me-2" aria-hidden="true"></i>{{ __('frontend.str.settings_form.mailing.notifications') }}
        </h4>
        <div class="d-grid gap-2">
            @foreach (['SHOW_UNSUBSCRIBE_LINK' => 'unsubscribe', 'REQUEST_REPLY' => 'read_receipt', 'NEW_SUBSCRIBER_NOTIFY' => 'new_subscriber'] as $key => $label)
                <div class="rounded bg-body-tertiary p-3">
                    <div class="form-check form-switch mb-0">
                        <input type="checkbox" name="{{ $key }}" value="1" class="form-check-input" id="{{ $key }}" role="switch" @checked((session()->hasOldInput() ? old($key) : SettingsHelper::getInstance()->getValueForKey($key)) == 1)>
                        <label for="{{ $key }}" class="form-check-label">{{ __('frontend.str.settings_form.mailing.'.$label) }}</label>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <section class="settings-section" aria-labelledby="settings-cleanup-title">
        <h4 class="settings-section-title mb-3" id="settings-cleanup-title">
            <i class="fa-solid fa-database text-primary me-2" aria-hidden="true"></i>{{ __('frontend.str.settings_form.mailing.cleanup') }}
        </h4>
        <div class="rounded bg-body-tertiary p-3">
            <div class="form-check form-switch mb-0">
                <input type="checkbox" name="REMOVE_SUBSCRIBER" value="1" class="form-check-input" id="REMOVE_SUBSCRIBER" role="switch" aria-controls="settings-cleanup-fields" aria-expanded="{{ $showCleanup ? 'true' : 'false' }}" @checked($removeSubscribers)>
                <label for="REMOVE_SUBSCRIBER" class="form-check-label">{{ __('frontend.str.settings_form.mailing.cleanup_enabled') }}</label>
            </div>
        </div>
        <div class="mt-3" id="settings-cleanup-fields" @if(!$showCleanup) hidden @endif>
            <label for="DAYS_FOR_REMOVE_SUBSCRIBER" class="form-label">{{ __('frontend.str.settings_form.mailing.cleanup_after') }}</label>
            <div class="settings-field-with-unit">
                <input type="text" inputmode="numeric" name="DAYS_FOR_REMOVE_SUBSCRIBER" value="{{ old('DAYS_FOR_REMOVE_SUBSCRIBER', SettingsHelper::getInstance()->getValueForKey('DAYS_FOR_REMOVE_SUBSCRIBER')) }}" class="form-control" id="DAYS_FOR_REMOVE_SUBSCRIBER" aria-describedby="settings-cleanup-unit">
                <span class="text-body-secondary" id="settings-cleanup-unit">{{ __('frontend.str.settings_form.mailing.days_after_registration') }}</span>
            </div>
            @error('DAYS_FOR_REMOVE_SUBSCRIBER')
                <span class="text-danger">{{ $message }}</span>
            @enderror
        </div>
    </section>
</div>

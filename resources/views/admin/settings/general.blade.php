@php
    $confirmationRequired = (session()->hasOldInput()
        ? old('REQUIRE_SUB_CONFIRMATION')
        : SettingsHelper::getInstance()->getValueForKey('REQUIRE_SUB_CONFIRMATION')) == 1;
    $confirmationHasErrors = $errors->hasAny(['SUBJECT_TEXT_CONFIRM', 'TEXT_CONFIRMATION']);
@endphp

<div class="settings-general">
    <section class="settings-section" aria-labelledby="settings-sender-title">
        <h4 class="settings-section-title" id="settings-sender-title">
            <i class="fa-solid fa-envelope text-primary me-2" aria-hidden="true"></i>{{ __('frontend.str.settings_form.sender') }}
        </h4>
        <p class="small text-body-secondary mb-4">{{ __('frontend.str.settings_form.sender_help') }}</p>
        <div class="row g-3">
            <div class="col-md-6">
                <label for="FROM" class="form-label">{{ __('frontend.str.sender_name') }}</label>
                <input type="text" name="FROM" value="{{ old('FROM', SettingsHelper::getInstance()->getValueForKey('FROM')) }}" placeholder="{{ __('frontend.str.sender_name') }}" class="form-control" id="FROM">
                @error('FROM')
                    <span class="text-danger">{{ $message }}</span>
                @enderror
            </div>
            <div class="col-md-6">
                <label for="EMAIL" class="form-label">{{ __('frontend.str.sender_email') }}</label>
                <input type="text" name="EMAIL" value="{{ old('EMAIL', SettingsHelper::getInstance()->getValueForKey('EMAIL')) }}" placeholder="Email" class="form-control" id="EMAIL">
                @error('EMAIL')
                    <span class="text-danger">{{ $message }}</span>
                @enderror
            </div>
            <div class="col-12">
                <label for="ORGANIZATION" class="form-label">{{ __('frontend.form.organization') }}</label>
                <input type="text" name="ORGANIZATION" value="{{ old('ORGANIZATION', SettingsHelper::getInstance()->getValueForKey('ORGANIZATION')) }}" placeholder="{{ __('frontend.form.organization') }}" class="form-control" id="ORGANIZATION">
                @error('ORGANIZATION')
                    <span class="text-danger">{{ $message }}</span>
                @enderror
            </div>
        </div>
        <details class="settings-additional-addresses mt-4" @if($errors->hasAny(['RETURN_PATH', 'LIST_OWNER'])) open @endif>
            <summary class="text-primary">{{ __('frontend.str.settings_form.additional_addresses') }}</summary>
            <div class="row g-3 mt-1">
                <div class="col-md-6">
                    <label for="RETURN_PATH" class="form-label">{{ __('frontend.str.settings_form.return_path') }}</label>
                    <input type="text" name="RETURN_PATH" value="{{ old('RETURN_PATH', SettingsHelper::getInstance()->getValueForKey('RETURN_PATH')) }}" placeholder="{{ __('frontend.str.settings_form.return_path') }}" class="form-control" id="RETURN_PATH" aria-describedby="return-path-help">
                    <div class="form-text" id="return-path-help">{{ __('frontend.str.settings_form.return_path_help') }}</div>
                    @error('RETURN_PATH')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label for="LIST_OWNER" class="form-label">{{ __('frontend.str.settings_form.list_owner') }}</label>
                    <input type="text" name="LIST_OWNER" value="{{ old('LIST_OWNER', SettingsHelper::getInstance()->getValueForKey('LIST_OWNER')) }}" placeholder="{{ __('frontend.str.settings_form.list_owner') }}" class="form-control" id="LIST_OWNER" aria-describedby="list-owner-help">
                    <div class="form-text" id="list-owner-help">{{ __('frontend.str.settings_form.list_owner_help') }}</div>
                    @error('LIST_OWNER')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </details>
    </section>

    <section class="settings-section" aria-labelledby="settings-confirmation-title">
        <h4 class="settings-section-title" id="settings-confirmation-title">
            <i class="fa-solid fa-envelope-circle-check text-primary me-2" aria-hidden="true"></i>{{ __('frontend.str.settings_form.confirmation') }}
        </h4>
        <div class="rounded bg-body-tertiary p-3 my-3">
            <div class="form-check form-switch mb-0">
                <input type="checkbox" name="REQUIRE_SUB_CONFIRMATION" value="1" class="form-check-input" id="REQUIRE_SUB_CONFIRMATION" role="switch" aria-controls="settings-confirmation-fields" aria-expanded="{{ $confirmationRequired || $confirmationHasErrors ? 'true' : 'false' }}" aria-describedby="confirmation-help" @checked($confirmationRequired)>
                <label for="REQUIRE_SUB_CONFIRMATION" class="form-check-label">{{ __('frontend.form.require_subscription_confirmation') }}</label>
                <div class="form-text mt-1" id="confirmation-help">{{ __('frontend.str.settings_form.confirmation_help') }}</div>
            </div>
        </div>
        <div class="row g-3" id="settings-confirmation-fields" @if(!$confirmationRequired && !$confirmationHasErrors) hidden @endif>
            <div class="col-12">
                <label for="SUBJECT_TEXT_CONFIRM" class="form-label">{{ __('frontend.str.settings_form.subject') }}</label>
                <input type="text" name="SUBJECT_TEXT_CONFIRM" value="{{ old('SUBJECT_TEXT_CONFIRM', SettingsHelper::getInstance()->getValueForKey('SUBJECT_TEXT_CONFIRM')) }}" placeholder="{{ __('frontend.str.settings_form.subject') }}" class="form-control" id="SUBJECT_TEXT_CONFIRM">
                @error('SUBJECT_TEXT_CONFIRM')
                    <span class="text-danger">{{ $message }}</span>
                @enderror
            </div>
            <div class="col-12">
                <label for="TEXT_CONFIRMATION" class="form-label">{{ __('frontend.str.settings_form.message') }}</label>
                <textarea name="TEXT_CONFIRMATION" rows="6" placeholder="{{ __('frontend.str.settings_form.message') }}" class="form-control" id="TEXT_CONFIRMATION" aria-describedby="confirmation-variables">{{ old('TEXT_CONFIRMATION', SettingsHelper::getInstance()->getValueForKey('TEXT_CONFIRMATION')) }}</textarea>
                <div class="form-text" id="confirmation-variables">{{ __('frontend.str.settings_form.confirmation_variables') }}</div>
                @error('TEXT_CONFIRMATION')
                    <span class="text-danger">{{ $message }}</span>
                @enderror
            </div>
        </div>
    </section>

    <section class="settings-section" aria-labelledby="settings-unsubscribe-title">
        <h4 class="settings-section-title" id="settings-unsubscribe-title">
            <i class="fa-solid fa-right-from-bracket text-primary me-2" aria-hidden="true"></i>{{ __('frontend.str.settings_form.unsubscribe') }}
        </h4>
        <p class="small text-body-secondary mb-4">{{ __('frontend.str.settings_form.unsubscribe_help') }}</p>
        <label for="UNSUBLINK" class="form-label">{{ __('frontend.str.settings_form.unsubscribe_text') }}</label>
        <textarea name="UNSUBLINK" rows="3" placeholder="{{ __('frontend.str.settings_form.unsubscribe_text') }}" class="form-control" id="UNSUBLINK" aria-describedby="unsubscribe-variables">{{ old('UNSUBLINK', SettingsHelper::getInstance()->getValueForKey('UNSUBLINK')) }}</textarea>
        <div class="form-text" id="unsubscribe-variables">{{ __('frontend.str.settings_form.unsubscribe_variables') }}</div>
        @error('UNSUBLINK')
            <span class="text-danger">{{ $message }}</span>
        @enderror
    </section>
</div>

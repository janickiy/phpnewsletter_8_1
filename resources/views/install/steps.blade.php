<div class="steps">
    <ul>
        <li>
            <a class="{{ $steps['welcome'] ?? '' }}">
                <div class="stepNumber"><i class="fa fa-home"></i></div>
                <span class="stepDesc text-small">{{ __('install.str.welcome') }}</span>
            </a>
        </li>
        <li>
            <a class="{{ $steps['requirements'] ?? '' }}">
                <div class="stepNumber"><i class="fa fa-list"></i></div>
                <span class="stepDesc text-small">{{ __('install.str.system_requirements') }}</span>
            </a>
        </li>
        <li>
            <a class="{{ $steps['permissions'] ?? '' }}">
                <div class="stepNumber"><i class="fa fa-lock"></i></div>
                <span class="stepDesc text-small">{{ __('install.str.permissions') }}</span>
            </a>
        </li>
        <li>
            <a class="{{ $steps['database'] ?? '' }}">
                <div class="stepNumber"><i class="fa fa-database"></i></div>
                <span class="stepDesc text-small">{{ __('install.str.database_info') }}</span>
            </a>
        </li>
        <li>
            <a class="{{ $steps['installation'] ?? '' }}">
                <div class="stepNumber"><i class="fa fa-terminal"></i></div>
                <span class="stepDesc text-small">{{ __('install.str.installation') }}</span>
            </a>
        </li>
        <li>
            <a class="{{$steps['complete'] ?? '' }}">
                <div class="stepNumber"><i class="fa fa-flag-checkered"></i></div>
                <span class="stepDesc text-small">{{ __('install.str.complete') }}</span>
            </a>
        </li>
    </ul>
</div>

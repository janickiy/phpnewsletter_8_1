<div class="alert alert-info alert-dismissible fade show" role="alert" id="alert_msg_block" style="display:none;">
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('frontend.str.close') }}" onClick="$.cookie('alertshow', 'no');"></button>
    <h5><i class="icon fa-solid fa-info"></i> {{ __('frontend.str.warning_alert') }}</h5>
    <span id="alert_warning_msg"></span>
</div>

@if (isset($infoAlert) && $infoAlert)
    <div class="callout callout-info">
        <p>{!! $infoAlert !!}</p>
    </div>
@endif

@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('frontend.str.close') }}"></button>
        <h5><i class="icon fa-solid fa-check"></i> {{ session('success') }}</h5>
    </div>
@endif

@if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('frontend.str.close') }}"></button>
        <h5><i class="icon fa-solid fa-ban"></i> {{ __('frontend.str.error_alert') }}</h5>
        {{ session('error') }}
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('frontend.str.close') }}"></button>
        <h5><i class="icon fa-solid fa-ban"></i> {{ __('frontend.str.error_alert') }}</h5>
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif



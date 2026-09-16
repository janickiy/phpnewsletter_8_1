@extends('layouts.install')

@section('content')

    @include('install.steps', ['steps' => ['welcome' => 'selected']])

    <div class="step-content">
        <h3>{{ __('install.str.license_agreement') }}</h3>
        <hr>
        <fieldset>
            <div class="form-group">

                <textarea name="readonly" class="form-control" rows="13" cols="50">{{ __('license.agreement') }}</textarea>

            </div>

            <div class="form-group row">
                <div class="col-md-6 offset-md-4">
                    <div class="form-check">

                    </div>
                </div>
            </div>

            <div class="form-group">

                <div class="chiller_cb">

                    <input type="checkbox" name="accept_license" value="1" id="accept_license" @checked(old('accept_license'))>

                    <label for="accept_license" class="form-check-label">{{ __('frontend.str.accept_license') }}</label>

                    <span></span>
                </div>

            </div>
        </fieldset>

        <a href="{{ route('install.requirements') }}" id="next_button" class="btn btn-primary float-right disabled" role="button">
            {{ __('install.button.next') }}
            <i class="fa fa-arrow-right"></i>
        </a>

        <div class="clearfix"></div>

    </div>

@endsection

@section('js')

    <script>

        $( "#accept_license" ).click(function() {
            let checked = $('#accept_license').is(":checked");

            if (checked) {
                $("#next_button").removeClass("disabled");
            } else {
                $("#next_button").addClass("disabled");
            }
        });

    </script>

@endsection

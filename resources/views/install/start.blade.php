@extends('layouts.install')

@section('content')

    @include('install.steps', ['steps' => ['welcome' => 'selected']])

    <div class="step-content">
        <h3>{{ __('install.str.license_agreement') }}</h3>
        <hr>
        <fieldset>
            <div class="form-group">

                {!! Form::textarea('readonly', __('license.agreement'), ['class' => "form-control", 'rows' => "13"]) !!}

            </div>

            <div class="form-group row">
                <div class="col-md-6 offset-md-4">
                    <div class="form-check">

                    </div>
                </div>
            </div>

            <div class="form-group">

                <div class="chiller_cb">

                    {!! Form::checkbox('accept_license', 1, false, ['id' => "accept_license"] ) !!}

                    {!! Form::label('accept_license', __('frontend.str.accept_license'), ['class' => 'form-check-label']) !!}

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

@extends('layouts.frontend')

@section('title', $title)

@section('css')

    <style>
        .vertical-center {
            min-height: 100%;
            min-height: 100vh;
            display: flex;
            align-items: center;
        }

    </style>

@endsection

@section('content')

    <div class="vertical-center">
        <div class="container">

            <div id="resultSub"></div>

            <form method="POST" action="{{ url()->current() }}" accept-charset="UTF-8" id="addsub" autocomplete="off">
                @csrf

            @php
                $selectedCategoryIds = collect(old('categoryId', []))->map(fn ($value) => (string) $value)->all();
            @endphp

            @foreach($category as $row)
                <div class="form-check">
                    <label class="form-check-label">
                        <input type="checkbox" name="categoryId[]" value="{{ $row['id'] }}" class="form-check-input" @checked(in_array((string) $row['id'], $selectedCategoryIds, true))> {{ $row['name'] }}
                    </label>
                </div>
            @endforeach

            <div class="form-group">
                <label for="name">{{ __('frontend.str.name') }}</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" class="form-control" autocomplete="off">
            </div>

            <div class="form-group">
                <label for="email">E-mail</label>
                <input type="text" name="email" id="email" value="{{ old('email') }}" class="form-control" autocomplete="off">

                <div id="error-email" class="text-danger"></div>
            </div>

            <button type="button" id="sub" class="btn btn-primary">{{ __('frontend.str.subscribe') }}</button>

            </form>

        </div>

    </div>

@endsection

@section('js')

    <script>

        $(document).on("click", "#sub", function () {
            let arr = $("#addsub").serializeArray();
            let aParams = [];
            let sParam;

            for (let i = 0, count = arr.length; i < count; i++) {
                sParam = encodeURIComponent(arr[i].name);
                sParam += "=";
                sParam += encodeURIComponent(arr[i].value);
                aParams.push(sParam);
            }

            sParam = 'action';
            aParams.push(sParam);

            let sendData = aParams.join("&");

            $.ajax({
                url: "{{ route('frontend.addsub') }}",
                method: "POST",
                data: sendData,
                dataType: "json",
                success: function (data) {
                    if (data.result != null) {
                        let alert_msg = '';
                        if (data.result === 'success') {
                            alert_msg += '<div class="alert alert-success alert-dismissable">';
                            alert_msg += '<button class="close" aria-hidden="true" data-dismiss="alert" type="button">×</button>';
                            alert_msg += data.msg;
                            alert_msg += '</div>';
                        } else if (data.result === 'error') {
                            alert_msg += '<div class="alert alert-danger alert-dismissable">';
                            alert_msg += '<button class="close" aria-hidden="true" data-dismiss="alert" type="button">×</button>';
                            alert_msg += data.msg;
                            alert_msg += '</div>';
                        } else if (data.result === 'errors') {
                            $.each(data.msg, function (index, val) {
                                alert_msg += '<div class="alert alert-danger alert-dismissable">';
                                alert_msg += '<button class="close" aria-hidden="true" data-dismiss="alert" type="button">×</button>';

                                let arr = data.msg[index];
                                alert_msg += '<ul>';

                                for (var i = 0; i < arr.length; i++) {
                                    alert_msg += '<li>' + arr[i] + '</li>';
                                }

                                alert_msg += '</ul>';
                            });
                        }

                        $("#resultSub").html(alert_msg);
                    }
                }
            });
        });

    </script>

@endsection

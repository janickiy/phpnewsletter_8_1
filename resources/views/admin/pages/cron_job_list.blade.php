@extends('admin.app')

@section('title', $title)

@section('css')


@endsection

@section('content')

    <div class="container-fluid">
        <div class="row">
            <div class="col-12">

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fa-solid fa-clock me-2" aria-hidden="true"></i>{{ $title }}
                        </h3>
                    </div>
                    <!-- /.card-header -->
                    <div class="card-body">

                        <table id="itemList" class="table table-bordered table-striped">
                            <thead>
                            <tr>
                                <th>Cronjob</th>
                                <th>{{ __('frontend.str.description') }}</th>
                            </tr>
                            </thead>

                            <tbody>

                            @foreach($cronJob as $job)
                                <tr>
                                    <td>{{ $job['cron'] }}</td>
                                    <td>{{ $job['description'] }}</td>
                                </tr>
                            @endforeach

                            </tbody>
                        </table>

                    </div>
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


@endsection

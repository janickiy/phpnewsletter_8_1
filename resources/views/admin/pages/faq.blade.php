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
                            <i class="fa-solid fa-circle-question me-2" aria-hidden="true"></i>{{ $title }}
                        </h3>
                    </div>
                    <!-- /.card-header -->
                    <div class="card-body">

                        {!! __('faq.str') !!}

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


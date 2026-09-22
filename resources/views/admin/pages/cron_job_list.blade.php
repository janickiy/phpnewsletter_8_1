@extends('admin.app')

@section('title', $title)

@section('css')
    <style>
        .cron-job-table {
            min-width: 36rem;
            table-layout: fixed;
        }

        #itemList.cron-job-table > :not(caption) > tr > * + * {
            border-inline-start: 0;
        }

        .cron-job-table code {
            font: inherit;
            color: inherit;
            overflow-wrap: anywhere;
            white-space: normal;
        }
    </style>
@endsection

@section('content')

    <div class="container-fluid">
        <div class="row">
            <div class="col-12">

                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fa-solid fa-clock me-2" aria-hidden="true"></i>{{ $title }}
                        </h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table id="itemList" class="table table-striped table-hover align-middle mb-0 cron-job-table">
                                <thead>
                                <tr>
                                    <th scope="col" class="px-3 py-2">Cronjob</th>
                                    <th scope="col" class="px-3 py-2">{{ __('frontend.str.description') }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($cronJob as $job)
                                    <tr>
                                        <td class="px-3 py-2"><code>{{ $job['cron'] }}</code></td>
                                        <td class="px-3 py-2">{{ $job['description'] }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <!-- /.card -->
            </div>
            <!-- /.col -->
        </div>
        <!-- /.row -->
    </div>
    <!-- /.container-fluid -->

@endsection

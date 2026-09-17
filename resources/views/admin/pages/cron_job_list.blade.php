@extends('admin.app')

@section('title', $title)

@section('css')
    <style>
        .cron-job-table {
            min-width: 36rem;
        }

        .cron-job-table code {
            display: block;
            padding: .75rem;
            border: 1px solid var(--bs-border-color);
            border-radius: var(--bs-border-radius);
            background: var(--bs-tertiary-bg);
            color: var(--bs-body-color);
            overflow-wrap: anywhere;
            white-space: pre-wrap;
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
                                <thead class="table-light">
                                <tr>
                                    <th scope="col" class="px-3 py-3">Cronjob</th>
                                    <th scope="col" class="px-3 py-3">{{ __('frontend.str.description') }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($cronJob as $job)
                                    <tr>
                                        <td class="px-3 py-3"><code>{{ $job['cron'] }}</code></td>
                                        <td class="px-3 py-3">{{ $job['description'] }}</td>
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

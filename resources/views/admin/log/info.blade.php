@extends('admin.app')

@section('title', $title)

@section('css')

    <!-- DataTables -->
    <link rel="stylesheet" href="{{ asset('vendor/datatables-bs5/css/dataTables.bootstrap5.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/datatables-responsive-bs5/css/responsive.bootstrap5.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/datatables-buttons-bs5/css/buttons.bootstrap5.min.css') }}">

@endsection

@section('content')

    <div class="container-fluid">
        <div class="row">
            <div class="col-12">

                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fa-solid fa-paper-plane me-2" aria-hidden="true"></i>{{ $title }}
                        </h3>
                    </div>
                    <!-- /.card-header -->
                    <div class="card-body">

                        <p>« <a href="{{ route('admin.log.index') }}">{{ __('frontend.str.back') }}</a></p>

                        <table id="itemList" class="table table-striped table-hover align-middle w-100">
                            <thead>
                            <tr>
                                <th>{{ __('frontend.str.newsletter') }}</th>
                                <th>E-mail</th>
                                <th>{{ __('frontend.str.time') }}</th>
                                <th>{{ __('frontend.str.status') }}</th>
                                <th>{{ __('frontend.str.read') }}</th>
                                <th>{{ __('frontend.str.error') }}</th>
                            </tr>
                            </thead>
                            <tbody>
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

    <!-- DataTables  & Plugins -->
    <script src="{{ asset('vendor/datatables/js/dataTables.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables-bs5/js/dataTables.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables-responsive-bs5/js/responsive.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables-buttons/js/dataTables.buttons.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables-buttons-bs5/js/buttons.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('/plugins/pdfmake/pdfmake.min.js') }}"></script>
    <script src="{{ asset('/plugins/pdfmake/vfs_fonts.js') }}"></script>
    <script src="{{ asset('vendor/datatables-buttons/js/buttons.html5.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables-buttons/js/buttons.print.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables-buttons/js/buttons.colVis.min.js') }}"></script>

    <script>

        $(document).ready(function () {
            $('#itemList').dataTable({
                "autoWidth": false,
                "oLanguage": {
                    "sLengthMenu": "{{ __('pagination.s_length_menu') }}",
                    "sZeroRecords": "{{ __('pagination.s_zero_records') }}",
                    "sInfo": "{{ __('pagination.s_info') }}",
                    "sInfoEmpty": "{{ __('pagination.s_info_empty') }}",
                    "sInfoFiltered": "{{ __('pagination.s_infofiltered') }}",
                    "oPaginate": {
                        "sFirst": "{{ __('pagination.s_paginate.first') }}",
                        "sLast": "{{ __('pagination.s_paginate.last') }}",
                        "sNext": "{{ __('pagination.s_paginate.next') }}",
                        "sPrevious": "{{ __('pagination.s_paginate.previous') }}",
                    },
                    "sSearch": ' <i class="fas fa-search" aria-hidden="true"></i>'
                },
                'createdRow': function (row, data, dataIndex) {
                    $(row).attr('id', 'rowid_' + data['id']);
                    if (data['status'] === 0) $(row).attr('class', 'table-danger');
                },
                aaSorting: [[2, 'desc']],
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('admin.datatable.info_log', ['id' => $id]) }}'
                },
                columns: [
                    {data: 'template', name: 'template'},
                    {data: 'email', name: 'email'},
                    {data: 'created_at', name: 'created_at'},
                    {data: 'success', name: 'success', searchable: false},
                    {data: 'readMail', name: 'readMail', searchable: false},
                    {data: 'errorMsg', name: 'errorMsg', orderable: false, searchable: false},
                ],
            });
        })

    </script>
@endsection

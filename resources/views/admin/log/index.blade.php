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
                            <i class="fa-solid fa-chart-area me-2" aria-hidden="true"></i>
                            {{ __('frontend.str.mailing_report') }}
                        </h3>

                        @if(auth()->user()->canManageProjects())
                            <div class="card-tools">
                                <button id="clearLogButton"
                                        type="button"
                                        class="btn btn-danger btn-sm"
                                        onclick="confirmation(event)"
                                        title="{{ __('frontend.str.log_clear') }}">
                                    <i class="fas fa-trash-alt me-1"></i>
                                    {{ __('frontend.str.log_clear') }}
                                </button>
                                <span id="clearLogSpinner" class="ms-2 d-none">
                                    <span class="spinner-border spinner-border-sm text-danger" role="status" aria-hidden="true"></span>
                                </span>
                            </div>
                        @endif
                    </div>

                    <div class="card-body">
                        <table id="itemList" class="table table-striped table-hover align-middle w-100">
                            <thead>
                            <tr>
                                <th>{{ __('frontend.str.time') }}</th>
                                <th>{{ __('frontend.str.total') }}</th>
                                <th>{{ __('frontend.str.sent') }}</th>
                                <th>{{ __('frontend.str.unsent') }}</th>
                                <th>{{ __('frontend.str.read') }}</th>
                                <th>{{ __('frontend.str.excel_report') }}</th>
                            </tr>
                            </thead>
                        </table>
                    </div>
                </div>

                <div class="card card-outline card-secondary mt-4">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fa-solid fa-paper-plane me-2" aria-hidden="true"></i>
                            {{ __('frontend.str.log') }}
                        </h3>
                    </div>

                    <div class="card-body">
                        <table id="logList" class="table table-striped table-hover align-middle w-100">
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
                </div>
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
        let itemListTable;
        let logListTable;

        function toggleClearLogLoading(isLoading) {
            const clearButton = $('#clearLogButton');

            clearButton.toggleClass('disabled', isLoading);
            clearButton.attr('aria-disabled', isLoading ? 'true' : 'false');
            clearButton.prop('disabled', isLoading);
            clearButton.css('pointer-events', isLoading ? 'none' : '');
            $('#clearLogSpinner').toggleClass('d-none', !isLoading);
        }

        $(function () {
            itemListTable = $('#itemList').DataTable({
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
                },
                layout: {topStart: 'pageLength', topEnd: null, bottomStart: 'info', bottomEnd: 'paging'},
                "autoWidth": false,
                "responsive": true,
                'createdRow': function (row, data, dataIndex) {
                    $(row).attr('id', 'rowid_' + data['id']);
                },
                aaSorting: [[0, 'asc']],
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('admin.datatable.logs') }}'
                },
                columns: [
                    {data: 'event_start', name: 'event_start'},
                    {data: 'count', name: 'count', searchable: false},
                    {data: 'sent', name: 'sent', searchable: false},
                    {data: 'unsent', name: 'unsent', searchable: false},
                    {data: 'read_mail', name: 'read_mail', searchable: false},
                    {data: 'report', name: 'report', orderable: false, searchable: false},
                ],
            });

            logListTable = $('#logList').DataTable({
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
                "autoWidth": false,
                "responsive": true,
                'createdRow': function (row, data, dataIndex) {
                    $(row).attr('id', 'rowid_' + data['id']);
                    if (data['status'] === 0) $(row).attr('class', 'table-danger');
                },
                aaSorting: [[2, 'desc']],
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('admin.datatable.info_log') }}'
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

        function confirmation(event) {
            if ($('#clearLogButton').hasClass('disabled')) {
                event.preventDefault();
                return;
            }

            Swal.fire({
                title: "{{ __('frontend.str.clear_confirmation') }}",
                text: "{{ __('frontend.str.want_to_log_clear') }}",
                showCancelButton: true,
                icon: 'warning',
                cancelButtonText: "{{ __('frontend.str.cancel') }}",
                confirmButtonText: "{{ __('frontend.str.yes') }}",
                reverseButtons: true,
                confirmButtonColor: "#DD6B55",
                customClass: {
                    actions: 'my-actions',
                    cancelButton: 'order-1',
                },
            }).then((result) => {
                if (!result.isConfirmed) {
                    return;
                }

                toggleClearLogLoading(true);

                $.ajax({
                    url: "{{ route('admin.log.clear') }}",
                    type: 'POST',
                    headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                    dataType: 'json',
                    success: function (response) {
                        Swal.fire({
                            icon: 'success',
                            title: response.message || "{{ __('frontend.msg.data_successfully_deleted') }}",
                            confirmButtonText: 'OK'
                        });

                        if (itemListTable) {
                            itemListTable.ajax.reload(null, false);
                        }

                        if (logListTable) {
                            logListTable.ajax.reload(null, false);
                        }
                    },
                    error: function (xhr) {
                        const response = xhr.responseJSON || {};

                        Swal.fire({
                            icon: 'error',
                            title: response.message || "{{ __('frontend.str.delete_error') }}",
                            confirmButtonText: 'OK'
                        });
                    },
                    complete: function () {
                        toggleClearLogLoading(false);
                    }
                });
            })
        }

    </script>

@endsection

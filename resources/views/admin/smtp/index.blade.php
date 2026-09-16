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
                    <div class="card-header d-flex flex-wrap align-items-center gap-2">
                        <h3 class="card-title">{{ $title }}</h3>
                        <div class="card-tools ms-auto">
                            <a href="{{ route('admin.smtp.create') }}"
                               class="btn btn-primary btn-sm">
                                <span class="fas fa-plus me-1"></span> {{ __('frontend.str.add_smtp_server') }}
                            </a>
                        </div>
                    </div>
                    <div class="card-body">

                        <form action="{{ route('admin.smtp.status') }}" method="POST">
                            @csrf

                        <table id="itemList" class="table table-striped table-hover align-middle w-100">
                            <thead>
                            <tr>
                                <th style="width: 10px">
                                <span>
                                   <input type="checkbox" class="form-check-input" title="{{ __('frontend.str.check_uncheck_all') }}" id="checkAll">
                                </span>
                                </th>
                                <th>{{ __('frontend.str.smtp_server') }}</th>
                                <th>E-mail</th>
                                <th>{{ __('frontend.str.login') }}</th>
                                <th>{{ __('frontend.str.port') }}</th>
                                <th>{{ __('frontend.str.connection_timeout') }}</th>
                                <th>{{ __('frontend.str.connection') }}</th>
                                <th>{{ __('frontend.str.authentication_method') }}</th>
                                <th>{{ __('frontend.str.status') }}</th>
                                <th class="text-end" style="width: 10%">{{ __('frontend.str.action') }}</th>
                            </tr>
                            </thead>
                        </table>

                        <div class="input-group input-group-sm mt-3" style="max-width: 24rem">
                            <select name="action" class="form-select" id="select_action">
                                <option value="" @selected((string) old('action', null) === '')>{{ '--' . __('frontend.str.action') . '--' }}</option>
                                @foreach ([ '1' => __('frontend.str.activate'), '0' => __('frontend.str.deactivate'), '2' => __('frontend.str.remove') ] as $value => $label)
                                <option value="{{ $value }}" @selected((string) old('action', null) === (string) $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-success" id="apply" disabled>{{ __('frontend.str.apply') }}</button>
                        </div>

                        </form>

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

        $(function () {
            $("#apply").click(function (event) {
                let idSelect = $('#select_action').val();

                if (idSelect === '') {
                    event.preventDefault();
                    Swal.fire({
                        title: "Error",
                        text: "{{ __('frontend.str.select_action') }}",
                        icon: "error",
                        showCancelButton: false,
                        cancelButtonText: "{{ __('frontend.str.cancel') }}",
                        confirmButtonColor: "#DD6B55",
                    });
                } else {
                    if (idSelect === '2') {
                        event.preventDefault();
                        let form = $(this).parents('form');
                        Swal.fire({
                            title: "{{ __('frontend.str.delete_confirmation') }}",
                            text: "{{ __('frontend.str.confirm_remove') }}",
                            icon: "warning",
                            showCancelButton: true,
                            confirmButtonColor: "#DD6B55",
                            confirmButtonText: "{{ __('frontend.str.yes') }}",
                            cancelButtonText: "{{ __('frontend.str.cancel') }}",
                        }).then((result) => {
                            if (result.isConfirmed) {
                               form.submit();
                            }
                        });
                    }
                }
            });

            $("#checkAll").click(function () {
                $('#itemList input.check').prop('checked', this.checked);
                countChecked();
            });

            $("#checkAll").on('change', function () {
                countChecked();
            });

            $("#itemList").on('change', 'input.check', function () {
                countChecked();
            });

            $("#itemList").DataTable({
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
                    if (data['activeStatus'] === 0) $(row).attr('class', 'table-danger');
                },
                drawCallback: countChecked,
                "processing": true,
                "responsive": true,
                aaSorting: [[1, 'asc']],
                "autoWidth": false,
                'serverSide': true,
                'ajax': {
                    url: '{{ route('admin.datatable.smtp') }}'
                },
                columnDefs: [{targets: -1, className: 'text-end'}],
                'columns': [
                    {data: 'checkbox', name: 'checkbox', orderable: false, searchable: false},
                    {data: 'host', name: 'host'},
                    {data: 'email', name: 'email'},
                    {data: 'username', name: 'username'},
                    {data: 'port', name: 'port'},
                    {data: 'timeout', name: 'timeout'},
                    {data: 'secure', name: 'secure'},
                    {data: 'authentication', name: 'authentication'},
                    {data: 'active', name: 'active'},
                    {data: 'action', name: 'action', orderable: false, searchable: false}
                ]
            });

            $('#itemList').on('click', '.deleteRow', function () {
                let rowid = $(this).attr('id');
                Swal.fire({
                    title: "{{ __('frontend.msg.are_you_sure') }}",
                    text: "{{ __('frontend.msg.will_not_be_able_to_recover_information') }}",
                    showCancelButton: true,
                    icon: 'warning',
                    cancelButtonText: "{{ __('frontend.str.cancel') }}",
                    confirmButtonText: "{{ __('frontend.msg.yes_remove') }}",
                    reverseButtons: true,
                    confirmButtonColor: "#DD6B55",
                    customClass: {
                        actions: 'my-actions',
                        cancelButton: 'order-1',
                    },
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '{{ url('smtp/destroy') }}/' + rowid,
                            type: "POST",
                            dataType: "html",
                            data: {_method: 'DELETE'},
                            headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                            success: function () {
                                $('#itemList').DataTable().ajax.reload(null, false);
                                Swal.fire("{{ __('frontend.msg.done') }}", "{{ __('frontend.msg.data_successfully_deleted') }}", 'success');
                            },
                            error: function (xhr, ajaxOptions, thrownError) {
                                Swal.fire("{{ __('frontend.msg.error_deleting') }}", "{{ __('frontend.msg.try_again') }}", 'error');
                                console.log(ajaxOptions);
                                console.log(thrownError);
                            }
                        });
                    }
                });
            });
        });

        function countChecked() {
            const checkboxes = $('#itemList input.check');
            const checked = checkboxes.filter(':checked').length;
            $('#apply').prop('disabled', checked === 0);
            $('#checkAll').prop('checked', checkboxes.length > 0 && checked === checkboxes.length);
            $('#checkAll').prop('indeterminate', checked > 0 && checked < checkboxes.length);
        }

    </script>

@endsection

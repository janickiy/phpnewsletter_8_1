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
                        <h3 class="card-title"><i class="fa-solid fa-user-group me-2" aria-hidden="true"></i>{{ $title }}</h3>
                        <div class="card-tools ms-auto d-flex flex-wrap align-items-center gap-2">
                            <a class="btn btn-outline-secondary btn-sm"
                               title="{{ __('frontend.str.import_subscribers') }}"
                               href="{{ route('admin.subscribers.import') }}">
                                <span class="fas fa-download me-1"></span> {{ __('frontend.str.import') }}
                            </a>
                            <a class="btn btn-outline-secondary btn-sm"
                               title="{{ __('frontend.str.export_subscribers') }}"
                               href="{{ route('admin.subscribers.export') }}">
                                <span class="fas fa-upload me-1"></span> {{ __('frontend.str.export') }}
                            </a>
                            <button type="button" id="removeAllSubscribersButton" class="btn btn-outline-danger btn-sm"
                                    title="{{ __('frontend.str.delete_all_subscribers') }}"
                                    onclick="confirmation(event)">
                                <span class="fas fa-trash me-1"></span> {{ __('frontend.str.delete_all') }}
                            </button>
                            <span id="removeAllSubscribersSpinner" class="d-none">
                                <span class="spinner-border spinner-border-sm text-danger" role="status" aria-hidden="true"></span>
                            </span>
                            <a href="{{ route('admin.subscribers.create') }}"
                               class="btn btn-primary btn-sm">
                                <span class="fas fa-plus me-1"></span> {{ __('frontend.str.add_subscriber') }}
                            </a>
                        </div>
                    </div>
                    <div class="card-body">

                        <form action="{{ route('admin.subscribers.status') }}" method="POST">
                        @csrf

                        <table id="itemList" class="table table-striped table-hover align-middle w-100">
                            <thead>
                            <tr>
                                <th style="width: 10px">
                                <span>
                                   <input type="checkbox" class="form-check-input" title="{{ __('frontend.str.check_uncheck_all') }}"
                                          id="checkAll">
                                </span>
                                </th>
                                <th>{{ __('frontend.str.name') }}</th>
                                <th>E-mail</th>
                                <th>{{ __('frontend.str.category') }}</th>
                                <th>{{ __('frontend.str.status') }}</th>
                                <th>{{ __('frontend.str.added') }}</th>
                                <th class="text-end" style="width: 10%">{{ __('frontend.str.action') }}</th>
                            </tr>
                            </thead>
                        </table>

                        <div class="input-group input-group-sm mt-3" style="max-width: 24rem">
                            <select name="action" class="form-select" id="select_action">
                                <option value="" @selected((string) old('action', '') === '')>--{{ __('frontend.str.action') }}--</option>
                                <option value="1" @selected((string) old('action', '') === '1')>{{ __('frontend.str.activate') }}</option>
                                <option value="0" @selected((string) old('action', '') === '0')>{{ __('frontend.str.deactivate') }}</option>
                                <option value="2" @selected((string) old('action', '') === '2')>{{ __('frontend.str.remove') }}</option>
                            </select>
                            <input type="submit" value="{{ __('frontend.str.apply') }}" class="btn btn-success" disabled id="apply">
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
                aaSorting: [[5, 'desc']],
                drawCallback: countChecked,
                "processing": true,
                "responsive": true,
                "autoWidth": false,
                "deferRender": true,
                "searchDelay": 500,
                'serverSide': true,
                'ajax': {
                    url: '{{ route('admin.datatable.subscribers') }}'
                },
                columnDefs: [{targets: -1, className: 'text-end'}],
                'columns': [
                    {data: 'checkbox', name: 'checkbox', orderable: false, searchable: false},
                    {data: 'name', name: 'name'},
                    {data: 'email', name: 'email'},
                    {data: 'subscriptions', name: 'subscriptions', orderable: false, searchable: false},
                    {data: 'active', name: 'active', searchable: false},
                    {data: 'created_at', name: 'created_at'},
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
                            url: '{{ url('subscribers/destroy') }}/' + rowid,
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

        function toggleRemoveAllSubscribersLoading(isLoading) {
            const removeButton = $('#removeAllSubscribersButton');

            removeButton.toggleClass('disabled', isLoading);
            removeButton.attr('aria-disabled', isLoading ? 'true' : 'false');
            removeButton.css('pointer-events', isLoading ? 'none' : '');
            $('#removeAllSubscribersSpinner').toggleClass('d-none', !isLoading);
        }

        $(window).on('pageshow', function () {
            toggleRemoveAllSubscribersLoading(false);
        });

        function confirmation(event) {
            if ($('#removeAllSubscribersButton').hasClass('disabled')) {
                event.preventDefault();
                return;
            }

            Swal.fire({
                title: "{{ __('frontend.str.delete_all_subscribers') }}",
                text: "{{ __('frontend.str.want_to_delete_all_subscribers')  }}",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "{{ __('frontend.str.yes') }}",
                cancelButtonText: "{{ __('frontend.str.cancel') }}",
            }).then((result) => {
                if (result.isConfirmed) {
                    toggleRemoveAllSubscribersLoading(true);
                    window.location.href = "{{ route('admin.subscribers.remove_all') }}";
                }
            });
        }

    </script>

@endsection

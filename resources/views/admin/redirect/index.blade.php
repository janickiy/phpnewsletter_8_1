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
                            <i class="fa-solid fa-link me-2" aria-hidden="true"></i>
                            {{ __('frontend.str.redirect') }}
                        </h3>

                        @if(auth()->user()->canManageProjects())
                            <div class="card-tools">
                                <button id="clearRedirectButton"
                                        type="button"
                                        class="btn btn-danger btn-sm"
                                        title="{{ __('frontend.str.redirect_clear') }}"
                                        onclick="confirmation(event)">
                                    <i class="fas fa-trash-alt me-1"></i>
                                    {{ __('frontend.str.redirect_clear') }}
                                </button>
                                <span id="clearRedirectSpinner" class="ms-2 d-none">
                                    <span class="spinner-border spinner-border-sm text-danger" role="status" aria-hidden="true"></span>
                                </span>
                            </div>
                        @endif
                    </div>

                    <div class="card-body">
                        <table id="itemList" class="table table-striped table-hover align-middle w-100">
                            <thead>
                            <tr>
                                <th>URL</th>
                                <th>{{ __('frontend.str.newsletter') }}</th>
                                <th>{{ __('frontend.str.redirect_number') }}</th>
                                <th>{{ __('frontend.str.excel_report') }}</th>
                                <th>{{ __('frontend.str.time') }}</th>
                            </tr>
                            </thead>
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
        let redirectTable;

        function toggleClearRedirectLoading(isLoading) {
            const clearButton = $('#clearRedirectButton');

            clearButton.toggleClass('disabled', isLoading);
            clearButton.attr('aria-disabled', isLoading ? 'true' : 'false');
            clearButton.prop('disabled', isLoading);
            clearButton.css('pointer-events', isLoading ? 'none' : '');
            $('#clearRedirectSpinner').toggleClass('d-none', !isLoading);
        }

        $(function () {
            redirectTable = $('#itemList').DataTable({
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
                aaSorting: [[4, 'desc']],
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('admin.datatable.redirect') }}'
                },
                columnDefs: [{targets: [2, 3], className: 'text-center'}],
                columns: [
                    {data: 'url', name: 'url'},
                    {data: 'template', name: 'template'},
                    {data: 'count', name: 'count', searchable: false},
                    {data: 'report', name: 'report', orderable: false, searchable: false},
                    {data: 'last_clicked_at', name: 'last_clicked_at', visible: false, searchable: false},
                ],
            });
        });

        function confirmation(event) {
            if ($('#clearRedirectButton').hasClass('disabled')) {
                event.preventDefault();
                return;
            }

            Swal.fire({
                title: "{{ __('frontend.str.clear_confirmation') }}",
                text: "{{ __('frontend.str.want_to_redirect_clear') }}",
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

                toggleClearRedirectLoading(true);

                $.ajax({
                    url: "{{ route('admin.redirect.clear') }}",
                    type: 'POST',
                    headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                    dataType: 'json',
                    success: function (response) {
                        Swal.fire({
                            icon: 'success',
                            title: response.message || "{{ __('frontend.msg.data_successfully_deleted') }}",
                            confirmButtonText: 'OK'
                        });

                        if (redirectTable) {
                            redirectTable.ajax.reload(null, false);
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
                        toggleClearRedirectLoading(false);
                    }
                });
            })
        }

    </script>

@endsection

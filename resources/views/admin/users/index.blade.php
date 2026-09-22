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
                        <h3 class="card-title"><i class="fa-solid fa-users me-2" aria-hidden="true"></i>{{ $title }}</h3>
                        <div class="card-tools ms-auto">
                            <a href="{{ route('admin.users.create') }}" class="btn btn-primary btn-sm">
                                <span class="fas fa-plus me-1"></span> {{ __('frontend.str.add_user') }}
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <table id="itemList" class="table table-striped table-hover align-middle w-100">
                            <thead>
                            <tr>
                                <th>{{ __('frontend.str.login') }}</th>
                                <th>{{ __('frontend.str.name') }}</th>
                                <th>{{ __('frontend.str.description') }}</th>
                                <th>{{ __('frontend.str.role') }}</th>
                                <th>{{ __('frontend.str.added') }}</th>
                                <th style="width: 10%">{{ __('frontend.str.action') }}</th>
                            </tr>
                            </thead>
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
        $(function (){

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
                },
                "processing": true,
                "responsive": true,
                "autoWidth": false,
                'serverSide': true,
                'ajax': {
                    url: '{{ route('admin.datatable.users') }}'
                },
                columnDefs: [{targets: [3, 4, 5], className: 'text-center'}],
                'columns': [
                    {data: 'login', name: 'login'},
                    {data: 'name', name: 'name'},
                    {data: 'description', name: 'description'},
                    {data: 'role', name: 'role'},
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
                            url: '{{ url('users/destroy') }}/' + rowid,
                            type: "POST",
                            dataType: "html",
                            data: {_method: 'DELETE'},
                            headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                            success: function () {
                                $('#itemList').DataTable().ajax.reload(null, false);
                                Swal.fire("{{ __('frontend.msg.done') }}", "{{ __('frontend.msg.data_successfully_deleted') }}", 'success');
                            },
                            error: function (xhr, ajaxOptions, thrownError) {
                                Swal.fire({
                                    title: @json(__('frontend.msg.error_deleting')),
                                    text: xhr.status === 422 ? @json(__('frontend.str.projects.owner_has_projects')) : @json(__('frontend.msg.try_again')),
                                    icon: 'error'
                                });
                            }
                        });
                    }
                });
            });
        });

    </script>
@endsection

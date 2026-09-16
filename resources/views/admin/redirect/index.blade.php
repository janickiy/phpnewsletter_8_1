@extends('admin.app')

@section('title', $title)

@section('css')

    <!-- DataTables -->
    {!! Html::style('/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') !!}
    {!! Html::style('/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') !!}
    {!! Html::style('/plugins/datatables-buttons/css/buttons.bootstrap4.min.css') !!}

@endsection

@section('content')

    <!-- Main content -->
    <section class="content">

        <div class="container-fluid">
            <div class="row">
                <div class="col-12">

                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-chart-line mr-1"></i>
                                {{ __('frontend.str.redirect') }}
                            </h3>

                            @if(PermissionsHelper::has_permission('admin'))
                                <div class="card-tools">
                                    <button id="clearRedirectButton"
                                            type="button"
                                            class="btn btn-danger btn-sm"
                                            title="{{ __('frontend.str.redirect_clear') }}"
                                            onclick="confirmation(event)">
                                        <i class="fas fa-trash-alt mr-1"></i>
                                        {{ __('frontend.str.redirect_clear') }}
                                    </button>
                                    <span id="clearRedirectSpinner" class="ml-2 d-none">
                                        <span class="spinner-border spinner-border-sm text-danger" role="status" aria-hidden="true"></span>
                                    </span>
                                </div>
                            @endif
                        </div>

                        <div class="card-body">
                            <table id="itemList" class="table table-striped table-bordered table-hover">
                                <thead>
                                <tr>
                                    <th>URL</th>
                                    <th>{{ __('frontend.str.redirect_number') }}</th>
                                    <th>{{ __('frontend.str.excel_report') }}</th>
                                </tr>
                                </thead>
                                <tfoot>
                                <tr>
                                    <th>URL</th>
                                    <th>{{ __('frontend.str.redirect_number') }}</th>
                                    <th>{{ __('frontend.str.excel_report') }}</th>
                                </tr>
                                </tfoot>
                            </table>

                        </div>
                    </div>
                </div>
                <!-- /.col -->
            </div>
            <!-- /.row -->
        </div>
        <!-- /.container-fluid -->

    </section>
    <!-- /.content -->

@endsection

@section('js')

    <!-- DataTables  & Plugins -->
    {!! Html::script('/plugins/datatables/jquery.dataTables.min.js') !!}
    {!! Html::script('/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') !!}
    {!! Html::script('/plugins/datatables-responsive/js/dataTables.responsive.min.js') !!}
    {!! Html::script('/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') !!}
    {!! Html::script('/plugins/datatables-buttons/js/dataTables.buttons.min.js') !!}
    {!! Html::script('/plugins/datatables-buttons/js/buttons.bootstrap4.min.js') !!}
    {!! Html::script('/plugins/pdfmake/pdfmake.min.js') !!}
    {!! Html::script('/plugins/pdfmake/vfs_fonts.js') !!}
    {!! Html::script('/plugins/datatables-buttons/js/buttons.html5.min.js') !!}
    {!! Html::script('/plugins/datatables-buttons/js/buttons.print.min.js') !!}
    {!! Html::script('/plugins/datatables-buttons/js/buttons.colVis.min.js') !!}

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
                "sDom": "flrtip",
                "autoWidth": false,
                "responsive": true,
                aaSorting: [[0, 'asc']],
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('admin.datatable.redirect') }}'
                },
                columns: [
                    {data: 'url', name: 'url'},
                    {data: 'count', name: 'count', searchable: false},
                    {data: 'report', name: 'report', orderable: false, searchable: false},
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
                    type: 'GET',
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

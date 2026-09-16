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

                    <div class="card">
                        <!-- /.card-header -->
                        <div class="card-body">
                            <div class="pb-3">
                                <a href="{{ route('admin.smtp.create') }}"
                                   class="btn btn-info btn-sm pull-left">
                                    <span class="fa fa-plus"> &nbsp;</span> {{ __('frontend.str.add_smtp_server') }}
                                </a>
                            </div>

                            {!! Form::open(['url' => route('admin.smtp.status'), 'method' => 'post']) !!}

                            <table id="itemList" class="table table-bordered table-striped">
                                <thead>
                                <tr>
                                    <th style="width: 10px">
                                    <span>
                                       <input type="checkbox" title="{{ __('frontend.str.check_uncheck_all') }}" id="checkAll">
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
                                    <th style="width: 10%">{{ __('frontend.str.action') }}</th>
                                </tr>
                                </thead>
                                <tfoot>
                                    <th style="width: 10px"></th>
                                    <th>{{ __('frontend.str.smtp_server') }}</th>
                                    <th>E-mail</th>
                                    <th>{{ __('frontend.str.login') }}</th>
                                    <th>{{ __('frontend.str.port') }}</th>
                                    <th>{{ __('frontend.str.connection_timeout') }}</th>
                                    <th>{{ __('frontend.str.connection') }}</th>
                                    <th>{{ __('frontend.str.authentication_method') }}</th>
                                    <th>{{ __('frontend.str.status') }}</th>
                                    <th style="width: 10%">{{ __('frontend.str.action') }}</th>
                                </tfoot>
                            </table>

                            <div class="row">
                                <div class="col-sm-12 padding-bottom-10">
                                    <div class="form-inline">
                                        <div class="control-group">

                                            {!! Form::select('action',[
                                            '1' => __('frontend.str.activate'),
                                            '0' => __('frontend.str.deactivate'),
                                            '2' => __('frontend.str.remove')
                                            ],null,['class' => 'span3 form-control', 'id' => 'select_action','placeholder' => '--' . __('frontend.str.action') . '--']) !!}

                                            <span class="help-inline">
                                                {!! Form::submit(__('frontend.str.apply'), ['class' => 'btn btn-success', 'disabled' => "", 'id' => 'apply']) !!}
                                            </span>

                                        </div>
                                    </div>
                                </div>
                            </div>

                            {!! Form::close() !!}

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

        $(function () {
            $("#apply").click(function (event) {
                let idSelect = $('#select_action').val();

                if (idSelect === '') {
                    event.preventDefault();
                    Swal.fire({
                        title: "Error",
                        text: "{{ __('frontend.str.select_action') }}",
                        type: "error",
                        showCancelButton: false,
                        cancelButtonText: "{{ __('frontend.str.cancel') }}",
                        confirmButtonColor: "#DD6B55",
                        closeOnConfirm: false
                    });
                } else {
                    if (idSelect === 2) {
                        event.preventDefault();
                        let form = $(this).parents('form');
                        Swal.fire({
                            title: "{{ __('frontend.str.delete_confirmation') }}",
                            text: "{{ __('frontend.str.confirm_remove') }}",
                            type: "warning",
                            showCancelButton: true,
                            confirmButtonColor: "#DD6B55",
                            confirmButtonText: "{{ __('frontend.str.yes') }}",
                            cancelButtonText: "{{ __('frontend.str.cancel') }}",
                            closeOnConfirm: false
                        }).then((result) => {
                            if (result.isConfirmed) {
                               form.submit();
                            }
                        });
                    }
                }
            });

            $("#checkAll").click(function () {
                $('input:checkbox').not(this).prop('checked', this.checked);
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
                "processing": true,
                "responsive": true,
                aaSorting: [[1, 'asc']],
                "autoWidth": true,
                'serverSide': true,
                'ajax': {
                    url: '{{ route('admin.datatable.smtp') }}'
                },
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

            $('#itemList').on('click', 'a.deleteRow', function () {
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
                                $("#rowid_" + rowid).remove();
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

        function countChecked()
        {
            if ($('.check').is(':checked'))
                $('#apply').attr('disabled',false);
            else
                $('#apply').attr('disabled',true);
        }

    </script>

@endsection

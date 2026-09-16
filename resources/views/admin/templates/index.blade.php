@extends('admin.app')

@section('title', $title)

@section('css')

    <!-- DataTables -->
    {!! Html::style('/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') !!}
    {!! Html::style('/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') !!}
    {!! Html::style('/plugins/datatables-buttons/css/buttons.bootstrap4.min.css') !!}

    <style>
        #divStatus {
            display: inline-block;
            min-height: 20px;
        }

        #divStatus.error {
            color: #dc3545;
        }

        #divStatus.success {
            color: #28a745;
            font-weight: 600;
        }
    </style>

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
                                <a href="{{ route('admin.templates.create') }}" class="btn btn-info btn-sm pull-left">
                                    <span class="fa fa-plus"> &nbsp;</span> {{ __('frontend.str.add_template') }}
                                </a>
                            </div>

                            {!! Form::open(['url' => route('admin.templates.status'), 'method' => 'post']) !!}

                            <table id="itemList" class="table table-bordered table-striped">
                                <thead>
                                <tr>
                                    <th style="width: 10px">
                                        <span>
                                            <input type="checkbox" title="{{ __('frontend.str.check_uncheck_all') }}"
                                                   id="checkAll">
                                        </span>
                                    </th>
                                    <th style="width: 10px">ID</th>
                                    <th>{{ __('frontend.str.template') }}</th>
                                    <th>{{ __('frontend.str.importance') }}</th>
                                    <th>{{ __('frontend.str.attachments') }}</th>
                                    <th>{{ __('frontend.str.date') }}</th>
                                    <th style="width: 10%">{{ __('frontend.str.action') }}</th>
                                </tr>
                                </thead>
                                <tfoot>
                                <th style="width: 10px"></th>
                                <th style="width: 10px">ID</th>
                                <th>{{ __('frontend.str.template') }}</th>
                                <th>{{ __('frontend.str.importance') }}</th>
                                <th>{{ __('frontend.str.attachments') }}</th>
                                <th>{{ __('frontend.str.date') }}</th>
                                <th style="width: 10%">{{ __('frontend.str.action') }}</th>
                                </tfoot>
                            </table>

                            <div class="row">
                                <div class="col-sm-12 padding-bottom-10">
                                    <div class="form-inline">
                                        <div class="control-group">

                                            {!! Form::select('action',[
                                            '0' => __('frontend.str.send'),
                                            '1' => __('frontend.str.remove')
                                            ],null,['class' => 'span3 form-control', 'id' => 'select_action','placeholder' => '--' . __('frontend.str.action') . '--'],[0 => ['data-id' => 'sendmail', 'class' => 'open_modal']]) !!}

                                            <span class="help-inline">

                                            {!! Form::submit(__('frontend.str.apply'), ['class' => 'btn btn-success', 'disabled' => "", 'id' => 'apply']) !!}

                                            </span>

                                        </div>
                                    </div>
                                </div>
                            </div>

                            {!! Form::close() !!}

                            <!-- /.card-body -->
                        </div>
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

    <div class="modal fade" id="modal-lg">
        <input id="logId" type="hidden" value="0">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">{{ __('frontend.str.online_newsletter_log') }}<span id="process"></span>
                    </h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="onlinelog"></div>
                    <div class="row">
                        <div class="col-sm-12 padding-top-10 padding-bottom-10">
                            <div class="form-inline">
                                <div class="control-group">

                                    {!! Form::select('categoryId[]', $categoryOptions, null, ['id' => 'categoryId','multiple'=>'multiple', 'placeholder' => __('frontend.form.select_category'), 'class' => 'form-control custom-scroll', 'style' => 'width: 100%']) !!}

                                </div>
                            </div>
                        </div>
                    </div>
                    <p><span id="leftsend">0</span>% {{ __('frontend.str.left') }}: <span id="timer2">00:00:00</span>
                    </p>
                    <div class="progress progress-sm progress-striped active">
                        <div class="progress-bar bg-color-darken" role="progressbar" style="width: 1%"></div>
                    </div>
                    <div class="online_statistics">{{ __('frontend.str.total') }}:
                        <span id="totalsendlog">0</span>
                        <span style="color: green">{{ __('frontend.str.good') }}: </span>
                        <span style="color: green" id="successful">0</span>
                        <span style="color: red">{{ __('frontend.str.bad') }}: </span>
                        <span style="color: red" id="unsuccessful">0</span><br><br>
                        <span id="divStatus"></span><br>
                        <button id="sendout" class="btn btn-default btn-circle btn-modal btn-lg"
                                style="margin-right: 15px;" title="{{ __('frontend.str.send_out_newsletter') }}"><i
                                class="fa fa-play"></i></button>
                        <button id="stopsendout"
                                class="btn btn-danger btn-circle btn-lg disabled" disabled="disabled"
                                title="{{ __('frontend.str.stop_newsletter') }}">
                            <i class="fa fa-stop"></i>
                        </button>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default"
                            data-dismiss="modal">{{ __('frontend.str.close') }}</button>
                </div>
            </div>
        </div>
    </div>

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
        const ajaxUrl = '{{ route('admin.ajax.action') }}';
        const csrfToken = $('meta[name="csrf-token"]').attr('content');
        const serverErrorText = "{{ __('frontend.str.error_server') }}";
        const noNewsletterSelectedText = "{{ __('frontend.str.no_newsletter_selected') }}";
        const noCategorySelectedText = "{{ __('frontend.form.select_category') }}";

        let mailingState = {
            paused: false,
            completed: true,
            countTimer: null,
            logTimer: null,
            sendRequest: null,
        };

        $(function () {
            const openModalButton = $('#apply');
            const modalElement = document.getElementById('modal-lg');
            const modalInstance = new bootstrap.Modal(modalElement, {});

            $('#sendout').on('click', function () {
                resetStatusMessage();

                const templateIds = getSelectedTemplateIds();
                const categoryIds = getSelectedCategoryIds();

                if (templateIds.length === 0) {
                    showStatusMessage(noNewsletterSelectedText);
                    return;
                }

                if (categoryIds.length === 0) {
                    showStatusMessage(noCategorySelectedText);
                    return;
                }

                startMailing(templateIds, categoryIds);
            });

            $('#stopsendout').on('click', function () {
                stopMailing();
            });

            openModalButton.on('click', function (event) {
                const actionId = $('#select_action').val();

                if (actionId === '') {
                    event.preventDefault();

                    Swal.fire({
                        title: 'Error',
                        text: "{{ __('frontend.str.select_action') }}",
                        type: 'error',
                        showCancelButton: false,
                        cancelButtonText: "{{ __('frontend.str.cancel') }}",
                        confirmButtonColor: '#DD6B55',
                        closeOnConfirm: false
                    });

                    return;
                }

                if (actionId == 1) {
                    event.preventDefault();
                    const form = $(this).parents('form');

                    Swal.fire({
                        title: "{{ __('frontend.str.delete_confirmation') }}",
                        text: "{{ __('frontend.str.confirm_remove') }}",
                        type: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#DD6B55',
                        confirmButtonText: "{{ __('frontend.str.yes') }}",
                        cancelButtonText: "{{ __('frontend.str.cancel') }}",
                        closeOnConfirm: false
                    }).then((result) => {
                        if (result.isConfirmed) {
                            form.submit();
                        }
                    });

                    return;
                }

                if (actionId == 0) {
                    event.preventDefault();
                    resetModalState();
                    modalInstance.show();
                }
            });

            $('#checkAll').on('click change', function () {
                $('#itemList').find('input.check').prop('checked', this.checked);
                countChecked();
            });

            $('#itemList').on('change', 'input.check', function () {
                syncCheckAllState();
                countChecked();
            });

            $('#itemList').DataTable({
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
                createdRow: function (row, data) {
                    $(row).attr('id', 'rowid_' + data.id);
                },
                aaSorting: [[1, 'desc']],
                processing: true,
                responsive: true,
                autoWidth: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('admin.datatable.templates') }}'
                },
                columns: [
                    {data: 'checkbox', name: 'checkbox', orderable: false, searchable: false},
                    {data: 'id', name: 'id'},
                    {data: 'name', name: 'name'},
                    {data: 'prior', name: 'prior', searchable: false},
                    {data: 'attach', name: 'attach.id', searchable: false},
                    {data: 'created_at', name: 'created_at'},
                    {data: 'action', name: 'action', orderable: false, searchable: false}
                ],
                drawCallback: function () {
                    syncCheckAllState();
                    countChecked();
                }
            });

            $('#itemList').on('click', 'a.deleteRow', function () {
                const rowid = $(this).attr('id');
                Swal.fire({
                    title: "{{ __('frontend.msg.are_you_sure') }}",
                    text: "{{ __('frontend.msg.will_not_be_able_to_recover_information') }}",
                    showCancelButton: true,
                    icon: 'warning',
                    cancelButtonText: "{{ __('frontend.str.cancel') }}",
                    confirmButtonText: "{{ __('frontend.msg.yes_remove') }}",
                    reverseButtons: true,
                    confirmButtonColor: '#DD6B55',
                    customClass: {
                        actions: 'my-actions',
                        cancelButton: 'order-1',
                    },
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '{{ url('template/destroy') }}/' + rowid,
                            type: 'POST',
                            dataType: 'html',
                            data: {_method: 'DELETE'},
                            headers: {'X-CSRF-TOKEN': csrfToken},
                            success: function () {
                                $('#rowid_' + rowid).remove();
                                Swal.fire("{{ __('frontend.msg.done') }}", "{{ __('frontend.msg.data_successfully_deleted') }}", 'success');
                            },
                            error: function (xhr, ajaxOptions, thrownError) {
                                Swal.fire("{{ __('frontend.msg.error_deleting') }}", "{{ __('frontend.msg.try_again') }}", 'error');
                                console.log(xhr);
                                console.log(ajaxOptions);
                                console.log(thrownError);
                            }
                        });
                    }
                });
            });

            $(modalElement).on('hidden.bs.modal', function () {
                if (!mailingState.completed && !mailingState.paused) {
                    stopMailing(true);
                    return;
                }

                resetModalState();
            });
        });

        function getSelectedTemplateIds() {
            return $('#itemList').find('input.check:checked').map(function () {
                const value = parseInt($(this).val(), 10);
                return Number.isInteger(value) ? value : null;
            }).get();
        }

        function getSelectedCategoryIds() {
            const values = $('#categoryId').val() || [];

            return values.map(function (value) {
                return parseInt(value, 10);
            }).filter(function (value) {
                return Number.isInteger(value);
            });
        }

        function countChecked() {
            $('#apply').prop('disabled', getSelectedTemplateIds().length === 0);
        }

        function syncCheckAllState() {
            const total = $('#itemList').find('input.check').length;
            const checked = $('#itemList').find('input.check:checked').length;
            $('#checkAll').prop('checked', total > 0 && total === checked);
        }

        function startMailing(templateIds, categoryIds) {
            mailingState.paused = false;
            mailingState.completed = false;
            clearTimers();
            resetCounters();
            resetStatusMessage();
            setRunningUiState();

            $.ajax({
                url: ajaxUrl,
                method: 'POST',
                headers: {'X-CSRF-TOKEN': csrfToken},
                data: {
                    action: 'start_mailing',
                },
                dataType: 'json'
            }).done(function (data) {
                if (data.result === true && data.logId) {
                    $('#logId').val(data.logId);
                    startPolling();
                    sendOut(templateIds, categoryIds);
                    return;
                }

                failProcess(data.errors || serverErrorText);
            }).fail(function (jqXHR, textStatus, errorThrown) {
                failProcess(extractErrorMessage(jqXHR, textStatus, errorThrown));
            });
        }

        function startPolling() {
            getCountProcess();
            onlineLogProcess();

            mailingState.countTimer = setInterval(function () {
                if (!mailingState.completed) {
                    getCountProcess();
                }
            }, 2000);

            mailingState.logTimer = setInterval(function () {
                if (!mailingState.completed) {
                    onlineLogProcess();
                }
            }, 2000);
        }

        function sendOut(templateIds, categoryIds) {
            mailingState.sendRequest = $.ajax({
                type: 'POST',
                url: ajaxUrl,
                headers: {'X-CSRF-TOKEN': csrfToken},
                data: {
                    action: 'send_out',
                    categoryId: categoryIds,
                    templateId: templateIds,
                    logId: $('#logId').val(),
                },
                cache: false,
                dataType: 'json',
                // A mailing can legitimately take several minutes because SLEEP is
                // applied between recipients. Keep this request alive until the
                // server reports completion or the user explicitly stops it.
                timeout: 0,
            }).done(function (json) {
                if (json.result !== true) {
                    failProcess(json.errors || serverErrorText);
                    return;
                }

                if (json.completed === true) {
                    completeProcess();
                }
            }).fail(function (jqXHR, textStatus, errorThrown) {
                if (textStatus === 'abort') {
                    return;
                }

                failProcess(extractErrorMessage(jqXHR, textStatus, errorThrown));
            }).always(function () {
                mailingState.sendRequest = null;
            });
        }

        function getCountProcess() {
            const logId = parseInt($('#logId').val(), 10);

            if (!logId || mailingState.completed) {
                return;
            }

            $.ajax({
                url: ajaxUrl,
                cache: false,
                method: 'POST',
                headers: {'X-CSRF-TOKEN': csrfToken},
                data: {
                    action: 'count_send',
                    logId: logId,
                    categoryId: getSelectedCategoryIds(),
                },
                dataType: 'json',
                success: function (json) {
                    if (json.result !== true) {
                        if (json.errors) {
                            failProcess(json.errors);
                        }
                        return;
                    }

                    $('#totalsendlog').text(json.total ?? 0);
                    $('#unsuccessful').text(json.unsuccessful ?? 0);
                    $('#successful').text(json.success ?? 0);
                    $('#timer2').text(json.time ?? '00:00:00');

                    const leftsend = Number(json.leftsend ?? 0);
                    $('.progress-bar').css('width', leftsend + '%');
                    $('#leftsend').text(leftsend);
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    failProcess(extractErrorMessage(jqXHR, textStatus, errorThrown));
                },
            });
        }

        function onlineLogProcess() {
            if (mailingState.completed) {
                return;
            }

            $.ajax({
                type: 'POST',
                cache: false,
                url: ajaxUrl,
                headers: {'X-CSRF-TOKEN': csrfToken},
                data: {
                    action: 'log_online',
                },
                dataType: 'json',
                success: function (data) {
                    if (!Array.isArray(data.item)) {
                        return;
                    }

                    const html = data.item
                        .filter(function (item) {
                            return item && typeof item.email !== 'undefined' && item.email !== null && item.email !== '';
                        })
                        .map(function (item) {
                            return escapeHtml(item.email) + ' - ' + escapeHtml(item.status ?? '');
                        })
                        .join('<br>');

                    $('#onlinelog').html(html);
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    failProcess(extractErrorMessage(jqXHR, textStatus, errorThrown));
                },
            });
        }

        function stopMailing(silent = false) {
            $.ajax({
                type: 'POST',
                url: ajaxUrl,
                headers: {'X-CSRF-TOKEN': csrfToken},
                data: {
                    action: 'process',
                    command: 'stop',
                },
                dataType: 'json',
                success: function (data) {
                    if (data.result !== true) {
                        failProcess(data.errors || serverErrorText);
                        return;
                    }

                    completeProcess();

                    if (!silent) {
                        showSuccessMessage('Stopped');
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    failProcess(extractErrorMessage(jqXHR, textStatus, errorThrown));
                },
            });
        }

        function completeProcess() {
            mailingState.completed = true;
            mailingState.paused = false;
            clearTimers();

            if (mailingState.sendRequest) {
                mailingState.sendRequest.abort();
                mailingState.sendRequest = null;
            }

            setIdleUiState();
            $('#timer2').text('00:00:00');
            $('#leftsend').text(100);
            $('.progress-bar').css('width', '100%');
        }

        function failProcess(message) {
            completeProcess();
            showStatusMessage(message || serverErrorText);
        }

        function clearTimers() {
            if (mailingState.countTimer) {
                clearInterval(mailingState.countTimer);
                mailingState.countTimer = null;
            }

            if (mailingState.logTimer) {
                clearInterval(mailingState.logTimer);
                mailingState.logTimer = null;
            }
        }

        function resetModalState() {
            clearTimers();
            mailingState.paused = false;
            mailingState.completed = true;

            if (mailingState.sendRequest) {
                mailingState.sendRequest.abort();
                mailingState.sendRequest = null;
            }

            $('#logId').val(0);
            $('#onlinelog').empty();
            resetCounters();
            resetStatusMessage();
            setIdleUiState();
            $('#timer2').text('00:00:00');
            $('#leftsend').text(0);
            $('.progress-bar').css('width', '0%');
        }

        function resetCounters() {
            $('#totalsendlog').text(0);
            $('#successful').text(0);
            $('#unsuccessful').text(0);
        }

        function setRunningUiState() {
            $('#stopsendout').removeClass('disabled').prop('disabled', false);
            $('#sendout').addClass('disabled').prop('disabled', true);
            $('#process').removeClass().addClass('showprocess');
        }

        function setIdleUiState() {
            $('#stopsendout').addClass('disabled').prop('disabled', true);
            $('#sendout').removeClass('disabled').prop('disabled', false);
            $('#process').removeClass();
        }

        function showStatusMessage(message) {
            $('#divStatus')
                .removeClass('success')
                .addClass('error')
                .html(escapeHtml(message));
        }

        function showSuccessMessage(message) {
            $('#divStatus')
                .removeClass('error')
                .addClass('success')
                .html(escapeHtml(message));
        }

        function resetStatusMessage() {
            $('#divStatus')
                .removeClass('error success')
                .empty();
        }

        function extractErrorMessage(jqXHR, textStatus, errorThrown) {
            const responseJson = jqXHR.responseJSON || {};
            return responseJson.errors || errorThrown || textStatus || serverErrorText;
        }

        function escapeHtml(value) {
            return $('<div>').text(value ?? '').html();
        }
    </script>

@endsection

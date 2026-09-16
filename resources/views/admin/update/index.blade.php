@extends('admin.app')

@section('title', $title)

@section('css')


@endsection

@section('content')

    <!-- Main content -->
    <section class="content">

        <div class="container-fluid">
            <div class="row">
                <div class="col-12">

                    @if (!empty($button_update))
                        <div id="btn_refresh">
                            <a id="start_update" class="btn btn-outline btn-default">
                                <i class="fa fa-sync-alt"></i> {!! $button_update !!}
                            </a>
                        </div>
                    @endif

                    @if (!empty($msg_no_update))
                        <a class="btn btn-outline btn-default" disabled>
                            <i class="fa fa-sync-alt"></i> {!! $msg_no_update !!}
                        </a>
                    @endif

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

    <script>

        $(function () {
            $(document).on("click", "#start_update", function () {
                renderUpdateProgress();
                runUpdateStep(0);
            });
        });

        const updateSteps = @json($update_steps);
        const buttonUpdateLabel = @json(strip_tags($button_update));
        const ajaxUrl = @json(route('admin.ajax.action'));
        const csrfToken = $('meta[name="csrf-token"]').attr('content');
        const failedToUpdateText = @json(__('frontend.msg.failed_to_update'));
        const startUpdateText = @json(__('frontend.str.start_update'));
        const updateCompletedText = @json(__('frontend.msg.update_completed'));
        let updateResetSent = false;
        let retryAttemptsByStep = {};
        let lastBytesByStep = {};

        function renderUpdateProgress() {
            const $progress = $('<div>', {class: 'progress'}).append(
	                $('<div>', {
	                    id: 'progress_bar',
	                    class: 'progress-bar bg-primary progress-bar-striped',
	                    role: 'progressbar',
	                    'aria-valuenow': 1,
	                    'aria-valuemin': 0,
	                    'aria-valuemax': 100,
	                    style: 'width: 1%; min-width: 42px;'
	                }).text('1%')
	            );
            const $status = $('<p>', {class: 'text-muted', id: 'status_process'}).text(startUpdateText);

            $('#btn_refresh').empty().append($progress).append($status);
            updateResetSent = false;
            retryAttemptsByStep = {};
            lastBytesByStep = {};
        }

        function renderRetryButton(message) {
            const $button = $('<a>', {id: 'start_update', class: 'btn btn-outline btn-default'}).append(
                $('<i>', {class: 'fa fa-sync-alt'})
            ).append(' ' + buttonUpdateLabel);
            const $status = $('<p>', {class: 'text-muted text-danger', id: 'status_process'}).text(message || failedToUpdateText);

            $('#btn_refresh').empty().append($button).append($status);
        }

        function runUpdateStep(index) {
            const step = updateSteps[index];

            if (!step) {
                return;
            }

            $('#status_process').text(step.status);
            const shouldReset = index === 0 && updateResetSent === false;
            updateResetSent = true;

            $.ajax({
                type: 'POST',
                cache: false,
                timeout: 70000,
                url: ajaxUrl,
                headers: {'X-CSRF-TOKEN': csrfToken},
	                data: {
	                    action: 'start_update',
	                    p: step.p,
	                    reset: shouldReset ? 1 : 0,
	                    chunked: 1,
	                },
	                success: function (data) {
	                    if (data && data.result === true) {
	                        setProgressBar(getStepProgress(index, step, data));
	                        $('#status_process').text(data.status || step.status);

                        if (data.done === false) {
                            if (!trackStepRetry(step.p, data)) {
                                renderRetryButton(data.retry_error || data.status || failedToUpdateText);
                                return;
                            }

                            setTimeout(function () {
                                runUpdateStep(index);
                            }, Number(data.retry_after || 250));
                            return;
                        }

                        retryAttemptsByStep[step.p] = 0;
                        lastBytesByStep[step.p] = Number(data.bytes_downloaded || 0);

                        if (step.final === true) {
                            $('#progress_bar').delay(3000).fadeOut();
                            setTimeout(function () {
                                $('#status_process').text(updateCompletedText);
                            }, 3000);
                            return;
                        }

                        runUpdateStep(index + 1);
	                        return;
	                    }

	                    if (shouldRetryDownloadFailure(step.p, data)) {
	                        setTimeout(function () {
	                            runUpdateStep(index);
	                        }, 2000);
	                        return;
	                    }

	                    renderRetryButton((data && (data.status || data.errors)) || failedToUpdateText);
	                },
                error: function (xhr, textStatus, error) {
                    const data = xhr.responseJSON || {};

                    renderRetryButton(data.status || data.errors || error || textStatus || failedToUpdateText);
                }
            });
        }

	        function trackStepRetry(stepName, data) {
            const bytes = Number(data.bytes_downloaded || 0);
            const previousBytes = Number(lastBytesByStep[stepName] || 0);

            if (bytes > previousBytes) {
                lastBytesByStep[stepName] = bytes;
                retryAttemptsByStep[stepName] = 0;
                return true;
            }

            if (data.retry === true) {
                retryAttemptsByStep[stepName] = Number(retryAttemptsByStep[stepName] || 0) + 1;
                return retryAttemptsByStep[stepName] <= 10;
            }

            return true;
		        }

	        function shouldRetryDownloadFailure(stepName, data) {
	            if (!String(stepName).startsWith('download_')) {
	                return false;
	            }

	            retryAttemptsByStep[stepName] = Number(retryAttemptsByStep[stepName] || 0) + 1;

	            if (retryAttemptsByStep[stepName] > 10) {
	                return false;
	            }

	            $('#status_process').text(((data && (data.status || data.errors)) || failedToUpdateText) + ' — retry ' + retryAttemptsByStep[stepName] + '/10');

	            return true;
	        }

	        function setProgressBar(progress) {
	            const percent = Math.max(1, Math.min(100, Math.round(Number(progress) || 1)));

	            $('#progress_bar')
	                .css('width', percent + '%')
	                .attr('aria-valuenow', percent)
	                .text(percent + '%');
	        }

	        function getStepProgress(index, step, data) {
            const previousProgress = index === 0 ? 1 : Number(updateSteps[index - 1].progress || 1);
            const targetProgress = Number(step.progress || previousProgress);
            const fileProgress = Number(data.file_progress);

            if (data.done === false && Number.isFinite(fileProgress)) {
                const stepRange = targetProgress - previousProgress;

                return Math.max(
                    previousProgress,
                    Math.min(targetProgress, previousProgress + (stepRange * fileProgress / 100))
                );
            }

            return targetProgress;
        }

    </script>

@endsection

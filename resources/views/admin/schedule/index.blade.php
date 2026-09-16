@extends('admin.app')

@section('title', $title)

@section('css')

{!! Html::style('/plugins/fullcalendar/main.css') !!}
{!! Html::style('/plugins/sweetalert2/sweetalert2.min.css') !!}

<style>

    .fc-time-grid .fc-event {
        overflow: auto;
    }

    .fc-day-today {
        background-color: #0f0 !important;
    }

    #calendar a,
    #calendar a:hover,
    #calendar a:focus,
    #calendar a:active {
        color: #00008B !important;
    }

    #calendar .fc-event-main,
    #calendar .fc-event-main:hover,
    #calendar .fc-event-main:focus,
    #calendar .fc-event-main:active,
    #calendar .fc-event-time,
    #calendar .fc-event-time:hover,
    #calendar .fc-event-time:focus,
    #calendar .fc-event-time:active,
    #calendar .fc-event-title,
    #calendar .fc-event-title:hover,
    #calendar .fc-event-title:focus,
    #calendar .fc-event-title:active,
    #calendar .fc-daygrid-event,
    #calendar .fc-daygrid-event:hover,
    #calendar .fc-daygrid-event:focus,
    #calendar .fc-daygrid-event:active,
    #calendar .fc-timegrid-event,
    #calendar .fc-timegrid-event:hover,
    #calendar .fc-timegrid-event:focus,
    #calendar .fc-timegrid-event:active {
        color: #00008B !important;
    }

    #calendar .calendar-event-content,
    #calendar .calendar-event-actions,
    #calendar .calendar-event-title {
        color: #00008B !important;
    }

    .fc-day-today a,
    .fc-day-today a:hover,
    .fc-day-today a:focus,
    .fc-day-today a:active,
    .fc-day-today .calendar-event-content,
    .fc-day-today .calendar-event-actions,
    .fc-day-today .calendar-event-title {
        color: #00008B !important;
    }

    #calendar .fc-daygrid-event,
    #calendar .fc-timegrid-event {
        max-width: 100%;
        overflow: hidden;
        white-space: normal;
    }

    #calendar .fc-daygrid-event .fc-event-main,
    #calendar .fc-daygrid-event .fc-event-main-frame,
    #calendar .fc-timegrid-event .fc-event-main {
        max-width: 100%;
        min-width: 0;
        overflow: hidden;
    }

    #calendar .calendar-event-content {
        align-items: flex-start;
        display: flex;
        gap: 3px;
        max-width: 100%;
        min-width: 0;
        overflow: hidden;
        white-space: normal;
    }

    #calendar .calendar-event-dot {
        background: #00008B;
        border-radius: 50%;
        flex: 0 0 0.55rem;
        height: 0.55rem;
        margin-top: 0.45rem;
        width: 0.55rem;
    }

    #calendar .calendar-event-time {
        flex: 0 0 auto;
        font-weight: 600;
        white-space: nowrap;
    }

    #calendar .calendar-event-title {
        flex: 1 1 auto;
        min-width: 0;
        overflow-wrap: anywhere;
        white-space: normal;
        word-break: break-word;
    }

    #calendar .calendar-event-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
        margin-top: 4px;
    }

    #calendar .calendar-event-actions .btn {
        line-height: 1;
        padding: 0.16rem 0.35rem;
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
                            <a href="{{ route('admin.schedule.create') }}" class="btn btn-info btn-sm pull-left">
                                <span class="fa fa-plus"> &nbsp;</span> {{ __('frontend.str.add_schedule') }}
                            </a>
                        </div>

                        <div id='calendar'></div>

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

{!! Html::script('/plugins/sweetalert2/sweetalert2.min.js') !!}
{!! Html::script('/plugins/moment/moment.min.js') !!}
{!! Html::script('/plugins/fullcalendar/main.js') !!}

{{ app()->getLocale() !== 'en' ? Html::script('/plugins/fullcalendar/locales/' . app()->getLocale() . '.js') : '' }}

<script>
    document.addEventListener('DOMContentLoaded', function() {
        let initialTimeZone = 'UTC';
        let calendarEl = document.getElementById('calendar');

        function escapeHtml(value) {
            return $('<div>').text(value ?? '').html();
        }

        function formatEventTime(event) {
            if (!event.start) {
                return '';
            }

            let hours = String(event.start.getUTCHours()).padStart(2, '0');
            let minutes = String(event.start.getUTCMinutes()).padStart(2, '0');

            return hours + ':' + minutes;
        }

        function renderCalendarEvent(event, showActions = false) {
            let eventTitle = escapeHtml(event.title);
            let eventTime = formatEventTime(event);
            let content = '<div class="calendar-event-content">' +
                '<span class="calendar-event-dot"></span>' +
                '<span class="calendar-event-time">' + eventTime + '</span>' +
                '<span class="calendar-event-title">' + eventTitle + '</span>' +
                '</div>';

            if (!showActions) {
                return content;
            }

            return content + '<div class="calendar-event-actions">' +
                '<a href="{{ url("schedule/edit") }}/' + event.id + '" class="btn btn-info btn-xs" title="{{ __('frontend.str.edit') }}"><i class="fa fa-edit"></i></a>' +
                '<button type="button" class="btn btn-danger btn-xs delete-event" data-id="' + event.id + '" title="{{ __('frontend.str.remove') }}"><i class="fa fa-trash"></i></button>' +
                '</div>';
        }

        let calendar = new FullCalendar.Calendar(calendarEl, {
            eventClassNames: ['calendar-event'],
            eventContent: function(info) {
                return { html: renderCalendarEvent(info.event) };
            },
            eventMouseEnter: function(info) {
                info.el.innerHTML = renderCalendarEvent(info.event, true);
            },
            eventMouseLeave: function(info) {
                info.el.innerHTML = renderCalendarEvent(info.event);
            },
            timeZone: initialTimeZone,
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
            },
            navLinks: true, // can click day/week names to navigate views
            editable: true,
            selectable: true,
            dayMaxEvents: true, // allow "more" link when too many events
            displayEventTime: false,
            events: "{{ route('admin.schedule.list') }}",

            @if(app()->getLocale()!= 'en') locale: '{{ app()->getLocale() }}', @endif

            eventTimeFormat: { hour: 'numeric', minute: '2-digit', timeZoneName: 'short' }
        });

        calendar.render();

        $('#calendar').on('click', '.delete-event', function (event) {
            event.preventDefault();
            event.stopPropagation();

            let eventId = String($(this).attr('data-id'));

            Swal.fire({
                title: "{{ __('frontend.str.confirm_remove') }}",
                showCancelButton: true,
                confirmButtonText: "{{ __('frontend.msg.yes_remove') }}",
                cancelButtonText: "{{ __('frontend.str.cancel') }}",
            }).then((result) => {
                if (!result.isConfirmed) {
                    return;
                }

                $.ajax({
                    url: '{{ url('schedule/destroy') }}/' + eventId,
                    type: 'POST',
                    data: {_method: 'DELETE'},
                    headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                    success: function () {
                        let calendarEvent = calendar.getEventById(eventId);

                        if (calendarEvent) {
                            calendarEvent.remove();
                        } else {
                            calendar.refetchEvents();
                        }

                        Swal.fire("{{ __('frontend.msg.done') }}", "{{ __('frontend.msg.data_successfully_deleted') }}", 'success');
                    },
                    error: function (xhr, ajaxOptions, thrownError) {
                        Swal.fire("{{ __('frontend.msg.error_deleting') }}", "{{ __('frontend.msg.try_again') }}", 'error');
                        console.log(ajaxOptions);
                        console.log(thrownError);
                    }
                });
            });
        });
    });
</script>
@endsection

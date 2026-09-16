@extends('admin.app')

@section('title', $title)

@section('css')

<link rel="stylesheet" href="{{ asset('plugins/sweetalert2/sweetalert2.min.css') }}">

<style>

    .app-content-header h1 {
        font-size: 1.8rem;
        font-weight: 400;
    }

    .app-content-header .callout-info {
        background-color: var(--bs-body-bg);
        border: 1px solid var(--bs-border-color);
        border-left: 5px solid #117a8b;
        border-radius: .25rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, .15);
        color: var(--bs-body-color);
    }

    .schedule-page {
        container-type: inline-size;
    }

    .schedule-page .card-body {
        padding: 1.25rem;
    }

    .schedule-actions {
        margin-bottom: 2rem;
    }

    .schedule-actions .btn {
        --bs-btn-color: #fff;
        --bs-btn-bg: #17a2b8;
        --bs-btn-border-color: #17a2b8;
        --bs-btn-hover-color: #fff;
        --bs-btn-hover-bg: #138496;
        --bs-btn-hover-border-color: #117a8b;
        --bs-btn-active-color: #fff;
        --bs-btn-active-bg: #117a8b;
        --bs-btn-active-border-color: #10707f;
    }

    #calendar {
        --fc-border-color: var(--bs-border-color);
        --fc-today-bg-color: #0f0;
        --fc-button-bg-color: #2c3e50;
        --fc-button-border-color: #2c3e50;
        --fc-button-hover-bg-color: #1e2b37;
        --fc-button-hover-border-color: #1a252f;
        --fc-button-active-bg-color: #1a252f;
        --fc-button-active-border-color: #151e27;
    }

    #calendar .fc-header-toolbar {
        gap: 1rem;
        margin-bottom: 2.5rem;
        padding: 0 1rem;
    }

    #calendar a {
        text-decoration: none;
    }

    #calendar .fc-col-header-cell-cushion,
    #calendar .fc-daygrid-day-number,
    #calendar .fc-list-day-text,
    #calendar .fc-list-day-side-text {
        color: #00008b;
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
        color: #fff !important;
    }

    #calendar .calendar-event-content,
    #calendar .calendar-event-time,
    #calendar .calendar-event-title {
        color: #fff !important;
    }

    #calendar .calendar-list-event-title,
    #calendar .fc-list-event-time,
    #calendar .fc-list-event-title,
    #calendar .fc-list-event-title a,
    #calendar .fc-list-event .fc-event-title {
        color: var(--bs-body-color) !important;
    }

    #calendar .fc-list-event:hover td {
        background-color: var(--bs-tertiary-bg);
    }

    #calendar .calendar-list-event {
        align-items: center;
        display: flex;
        gap: 0.5rem;
        min-width: 0;
        width: 100%;
    }

    #calendar .calendar-list-event-title {
        flex: 1 1 auto;
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    #calendar .calendar-list-event-actions {
        display: flex;
        flex: 0 0 auto;
        gap: 0.25rem;
        margin-left: auto;
    }

    #calendar .calendar-list-event-actions .btn {
        align-items: center;
        display: inline-flex;
        height: 1.75rem;
        justify-content: center;
        padding: 0;
        width: 1.75rem;
    }

    #calendar .calendar-list-event-actions .btn-outline-primary {
        color: var(--bs-primary) !important;
    }

    #calendar .calendar-list-event-actions .btn-outline-danger {
        color: var(--bs-danger) !important;
    }

    #calendar .calendar-list-event-actions .btn-outline-primary:hover,
    #calendar .calendar-list-event-actions .btn-outline-danger:hover {
        color: #fff !important;
    }

    #calendar .fc-daygrid-event,
    #calendar .fc-timegrid-event {
        max-width: 100%;
        overflow: hidden;
        white-space: nowrap;
    }

    #calendar .fc-daygrid-event .fc-event-main,
    #calendar .fc-daygrid-event .fc-event-main-frame,
    #calendar .fc-timegrid-event .fc-event-main {
        max-width: 100%;
        min-width: 0;
        overflow: hidden;
    }

    #calendar .calendar-event-content {
        align-items: center;
        display: flex;
        font-size: 0.875rem;
        gap: 4px;
        line-height: 1.2;
        max-width: 100%;
        min-width: 0;
        overflow: hidden;
        padding: 1px 3px;
        white-space: nowrap;
    }

    #calendar .calendar-event-dot {
        background: #fff;
        border-radius: 50%;
        flex: 0 0 0.55rem;
        height: 0.55rem;
        width: 0.55rem;
    }

    #calendar .calendar-event-time {
        flex: 0 0 auto;
        font-weight: 600;
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    #calendar .calendar-event-title {
        flex: 1 1 auto;
        min-width: 0;
        overflow: hidden;
        overflow-wrap: normal;
        text-overflow: ellipsis;
        white-space: nowrap;
        word-break: normal;
    }

    #calendar .calendar-event-actions {
        display: flex;
        flex: 0 0 auto;
        gap: 2px;
        margin-left: auto;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.15s ease;
    }

    #calendar .calendar-event:hover .calendar-event-actions,
    #calendar .calendar-event:focus-within .calendar-event-actions {
        opacity: 1;
        pointer-events: auto;
    }

    #calendar .calendar-event-actions .btn {
        align-items: center;
        display: inline-flex;
        font-size: 0.7rem;
        height: 1rem;
        justify-content: center;
        line-height: 1;
        padding: 0;
        width: 1rem;
    }
    @media (hover: none) {
        #calendar .calendar-event-actions {
            opacity: 1;
            pointer-events: auto;
        }
    }

    @container (max-width: 52rem) {
        #calendar .fc-toolbar {
            display: grid;
            grid-template-columns: 1fr auto;
            padding: 0;
        }

        #calendar .fc-toolbar-chunk:nth-child(2) {
            grid-column: 1 / -1;
            grid-row: 1;
            text-align: center;
        }
    }

    @container (max-width: 36rem) {
        #calendar .fc-toolbar {
            align-items: flex-start;
            display: flex;
            flex-direction: column;
            gap: .75rem;
        }

        #calendar .fc-toolbar-chunk:nth-child(2) {
            order: -1;
        }

        #calendar .fc-toolbar-title {
            font-size: 1.25rem;
        }

        #calendar .fc-button {
            font-size: .875rem;
            padding: .3rem .45rem;
        }

        #calendar .fc-view-harness {
            min-height: 20rem;
        }
    }
</style>

@endsection

@section('content')

    <div class="container-fluid schedule-page">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="schedule-actions">
                            <a href="{{ route('admin.schedule.create') }}" class="btn btn-info">
                                <i class="fas fa-plus me-1"></i>
                                {{ __('frontend.str.add_schedule') }}
                            </a>
                        </div>
                        <div id='calendar'></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('js')

<script src="{{ asset('/plugins/sweetalert2/sweetalert2.min.js') }}"></script>
<script src="{{ asset('/plugins/fullcalendar/main.js') }}"></script>

@php($calendarLocale = strtolower(app()->getLocale()))
@if($calendarLocale !== 'en')
    <script src="{{ asset('/plugins/fullcalendar/locales/' . $calendarLocale . '.js') }}"></script>
@endif

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

        function renderCalendarEvent(event, isListView = false) {
            let eventTitle = escapeHtml(event.title);

            if (isListView) {
                let listActions = '<span class="calendar-list-event-actions">' +
                    '<a href="{{ url("schedule/edit") }}/' + event.id + '" class="btn btn-outline-primary btn-sm" title="{{ __('frontend.str.edit') }}"><i class="fas fa-edit"></i></a>' +
                    '<button type="button" class="btn btn-outline-danger btn-sm delete-event" data-id="' + event.id + '" title="{{ __('frontend.str.remove') }}"><i class="fas fa-trash"></i></button>' +
                    '</span>';

                return '<span class="calendar-list-event">' +
                    '<span class="calendar-list-event-title">' + eventTitle + '</span>' +
                    listActions +
                    '</span>';
            }

            let eventTime = formatEventTime(event);
            let actions = '<span class="calendar-event-actions">' +
                '<a href="{{ url("schedule/edit") }}/' + event.id + '" class="btn btn-light btn-sm" title="{{ __('frontend.str.edit') }}"><i class="fas fa-edit"></i></a>' +
                '<button type="button" class="btn btn-danger btn-sm delete-event" data-id="' + event.id + '" title="{{ __('frontend.str.remove') }}"><i class="fas fa-trash"></i></button>' +
                '</span>';

            return '<div class="calendar-event-content">' +
                '<span class="calendar-event-dot"></span>' +
                '<span class="calendar-event-time">' + eventTime + '</span>' +
                '<span class="calendar-event-title">' + eventTitle + '</span>' +
                actions +
                '</div>';
        }

        let calendar = new FullCalendar.Calendar(calendarEl, {
            eventClassNames: ['calendar-event'],
            eventContent: function(info) {
                return { html: renderCalendarEvent(info.event, info.view.type.startsWith('list')) };
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

            locale: @json($calendarLocale),

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

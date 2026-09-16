# Admin UI assets

The AdminLTE 4 migration follows
[janickiy/phpnewsletter_8 at 1173270](https://github.com/janickiy/phpnewsletter_8/tree/1173270a0ad493eb7dd1071e404111d0c3efb69a).
The reference's compiled assets are vendored in this repository, so deployment
does not need Node.js, a CDN or an npm build.

| Component | Version | Location under `public/` |
| --- | --- | --- |
| AdminLTE | 4.1.0 | `vendor/adminlte4` |
| Bootstrap JS bundle | 5.3.8 | `vendor/bootstrap5` |
| Font Awesome | 7.3.0 | `vendor/fontawesome7` |
| DataTables | 2.3.8 | `vendor/datatables*` |
| DataTables Buttons | 3.2.6 | `vendor/datatables-buttons*` |
| DataTables Responsive | 3.0.8 | `vendor/datatables-responsive*` |
| Summernote (Bootstrap 5 build) | 0.9.1 | `plugins/summernote/summernote-bs5.min.*` |
| FullCalendar | 6.1.21 | `plugins/fullcalendar` |
| pdfmake | 0.3.11 | `plugins/pdfmake` |

AdminLTE CSS includes Bootstrap styles. Do not add a second Bootstrap stylesheet
to the admin layout. FullCalendar 6's standard bundle includes its own CSS.
The admin still uses jQuery for DataTables, Summernote and existing application
handlers; navigation, tabs, dropdowns and modals use Bootstrap 5 / AdminLTE 4 APIs.

Custom styling and branding live in `public/assets/css/admin.css` and
`public/assets/img`. Their URLs include modification times for cache invalidation.
The independent public subscription and installer layouts still use their
existing styles in `public/dist` and `public/plugins/bootstrap`; keep those files
until those layouts are migrated separately.

When updating vendored libraries, replace the matching CSS, JavaScript, fonts
and locales together, preserve upstream license notices, and update this table.
Verify the login, sidebar, DataTables actions and export controls, settings tabs,
Summernote dialogs/HTML editing, and schedule calendar/date range picker.
Run the isolated Docker test command documented in the main README.

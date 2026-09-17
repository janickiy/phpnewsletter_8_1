@extends('admin.app')

@section('title', $title)

@section('css')
    <style>
        .phpinfo-table {
            table-layout: fixed;
        }

        .phpinfo-table th:first-child {
            width: 35%;
        }

        .phpinfo-table th,
        .phpinfo-table td {
            overflow-wrap: anywhere;
        }

        .phpinfo-table dd:last-child {
            margin-bottom: 0;
        }
    </style>
@endsection

@section('content')

    <div class="container-fluid">
        <div class="row">
            <div class="col-12">

                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fa-brands fa-php me-2" aria-hidden="true"></i>{{ $title }}
                        </h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="accordion accordion-flush" id="phpinfo-accordion">
                            @foreach($phpinfo as $section => $values)
                                <div class="accordion-item">
                                    <h4 class="accordion-header" id="phpinfo-heading-{{ $loop->index }}">
                                        <button class="accordion-button {{ $loop->first ? '' : 'collapsed' }}" type="button"
                                                data-bs-toggle="collapse" data-bs-target="#phpinfo-section-{{ $loop->index }}"
                                                aria-expanded="{{ $loop->first ? 'true' : 'false' }}"
                                                aria-controls="phpinfo-section-{{ $loop->index }}">
                                            {{ html_entity_decode($section, ENT_QUOTES | ENT_HTML5, 'UTF-8') }}
                                        </button>
                                    </h4>
                                    <div id="phpinfo-section-{{ $loop->index }}" class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}"
                                         aria-labelledby="phpinfo-heading-{{ $loop->index }}">
                                        <div class="accordion-body p-0">
                                            <div class="table-responsive">
                                                <table class="table table-striped table-hover align-middle mb-0 phpinfo-table">
                                                    <thead class="table-light">
                                                    <tr>
                                                        <th scope="col" class="px-3 py-2">{{ __('frontend.str.name') }}</th>
                                                        <th scope="col" class="px-3 py-2">{{ __('frontend.str.value') }}</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    @foreach($values as $name => $value)
                                                        <tr>
                                                            <th scope="row" class="px-3 py-3 fw-normal">{{ html_entity_decode($name, ENT_QUOTES | ENT_HTML5, 'UTF-8') }}</th>
                                                            <td class="px-3 py-3">
                                                                @if(is_array($value))
                                                                    <dl class="mb-0">
                                                                        @foreach($value as $scope => $setting)
                                                                            <dt class="small text-body-secondary">{{ $scope }}</dt>
                                                                            <dd>{{ html_entity_decode((string) $setting, ENT_QUOTES | ENT_HTML5, 'UTF-8') }}</dd>
                                                                        @endforeach
                                                                    </dl>
                                                                @else
                                                                    {{ html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8') }}
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <!-- /.card -->
            </div>
            <!-- /.col -->
        </div>
        <!-- /.row -->
    </div>
    <!-- /.container-fluid -->

@endsection

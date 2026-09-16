@extends('admin.app')

@section('title', $title)

@section('breadcrumbs')
    <li class="breadcrumb-item">
        <a href="{{ route('admin.smtp.index') }}">{{ __('frontend.title.smtp_index') }}</a>
    </li>
    <li class="breadcrumb-item active">{{ $row->host }}</li>
@endsection

@section('content')

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-server mr-1"></i>
                                {{ $row->host }}
                            </h3>

                            <div class="card-tools">
                                <a class="btn btn-tool"
                                   href="{{ route('admin.smtp.edit', ['id' => $row->id]) }}"
                                   title="{{ __('frontend.str.edit') }}">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a class="btn btn-tool"
                                   href="{{ route('admin.smtp.index') }}"
                                   title="{{ __('frontend.form.back') }}">
                                    <i class="fas fa-arrow-left"></i>
                                </a>
                            </div>
                        </div>

                        <div class="card-body p-0">
                            <table class="table table-striped mb-0">
                                <tbody>
                                <tr>
                                    <th style="width: 260px;">{{ __('frontend.str.smtp_server') }}</th>
                                    <td>{{ $row->host }}</td>
                                </tr>
                                <tr>
                                    <th>E-mail</th>
                                    <td>{{ $row->email }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('frontend.str.login') }}</th>
                                    <td>{{ $row->username }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('frontend.str.port') }}</th>
                                    <td>{{ $row->port }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('frontend.str.connection_timeout') }}</th>
                                    <td>{{ $row->timeout }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('frontend.str.connection') }}</th>
                                    <td>{{ $row->secure ?: __('frontend.str.no') }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('frontend.str.authentication_method') }}</th>
                                    <td>{{ $row->authentication ?: __('frontend.str.no') }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('frontend.str.status') }}</th>
                                    <td>
                                        @if((int) $row->active === 1)
                                            <span class="badge badge-success">{{ __('frontend.str.yes') }}</span>
                                        @else
                                            <span class="badge badge-secondary">{{ __('frontend.str.no') }}</span>
                                        @endif
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="card-footer">
                            <a class="btn btn-primary" href="{{ route('admin.smtp.edit', ['id' => $row->id]) }}">
                                <i class="fas fa-edit mr-1"></i>
                                {{ __('frontend.str.edit') }}
                            </a>
                            <a class="btn btn-default bg-white float-right" href="{{ route('admin.smtp.index') }}">
                                <i class="fas fa-arrow-left mr-1"></i>
                                {{ __('frontend.form.back') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

@endsection

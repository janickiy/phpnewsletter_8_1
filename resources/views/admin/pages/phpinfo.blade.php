@extends('admin.app')

@section('title', $title)

@section('css')
    <link rel="stylesheet" href="{{ asset('plugins/jquery-treeview/jquery.treeview.css') }}">
    <style>
        .phpinfo-page #tree {
            overflow-wrap: anywhere;
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid phpinfo-page">
        <div class="row">
            <div class="col-12">
                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-info-circle me-1" aria-hidden="true"></i>
                            {{ $title }}
                        </h3>
                    </div>
                    <div class="card-body">
                        <div id="tree" class="pb-3">
                            @include('admin.pages.partials.phpinfo_tree', ['items' => $phpinfo, 'root' => true])
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script src="{{ asset('plugins/jquery-treeview/jquery.treeview.js') }}"></script>
    <script>
        $(function () {
            $('#tree-checkbox').treeview({
                collapsed: true,
                animated: 'medium',
                unique: false
            });
        });
    </script>
@endsection

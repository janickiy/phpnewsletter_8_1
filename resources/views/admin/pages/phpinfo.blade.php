@extends('admin.app')

@section('title', $title)

@section('css')

    <link rel="stylesheet" href="{{ asset('/plugins/jquery-treeview/jquery.treeview.css') }}">

@endsection

@section('content')

    <div class="container-fluid">
        <div class="row">
            <div class="col-12">

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fa-brands fa-php me-2" aria-hidden="true"></i>{{ $title }}
                        </h3>
                    </div>
                    <!-- /.card-header -->
                    <div class="card-body">

                        <div id="tree" style="padding-bottom: 15px;">

                            {!! StringHelper::tree($phpinfo) !!}

                        </div>

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

@endsection

@section('js')

    <script src="{{ asset('/plugins/jquery-treeview/jquery.treeview.js') }}"></script>

    <script>
        $(function () {
            $('.tree-checkbox').treeview({
                collapsed: true,
                animated: 'medium',
                unique: false
            });
            $('#buttom_json').on('click', function () {
                if ($(this).attr('data-tree') == 'true') {
                    $(this).attr('data-tree', "false");
                    $('#tree').hide();
                    $('#json').show();
                } else {
                    $(this).attr('data-tree', "true");
                    $('#json').hide();
                    $('#tree').show();
                }
            });
        });
    </script>

@endsection

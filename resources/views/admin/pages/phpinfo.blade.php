@extends('admin.app')

@section('title', $title)

@section('css')

    {!! Html::style('/plugins/jquery-treeview/jquery.treeview.css') !!}

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

    </section>
    <!-- /.content -->

@endsection

@section('js')

    {!! Html::script('/plugins/jquery-treeview/jquery.treeview.js') !!}

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

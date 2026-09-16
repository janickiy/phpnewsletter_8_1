<div class="form-group">
    <div id="resultSub"></div>

    <form method="POST" action="#" accept-charset="UTF-8" id="addsub" autocomplete="off">
        <div class="form-group">
            <label for="name">{{ trans('frontend.str.name') }}</label>
            <input class="form-control" autocomplete="off" name="name" type="text" id="name">
        </div>

        <div class="form-group">
            <label for="email">E-mail</label>
            <input class="form-control" autocomplete="off" name="email" type="text" id="email">

            <div id="error-email" class="text-danger"></div>
        </div>

        <button id="sub" class="btn btn-primary" type="button">{{ trans('frontend.str.subscribe') }}</button>
    </form>

</div>

<div class="mb-3">
    <div id="resultSub"></div>

    <form method="POST" action="#" accept-charset="UTF-8" id="addsub" autocomplete="off">
        <div class="mb-3">
            <label for="name" class="form-label">{{ trans('frontend.str.name') }}</label>
            <input class="form-control" autocomplete="off" name="name" type="text" id="name">
        </div>

        <div class="mb-3">
            <label for="email" class="form-label">E-mail</label>
            <input class="form-control" autocomplete="off" name="email" type="text" id="email">

            <div id="error-email" class="text-danger"></div>
        </div>

        <button id="sub" class="btn btn-primary" type="button">{{ trans('frontend.str.subscribe') }}</button>
    </form>

</div>

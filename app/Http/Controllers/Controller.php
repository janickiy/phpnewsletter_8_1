<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    /**
     * Provide the shared Laravel authorization and validation traits to all controllers.
     */
    use AuthorizesRequests, ValidatesRequests;
}

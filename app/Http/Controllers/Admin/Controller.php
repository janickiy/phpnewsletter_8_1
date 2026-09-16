<?php

namespace App\Http\Controllers\Admin;

class Controller extends \App\Http\Controllers\Controller
{
    /**
     * Require authenticated access for all admin controllers that extend this base class.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }
}

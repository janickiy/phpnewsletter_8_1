<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_login_route_is_registered(): void
    {
        $this->assertTrue(Route::has('login'));
    }
}

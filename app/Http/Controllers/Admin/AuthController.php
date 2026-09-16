<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\Auth\LoginRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Restrict authentication routes to guests except for logout.
     */
    public function __construct()
    {
        $this->middleware('guest:web')->except('logout');
    }

    /**
     * Show the administrator login form.
     *
     * @return View
     */
    public function showLoginForm(): View
    {
        return view('admin.login', [
            'title' => __('frontend.str.auth'),
        ]);
    }

    /**
     * Authenticate an administrator and start a fresh session on success.
     *
     * @param LoginRequest $request
     * @return RedirectResponse
     */
    public function login(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->safe()->only(['login', 'password']);
        $remember = $request->boolean('remember');

        if (Auth::guard('web')->attempt($credentials, $remember)) {
            $request->session()->regenerate();

            return redirect()->intended(route('admin.dashboard.index'));
        }

        return back()
            ->withErrors([
                'login' => __('auth.failed'),
            ])
            ->withInput($request->only('login', 'remember'));
    }

    /**
     * Redirect authenticated administrators to the dashboard landing page.
     *
     * @param Request $request
     * @param mixed $user
     * @return RedirectResponse
     */
    protected function authenticated(Request $request, mixed $user): RedirectResponse
    {
        return to_route('admin.dashboard.index');
    }

    /**
     * Log out the current administrator and invalidate the session.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('login');
    }
}

<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\AuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;
use Inertia\Inertia;

class AuthController extends Controller
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }
    public function index(): Response
    {
        return Inertia::render('Auth/Login', [
            'status' => session('status')
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean']
        ]);

        $this->authService->login($credentials, $request->ip());

        $request->session()->regenerate();

        return redirect()->intended('/dashboard');
    }

    public function forgotPassword(): Response
    {
        return Inertia::render('Auth/ForgotPassword', [
            'status' => session('status')
        ]);
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email|exists:users,email'
        ], [
            'email.exists' => 'Alamat email tidak terdaftar di sistem kami.'
        ]);

        $this->authService->sendResetLink($request->only('email'));

        return back()->with('status', 'We have sent a link to reset your password to your email address. Please check your inbox/spam folder.');
    }

    public function showResetPassword(Request $request, string $token): Response
    {
        return Inertia::render('Auth/ResetPassword', [
            'token' => $token,
            'email' => $request->email
        ]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email|exists:users,email',
            'password' => 'required|string|min:8|confirmed', // Wajib ada password_confirmation di Vue
        ]);

        $this->authService->resetPassword(
            $request->only('email', 'password', 'password_confirmation', 'token')
        );

        return redirect()->route('login')->with('status', 'A new password has been set. Please log in using your new password.!');
    }
}

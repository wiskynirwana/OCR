<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\ResetPasswordCode;
use App\Services\VerificationCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    // kirim kode reset 6 digit ke email user
    public function store(Request $request, VerificationCodeService $codes): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
        ]);

        $user = User::where('email', $request->email)->first();

        // pesan sukses tetap sama walau akun gak ada, biar email terdaftar gak bisa ditebak
        if ($user) {
            $code = $codes->generate('password_reset', $user->email);
            $user->notify(new ResetPasswordCode($code));
        }

        return redirect()->route('password.reset')
            ->with('email', $request->email)
            ->with('status', 'Kode verifikasi telah dikirim ke email Anda.');
    }
}

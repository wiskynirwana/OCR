<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\VerificationCodeService;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VerifyEmailController extends Controller
{
    /**
     * Verifikasi email lewat kode 6 digit (menggantikan link bertanda tangan).
     */
    public function __invoke(Request $request, VerificationCodeService $codes): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        $request->validate([
            'code' => ['required', 'digits:6'],
        ], [
            'code.required' => 'Kode verifikasi wajib diisi.',
            'code.digits' => 'Kode verifikasi harus 6 digit angka.',
        ]);

        if (!$codes->verify('email_verify', $user->email, $request->code)) {
            return back()->withErrors(['code' => 'Kode salah atau sudah kadaluarsa. Silakan minta kode baru.']);
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }
}

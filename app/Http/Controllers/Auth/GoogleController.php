<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')
            ->with(['prompt' => 'select_account'])
            ->redirect();
    }

    public function callback()
    {
        if (request()->has('error')) {
            Log::warning('Google OAuth ditolak', ['error' => request('error')]);

            return redirect()->route('login')
                ->with('error', 'Login Google dibatalkan atau akun tidak diizinkan.');
        }

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            Log::error('Google OAuth callback gagal', [
                'message' => $e->getMessage(),
                'class'   => get_class($e),
            ]);

            return redirect()->route('login')
                ->with('error', $e->getMessage());
        }

        $user = User::firstOrNew(['email' => $googleUser->getEmail()]);

        if (!$user->exists) {
            $user->name = $googleUser->getName() ?: $googleUser->getNickname() ?: 'Pengguna';
            $user->password = Hash::make(Str::random(32));
            $user->email_verified_at = now();
        }

        $user->google_id = $googleUser->getId();
        $user->save();

        Auth::login($user, true);

        return redirect()->intended(route('dashboard'));
    }
}

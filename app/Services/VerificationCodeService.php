<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

// Kode verifikasi 6 digit, disimpan di cache 10 menit dalam bentuk hash.
class VerificationCodeService
{
    private const TTL_MINUTES = 10;
    private const MAX_ATTEMPTS = 5;

    public function generate(string $purpose, string $email): string
    {
        $code = (string) random_int(100000, 999999);

        Cache::put($this->key($purpose, $email), [
            'hash' => Hash::make($code),
            'attempts' => 0,
        ], now()->addMinutes(self::TTL_MINUTES));

        return $code;
    }

    public function verify(string $purpose, string $email, string $code): bool
    {
        $key = $this->key($purpose, $email);
        $entry = Cache::get($key);

        if (!$entry) {
            return false;
        }

        // batasi percobaan biar 6 digit gak bisa di-brute-force
        if ($entry['attempts'] >= self::MAX_ATTEMPTS) {
            Cache::forget($key);
            return false;
        }

        if (!Hash::check($code, $entry['hash'])) {
            $entry['attempts']++;
            Cache::put($key, $entry, now()->addMinutes(self::TTL_MINUTES));
            return false;
        }

        Cache::forget($key); // sekali pakai
        return true;
    }

    private function key(string $purpose, string $email): string
    {
        return "verification_code:{$purpose}:" . strtolower($email);
    }
}

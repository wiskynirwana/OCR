<x-guest-layout>
    <div class="mb-4 text-sm text-ink-soft">
        Lupa password? Tidak masalah. Masukkan alamat email Anda, kami akan mengirimkan
        kode verifikasi 6 digit untuk mengatur ulang password Anda.
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <!-- Alamat Email -->
        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between mt-4">
            <a href="{{ route('login') }}" class="inline-flex items-center gap-1 text-sm text-ink-soft hover:text-ink underline rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-pine/40">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
                Kembali ke halaman masuk
            </a>

            <x-primary-button>
                Kirim Kode
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>

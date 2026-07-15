<x-guest-layout>
    <div class="mb-4 text-sm text-ink-soft">
        Masukkan kode verifikasi 6 digit yang telah dikirim ke email Anda,
        lalu buat password baru.
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.store') }}">
        @csrf

        <!-- Alamat Email -->
        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email', $email ?? '')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Kode Verifikasi -->
        <div class="mt-4">
            <x-input-label for="code" value="Kode Verifikasi" />
            <x-text-input id="code" class="block mt-1 w-full tracking-[0.5em] text-center font-semibold" type="text" name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" placeholder="••••••" required autofocus />
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
        </div>

        <!-- Password Baru -->
        <div class="mt-4">
            <x-input-label for="password" value="Password Baru" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Konfirmasi Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" value="Konfirmasi Password" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                                type="password"
                                name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between mt-4">
            <a href="{{ route('password.request') }}" class="inline-flex items-center gap-1 text-sm text-ink-soft hover:text-ink underline rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-pine/40">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
                Kembali
            </a>

            <x-primary-button>
                Ubah Password
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>

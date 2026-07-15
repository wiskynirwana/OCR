<x-guest-layout>
    <div class="mb-4 text-sm text-ink-soft">
        Terima kasih sudah mendaftar! Kami telah mengirimkan kode verifikasi 6 digit
        ke email Anda. Masukkan kode tersebut untuk memverifikasi alamat email Anda.
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 font-medium text-sm text-pine">
            Kode verifikasi baru telah dikirim ke alamat email Anda.
        </div>
    @endif

    <form method="POST" action="{{ route('verification.verify') }}">
        @csrf

        <!-- Kode Verifikasi -->
        <div>
            <x-input-label for="code" value="Kode Verifikasi" />
            <x-text-input id="code" class="block mt-1 w-full tracking-[0.5em] text-center font-semibold" type="text" name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" placeholder="••••••" required autofocus />
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-primary-button class="w-full justify-center">
                Verifikasi
            </x-primary-button>
        </div>
    </form>

    <div class="mt-4 flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <button type="submit" class="underline text-sm text-ink-soft hover:text-ink rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-pine/40">
                Kirim ulang kode
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="underline text-sm text-ink-soft hover:text-ink rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-pine/40">
                Keluar
            </button>
        </form>
    </div>
</x-guest-layout>

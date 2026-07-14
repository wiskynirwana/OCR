<section class="space-y-6">
    <header>
        <h2 class="text-lg font-medium text-ink">
            Hapus Akun
        </h2>

        <p class="mt-1 text-sm text-ink-soft">
            Setelah akun dihapus, semua data dan dokumen di dalamnya akan terhapus permanen. Sebelum menghapus akun, unduh dulu data atau file yang masih ingin Anda simpan.
        </p>
    </header>

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >Hapus Akun</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <h2 class="text-lg font-medium text-ink">
                Yakin ingin menghapus akun Anda?
            </h2>

            <p class="mt-1 text-sm text-ink-soft">
                Setelah akun dihapus, semua data dan dokumen di dalamnya akan terhapus permanen. Masukkan kata sandi Anda untuk mengonfirmasi penghapusan akun secara permanen.
            </p>

            <div class="mt-6">
                <x-input-label for="password" value="Kata Sandi" class="sr-only" />

                <x-text-input
                    id="password"
                    name="password"
                    type="password"
                    class="mt-1 block w-3/4"
                    placeholder="Kata Sandi"
                />

                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    Batal
                </x-secondary-button>

                <x-danger-button class="ms-3">
                    Hapus Akun
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>

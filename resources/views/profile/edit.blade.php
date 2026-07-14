<x-app-layout>
    <div class="min-h-screen bg-paper py-10">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 space-y-5">

            <div>
                <h1 class="text-xl font-semibold tracking-tight text-ink">Profil</h1>
            </div>

            <div class="card p-6">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="card p-6">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div class="card p-6">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>

        </div>
    </div>
</x-app-layout>

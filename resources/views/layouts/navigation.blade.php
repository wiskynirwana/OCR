<nav x-data="{ open: false }" class="bg-surface border-b border-line">
    <div class="w-full px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center gap-8">
                {{-- Logo --}}
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5">
                    <img src="{{ asset('images/logo a.png') }}" alt="As-Syifa" class="block h-8 w-auto object-contain">
                    <span class="text-sm font-semibold tracking-tight text-ink">As-Syifa</span>
                </a>

                {{-- Navigasi --}}
                <div class="hidden sm:flex sm:items-center sm:gap-7 h-full">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">Dashboard</x-nav-link>
                    <x-nav-link :href="route('documents.index')" :active="request()->routeIs('documents.index')">Riwayat</x-nav-link>
                    <x-nav-link :href="route('documents.upload')" :active="request()->routeIs('documents.upload')">Upload</x-nav-link>
                    <x-nav-link :href="route('outputs.download')" :active="request()->routeIs('outputs.download')">Download</x-nav-link>
                </div>
            </div>

            {{-- User --}}
            <div class="hidden sm:flex sm:items-center">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center gap-2 rounded-full border border-line bg-paper px-3 py-1.5 text-sm font-medium text-ink-soft hover:text-ink hover:border-ink-faint/50 focus:outline-none transition">
                            <span class="grid h-6 w-6 place-items-center rounded-full bg-pine-soft text-xs font-semibold text-pine-dark">
                                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                            </span>
                            {{ Auth::user()->name }}
                            <svg class="fill-current h-4 w-4 text-ink-faint" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">{{ __('Profile') }}</x-dropdown-link>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault(); this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            {{-- Hamburger --}}
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-lg text-ink-faint hover:text-ink hover:bg-paper focus:outline-none transition">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- Menu responsif --}}
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden border-t border-line">
        <div class="py-2 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">Dashboard</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('documents.index')" :active="request()->routeIs('documents.index')">Riwayat</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('documents.upload')" :active="request()->routeIs('documents.upload')">Upload</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('outputs.download')" :active="request()->routeIs('outputs.download')">Download</x-responsive-nav-link>
        </div>

        <div class="py-3 border-t border-line">
            <div class="px-4">
                <div class="text-sm font-medium text-ink">{{ Auth::user()->name }}</div>
                <div class="text-sm text-ink-faint">{{ Auth::user()->email }}</div>
            </div>
            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">{{ __('Profile') }}</x-responsive-nav-link>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault(); this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>

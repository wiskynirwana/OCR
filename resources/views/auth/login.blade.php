<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk — Sistem Rename Otomatis Arsip Digital</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://www.google.com/recaptcha/api.js?render={{ env('RECAPTCHA_SITE_KEY') }}"></script>
</head>
<body class="min-h-screen flex items-center justify-center bg-paper font-sans text-ink">

    <div class="w-full max-w-sm px-4">

        {{-- Judul --}}
        <div class="text-center mb-8">
            <img src="{{ asset('images/logo a.png') }}" alt="Logo" class="w-12 h-12 object-contain mx-auto mb-4">
            <h1 class="text-lg font-semibold text-ink">Sistem Rename Otomatis Arsip Digital</h1>
            <p class="text-sm text-ink-soft mt-0.5">Yayasan As-Syifa Al-Khoeriyyah</p>
        </div>

        <div class="bg-surface border border-line rounded-2xl shadow-card p-6">

            @if (session('error'))
                <div class="flex items-center gap-2 p-3 mb-4 text-sm rounded-xl text-danger-dark bg-danger-soft border border-danger/20" role="alert">
                    <span class="w-1.5 h-1.5 rounded-full bg-danger"></span>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            @if (session('status'))
                <div class="flex items-center gap-2 p-3 mb-4 text-sm rounded-xl text-pine-dark bg-pine-soft border border-pine/20" role="alert">
                    <span class="w-1.5 h-1.5 rounded-full bg-pine"></span>
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            {{-- Google --}}
            <a href="{{ route('auth.google') }}"
                class="flex items-center justify-center gap-2.5 w-full py-2.5 rounded-xl text-sm font-medium text-ink border border-line hover:bg-paper transition">
                <svg class="w-4 h-4" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                </svg>
                Masuk dengan Google
            </a>

            <div class="flex items-center gap-3 my-5">
                <div class="flex-1 h-px bg-line"></div>
                <span class="text-xs text-ink-faint">atau email</span>
                <div class="flex-1 h-px bg-line"></div>
            </div>

            <form method="POST" action="{{ route('login') }}" id="loginForm">
                @csrf
                <input type="hidden" name="recaptcha_token" id="recaptchaToken">

                <div class="mb-4">
                    <label class="block text-sm font-medium text-ink mb-1.5">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" required autofocus
                        class="w-full px-3.5 py-2.5 rounded-xl text-sm text-ink bg-paper/50 border {{ $errors->has('email') ? 'border-danger/60' : 'border-line' }} focus:outline-none focus:bg-white focus:ring-2 focus:ring-pine/20 focus:border-pine transition"
                        placeholder="nama@email.com">
                    @error('email')<p class="text-xs mt-1 text-danger-dark">{{ $message }}</p>@enderror
                </div>

                <div class="mb-4">
                    <div class="flex justify-between items-center mb-1.5">
                        <label class="block text-sm font-medium text-ink">Password</label>
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="text-xs text-pine hover:text-pine-dark hover:underline">Lupa password?</a>
                        @endif
                    </div>
                    <input type="password" name="password" required
                        class="w-full px-3.5 py-2.5 rounded-xl text-sm text-ink bg-paper/50 border {{ $errors->has('password') ? 'border-danger/60' : 'border-line' }} focus:outline-none focus:bg-white focus:ring-2 focus:ring-pine/20 focus:border-pine transition"
                        placeholder="••••••••">
                    @error('password')<p class="text-xs mt-1 text-danger-dark">{{ $message }}</p>@enderror
                </div>

                <label class="flex items-center gap-2 mb-5 text-sm text-ink-soft">
                    <input type="checkbox" name="remember" class="rounded border-line text-pine focus:ring-pine/30">
                    Ingat saya
                </label>

                <button type="submit" class="btn-primary w-full">
                    Masuk
                </button>
            </form>

            @if (Route::has('register'))
                <p class="text-center text-sm mt-6 text-ink-soft">
                    Belum punya akun?
                    <a href="{{ route('register') }}" class="font-medium text-pine hover:text-pine-dark hover:underline">Daftar</a>
                </p>
            @endif

        </div>

        <p class="text-center text-xs mt-6 text-ink-faint">
            &copy; {{ date('Y') }} Yayasan As-Syifa Al-Khoeriyyah
        </p>

    </div>

    <script>
        // reCAPTCHA v3 — invisible, jalan otomatis saat submit.
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const form = this;
            if (typeof grecaptcha === 'undefined') { return; }
            e.preventDefault();
            try {
                grecaptcha.ready(function() {
                    grecaptcha.execute('{{ env('RECAPTCHA_SITE_KEY') }}', {action: 'login'})
                        .then(function(token) {
                            document.getElementById('recaptchaToken').value = token;
                            form.submit();
                        })
                        .catch(function() { form.submit(); });
                });
            } catch (err) { form.submit(); }
        });
    </script>

</body>
</html>

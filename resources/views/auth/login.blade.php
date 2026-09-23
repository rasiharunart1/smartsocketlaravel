<x-guest-layout>
    <div style="margin-bottom: 25px;">
        <h1 style="font-size: 22px; font-weight: 800; color: #163c56; margin-bottom: 6px;">
            Selamat Datang
        </h1>
        <p style="font-size: 12px; color: #697586;">
            Masuk untuk memantau dan mengendalikan Smart Socket Anda
        </p>
    </div>

    @if (session('status'))
        <div style="margin-bottom: 15px; color: #159b91; font-size: 13px; font-weight: 600;">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div style="margin-bottom: 15px; padding: 10px; background: #fdf2f2; border: 1px solid #f8b4b4; border-radius: 6px; color: #d83838; font-size: 12px;">
            <ul style="margin: 0; padding-left: 18px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="field">
            <label class="req" for="email">Email</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="Masukkan email" required autofocus autocomplete="username">
        </div>

        <div class="field">
            <label class="req" for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="Masukkan password" required autocomplete="current-password">
        </div>

        <div class="auth-options">
            <label class="remember">
                <input type="checkbox" id="remember" name="remember">
                <span>Ingat saya</span>
            </label>

            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="auth-link">
                    Lupa password?
                </a>
            @endif
        </div>

        <button type="submit" class="auth-btn">
            Masuk
        </button>
    </form>

    <div class="auth-footer">
        Belum punya akun?
        <a href="{{ route('register') }}" class="auth-link">Daftar</a>
    </div>
</x-guest-layout>

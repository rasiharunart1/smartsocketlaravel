<x-guest-layout>
    <div style="margin-bottom: 25px;">
        <h1 style="font-size: 22px; font-weight: 800; color: #163c56; margin-bottom: 6px;">
            Konfirmasi Password
        </h1>
        <p style="font-size: 12px; color: #697586;">
            Ini adalah area aman aplikasi. Silakan konfirmasi password Anda sebelum melanjutkan.
        </p>
    </div>

    @if ($errors->any())
        <div style="margin-bottom: 15px; padding: 10px; background: #fdf2f2; border: 1px solid #f8b4b4; border-radius: 6px; color: #d83838; font-size: 12px;">
            <ul style="margin: 0; padding-left: 18px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <div class="field">
            <label class="req" for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="Masukkan password" required autocomplete="current-password">
        </div>

        <button type="submit" class="auth-btn">
            Konfirmasi
        </button>
    </form>
</x-guest-layout>

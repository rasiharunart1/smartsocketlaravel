<x-guest-layout>
    <div style="margin-bottom: 25px;">
        <h1 style="font-size: 22px; font-weight: 800; color: #163c56; margin-bottom: 6px;">
            Buat Akun
        </h1>
        <p style="font-size: 12px; color: #697586;">
            Daftarkan akun untuk menggunakan Smart Socket
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

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <div class="field">
            <label class="req" for="name">Nama Lengkap</label>
            <input type="text" id="name" name="name" value="{{ old('name') }}" placeholder="Masukkan nama lengkap" required autofocus autocomplete="name">
        </div>

        <div class="field">
            <label class="req" for="email">Email</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="Masukkan email" required autocomplete="username">
        </div>

        <div class="field">
            <label class="req" for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="Masukkan password" required autocomplete="new-password">
        </div>

        <div class="field">
            <label class="req" for="password_confirmation">Konfirmasi Password</label>
            <input type="password" id="password_confirmation" name="password_confirmation" placeholder="Masukkan kembali password" required autocomplete="new-password">
        </div>

        <button type="submit" class="auth-btn">
            Daftar
        </button>
    </form>

    <div class="auth-footer">
        Sudah punya akun?
        <a href="{{ route('login') }}" class="auth-link">Masuk</a>
    </div>
</x-guest-layout>

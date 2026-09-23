<x-guest-layout>
    <div style="margin-bottom: 25px;">
        <h1 style="font-size: 22px; font-weight: 800; color: #163c56; margin-bottom: 6px;">
            Lupa Password
        </h1>
        <p style="font-size: 12px; color: #697586;">
            Masukkan email Anda dan kami akan mengirimkan tautan reset password.
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

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div class="field">
            <label class="req" for="email">Email</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="Masukkan email" required autofocus>
        </div>

        <button type="submit" class="auth-btn">
            Kirim Tautan Reset
        </button>
    </form>

    <div class="auth-footer">
        <a href="{{ route('login') }}" class="auth-link">Kembali ke halaman login</a>
    </div>
</x-guest-layout>

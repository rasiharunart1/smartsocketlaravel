<x-guest-layout>
    <div style="margin-bottom: 25px;">
        <h1 style="font-size: 22px; font-weight: 800; color: #163c56; margin-bottom: 6px;">
            Reset Password
        </h1>
        <p style="font-size: 12px; color: #697586;">
            Masukkan password baru untuk akun Anda.
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

    <form method="POST" action="{{ route('password.store') }}">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="field">
            <label class="req" for="email">Email</label>
            <input type="email" id="email" name="email" value="{{ old('email', $request->email) }}" placeholder="Masukkan email" required autofocus autocomplete="username">
        </div>

        <div class="field">
            <label class="req" for="password">Password Baru</label>
            <input type="password" id="password" name="password" placeholder="Masukkan password baru" required autocomplete="new-password">
        </div>

        <div class="field">
            <label class="req" for="password_confirmation">Konfirmasi Password</label>
            <input type="password" id="password_confirmation" name="password_confirmation" placeholder="Masukkan kembali password baru" required autocomplete="new-password">
        </div>

        <button type="submit" class="auth-btn">
            Reset Password
        </button>
    </form>
</x-guest-layout>

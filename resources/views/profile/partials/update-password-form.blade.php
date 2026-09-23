<div class="panel" style="margin-bottom: 20px;">
    <div class="section-label">Perbarui Password</div>
    <p style="font-size: 12px; color: #64748b; margin: 0 0 18px;">
        Pastikan akun Anda menggunakan password yang panjang dan acak agar tetap aman.
    </p>

    <form method="post" action="{{ route('password.update') }}">
        @csrf
        @method('put')

        <div class="field">
            <label class="req" for="update_password_current_password">Password Saat Ini</label>
            <input type="password" id="update_password_current_password" name="current_password" placeholder="Masukkan password saat ini" autocomplete="current-password">
            @if ($errors->updatePassword->get('current_password'))
                <div style="font-size: 11px; color: #d83838; margin-top: 4px;">{{ $errors->updatePassword->first('current_password') }}</div>
            @endif
        </div>

        <div class="field">
            <label class="req" for="update_password_password">Password Baru</label>
            <input type="password" id="update_password_password" name="password" placeholder="Masukkan password baru" autocomplete="new-password">
            @if ($errors->updatePassword->get('password'))
                <div style="font-size: 11px; color: #d83838; margin-top: 4px;">{{ $errors->updatePassword->first('password') }}</div>
            @endif
        </div>

        <div class="field">
            <label class="req" for="update_password_password_confirmation">Konfirmasi Password</label>
            <input type="password" id="update_password_password_confirmation" name="password_confirmation" placeholder="Masukkan kembali password baru" autocomplete="new-password">
            @if ($errors->updatePassword->get('password_confirmation'))
                <div style="font-size: 11px; color: #d83838; margin-top: 4px;">{{ $errors->updatePassword->first('password_confirmation') }}</div>
            @endif
        </div>

        <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
            <button type="submit" class="filter-btn" style="cursor: pointer; padding: 10px 24px; font-size: 12px; font-weight: 700;">
                Simpan
            </button>

            @if (session('status') === 'password-updated')
                <span style="font-size: 12px; color: #159b91; font-weight: 600;">Tersimpan.</span>
            @endif
        </div>
    </form>
</div>

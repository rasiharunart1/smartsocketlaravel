<div class="panel" style="margin-bottom: 20px;">
    <div class="section-label">Informasi Profil</div>
    <p style="font-size: 12px; color: #64748b; margin: 0 0 18px;">
        Perbarui nama dan alamat email akun Anda.
    </p>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}">
        @csrf
        @method('patch')

        <div class="field">
            <label class="req" for="name">Nama Lengkap</label>
            <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" placeholder="Masukkan nama lengkap" required autofocus autocomplete="name">
            @if ($errors->get('name'))
                <div style="font-size: 11px; color: #d83838; margin-top: 4px;">{{ $errors->first('name') }}</div>
            @endif
        </div>

        <div class="field">
            <label class="req" for="email">Email</label>
            <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" placeholder="Masukkan email" required autocomplete="username">
            @if ($errors->get('email'))
                <div style="font-size: 11px; color: #d83838; margin-top: 4px;">{{ $errors->first('email') }}</div>
            @endif

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div style="margin-top: 10px; font-size: 12px; color: #475569;">
                    Alamat email Anda belum terverifikasi.

                    <button type="submit" form="send-verification" class="auth-link" style="background: none; border: none; cursor: pointer; padding: 0;">
                        Klik di sini untuk mengirim ulang email verifikasi.
                    </button>

                    @if (session('status') === 'verification-link-sent')
                        <div style="margin-top: 6px; color: #159b91; font-weight: 600;">
                            Tautan verifikasi baru telah dikirim ke alamat email Anda.
                        </div>
                    @endif
                </div>
            @endif
        </div>

        <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
            <button type="submit" class="filter-btn" style="cursor: pointer; padding: 10px 24px; font-size: 12px; font-weight: 700;">
                Simpan
            </button>

            @if (session('status') === 'profile-updated')
                <span style="font-size: 12px; color: #159b91; font-weight: 600;">Tersimpan.</span>
            @endif
        </div>
    </form>
</div>

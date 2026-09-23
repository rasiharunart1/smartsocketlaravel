<x-guest-layout>
    <div style="margin-bottom: 25px;">
        <h1 style="font-size: 22px; font-weight: 800; color: #163c56; margin-bottom: 6px;">
            Verifikasi Email
        </h1>
        <p style="font-size: 12px; color: #697586;">
            Terima kasih sudah mendaftar! Sebelum melanjutkan, mohon verifikasi alamat email Anda dengan mengeklik tautan yang baru saja kami kirim. Jika Anda tidak menerima email, kami akan dengan senang hati mengirimkannya kembali.
        </p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <div style="margin-bottom: 15px; color: #159b91; font-size: 13px; font-weight: 600;">
            Tautan verifikasi baru telah dikirim ke alamat email yang Anda gunakan saat pendaftaran.
        </div>
    @endif

    <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px;">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="auth-btn" style="width: auto; padding: 0 20px;">
                Kirim Ulang Email Verifikasi
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="auth-link" style="background: none; border: none; cursor: pointer; font-size: 12px;">
                Keluar
            </button>
        </form>
    </div>
</x-guest-layout>

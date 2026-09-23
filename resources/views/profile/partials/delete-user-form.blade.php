<div class="panel" style="border-color: #f1c9c9;">
    <div class="section-label" style="color: #b91c1c;">Hapus Akun</div>
    <p style="font-size: 12px; color: #64748b; margin: 0 0 18px;">
        Setelah akun Anda dihapus, seluruh data dan sumber dayanya akan dihapus secara permanen. Sebelum menghapus akun, mohon unduh data atau informasi yang ingin Anda simpan.
    </p>

    @if ($errors->userDeletion->isNotEmpty())
        <div style="margin-bottom: 15px; padding: 10px; background: #fdf2f2; border: 1px solid #f8b4b4; border-radius: 6px; color: #d83838; font-size: 12px;">
            {{ $errors->userDeletion->first('password') }}
        </div>
    @endif

    <form method="post" action="{{ route('profile.destroy') }}">
        @csrf
        @method('delete')

        <div class="field" id="delete-confirm-box" style="display: none;">
            <label class="req" for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="Masukkan password untuk konfirmasi" required>
        </div>

        <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
            <button type="button" id="delete-toggle-btn" class="filter-btn" style="cursor: pointer; padding: 10px 24px; font-size: 12px; font-weight: 700; background: #dc2626; color: #fff;">
                Hapus Akun
            </button>
            <button type="submit" id="delete-submit-btn" class="filter-btn" style="display: none; cursor: pointer; padding: 10px 24px; font-size: 12px; font-weight: 700; background: #dc2626; color: #fff;">
                Ya, Hapus Akun Saya
            </button>
            <button type="button" id="delete-cancel-btn" class="filter-btn" style="display: none; cursor: pointer; padding: 10px 24px; font-size: 12px; font-weight: 700; background: #e5e7eb; color: #374151;">
                Batal
            </button>
        </div>
    </form>
</div>

<script>
    (function () {
        var toggleBtn = document.getElementById('delete-toggle-btn');
        var submitBtn = document.getElementById('delete-submit-btn');
        var cancelBtn = document.getElementById('delete-cancel-btn');
        var confirmBox = document.getElementById('delete-confirm-box');
        if (!toggleBtn || !submitBtn || !cancelBtn || !confirmBox) return;

        toggleBtn.addEventListener('click', function () {
            toggleBtn.style.display = 'none';
            submitBtn.style.display = 'inline-block';
            cancelBtn.style.display = 'inline-block';
            confirmBox.style.display = 'block';
        });

        cancelBtn.addEventListener('click', function () {
            toggleBtn.style.display = 'inline-block';
            submitBtn.style.display = 'none';
            cancelBtn.style.display = 'none';
            confirmBox.style.display = 'none';
        });
    })();
</script>

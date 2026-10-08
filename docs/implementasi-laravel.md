# Implementasi Laravel

Semua rute aplikasi utama memakai middleware `auth`. Rute autentikasi bawaan ada di `routes/auth.php`.

## Endpoint utama

| Method | URL | Parameter penting | Hasil |
|---|---|---|---|
| GET | `/dashboard` | - | Halaman dashboaPeran: Technical writer untuk skripsi mahasiswa Teknik Informatika (proyek IoT: Laravel + firmware ESP32).

Tugas: Baca repo ini, lalu buat dokumentasi dan diagram yang dapat dipakai mahasiswa untuk menulis bab perancangan/implementasi dan memahami sistemnya saat sidang. Jangan ubah logika kode.

Output (folder `docs/`):

1. `ringkasan-sistem.md`: tujuan sistem, komponen (hardware, firmware, backend, database, UI), alur data end-to-end dalam bahasa sederhana.

2. `perancangan.md` + `docs/diagram/*.mmd` (Mermaid, satu file per diagram, di-embed di `perancangan.md` dengan blok ```mermaid):
   - Arsitektur sistem / deployment (ESP32, broker MQTT, server Laravel, database, UI)
   - Use case diagram (aktor dan fitur dari route/fitur yang ada)
   - Class diagram (Model/Controller/Service Laravel dan relasinya)
   - ERD (dari migration)
   - Sequence diagram: (a) kirim data sensor ESP32 → MQTT → Laravel → DB → UI, (b) kontrol UI → Laravel → MQTT → ESP32
   - Activity diagram / flowchart firmware (setup, loop, reconnect WiFi/MQTT)
   - State diagram, hanya jika ada state machine (mis. status relay/koneksi)
   - Tabel pin mapping dan daftar komponen

   Aturan diagram: hanya gambarkan yang ada di kode, label Bahasa Indonesia, maksimal ±15 node per diagram (pecah jika lebih), sintaks Mermaid harus valid.

3. `implementasi-laravel.md`: routes/endpoint (method, URL, parameter, contoh request/response), controller, model, service/job MQTT, variabel `.env` yang dibutuhkan (nama saja).

4. `implementasi-firmware.md`: board, library + versi, alur setup/loop/task, konfigurasi WiFi/MQTT, topik MQTT + format payload JSON, penanganan koneksi putus, cara flash.

5. `pengujian.md`: template tabel pengujian (skenario, input, hasil diharapkan, hasil aktual dikosongkan) berdasarkan fitur yang ada di kode.

6. `tanya-jawab-sidang.md`: 15 pertanyaan yang mungkin diajukan penguji beserta jawaban singkat berdasarkan kode (alasan pakai ESP32, MQTT, QoS, interval kirim, keamanan, keterbatasan sistem, dll).

7. Tambahkan PHPDoc pada class/method publik Laravel dan komentar ringkas pada fungsi utama firmware (hanya komentar).

8. Buat `docs/render-diagram.sh` yang mengubah semua `.mmd` menjadi PNG (skala tinggi, latar putih) dan SVG ke `docs/diagram/out/` memakai @mermaid-js/mermaid-cli. Jalankan jika lingkungan mengizinkan; jika tidak, cukup buat script-nya.

Aturan:
- Bahasa Indonesia, formal-sederhana, mudah dipahami mahasiswa.
- Isi harus berdasarkan kode yang ada. Jika tidak yakin, tulis "TODO: perlu konfirmasi", jangan menebak.
- Jangan tulis kredensial/token/password; pakai placeholder.
- Singkat, gunakan tabel/bullet, hindari pengulangan antar file (gunakan tautan antar dokumen).
- Abaikan `vendor/`, `node_modules/`, `storage/`, `.git/`, dan file build.
- Mulai dengan rencana file singkat, lalu kerjakan berurutan. Di akhir, tampilkan daftar file yang dibuat dan semua item TODO.rd |
| POST | `/api/socket/toggle` | `socket_number` (1/2), `state` boolean opsional, `skip_mqtt` boolean opsional | JSON status soket dan `mqtt_delivered` |
| POST | `/api/device/reconnect` | - | JSON status publish perintah WiFi reconnect |
| GET | `/api/device/telemetry` | - | JSON perangkat, dua soket, lingkungan, notifikasi |
| GET | `/api/notifications` | - | JSON daftar notifikasi |
| POST | `/api/notifications/resolve-all` | - | Menandai alarm selesai |
| GET | `/analytics` | `period=day|week|month` | Halaman analitik |
| GET | `/history` | `start_date`, `end_date`, `socket` | Halaman riwayat (parameter `socket` diteruskan ke view; tidak memfilter query pada controller) |
| GET | `/history/export` | `start_date`, `end_date` | CSV log sensor |
| POST | `/history/reset` | - | Menghapus log sensor, lingkungan, telemetry perangkat |
| GET/POST | `/settings` | nilai ambang, tarif, interval | Form/pembaruan konfigurasi |
| PUT | `/settings/mqtt` | host, port, TLS, username, password, client ID | Simpan konfigurasi MQTT perangkat |
| POST | `/settings/energy/reset` | `socket_number` 0/1/2 | Kirim reset energi PZEM |

Contoh kontrol soket:

```http
POST /api/socket/toggle
Content-Type: application/json

{"socket_number":1,"state":true}
```

```json
{"success":true,"is_active":true,"status":"normal","mqtt_delivered":true,"message":"Socket 1 berhasil dinyalakan"}
```

## Controller, model, dan MQTT

- `DashboardController`: halaman dashboard, kontrol relay, reconnect, telemetri, notifikasi.
- `SettingsController`: ambang, interval, tarif, kredensial MQTT, dan reset energi.
- `HistoryController`: riwayat, ekspor CSV, serta reset log.
- `AnalyticsController`: agregasi daya/energi harian, mingguan, bulanan.
- `MqttService`: membangun klien, koneksi TLS, dan publish perintah QoS 1.
- `MqttListenCommand`: `php artisan mqtt:listen [--device=UID]`; berlangganan telemetry QoS 0, status QoS 1, alert QoS 1, lalu menyimpan data dalam transaksi.
- Model inti: `Device`, `SocketChannel`, `DeviceThreshold`, `SensorLog`, `DeviceAlert`, `ActivityLog`; model lama/pendukung `TelemetryLog` dan `EnvironmentalLog` masih dipakai sebagai fallback di beberapa controller.

## Variabel `.env`

`APP_KEY`, `APP_URL`, `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `SESSION_DRIVER`, `CACHE_STORE`, `QUEUE_CONNECTION`, `MQTT_HOST`, `MQTT_PORT`, `MQTT_TLS`, `MQTT_AUTH_USERNAME`, `MQTT_AUTH_PASSWORD`, `MQTT_CLIENT_ID`, `MQTT_TIMEOUT`, `DEFAULT_DEVICE_UID`.

Jangan menyimpan nilai rahasia pada dokumentasi atau repositori; gunakan placeholder dan secret manager/deployment environment.

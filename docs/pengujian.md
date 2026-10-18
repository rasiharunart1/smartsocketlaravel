# Template Pengujian

Isi kolom hasil aktual, status, dan bukti saat pengujian dilakukan.

| No | Skenario | Input/langkah | Hasil diharapkan | Hasil aktual | Status | Bukti |
|---|---|---|---|---|---|---|
| 1 | Login | Kredensial valid | Dashboard terbuka |  |  |  |
| 2 | Kontrol soket 1 ON | POST toggle `1,true` | Perintah MQTT dikirim dan status UI berubah ON |  |  |  |
| 3 | Kontrol soket 2 OFF | POST toggle `2,false` | Perintah MQTT dikirim dan status UI berubah OFF |  |  |  |
| 4 | Telemetri normal | ESP32 publish payload valid | Data terbaru tampil di dashboard |  |  |  |
| 5 | Penyimpanan interval | Kirim telemetri sebelum/sesudah `log_interval` | Log tersimpan saat interval tercapai |  |  |  |
| 6 | Perubahan relay | Ubah relay fisik melalui command | Log disimpan meskipun interval belum tercapai |  |  |  |
| 7 | Alarm overcurrent | Nilai arus ≥ ambang | Relay terkait OFF dan alarm tercatat |  |  |  |
| 8 | Alarm asap | Nilai asap ≥ ambang | Dua relay OFF dan alarm tercatat |  |  |  |
| 9 | Lockout trip | Kirim ON saat `isTripped` | ON ditolak; telemetri diperbarui |  |  |  |
| 10 | Rekoneksi WiFi | Putuskan WiFi lalu pulihkan | Firmware mencoba ulang tiap 10 detik |  |  |  |
| 11 | Rekoneksi MQTT | Putuskan broker lalu pulihkan | Firmware mencoba ulang tiap 5 detik |  |  |  |
| 12 | Pengaturan ambang | Simpan nilai valid pada Settings | DB diperbarui dan perintah threshold dipublish |  |  |  |
| 13 | Reset energi | `socket_number` 0/1/2 | ESP32 memanggil reset PZEM sesuai pilihan |  |  |  |
| 14 | Riwayat dan ekspor | Filter tanggal lalu ekspor | CSV sesuai rentang tanggal |  |  |  |
| 15 | Reset riwayat | Kirim POST reset | Log perangkat dihapus dan aktivitas dicatat |  |  |  |
| 16 | Notifikasi | Alarm belum selesai lalu resolve-all | Hitungan notifikasi berubah menjadi selesai |  |  |  |
| 17 | Portal konfigurasi | Buka IP ESP32 pada port 80 dan autentikasi | Halaman pengaturan perangkat tampil |  |  |  |
| 18 | Simpan portal | Isi SSID, MQTT, UID, dan port valid | Konfigurasi masuk NVS lalu ESP32 restart |  |  |  |
| 19 | Validasi portal | Kosongkan SSID/host/UID atau isi port di luar 1-65535 | Portal menolak penyimpanan |  |  |  |
| 20 | AP setup saat boot | Nyalakan tanpa konfigurasi WiFi | AP setup dan captive portal aktif |  |  |  |
| 21 | BOOT paksa AP | Tahan BOOT minimal 3 detik saat power-on | AP setup aktif meski konfigurasi WiFi ada |  |  |  |
| 22 | AP setup saat putus | Putuskan WiFi lebih dari 2 menit | AP setup aktif |  |  |  |

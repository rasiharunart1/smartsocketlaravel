# Ringkasan Sistem

## Tujuan

Smart Socket memantau dua soket listrik dan kondisi enclosure, menampilkan data pada web, serta mengendalikan relay dari dashboard. Firmware juga memutus relay ketika ambang keselamatan terlampaui.

## Komponen

| Lapisan | Implementasi berdasarkan kode |
|---|---|
| Hardware | ESP32, 2 relay, 2 PZEM-004T v3.0, DS18B20, MQ-2, LCD I2C 16x2 |
| Firmware | Arduino/PlatformIO; membaca sensor, mengatur relay, menyimpan state/ambang di NVS, MQTT TLS |
| Backend | Laravel 13, `MqttService`, perintah `mqtt:listen` |
| Database | Tabel perangkat, kanal soket, ambang, log sensor/telemetri/lingkungan, alarm, aktivitas |
| UI | Blade: dashboard, analytics, history, settings, about, profil, dan autentikasi |

## Alur data end-to-end

1. ESP32 membaca dua PZEM, DS18B20, dan MQ-2 setiap 1 detik.
2. ESP32 mengevaluasi ambang; kondisi bahaya dapat mematikan relay dan mengirim alarm.
3. Sesuai `device_interval` (awal 5 detik), ESP32 mengirim JSON telemetri ke broker MQTT.
4. Daemon `php artisan mqtt:listen` menerima pesan, memperbarui status perangkat, lalu menyimpan log berdasarkan `log_interval` (minimum 3 detik) atau saat relay/alarm berubah.
5. Dashboard meminta telemetri terbaru melalui endpoint Laravel dan menampilkannya kepada pengguna.
6. Saat pengguna mengubah soket atau pengaturan, Laravel menerbitkan perintah MQTT; ESP32 memprosesnya lalu menerbitkan telemetri terbaru.

Detail rancangan ada di [perancangan.md](perancangan.md), kontrak implementasi di [implementasi-laravel.md](implementasi-laravel.md) dan [implementasi-firmware.md](implementasi-firmware.md).

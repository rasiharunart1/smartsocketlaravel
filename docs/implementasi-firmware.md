# Implementasi Firmware

## Target dan library

Firmware yang didokumentasikan adalah `firmware/smartsocket/smartsocket.ino` versi 2.6.0 untuk board ESP32 dengan framework Arduino. TODO: file `platformio.ini` tidak ditemukan pada folder firmware terbaru; gunakan konfigurasi board/library yang sesuai atau konfirmasi konfigurasi build terbaru.

Library yang dipakai: `WiFi`, `WiFiClientSecure`, `PubSubClient`, `ArduinoJson`, `PZEM004Tv30`, `OneWire`, `DallasTemperature`, `Wire`, `LiquidCrystal_I2C`, `Preferences`, `WebServer`, dan `DNSServer`. Versi library sebelumnya tersedia di folder firmware lama, tetapi TODO: perlu konfirmasi versi build untuk firmware terbaru.

## Setup dan loop

`setup()` memuat konfigurasi portal dari namespace NVS `config`, status relay, dan ambang keselamatan. Setelah itu firmware menyiapkan pin, ADC, LCD, UART PZEM, DS18B20, WiFi, NTP, portal web, dan klien MQTT TLS.

`loop()` melayani portal web lokal, menjaga koneksi WiFi/MQTT, membaca sensor serta mengevaluasi proteksi setiap 1 detik, mengirim telemetri sesuai interval dinamis (awal 5 detik), mengirim heartbeat setiap 30 detik, dan memperbarui LCD. WiFi dicoba ulang setiap 10 detik bila SSID ada. MQTT dicoba ulang setiap 5 detik bila WiFi dan host MQTT tersedia.

## Portal konfigurasi lokal

Portal HTTP lokal berjalan pada port 80 dengan Basic Authentication. Nama pengguna tetap `admin`; kata sandi portal disimpan di NVS dan dapat diganti melalui portal dengan panjang minimal 8 karakter. Portal menyimpan SSID/password WiFi, host/port/client ID/username/password MQTT, Device UID, URL dashboard, NTP server, zona waktu GMT, dan password portal.

Mode AP setup dibuka bila WiFi belum dikonfigurasi atau gagal tersambung saat boot, tombol BOOT GPIO0 ditahan minimal 3 detik saat power-on, atau WiFi terputus lebih dari 2 menit. Nama AP dibuat dari `SmartSocket-` dan empat karakter terakhir Device UID. Captive-portal redirect tersedia untuk rute yang tidak ditemukan. Setelah konfigurasi valid disimpan, perangkat melakukan restart terjadwal.

Nilai rahasia dan nilai default sensitif tidak dicantumkan di dokumentasi. TODO: ganti password portal bawaan sebelum perangkat digunakan di lingkungan nyata.

## Konfigurasi dan topik MQTT

Konfigurasi tidak lagi ditanam langsung di sketch utama; nilai diambil dari objek `cfg` yang dibaca dari NVS. Topik dibentuk dari Device UID.

| Arah | Topik | QoS/retain yang tampak di kode |
|---|---|---|
| ESP32 → broker | `smartsocket/{UID}/telemetry` | API firmware tidak menentukan QoS; retain `false` |
| ESP32 → broker | `smartsocket/{UID}/status` | LWT QoS 1 dan retained; publish status retained |
| ESP32 → broker | `smartsocket/{UID}/alert` | API firmware tidak menentukan QoS; retain `false` |
| Laravel → ESP32 | `.../command/switch` | subscribe QoS 1 |
| Laravel → ESP32 | `.../command/switch/sync` | subscribe QoS 1 untuk sinkronisasi awal |
| Laravel → ESP32 | `.../command/threshold` | subscribe QoS 1 |
| Laravel → ESP32 | `.../command/reconnect` | subscribe QoS 1 |
| Laravel → ESP32 | `.../command/reset_energy` | subscribe QoS 1 |

Payload telemetri tetap memuat `device_id`, `timestamp`, `environmental`, serta `sockets.socket_1` dan `sockets.socket_2`. Payload switch memuat `socket_number` dan `state`; payload ambang memuat batas tegangan, arus, suhu, asap, serta `device_interval`; reset energi memakai `socket_number` 0, 1, atau 2.

## Proteksi dan flash

Overvoltage mematikan dua soket; overcurrent mematikan soket terkait; asap mematikan dua soket; overtemperature memberi alarm dan mematikan dua soket jika suhu 10 derajat C di atas ambang. Saat `isTripped`, perintah ON ditolak sampai kondisi normal sesuai evaluasi kode.

Untuk flash: buka folder `firmware/smartsocket`, pastikan `smartsocket.ino` dan `config_portal.h` berada dalam folder sketch yang sama, hubungkan ESP32 melalui USB, lalu build/upload dengan konfigurasi PlatformIO yang sesuai. Setelah menyala, gunakan portal lokal untuk mengisi konfigurasi, bukan menulis kredensial ke source. TODO: perlu konfirmasi port USB dan file `platformio.ini` firmware terbaru.

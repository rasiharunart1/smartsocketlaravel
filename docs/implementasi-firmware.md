# Implementasi Firmware

## Target dan library

Board: `esp32dev`, framework Arduino, PlatformIO. Kecepatan upload yang dikonfigurasi adalah 921600 dan serial monitor 115200.

| Library | Versi pada `platformio.ini` |
|---|---|
| PubSubClient | `^2.8` |
| ArduinoJson | `^6.21.5` |
| PZEM-004T-v30 | `^1.1.2` |
| OneWire | `^2.3.7` |
| DallasTemperature | `^3.11.0` |
| LiquidCrystal_I2C | `^1.1.4` |

Firmware juga memakai library bawaan `WiFi`, `WiFiClientSecure`, `Wire`, dan `Preferences`.

## Setup dan loop

`setup()` memulihkan status relay dan ambang dari NVS, menyiapkan pin/ADC/I2C/LCD/UART PZEM/DS18B20, membentuk topik MQTT, lalu memulai WiFi dan MQTT TLS. `loop()` tidak memakai penundaan utama: ia menjaga koneksi, membaca sensor serta mengevaluasi proteksi tiap 1 detik, menerbitkan telemetri sesuai interval dinamis, status tiap 30 detik, dan memperbarui LCD.

WiFi dicoba ulang setiap 10 detik. MQTT dicoba ulang setiap 5 detik setelah WiFi aktif. LWT MQTT menerbitkan status offline retained bila sambungan terputus tak terduga.

## Konfigurasi dan topik

Sebelum flash, isi identitas perangkat serta kredensial dengan placeholder/secret yang benar; jangan menyalin nilai sensitif ke laporan.

| Arah | Topik | QoS/retain yang tampak di kode |
|---|---|---|
| ESP32 → broker | `smartsocket/{UID}/telemetry` | API firmware tidak menentukan QoS; retain `false` |
| ESP32 → broker | `smartsocket/{UID}/status` | connect LWT QoS 1, retained; publish API retained |
| ESP32 → broker | `smartsocket/{UID}/alert` | API firmware tidak menentukan QoS; retain `false` |
| Laravel → ESP32 | `.../command/switch` | QoS 1, tidak retained |
| Laravel → ESP32 | `.../command/switch/sync` | QoS 1; dipakai sinkronisasi awal |
| Laravel → ESP32 | `.../command/threshold` | QoS 1, retained |
| Laravel → ESP32 | `.../command/reconnect` | QoS 1, tidak retained |
| Laravel → ESP32 | `.../command/reset_energy` | QoS 1, tidak retained |

Contoh telemetri (nilai ilustratif):

```json
{"device_id":"<UID>","timestamp":1700000000,"environmental":{"temperature":30.2,"smoke_ppm":120},"sockets":{"socket_1":{"relay_state":"ON","voltage":220,"current":0.3,"power":66,"energy":1.2,"frequency":50,"power_factor":0.9},"socket_2":{"relay_state":"OFF","voltage":220,"current":0,"power":0,"energy":0,"frequency":50,"power_factor":0}}}
```

Payload switch: `{"socket_number":1,"state":"ON","requested_by":"web_user","timestamp":...}`. Payload ambang memuat `max_voltage`, `max_current`, `max_temperature`, `max_smoke_ppm`, dan `device_interval`. Reset energi memakai `socket_number` 0 (semua), 1, atau 2.

## Proteksi dan flash

Overvoltage mematikan dua soket; overcurrent mematikan soket terkait; asap mematikan dua soket; overtemperature memberi alarm dan mematikan dua soket jika suhu 10°C di atas ambang. Saat `isTripped`, perintah ON ditolak sampai kondisi normal sesuai evaluasi kode.

Untuk flash: pasang PlatformIO, buka folder `firmware/smart_socket_esp32`, periksa `DEVICE_UID`, konfigurasi jaringan/broker secara aman, hubungkan ESP32 melalui USB, lalu jalankan `pio run -t upload`; monitor dengan `pio device monitor`. TODO: perlu konfirmasi port USB dan mekanisme penyediaan kredensial yang aman untuk perangkat produksi.

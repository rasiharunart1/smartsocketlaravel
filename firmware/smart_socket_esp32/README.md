# Smart Socket ESP32 — Firmware

Firmware untuk perangkat **Smart Socket** berbasis ESP32 yang terhubung ke backend
Laravel (folder `smartsocket/`) melalui MQTT broker HiveMQ Cloud (TLS port 8883).

## Hardware & Pin Mapping

| Komponen          | GPIO  | Keterangan                         |
|-------------------|-------|------------------------------------|
| Relay Channel 1   | 21    | Kontrol ON/OFF Soket 1             |
| Relay Channel 2   | 19    | Kontrol ON/OFF Soket 2             |
| DS18B20 (Suhu)    | 5     | 1-Wire (DallasTemperature)         |
| MQ-2 (Asap)       | 34    | ADC1 (input analog)                |
| PZEM-004T #1 RX   | 16    | HardwareSerial 1                   |
| PZEM-004T #1 TX   | 17    | HardwareSerial 1                   |
| PZEM-004T #2 RX   | 26    | HardwareSerial 2                   |
| PZEM-004T #2 TX   | 27    | HardwareSerial 2                   |

> **Catatan Relay**: Logika `HIGH`/`LOW` untuk relay aktif/nonaktif bergantung pada
> jenis modul relay yang dipakai (active-high vs active-low). Sesuaikan baris
> `digitalWrite(RELAY1_PIN, ...)` di file `.ino` jika perlu.

## Dependencies (Arduino Libraries)

| Library             | Fungsi                    |
|---------------------|---------------------------|
| PubSubClient        | Klien MQTT                |
| ArduinoJson (v6)    | Serialisasi/parsing JSON  |
| PZEM-004T-v30       | Baca sensor PZEM-004T     |
| OneWire             | Protokol 1-Wire           |
| DallasTemperature   | Baca sensor DS18B20       |

Instalasi via **PlatformIO** cukup dengan `platformio.ini` yang sudah disediakan
(`pio run` / `pio run -t upload`).

Jika memakai **Arduino IDE**, install library di atas melalui Library Manager.

## Konfigurasi Wajib Sebelum Flash

Edit bagian berikut di `smart_socket_esp32.ino`:

```cpp
const char* WIFI_SSID = "YOUR_WIFI_SSID";
const char* WIFI_PASS = "YOUR_WIFI_PASSWORD";
```

Nilai MQTT sudah terisi (HiveMQ Cloud):
```cpp
const char* MQTT_HOST      = "ab11f67ab13c48b5937d15d0439112f4.s1.eu.hivemq.cloud";
const uint16_t MQTT_PORT   = 8883;
const char* MQTT_USER      = "wilda";
const char* MQTT_PASS      = "wildajuwita321";
const char* MQTT_CLIENT_ID = "esp32_socket_01_client";
```

`DEVICE_UID` harus **sama** dengan `DEFAULT_DEVICE_UID` pada `.env` backend
(default: `ESP32_SOCKET_01`).

## Topik MQTT

| Arah             | Topik                                    | Deskripsi               |
|------------------|------------------------------------------|-------------------------|
| ESP32 → Server   | `smartsocket/{uid}/telemetry`            | Data sensor (3-5 detik) |
| ESP32 → Server   | `smartsocket/{uid}/status`               | Status LWT (retain)     |
| ESP32 → Server   | `smartsocket/{uid}/alert`                | Alarm keamanan          |
| Server → ESP32   | `smartsocket/{uid}/command/switch`       | Toggle relay            |
| Server → ESP32   | `smartsocket/{uid}/command/threshold`    | Update threshold        |
| Server → ESP32   | `smartsocket/{uid}/command/reconnect`    | Rekoneksi WiFi          |

## Format Payload Telemetri (yang dikirim ESP32)

```json
{
  "device_id": "ESP32_SOCKET_01",
  "timestamp": 1726484400,
  "environmental": { "temperature": 34.2, "smoke_ppm": 930 },
  "sockets": {
    "socket_1": { "relay_state": "ON", "voltage": 220.5, "current": 0.45, "power": 99.2, "energy": 12.45, "frequency": 50.0, "power_factor": 0.98 },
    "socket_2": { "relay_state": "ON", "voltage": 221.0, "current": 0.32, "power": 70.7, "energy": 8.12, "frequency": 50.0, "power_factor": 0.97 }
  }
}
```

## Catatan Kalibrasi

- **MQ-2 → PPM**: Fungsi `readSmokePPM()` saat ini memakai konversi linear kasar
  (`map`). Untuk akurasi nyata, kalibrasi dengan kurva resistansi sensor MQ-2 dan
  beban referensi sesuai datasheet.
- **Timestamp**: Saat ini memakai `millis()/1000` (uptime). Untuk timestamp epoch
  yang benar, integrasikan NTP (mis. library `ezTime` / `NTPClient`).
- **TLS**: Memakai `wifiClient.setInsecure()` (tanpa verifikasi CA). Untuk
  keamanan produksi, tambahkan CA certificate HiveMQ (ISRG Root X1 / Let's Encrypt).

## Build & Flash (PlatformIO)

```bash
cd firmware/smart_socket_esp32
pio run            # compile
pio run -t upload  # flash ke ESP32
pio device monitor # lihat serial output
```

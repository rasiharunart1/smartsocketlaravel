# Smart Socket ESP32 — Firmware

Firmware untuk perangkat **Smart Socket** berbasis ESP32 yang terhubung ke backend
Laravel (folder `smartsocket/`) melalui MQTT broker HiveMQ Cloud (TLS port 8883).

## Hardware & Pin Mapping

| Komponen          | GPIO  | Keterangan                         |
|-------------------|-------|------------------------------------|
| Relay Channel 1   | 18    | Kontrol ON/OFF Soket 1 (Output)    |
| Relay Channel 2   | 19    | Kontrol ON/OFF Soket 2 (Output)    |
| DS18B20 (Suhu)    | 5     | 1-Wire (DallasTemperature)         |
| MQ-2 (Asap)       | 34    | ADC1 CH6 (Input Analog 0–3.3V)     |
| PZEM-004T #1 RX   | 16    | HardwareSerial 1 (ESP32 RX1)       |
| PZEM-004T #1 TX   | 17    | HardwareSerial 1 (ESP32 TX1)       |
| PZEM-004T #2 RX   | 26    | HardwareSerial 2 (ESP32 RX2)       |
| PZEM-004T #2 TX   | 27    | HardwareSerial 2 (ESP32 TX2)       |
| LCD 16x2 I2C SDA  | 21    | Data I2C (Default Hardware I2C)    |
| LCD 16x2 I2C SCL  | 22    | Clock I2C (Default Hardware I2C)   |

Alamat I2C LCD default adalah `0x27` (atau `0x3F` pada beberapa backpack PCF8574A).

> **Catatan Relay**: Modul relay 2-channel optocoupler pada umumnya bertipe **ACTIVE-LOW** (`LOW` = Hidup, `HIGH` = Mati). Firmware sudah dilengkapi macro `#define RELAY_ACTIVE_LOW true` dan proteksi anti-glitch klik relay saat ESP32 booting.

## Dependencies (Arduino Libraries)

| Library             | Versi Rekomendasi         | Fungsi                         |
|---------------------|---------------------------|--------------------------------|
| PubSubClient        | ^2.8                      | Klien MQTT (HiveMQ Cloud TLS)  |
| ArduinoJson         | v6 atau v7 (Kompatibel!)  | Serialisasi & parsing JSON     |
| PZEM-004T-v30       | ^1.1.2                    | Komunikasi UART PZEM-004T v3.0 |
| OneWire             | ^2.3.7                    | Bus 1-Wire protokol            |
| DallasTemperature   | ^3.11.0                   | Pembacaan suhu DS18B20         |
| LiquidCrystal_I2C   | ^1.1.4                    | Driver display LCD 16x2 I2C    |

Instalasi via **PlatformIO** cukup dengan `platformio.ini` yang sudah disediakan
(`pio run` / `pio run -t upload`).

Jika memakai **Arduino IDE**, install library di atas melalui Library Manager.

## Konfigurasi Wajib Sebelum Flash

Edit bagian berikut di `smart_socket_esp32.ino`:

```cpp
const char* WIFI_SSID = "YOUR_WIFI_SSID";
const char* WIFI_PASS = "YOUR_WIFI_PASSWORD";
const char* MQTT_HOST      = "ab11f67ab13c48b5937d15d0439112f4.s1.eu.hivemq.cloud";
const uint16_t MQTT_PORT   = 8883;
const char* MQTT_USER      = "wilda";
const char* MQTT_PASS      = "wildajuwita321";
const char* MQTT_CLIENT_ID = "ESP32_SmartSocket";
```

`DEVICE_UID` harus sama persis dengan Device UID yang dibuat saat registrasi dan
ditampilkan pada halaman Settings Laravel (default: `ESP32_SOCKET_01`).

## Informasi LCD

LCD berganti halaman secara otomatis setiap 2,5 detik tanpa menghentikan loop MQTT (non-blocking):

1. **INSIGHT 1**: Status WiFi, MQTT, dan RSSI.
2. **INSIGHT 2**: Tegangan, arus, daya, dan status relay Soket 1.
3. **INSIGHT 3**: Tegangan, arus, daya, dan status relay Soket 2.
4. **INSIGHT 4**: Total konsumsi daya (Watt) & akumulasi energi (kWh).
5. **INSIGHT 5**: Kualitas listrik (Frekuensi Hz & Faktor Daya PF).
6. **INSIGHT 6**: Keselamatan enclosure (Suhu DS18B20 & Asap MQ-2 ppm).
7. **INSIGHT 7**: Status proteksi (Trip / Normal) & status saklar relay.

Saat threshold keselamatan terpicu, LCD menampilkan pesan darurat secara instan selama 4,5 detik.

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
  "environmental": {
    "temperature": 28.5,
    "smoke_ppm": 120.0
  },
  "sockets": {
    "socket_1": {
      "relay_state": "ON",
      "voltage": 220.5,
      "current": 1.25,
      "power": 275.6,
      "energy": 0.45,
      "frequency": 50.0,
      "power_factor": 0.98
    },
    "socket_2": {
      "relay_state": "OFF",
      "voltage": 220.3,
      "current": 0.0,
      "power": 0.0,
      "energy": 0.12,
      "frequency": 50.0,
      "power_factor": 0.0
    }
  }
}
```

## Catatan Fitur & Keamanan

- **Anti Freeze / Non-Blocking**: Rekoneksi WiFi dan MQTT berjalan secara asinkron tanpa memblokir pembacaan sensor atau trip keselamatan bila WiFi offline.
- **Trip Granular**: Overload arus pada Soket 1 hanya mematikan Soket 1 tanpa mengganggu Soket 2. Bahaya asap atau over-voltage mematikan kedua soket secara darurat.
- **Flash NVS Persistence**: Ambang batas proteksi yang diubah melalui web disimpan langsung ke memori flash ESP32 sehingga tidak hilang saat mati listrik.
- **MQTT Retain Bug Fixed**: Telemetri dan alert dikirim tanpa retain flag, sedangkan LWT status online dikirim dengan retain flag.
- **Trip Lockout Sync**: Jika pengguna mencoba menyalakan relay dari web saat sistem masih terpicu trip (misal asap tebal), firmware menolak perintah dan segera menyinkronkan status relay web kembali ke OFF.

## Build & Flash (PlatformIO)

```bash
cd firmware/smart_socket_esp32
pio run            # compile
pio run -t upload  # flash ke ESP32
pio device monitor # lihat serial output
```

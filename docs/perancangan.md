# Perancangan Sistem

Diagram sumber ada di `docs/diagram/`; blok berikut sengaja sama agar dapat dirender oleh pembaca Markdown.

## Arsitektur/deployment

```mermaid
flowchart LR
  U[Pengguna] --> UI[UI Blade Laravel]
  UI --> WEB[Server Laravel]
  WEB --> DB[(Database)]
  WEB <--> BROKER[Broker MQTT TLS]
  BROKER <--> ESP[ESP32 Smart Socket]
  P[Browser portal lokal] --> ESP
  ESP --> PZEM[2x PZEM-004T]
  ESP --> ENV[DS18B20 dan MQ-2]
  ESP --> RELAY[Relay 2 kanal dan LCD]
```

## Use case

```mermaid
flowchart LR
  A[Pengguna terautentikasi] --> D[Melihat dashboard dan telemetri]
  A --> S[Mengendalikan soket]
  A --> R[Meminta rekoneksi WiFi]
  A --> N[Melihat atau menyelesaikan notifikasi]
  A --> H[Melihat, ekspor, atau reset riwayat]
  A --> G[Mengatur ambang, interval, tarif, MQTT]
  A --> AN[Melihat analitik]
  A --> P[Mengelola profil]
  B[ESP32] --> T[Mengirim telemetri, status, dan alarm]
  B --> C[Menerima perintah MQTT]
```

## Kelas Laravel

```mermaid
classDiagram
  class User { +devices() }
  class Device { +socketChannels() +threshold() +sensorLogs() +alerts() }
  class SocketChannel { +telemetryLogs() }
  class DeviceThreshold
  class SensorLog { +total_power +total_energy }
  class DeviceAlert
  class ActivityLog
  class DashboardController
  class SettingsController
  class MqttService { +publishSwitch() +publishThreshold() +publishReconnect() }
  class MqttListenCommand { +handle() }
  User "1" --> "0..*" Device
  Device "1" --> "2" SocketChannel
  Device "1" --> "0..1" DeviceThreshold
  Device "1" --> "0..*" SensorLog
  Device "1" --> "0..*" DeviceAlert
  Device "1" --> "0..*" ActivityLog
  DashboardController --> MqttService
  SettingsController --> MqttService
  MqttListenCommand --> MqttService
```

## ERD

```mermaid
erDiagram
  USERS ||--o{ DEVICES : memiliki
  DEVICES ||--|{ SOCKET_CHANNELS : menyediakan
  DEVICES ||--o| DEVICE_THRESHOLDS : memiliki
  DEVICES ||--o{ SENSOR_LOGS : mencatat
  DEVICES ||--o{ ENVIRONMENTAL_LOGS : mencatat
  DEVICES ||--o{ DEVICE_ALERTS : menghasilkan
  DEVICES ||--o{ ACTIVITY_LOGS : mencatat
  SOCKET_CHANNELS ||--o{ TELEMETRY_LOGS : mencatat
  SOCKET_CHANNELS ||--o{ DEVICE_ALERTS : terkait
  USERS { bigint id PK string email }
  DEVICES { bigint id PK bigint user_id FK string device_uid UK string status }
  SOCKET_CHANNELS { bigint id PK bigint device_id FK int channel_number bool is_active string status }
  DEVICE_THRESHOLDS { bigint id PK bigint device_id FK decimal max_voltage decimal max_current int device_interval }
  SENSOR_LOGS { bigint id PK bigint device_id FK decimal voltage_1 decimal voltage_2 decimal temperature datetime recorded_at }
  DEVICE_ALERTS { bigint id PK bigint device_id FK bigint socket_channel_id FK string alert_type bool is_resolved }
```

## Urutan telemetri

```mermaid
sequenceDiagram
  participant E as ESP32
  participant B as Broker MQTT
  participant L as Daemon Laravel
  participant D as Database
  participant U as UI Dashboard
  E->>B: publish telemetry (QoS 0)
  B->>L: kirim topik telemetry
  L->>D: perbarui perangkat dan simpan log sesuai interval
  U->>L: GET /api/device/telemetry
  L->>D: ambil log terbaru
  L-->>U: JSON telemetri
```

## Urutan kontrol

```mermaid
sequenceDiagram
  participant U as Pengguna
  participant UI as UI Dashboard
  participant L as Laravel
  participant B as Broker MQTT
  participant E as ESP32
  U->>UI: pilih ON atau OFF
  UI->>L: POST /api/socket/toggle
  L->>B: publish command/switch QoS 1
  B->>E: payload JSON
  E->>E: ubah relay jika tidak trip
  E->>B: publish telemetry
  B->>L: telemetry diterima daemon
  L-->>UI: respons JSON awal
```

## Alur firmware

```mermaid
flowchart TD
  A[setup] --> B[Muat konfigurasi portal, relay, dan ambang dari NVS]
  B --> C[Inisialisasi pin, sensor, LCD, WiFi, MQTT, portal]
  C --> D{WiFi tersedia?}
  D -- Tidak atau BOOT 3 detik --> E[Aktifkan AP setup]
  D -- Ya --> F[Mode station]
  E --> G[loop]
  F --> G
  G --> H[Layani portal web lokal]
  H --> I{WiFi terhubung?}
  I -- Tidak --> J[Coba ulang tiap 10 detik; AP setelah 2 menit]
  J --> G
  I -- Ya --> K{MQTT terhubung?}
  K -- Tidak --> L[Hubungkan ulang tiap 5 detik]
  L --> G
  K -- Ya --> M[Baca sensor, proses MQTT, kirim data, LCD]
  M --> G
```

## State relay

```mermaid
stateDiagram-v2
  [*] --> Mati
  Mati --> Menyala: perintah ON
  Menyala --> Mati: perintah OFF
  Menyala --> TerkunciTrip: ambang terlampaui
  Mati --> TerkunciTrip: ambang terlampaui
  TerkunciTrip --> Mati: lingkungan normal
  TerkunciTrip --> TerkunciTrip: perintah ON ditolak
```

## Pin mapping dan komponen

| Komponen | Pin/antarmuka | Fungsi |
|---|---|---|
| Relay soket 1 | GPIO 18 | Keluaran relay 1 |
| Relay soket 2 | GPIO 19 | Keluaran relay 2 |
| DS18B20 | GPIO 5 / OneWire | Suhu enclosure |
| MQ-2 | GPIO 34 / ADC | Asap/gas |
| PZEM-1 | RX 16, TX 17, UART1 | Metrik soket 1 |
| PZEM-2 | RX 26, TX 27, UART2 | Metrik soket 2 |
| LCD I2C | SDA 21, SCL 22, alamat 0x27 | Tampilan lokal |

Catatan: kode menetapkan `RELAY_ACTIVE_LOW` bernilai `false`; sesuaikan dengan modul relay fisik bila diperlukan.

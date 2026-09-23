# Panduan Pengembangan CLAUDE.md
# Smart Socket IoT Management Platform

Dokumen panduan referensi, instruksi build/test, arsitektur, dan konvensi kode untuk AI assistant dan developer.

---

## 1. Ikhtisar Proyek

Aplikasi pemantauan dan pengendalian daya listrik berbasis IoT:
* **Hardware**: ESP32, 2x Sensor PZEM-004T (independen per soket), 2-Channel Relay, 1x Sensor Suhu (DHT22/DS18B20), 1x Sensor Asap (MQ-2).
* **Broker MQTT**: HiveMQ Cloud (TLS port 8883).
* **Backend**: Laravel 13 (PHP 8.3+) di folder `smartsocket/`.
* **Frontend**: Blade templates dengan custom CSS diadaptasi dari folder prototipe `smart_socket/`.
* **Autentikasi**: Laravel Breeze (Blade Stack) dengan styling visual kustom identik prototipe.

---

## 2. Struktur Folder Workspace

```text
PROYEK/WILDA/
├── CLAUDE.md               # Panduan developer & instruksi coding
├── PRD.md                  # Product Requirements Document lengkap
├── smart_socket/           # Prototipe statik HTML/CSS (UI mock & asset referensi)
│   ├── style.css           # Sumber styling utama (skema warna, tata letak)
│   ├── dashboard.html      # Tampilan dashboard dual socket & 7 kartu sensor
│   ├── analytics.html      # Tampilan grafik beban per jam & komparasi
│   ├── history.html        # Tampilan tabel riwayat log data + ekspor
│   ├── settings.html       # Tampilan pengaturan batas threshold & WiFi
│   ├── about.html          # Tampilan deskripsi sistem & hardware
│   ├── login.html          # Referensi UI auth login (.auth-page, .auth-card)
│   └── register.html       # Referensi UI auth register
└── smartsocket/            # Aplikasi utama Laravel 13
    ├── app/
    │   ├── Console/Commands/   # Worker daemon MQTT (mqtt:listen)
    │   ├── Http/Controllers/  # Web & API controllers
    │   ├── Models/             # Model Eloquent (Device, SocketChannel, TelemetryLog, dll.)
    │   └── Services/           # MQTT Publisher & Business logic service
    ├── database/
    │   ├── migrations/         # Skema tabel database
    │   └── seeders/            # Seeder default device & soket
    ├── resources/views/        # Blade templates (Layouts, Dashboard, Auth Breeze kustom)
    └── routes/web.php          # Definisi rute web
```

---

## 3. Perintah Pengembangan Utama (CLI)

Semua perintah dijalankan di dalam direktori `smartsocket/`:

### Instalasi & Setup Lingkungan
```bash
cd smartsocket
composer install
npm install
cp .env.example .env
php artisan key:generate
```

### Database & Migrasi
```bash
php artisan migrate
php artisan db:seed
php artisan migrate:fresh --seed
```

### Menjalankan Server & Build Asset
```bash
# Server Laravel
php artisan serve

# Vite Asset Builder (CSS/JS)
npm run dev

# Build Production Asset
npm run build
```

### Menjalankan MQTT Subscriber Worker (HiveMQ Listener)
```bash
php artisan mqtt:listen
```

### Testing & Code Styling
```bash
# Unit & Feature Tests
php artisan test

# Format kode via Laravel Pint
./vendor/bin/pint
```

---

## 4. Konfigurasi Environment (`.env`)

Tambahkan konfigurasi broker HiveMQ Cloud pada `smartsocket/.env`:

```env
# HiveMQ Cloud MQTT Configuration
MQTT_HOST=your-cluster-id.s1.eu.hivemq.cloud
MQTT_PORT=8883
MQTT_TLS=true
MQTT_AUTH_USERNAME=your_username
MQTT_AUTH_PASSWORD=your_password
MQTT_CLIENT_ID=laravel_backend_daemon
MQTT_TIMEOUT=10

# Default Device Setup
DEFAULT_DEVICE_UID=ESP32_SOCKET_01
```

---

## 5. Standar Arsitektur & Aturan Kode

### 5.1. Dual Socket & Dual PZEM-004T Mapping
* **Soket 1**: Terhubung ke Relay Channel 1 dan PZEM-004T Unit 1.
* **Soket 2**: Terhubung ke Relay Channel 2 dan PZEM-004T Unit 2.
* Telemetri listrik (V, A, W, kWh, Hz, PF) wajib dicatat terpisah per kanal soket di tabel `telemetry_logs`.
* Telemetri lingkungan (suhu & asap) dicatat pada level device di tabel `environmental_logs`.

### 5.2. Format Topik MQTT HiveMQ
* Telemetri masuk: `smartsocket/{device_uid}/telemetry`
* Status LWT masuk: `smartsocket/{device_uid}/status`
* Notifikasi alarm masuk: `smartsocket/{device_uid}/alert`
* Perintah relay keluar: `smartsocket/{device_uid}/command/switch`
* Perintah threshold keluar: `smartsocket/{device_uid}/command/threshold` (QoS 1, retain: true)

### 5.3. Penanganan Pesan MQTT (Subscriber)
* Validasi payload JSON wajib di dalam blok `try-catch`.
* Pesan yang tidak valid atau corrupt dicatat via `Log::warning()`, worker tidak boleh crash.
* Simpan data telemetri dan perbarui status soket (`is_active`, `status`) secara atomik dalam database transaction bila perlu.

### 5.4. Autentikasi Laravel Breeze Kustom
* Gunakan stack Laravel Breeze Blade.
* Tampilan views pada `resources/views/auth/` harus dimodifikasi total agar memakai CSS dari `smart_socket/style.css` (bukan template default Tailwind).
* Semua rute operasional (`/dashboard`, `/analytics`, `/history`, `/settings`, `/about`) dilindungi middleware `auth`.

### 5.5. Konvensi Kode Laravel
* Controller tetap ramping (thin controller); logika publish MQTT dan kalkulasi analitik diletakkan di `app/Services/`.
* Gunakan Form Request untuk validasi input threshold dan profil.
* Gunakan migrasi dengan tipe data presisi: `decimal('voltage', 6, 2)`, `decimal('current', 6, 3)`, `decimal('power', 8, 2)`, `decimal('energy', 10, 3)`.
* Pastikan foreign key onDelete cascade diatur dengan benar pada tabel turunan `devices`.

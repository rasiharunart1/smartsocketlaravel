@extends('layouts.app')

@section('title', 'Pengaturan')
@section('header_title', 'Socket Monitor')

@section('content')
<section class="content">

    <!-- Page Header -->
    <div class="page-header">
        <div>
            <h1 class="page-title">Konfigurasi</h1>
            <p class="page-subtitle">
                Kelola parameter teknis dan batas keamanan Smart Socket.
            </p>
        </div>
        <span class="online" style="display: inline-flex; align-items: center; gap: 6px;">
            <span style="width: 8px; height: 8px; border-radius: 50%; background: currentColor; display: inline-block;"></span> {{ ucfirst($device->status ?? 'offline') }}
        </span>
    </div>

    <!-- Form Batas Perlindungan -->
    <form method="POST" action="{{ route('settings.update') }}">
        @csrf
        <input type="hidden" name="kwh_rate" value="{{ $threshold?->kwh_rate ?? 0 }}">

        <div class="panel protection">
            <div class="section-label">
                Batas Perlindungan Otomatis
            </div>

            <div class="threshold-grid">
                <!-- Proteksi Tegangan Berlebih -->
                <div class="threshold">
                    <div class="threshold-head">
                        <h3>
                            Proteksi Tegangan<br>
                            Berlebih
                        </h3>
                        <span class="threshold-value" id="badge-voltage">{{ $threshold->max_voltage ?? 0 }}V</span>
                    </div>

                    <input type="range" name="max_voltage" min="0" max="270" step="1"
                           value="{{ old('max_voltage', $threshold->max_voltage ?? 0) }}"
                           oninput="document.getElementById('badge-voltage').textContent = this.value + 'V'"
                           style="width: 100%; margin: 15px 0; accent-color: #123f91; cursor: pointer;">

                    <div class="slider-note">
                        <span>NORMAL (230V)</span>
                        <span>MAKS. (270V)</span>
                    </div>

                    <p>
                        Memutus daya relay secara otomatis ketika tegangan listrik melebihi batas aman yang ditentukan.
                    </p>
                </div>

                <!-- Proteksi Arus Berlebih -->
                <div class="threshold">
                    <div class="threshold-head">
                        <h3>
                            Proteksi Arus<br>
                            Berlebih
                        </h3>
                        <span class="threshold-value" id="badge-current">{{ $threshold->max_current ?? 0 }}A</span>
                    </div>

                    <input type="range" name="max_current" min="0" max="25" step="0.5"
                           value="{{ old('max_current', $threshold->max_current ?? 0) }}"
                           oninput="document.getElementById('badge-current').textContent = this.value + 'A'"
                           style="width: 100%; margin: 15px 0; accent-color: #123f91; cursor: pointer;">

                    <div class="slider-note">
                        <span>NORMAL (15A)</span>
                        <span>MAKS. (25A)</span>
                    </div>

                    <p>
                        Memutus daya relay secara otomatis ketika arus beban melebihi kapasitas kabel atau steker.
                    </p>
                </div>

                <!-- Proteksi Suhu Berlebih -->
                <div class="threshold">
                    <div class="threshold-head">
                        <h3>
                            Proteksi Suhu<br>
                            Berlebih
                        </h3>
                        <span class="threshold-value" id="badge-temperature">{{ $threshold->max_temperature ?? 0 }}°C</span>
                    </div>

                    <input type="range" name="max_temperature" min="0" max="100" step="1"
                           value="{{ old('max_temperature', $threshold->max_temperature ?? 0) }}"
                           oninput="document.getElementById('badge-temperature').textContent = this.value + '°C'"
                           style="width: 100%; margin: 15px 0; accent-color: #123f91; cursor: pointer;">

                    <div class="slider-note">
                        <span>NORMAL (55°C)</span>
                        <span>MAKS. (100°C)</span>
                    </div>

                    <p>
                        Memantau suhu enclosure pada socket untuk mencegah overheating dan bahaya kebakaran.
                    </p>
                </div>

                <!-- Batas Deteksi Asap -->
                <div class="threshold">
                    <div class="threshold-head">
                        <h3>
                            Batas Deteksi<br>
                            Asap
                        </h3>
                        <span class="threshold-value" id="badge-smoke">{{ $threshold->max_smoke_ppm ?? 0 }} ppm</span>
                    </div>

                    <input type="range" name="max_smoke_ppm" min="0" max="4000" step="25"
                           value="{{ old('max_smoke_ppm', $threshold->max_smoke_ppm ?? 0) }}"
                           oninput="document.getElementById('badge-smoke').textContent = this.value + ' ppm'"
                           style="width: 100%; margin: 15px 0; accent-color: #123f91; cursor: pointer;">

                    <div class="slider-note">
                        <span>NORMAL (500 ppm)</span>
                        <span>MAKS. (4000 ppm)</span>
                    </div>

                    <p>
                        Menentukan batas deteksi asap (MQ-2) untuk memicu alarm peringatan dini kebakaran.
                    </p>
                </div>
            </div>

            <div style="margin-top: 24px; display: flex; justify-content: flex-end;">
                <button type="submit" class="filter-btn" style="cursor: pointer; padding: 10px 24px; font-size: 12px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    Simpan & Sinkronkan ke ESP32
                </button>
            </div>
        </div>
    </form>

    <!-- Tarif Listrik -->
    <div class="panel network" style="margin-top: 20px;">
        <div class="section-label">
            Tarif Biaya Listrik
            <span style="float: right; color: #087c71;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v12"/><path d="m15 9-3 3-3-3"/></svg>
            </span>
        </div>

        <p style="font-size: 12px; color: #64748b; margin: 0 0 16px;">
            Tentukan tarif dasar listrik (Rp / kWh) yang digunakan untuk menghitung estimasi biaya pada halaman Analisis Energi.
        </p>

        <form method="POST" action="{{ route('settings.update') }}">
            @csrf
            <input type="hidden" name="max_voltage" value="{{ $threshold->max_voltage ?? 0 }}">
            <input type="hidden" name="max_current" value="{{ $threshold->max_current ?? 0 }}">
            <input type="hidden" name="max_temperature" value="{{ $threshold->max_temperature ?? 0 }}">
            <input type="hidden" name="max_smoke_ppm" value="{{ $threshold->max_smoke_ppm ?? 0 }}">

            <div style="display: flex; align-items: flex-end; gap: 12px; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 220px;">
                    <label for="kwh_rate" style="display: block; font-size: 11px; font-weight: 700; color: #475569; margin-bottom: 6px;">
                        Tarif per kWh (Rp)
                    </label>
                    <input type="number" name="kwh_rate" id="kwh_rate" step="0.01" min="0" max="100000"
                           value="{{ old('kwh_rate', $threshold->kwh_rate ?? 0) }}"
                           style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; font-weight: 600; color: #0f243d;"
                           required>
                </div>
                <button type="submit" class="filter-btn" style="cursor: pointer; padding: 10px 24px; font-size: 12px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    Simpan Tarif
                </button>
            </div>
        </form>
    </div>

    <!-- Kredensial MQTT -->
    <div class="panel network" style="margin-top: 20px;">
        <div class="section-label">Kredensial MQTT Perangkat</div>

        <p style="font-size: 12px; color: #64748b; margin: 0 0 16px;">
            Kredensial ini khusus untuk perangkat Anda. Username dan password disimpan terenkripsi.
        </p>

        <form method="POST" action="{{ route('settings.mqtt.update') }}">
            @csrf
            @method('PUT')

            <div class="threshold-grid">
                <div>
                    <label for="mqtt_host" style="display: block; font-size: 11px; font-weight: 700; margin-bottom: 6px;">Broker Host</label>
                    <input type="text" id="mqtt_host" name="mqtt_host" value="{{ old('mqtt_host', $device->mqtt_host) }}" placeholder="cluster.example.hivemq.cloud" style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px;">
                </div>
                <div>
                    <label for="mqtt_port" style="display: block; font-size: 11px; font-weight: 700; margin-bottom: 6px;">Port</label>
                    <input type="number" id="mqtt_port" name="mqtt_port" value="{{ old('mqtt_port', $device->mqtt_port ?: 0) }}" min="0" max="65535" style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px;">
                </div>
                <div>
                    <label for="mqtt_username" style="display: block; font-size: 11px; font-weight: 700; margin-bottom: 6px;">Username</label>
                    <input type="text" id="mqtt_username" name="mqtt_username" value="{{ old('mqtt_username', $device->mqtt_username ?? '') }}" autocomplete="username" style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px;">
                </div>
                <div>
                    <label for="mqtt_password" style="display: block; font-size: 11px; font-weight: 700; margin-bottom: 6px;">Password</label>
                    <input type="password" id="mqtt_password" name="mqtt_password" placeholder="Kosongkan untuk mempertahankan password" autocomplete="new-password" style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px;">
                </div>
                <div>
                    <label for="mqtt_client_id" style="display: block; font-size: 11px; font-weight: 700; margin-bottom: 6px;">Client ID</label>
                    <input type="text" id="mqtt_client_id" name="mqtt_client_id" value="{{ old('mqtt_client_id', $device->mqtt_client_id) }}" placeholder="Opsional" style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px;">
                </div>
                <label style="display: flex; align-items: center; gap: 8px; font-size: 12px; font-weight: 700; padding-top: 24px;">
                    <input type="checkbox" name="mqtt_tls" value="1" @checked(old('mqtt_tls', $device->mqtt_tls))>
                    Gunakan TLS
                </label>
            </div>

            <div style="margin-top: 20px; display: flex; justify-content: flex-end;">
                <button type="submit" class="filter-btn" style="cursor: pointer; padding: 10px 24px; font-size: 12px; font-weight: 700;">Simpan Kredensial MQTT</button>
            </div>
        </form>
    </div>

    <!-- Status Jaringan -->
    <div class="panel network" style="margin-top: 20px;">
        <div class="section-label">
            Status Jaringan Perangkat
            <span style="float: right; color: #087c71;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.55a11 11 0 0 1 14.08 0"/><path d="M1.42 9a16 16 0 0 1 21.16 0"/><path d="M8.53 16.11a6 6 0 0 1 6.95 0"/><circle cx="12" cy="20" r="1"/></svg>
            </span>
        </div>

        <div class="network-row">
            <span>Device UID:</span>
            <strong>{{ $device->device_uid }}</strong>
        </div>

        <div class="network-row">
            <span>Kekuatan Sinyal (RSSI):</span>
            <strong>{{ $device->wifi_rssi ?? 0 }} dBm ({{ max(10, min(100, 100 + ($device->wifi_rssi ?? -50))) }}%)</strong>
        </div>

        <div class="network-row">
            <span>Alamat IP ESP32:</span>
            <strong>{{ $device->ip_address ?? 'Belum terhubung' }}</strong>
        </div>

        <div class="network-row">
            <span>MAC Address:</span>
            <strong>{{ $device->mac_address ?? 'N/A' }}</strong>
        </div>

        <div class="network-row">
            <span>Versi Firmware:</span>
            <strong>{{ $device->firmware_version ? 'v'.$device->firmware_version : 'Belum tersedia' }}</strong>
        </div>

        <div class="network-row">
            <span>Terakhir Terhubung:</span>
            <strong>{{ $device->last_seen_at ? $device->last_seen_at->diffForHumans() : 'Belum pernah' }}</strong>
        </div>

    </div>

</section>
@endsection

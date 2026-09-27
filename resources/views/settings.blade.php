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

                <!-- Interval Device (Dashboard Live) -->
                <div class="threshold">
                    <div class="threshold-head">
                        <h3>
                            Interval Device<br>
                            (Dashboard Live)
                        </h3>
                        <span class="threshold-value" id="badge-dev-interval">{{ $threshold->device_interval ?? 5 }} Detik</span>
                    </div>

                    <select name="device_interval" id="device_interval"
                            onchange="document.getElementById('badge-dev-interval').textContent = this.value + ' Detik'"
                            style="width: 100%; margin: 15px 0; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; font-weight: 600; color: #0f243d; background: #fff; cursor: pointer;">
                        <option value="1" {{ old('device_interval', $threshold->device_interval ?? 5) == 1 ? 'selected' : '' }}>1 Detik (Super Responsif / High Frequency)</option>
                        <option value="2" {{ old('device_interval', $threshold->device_interval ?? 5) == 2 ? 'selected' : '' }}>2 Detik (Sangat Cepat)</option>
                        <option value="3" {{ old('device_interval', $threshold->device_interval ?? 5) == 3 ? 'selected' : '' }}>3 Detik (Cepat / Responsif)</option>
                        <option value="5" {{ old('device_interval', $threshold->device_interval ?? 5) == 5 ? 'selected' : '' }}>5 Detik (Standar Rekomendasi)</option>
                        <option value="10" {{ old('device_interval', $threshold->device_interval ?? 5) == 10 ? 'selected' : '' }}>10 Detik (Santai / Ringan WiFi)</option>
                    </select>

                    <div class="slider-note">
                        <span>CEPAT (1s)</span>
                        <span>HEMAT (10s)</span>
                    </div>

                    <p>
                        Frekuensi pengiriman data dari ESP32 untuk pembaruan angka sensor di <b>Dashboard Web secara real-time</b>.
                    </p>
                </div>

                <!-- Interval Record Database (Log Riwayat) -->
                <div class="threshold">
                    <div class="threshold-head">
                        <h3>
                            Interval Record<br>
                            (Database Log)
                        </h3>
                        <span class="threshold-value" id="badge-log-interval">{{ $threshold->log_interval ?? 30 }} Detik</span>
                    </div>

                    <select name="log_interval" id="log_interval"
                            onchange="document.getElementById('badge-log-interval').textContent = this.value + ' Detik'"
                            style="width: 100%; margin: 15px 0; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; font-weight: 600; color: #0f243d; background: #fff; cursor: pointer;">
                        <option value="10" {{ old('log_interval', $threshold->log_interval ?? 30) == 10 ? 'selected' : '' }}>10 Detik (Rapat / Detail Tinggi)</option>
                        <option value="30" {{ old('log_interval', $threshold->log_interval ?? 30) == 30 ? 'selected' : '' }}>30 Detik (Standar Rekomendasi)</option>
                        <option value="60" {{ old('log_interval', $threshold->log_interval ?? 30) == 60 ? 'selected' : '' }}>60 Detik (1 Menit / Optimal &amp; Ringan)</option>
                        <option value="180" {{ old('log_interval', $threshold->log_interval ?? 30) == 180 ? 'selected' : '' }}>180 Detik (3 Menit / Efisien)</option>
                        <option value="300" {{ old('log_interval', $threshold->log_interval ?? 30) == 300 ? 'selected' : '' }}>300 Detik (5 Menit / Hemat Database)</option>
                        <option value="600" {{ old('log_interval', $threshold->log_interval ?? 30) == 600 ? 'selected' : '' }}>600 Detik (10 Menit / Ultra Hemat Storage)</option>
                    </select>

                    <div class="slider-note">
                        <span>DETAIL (10s)</span>
                        <span>HEMAT (600s)</span>
                    </div>

                    <p>
                        Jeda waktu perekaman permanen ke <b>Database Server (Riwayat Log)</b> agar penyimpanan tidak cepat membengkak.
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
            <input type="hidden" name="device_interval" value="{{ $threshold->device_interval ?? 5 }}">
            <input type="hidden" name="log_interval" value="{{ $threshold->log_interval ?? 30 }}">

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

    {{-- Panel: Reset Energi kWh PZEM --}}
    <div class="panel network" style="margin-top: 20px; border-left: 4px solid #f59e0b;">
        <div class="section-label" style="display: flex; align-items: center; justify-content: space-between;">
            <span style="display: inline-flex; align-items: center; gap: 8px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/>
                    <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>
                </svg>
                Reset Energi kWh PZEM-004T
            </span>
            <span style="font-size: 11px; font-weight: 600; background: #fef3c7; color: #b45309; padding: 3px 10px; border-radius: 20px;">
                ⚡ Aksi Permanen
            </span>
        </div>

        <p style="font-size: 12px; color: #64748b; margin: 0 0 16px; line-height: 1.6;">
            Reset register akumulasi energi <strong>kWh</strong> pada modul <strong>PZEM-004T</strong> kembali ke <strong>0</strong>.
            Operasi ini bersifat permanen pada hardware sensor dan <em>tidak dapat dibatalkan</em>.
            Gunakan untuk memulai penghitungan ulang konsumsi daya dari nol.
        </p>

        {{-- Nilai kWh saat ini --}}
        @php
            $latestSLog = \App\Models\SensorLog::where('device_id', $device->id)->latest('recorded_at')->first();
            $curE1 = $latestSLog ? number_format((float)$latestSLog->energy_1, 3) : '—';
            $curE2 = $latestSLog ? number_format((float)$latestSLog->energy_2, 3) : '—';
        @endphp
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 20px;">
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px 16px; text-align: center;">
                <div style="font-size: 10px; font-weight: 800; color: #64748b; letter-spacing: 0.4px; text-transform: uppercase; margin-bottom: 6px;">PZEM-1 · Soket 1</div>
                <div style="font-size: 24px; font-weight: 800; color: #0f243d;">{{ $curE1 }}</div>
                <div style="font-size: 11px; font-weight: 700; color: #64748b; margin-top: 2px;">kWh</div>
            </div>
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px 16px; text-align: center;">
                <div style="font-size: 10px; font-weight: 800; color: #64748b; letter-spacing: 0.4px; text-transform: uppercase; margin-bottom: 6px;">PZEM-2 · Soket 2</div>
                <div style="font-size: 24px; font-weight: 800; color: #0f243d;">{{ $curE2 }}</div>
                <div style="font-size: 11px; font-weight: 700; color: #64748b; margin-top: 2px;">kWh</div>
            </div>
        </div>

        {{-- Tombol Reset --}}
        <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">

            {{-- Reset Soket 1 --}}
            <form method="POST" action="{{ route('settings.energy.reset') }}" id="form-reset-e1"
                  onsubmit="return confirm('⚠️ Reset Energi PZEM-1 (Soket 1)?\n\nNilai kWh saat ini: {{ $curE1 }} kWh akan dikembalikan ke 0.\nAksi ini PERMANEN dan tidak dapat dibatalkan.\n\nLanjutkan?')">
                @csrf
                <input type="hidden" name="socket_number" value="1">
                <button type="submit" id="btn-reset-energy-1"
                        style="display: inline-flex; align-items: center; gap: 7px; padding: 9px 18px; border: 1.5px solid #f59e0b; border-radius: 8px; background: #fffbeb; color: #b45309; font-size: 12px; font-weight: 700; cursor: pointer; transition: all 0.2s ease;"
                        onmouseover="this.style.background='#f59e0b';this.style.color='#fff';"
                        onmouseout="this.style.background='#fffbeb';this.style.color='#b45309';">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/>
                    </svg>
                    Reset Soket 1
                </button>
            </form>

            {{-- Reset Soket 2 --}}
            <form method="POST" action="{{ route('settings.energy.reset') }}" id="form-reset-e2"
                  onsubmit="return confirm('⚠️ Reset Energi PZEM-2 (Soket 2)?\n\nNilai kWh saat ini: {{ $curE2 }} kWh akan dikembalikan ke 0.\nAksi ini PERMANEN dan tidak dapat dibatalkan.\n\nLanjutkan?')">
                @csrf
                <input type="hidden" name="socket_number" value="2">
                <button type="submit" id="btn-reset-energy-2"
                        style="display: inline-flex; align-items: center; gap: 7px; padding: 9px 18px; border: 1.5px solid #f59e0b; border-radius: 8px; background: #fffbeb; color: #b45309; font-size: 12px; font-weight: 700; cursor: pointer; transition: all 0.2s ease;"
                        onmouseover="this.style.background='#f59e0b';this.style.color='#fff';"
                        onmouseout="this.style.background='#fffbeb';this.style.color='#b45309';">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/>
                    </svg>
                    Reset Soket 2
                </button>
            </form>

            {{-- Reset Keduanya --}}
            <form method="POST" action="{{ route('settings.energy.reset') }}" id="form-reset-eall"
                  onsubmit="return confirm('🔴 Reset Energi SEMUA SOKET (PZEM-1 & PZEM-2)?\n\nSoket 1: {{ $curE1 }} kWh → 0\nSoket 2: {{ $curE2 }} kWh → 0\n\nAksi ini PERMANEN dan tidak dapat dibatalkan.\n\nLanjutkan reset keduanya?')">
                @csrf
                <input type="hidden" name="socket_number" value="0">
                <button type="submit" id="btn-reset-energy-all"
                        style="display: inline-flex; align-items: center; gap: 7px; padding: 9px 18px; border: 1.5px solid #ef4444; border-radius: 8px; background: #fff1f2; color: #dc2626; font-size: 12px; font-weight: 700; cursor: pointer; transition: all 0.2s ease;"
                        onmouseover="this.style.background='#ef4444';this.style.color='#fff';"
                        onmouseout="this.style.background='#fff1f2';this.style.color='#dc2626';">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="1 4 1 10 7 10"/><polyline points="23 20 23 14 17 14"/>
                        <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>
                    </svg>
                    Reset Keduanya
                </button>
            </form>
        </div>

        <p style="margin-top: 14px; font-size: 11px; color: #94a3b8; line-height: 1.5;">
            <strong style="color: #b45309;">⚠ Catatan:</strong>
            Perintah dikirim melalui MQTT ke ESP32 secara langsung. Nilai kWh di dashboard akan diperbarui otomatis dalam beberapa detik setelah ESP32 menerima dan mengeksekusi perintah. Pastikan perangkat dalam kondisi <strong>Online</strong> sebelum melakukan reset.
        </p>

        @if(session('warning'))
            <div style="margin-top: 12px; padding: 10px 14px; background: #fef3c7; border: 1px solid #fbbf24; border-radius: 8px; font-size: 12px; color: #92400e; font-weight: 600;">
                ⚠ {{ session('warning') }}
            </div>
        @endif
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

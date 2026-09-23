@extends('layouts.app')

@section('title', 'Tentang Sistem')
@section('header_title', 'Socket Monitor')

@section('content')
<section class="content about-main">

    <div class="panel about-card">

        <!-- Introduction -->
        <div class="about-intro">
            <div class="device-photo" style="display: grid; place-items: center; color: #123c62; background: #eef3ff; font-weight: 700; text-align: center; padding: 20px;">
                <div>
                    <div style="font-size: 32px; margin-bottom: 8px; line-height: 1;">
                        <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                    </div>
                    <div>Smart Socket IoT Dual Channel</div>
                    <small style="font-size: 10px; color: #64748b;">ESP32 + Dual PZEM-004T + HiveMQ</small>
                </div>
            </div>

            <div class="about-text">
                Smart Socket merupakan perangkat berbasis Internet of Things (IoT) yang digunakan untuk memantau dan mengendalikan penggunaan listrik pada perangkat elektronik. Sistem ini memanfaatkan sensor ganda PZEM-004T untuk membaca kondisi listrik independen per soket, sensor suhu, dan kadar asap secara real-time, serta menyediakan pengendalian relay jarak jauh melalui broker HiveMQ Cloud TLS.
            </div>
        </div>

        <!-- Features -->
        <div class="panel features">
            <div class="features-title">
                Fitur Unggulan Smart Socket
            </div>

            <div class="feature-grid">

                <!-- Monitoring Real-Time -->
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M4 19V10" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                            <path d="M9.5 19V6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                            <path d="M15 19V12" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                            <path d="M20 19V4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <h3>Monitoring Real-Time</h3>
                    <p>
                        Memantau tegangan, arus, daya, energi, frekuensi, suhu, dan kadar asap secara real-time melalui HiveMQ Cloud MQTT.
                    </p>
                </div>

                <!-- Pengaturan Threshold -->
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M4 6H20" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                            <path d="M4 12H20" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                            <path d="M4 18H20" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                            <circle cx="9" cy="6" r="2" fill="white" stroke="currentColor" stroke-width="1.6"/>
                            <circle cx="15" cy="12" r="2" fill="white" stroke="currentColor" stroke-width="1.6"/>
                            <circle cx="11" cy="18" r="2" fill="white" stroke="currentColor" stroke-width="1.6"/>
                        </svg>
                    </div>
                    <h3>Pengaturan Nilai Threshold</h3>
                    <p>
                        Menentukan batas aman untuk proteksi tegangan berlebih, arus berlebih, suhu, dan kadar asap dengan pemutusan relay otomatis.
                    </p>
                </div>

                <!-- Kontrol Jarak Jauh -->
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <rect x="7" y="2.5" width="10" height="19" rx="2" stroke="currentColor" stroke-width="1.6"/>
                            <path d="M10 6H14" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                            <circle cx="12" cy="18" r="1" fill="currentColor"/>
                        </svg>
                    </div>
                    <h3>Kontrol Jarak Jauh Dual Socket</h3>
                    <p>
                        Mengatur kondisi ON dan OFF secara terpisah pada Relay Socket 1 dan Socket 2 secara instan dari dashboard.
                    </p>
                </div>

                <!-- Notifikasi Keamanan -->
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M18 9C18 5.7 15.3 3 12 3C8.7 3 6 5.7 6 9C6 15.5 3.5 16.5 3.5 18H20.5C20.5 16.5 18 15.5 18 9Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M10 21H14" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <h3>Notifikasi Keamanan</h3>
                    <p>
                        Memberikan peringatan darurat ketika suhu terlalu tinggi atau kadar asap melebihi batas batas aman kebakaran.
                    </p>
                </div>

                <!-- Riwayat Data -->
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="12" cy="12" r="8.5" stroke="currentColor" stroke-width="1.6"/>
                            <path d="M12 7V12L15 14" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <h3>Riwayat Data Telemetri</h3>
                    <p>
                        Menyimpan setiap detik pembacaan sensor ke SQLite/MySQL dan mendukung ekspor log telemetri ke format CSV.
                    </p>
                </div>

                <!-- Analisis Konsumsi -->
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M4 19V5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                            <path d="M4 19H20" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                            <path d="M7 15L10 11L13 13L18 7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <h3>Analisis Beban & Estimasi Biaya</h3>
                    <p>
                        Grafik beban komparatif per jam, perhitungan energi kumulatif kWh, dan estimasi biaya tarif dasar listrik PLN.
                    </p>
                </div>

            </div>
        </div>

        <!-- Statistik Sistem Real dari Database -->
        <div class="panel" style="margin-top: 18px; padding: 20px; background: #f8fafc; border-color: #dce3ef;">
            <div style="font-size: 13px; font-weight: 800; color: #0f243d; margin-bottom: 14px; text-transform: uppercase; letter-spacing: 0.5px;">
                Statistik Sistem & Status Database
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px;">
                <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px;">
                    <div style="font-size: 10px; color: #64748b; font-weight: 700;">TOTAL LOG TELEMETRI</div>
                    <div style="font-size: 20px; font-weight: 800; color: #123f91; margin-top: 6px;">{{ number_format($totalLogs) }}</div>
                    <small style="font-size: 9px; color: #94a3b8;">Tersimpan di database</small>
                </div>
                <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px;">
                    <div style="font-size: 10px; color: #64748b; font-weight: 700;">TOTAL ALARM / INSIDEN</div>
                    <div style="font-size: 20px; font-weight: 800; color: {{ $totalAlerts > 0 ? '#dc2626' : '#16897f' }}; margin-top: 6px;">{{ number_format($totalAlerts) }}</div>
                    <small style="font-size: 9px; color: #94a3b8;">Insiden keamanan terekam</small>
                </div>
                <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px;">
                    <div style="font-size: 10px; color: #64748b; font-weight: 700;">KANAL RELAY SOKET</div>
                    <div style="font-size: 20px; font-weight: 800; color: #0f243d; margin-top: 6px;">{{ $sockets->count() }} Kanal</div>
                    <small style="font-size: 9px; color: #94a3b8;">PZEM-01 & PZEM-02</small>
                </div>
                <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px;">
                    <div style="font-size: 10px; color: #64748b; font-weight: 700;">STATUS ESP32</div>
                    <div style="font-size: 20px; font-weight: 800; color: {{ ($device->status ?? 'offline') === 'online' ? '#16897f' : '#dc2626' }}; margin-top: 6px;">{{ strtoupper($device->status ?? 'OFFLINE') }}</div>
                    <small style="font-size: 9px; color: #94a3b8;">HiveMQ Cloud TLS</small>
                </div>
            </div>
            <div style="margin-top: 14px; padding-top: 12px; border-top: 1px solid #e2e8f0; font-size: 10.5px; color: #64748b; display: flex; justify-content: space-between; flex-wrap: wrap; gap: 8px;">
                <span>Ambang Batas Aktif: <b>{{ $threshold->max_voltage ?? 0 }}V | {{ $threshold->max_current ?? 0 }}A | {{ $threshold->max_temperature ?? 0 }}°C | {{ $threshold->max_smoke_ppm ?? 0 }} ppm</b></span>
                <span>Firmware: <b>{{ $device->firmware_version ? 'v'.$device->firmware_version : 'Belum tersedia' }}</b></span>
            </div>
        </div>

    </div>

</section>
@endsection

@extends('layouts.app')

@section('title', 'Tentang Sistem')
@section('header_title', 'Socket Monitor')

@section('content')
<section class="content about-main">

    <!-- Page Header -->
    <div class="page-header" style="margin-bottom: 20px;">
        <div>
            <h1 class="page-title">Tentang Sistem</h1>
            <p class="page-subtitle">Arsitektur perangkat keras, spesifikasi sensor dual-channel, dan integrasi cloud IoT.</p>
        </div>
    </div>

    <div class="panel about-card">

        <!-- Introduction -->
        <div class="about-intro">
            <div class="device-photo" style="position: relative; overflow: hidden; border-radius: 12px; box-shadow: 0 4px 20px rgba(15, 36, 61, 0.12); border: 1px solid #d8e2ef; background: #0f243d;">
                <img src="{{ asset('img/device/device_perspective.jpg') }}" alt="Foto Fisik Smart Socket IoT" style="width: 100%; height: 100%; min-height: 270px; object-fit: cover; display: block; transition: transform 0.3s ease;" onmouseover="this.style.transform='scale(1.03)'" onmouseout="this.style.transform='scale(1)'">
                <div style="position: absolute; bottom: 0; left: 0; right: 0; padding: 12px 16px; background: linear-gradient(180deg, transparent 0%, rgba(15, 36, 61, 0.88) 100%); color: #fff;">
                    <div style="font-size: 13px; font-weight: 700; display: flex; align-items: center; gap: 6px;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                        Prototipe Fisik Smart Socket IoT
                    </div>
                    <div style="font-size: 10.5px; color: #cbd5e1; margin-top: 2px;">ESP32 · Dual PZEM-004T · LCD 16x2 · Proteksi Kebakaran</div>
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

        <!-- Galeri Prototipe Hardware -->
        <div class="panel" style="margin-top: 20px; padding: 22px; background: #ffffff; border: 1px solid #dce3ef; border-radius: 10px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 16px; flex-wrap: wrap; gap: 8px;">
                <div>
                    <div style="font-size: 15px; font-weight: 800; color: #0f243d; display: flex; align-items: center; gap: 8px;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M20.4 14.5L16 10 4 20"/></svg>
                        Galeri Prototipe & Struktur Fisik Perangkat
                    </div>
                    <p style="font-size: 11.5px; color: #64748b; margin-top: 3px; margin-bottom: 0;">
                        Dokumentasi fisik enclosure modular Smart Socket IoT dual channel dengan standar proteksi lingkungan.
                    </p>
                </div>
                <span style="font-size: 11px; background: #eff6ff; color: #1d4ed8; padding: 4px 10px; border-radius: 20px; font-weight: 600; border: 1px solid #bfdbfe;">
                    Klik foto untuk memperbesar
                </span>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
                
                <!-- Foto 1: Perspektif -->
                <div class="device-gallery-item" onclick="openPhotoModal('{{ asset('img/device/device_perspective.jpg') }}', 'Tampak Sudut Perspektif Enclosure', 'Desain enclosure box proteksi industri dengan jendela LCD 16x2 dan dual outlet outdoor di sisi samping.')" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden; cursor: pointer; transition: all 0.25s ease; box-shadow: 0 2px 6px rgba(0,0,0,0.03);" onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 8px 18px rgba(15, 36, 61, 0.12)'; this.style.borderColor='#93c5fd';" onmouseout="this.style.transform='none'; this.style.boxShadow='0 2px 6px rgba(0,0,0,0.03)'; this.style.borderColor='#e2e8f0';">
                    <div style="height: 190px; overflow: hidden; background: #e2e8f0; position: relative;">
                        <img src="{{ asset('img/device/device_perspective.jpg') }}" alt="Tampak Perspektif Smart Socket" style="width: 100%; height: 100%; object-fit: cover; display: block; transition: transform 0.3s ease;">
                        <span style="position: absolute; top: 8px; right: 8px; background: rgba(15, 23, 42, 0.7); color: #fff; padding: 2px 8px; border-radius: 6px; font-size: 10px; font-weight: 600;">Perspektif</span>
                    </div>
                    <div style="padding: 12px 14px;">
                        <div style="font-size: 12.5px; font-weight: 700; color: #0f243d;">Tampak Perspektif</div>
                        <div style="font-size: 11px; color: #64748b; margin-top: 4px; line-height: 1.5;">Enclosure proteksi luar dengan jendela display LCD & soket outlet samping.</div>
                    </div>
                </div>

                <!-- Foto 2: Tampak Depan LCD -->
                <div class="device-gallery-item" onclick="openPhotoModal('{{ asset('img/device/device_front_lcd.jpg') }}', 'Tampak Depan Display LCD 16x2', 'Panel display visual lokal LCD 16x2 I2C menampilkan pembacaan voltase, arus, suhu enclosure, dan kadar asap secara rotatif.')" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden; cursor: pointer; transition: all 0.25s ease; box-shadow: 0 2px 6px rgba(0,0,0,0.03);" onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 8px 18px rgba(15, 36, 61, 0.12)'; this.style.borderColor='#93c5fd';" onmouseout="this.style.transform='none'; this.style.boxShadow='0 2px 6px rgba(0,0,0,0.03)'; this.style.borderColor='#e2e8f0';">
                    <div style="height: 190px; overflow: hidden; background: #e2e8f0; position: relative;">
                        <img src="{{ asset('img/device/device_front_lcd.jpg') }}" alt="Tampak Depan LCD 16x2" style="width: 100%; height: 100%; object-fit: cover; display: block; transition: transform 0.3s ease;">
                        <span style="position: absolute; top: 8px; right: 8px; background: rgba(15, 23, 42, 0.7); color: #fff; padding: 2px 8px; border-radius: 6px; font-size: 10px; font-weight: 600;">Display Depan</span>
                    </div>
                    <div style="padding: 12px 14px;">
                        <div style="font-size: 12.5px; font-weight: 700; color: #0f243d;">Tampak Depan (LCD 16x2)</div>
                        <div style="font-size: 11px; color: #64748b; margin-top: 4px; line-height: 1.5;">Jendela LCD 16x2 untuk pemantauan metrik dan status WiFi/MQTT secara lokal.</div>
                    </div>
                </div>

                <!-- Foto 3: Dual Outlet Samping -->
                <div class="device-gallery-item" onclick="openPhotoModal('{{ asset('img/device/device_dual_sockets.jpg') }}', 'Dual Outlet Soket Outdoor (Beban 1 & 2)', 'Dua soket output AC tipe outdoor independen dilengkapi tutup berpegas (spring cover) kedap debu dan percikan air.')" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden; cursor: pointer; transition: all 0.25s ease; box-shadow: 0 2px 6px rgba(0,0,0,0.03);" onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 8px 18px rgba(15, 36, 61, 0.12)'; this.style.borderColor='#93c5fd';" onmouseout="this.style.transform='none'; this.style.boxShadow='0 2px 6px rgba(0,0,0,0.03)'; this.style.borderColor='#e2e8f0';">
                    <div style="height: 190px; overflow: hidden; background: #e2e8f0; position: relative;">
                        <img src="{{ asset('img/device/device_dual_sockets.jpg') }}" alt="Dual Socket Outdoor" style="width: 100%; height: 100%; object-fit: cover; display: block; transition: transform 0.3s ease;">
                        <span style="position: absolute; top: 8px; right: 8px; background: rgba(15, 23, 42, 0.7); color: #fff; padding: 2px 8px; border-radius: 6px; font-size: 10px; font-weight: 600;">Sisi Kiri</span>
                    </div>
                    <div style="padding: 12px 14px;">
                        <div style="font-size: 12.5px; font-weight: 700; color: #0f243d;">Dual Soket Outdoor</div>
                        <div style="font-size: 11px; color: #64748b; margin-top: 4px; line-height: 1.5;">2 Kanal colokan beban listrik dengan tutup proteksi pegas cuaca tahan debu.</div>
                    </div>
                </div>

                <!-- Foto 4: Input Power & Fan Pendingin -->
                <div class="device-gallery-item" onclick="openPhotoModal('{{ asset('img/device/device_power_fan.jpg') }}', 'Input Daya AC & Kipas Sirkulasi', 'Port konektor steker daya input utama standar IEC C14 dan lubang exhaust fan pendingin aktif sirkulasi udara.')" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden; cursor: pointer; transition: all 0.25s ease; box-shadow: 0 2px 6px rgba(0,0,0,0.03);" onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 8px 18px rgba(15, 36, 61, 0.12)'; this.style.borderColor='#93c5fd';" onmouseout="this.style.transform='none'; this.style.boxShadow='0 2px 6px rgba(0,0,0,0.03)'; this.style.borderColor='#e2e8f0';">
                    <div style="height: 190px; overflow: hidden; background: #e2e8f0; position: relative;">
                        <img src="{{ asset('img/device/device_power_fan.jpg') }}" alt="Power Input & Exhaust Fan" style="width: 100%; height: 100%; object-fit: cover; display: block; transition: transform 0.3s ease;">
                        <span style="position: absolute; top: 8px; right: 8px; background: rgba(15, 23, 42, 0.7); color: #fff; padding: 2px 8px; border-radius: 6px; font-size: 10px; font-weight: 600;">Sisi Kanan</span>
                    </div>
                    <div style="padding: 12px 14px;">
                        <div style="font-size: 12.5px; font-weight: 700; color: #0f243d;">Input Daya & Kipas Pendingin</div>
                        <div style="font-size: 11px; color: #64748b; margin-top: 4px; line-height: 1.5;">Konektor colokan daya utama IEC AC 220V dan kipas exhaust sirkulasi panas.</div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Lightbox Modal untuk Preview Foto -->
        <div id="devicePhotoModal" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.85); z-index: 9999; backdrop-filter: blur(4px); align-items: center; justify-content: center; padding: 20px;" onclick="closePhotoModal(event)">
            <div style="background: #ffffff; border-radius: 12px; max-width: 720px; width: 100%; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35); position: relative; animation: fadeInModal 0.2s ease-out;" onclick="event.stopPropagation()">
                <div style="position: relative; max-height: 70vh; background: #0b1523; display: flex; align-items: center; justify-content: center;">
                    <img id="modalImg" src="" alt="Preview Perangkat" style="max-width: 100%; max-height: 70vh; object-fit: contain; display: block;">
                    <button type="button" onclick="closePhotoModal()" style="position: absolute; top: 12px; right: 12px; width: 34px; height: 34px; border-radius: 50%; background: rgba(0,0,0,0.6); color: #fff; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 18px; transition: background 0.2s;" onmouseover="this.style.background='rgba(220,38,38,0.9)'" onmouseout="this.style.background='rgba(0,0,0,0.6)'">
                        ✕
                    </button>
                </div>
                <div style="padding: 16px 20px;">
                    <h3 id="modalTitle" style="font-size: 15px; font-weight: 800; color: #0f243d; margin: 0 0 6px 0;"></h3>
                    <p id="modalDesc" style="font-size: 12px; color: #64748b; margin: 0; line-height: 1.6;"></p>
                </div>
            </div>
        </div>

        <script>
            function openPhotoModal(imgSrc, title, desc) {
                const modal = document.getElementById('devicePhotoModal');
                document.getElementById('modalImg').src = imgSrc;
                document.getElementById('modalTitle').innerText = title;
                document.getElementById('modalDesc').innerText = desc;
                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            }

            function closePhotoModal(e) {
                const modal = document.getElementById('devicePhotoModal');
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }

            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closePhotoModal();
                }
            });
        </script>

        <!-- Statistik Sistem Real dari Database -->
        <div class="panel" style="margin-top: 20px; padding: 20px; background: #f8fafc; border-color: #dce3ef;">
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

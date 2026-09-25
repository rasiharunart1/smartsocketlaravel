@extends('layouts.app')

@section('title', 'Dashboard')
@section('header_title', 'Socket Monitor')

@section('content')
<section class="content">

    @php
        $v1 = $sensorLog ? (float) $sensorLog->voltage_1 : ($socket1Telemetry ? (float) $socket1Telemetry->voltage : 0);
        $c1 = $sensorLog ? (float) $sensorLog->current_1 : ($socket1Telemetry ? (float) $socket1Telemetry->current : 0);
        $p1 = $sensorLog ? (float) $sensorLog->power_1 : ($socket1Telemetry ? (float) $socket1Telemetry->power : 0);
        $e1 = $sensorLog ? (float) $sensorLog->energy_1 : ($socket1Telemetry ? (float) $socket1Telemetry->energy : 0);
        $f1 = $sensorLog ? (float) $sensorLog->frequency_1 : ($socket1Telemetry ? (float) $socket1Telemetry->frequency : 0);
        $pf1 = $sensorLog ? (float) $sensorLog->power_factor_1 : ($socket1Telemetry ? (float) $socket1Telemetry->power_factor : 0);

        $v2 = $sensorLog ? (float) $sensorLog->voltage_2 : ($socket2Telemetry ? (float) $socket2Telemetry->voltage : 0);
        $c2 = $sensorLog ? (float) $sensorLog->current_2 : ($socket2Telemetry ? (float) $socket2Telemetry->current : 0);
        $p2 = $sensorLog ? (float) $sensorLog->power_2 : ($socket2Telemetry ? (float) $socket2Telemetry->power : 0);
        $e2 = $sensorLog ? (float) $sensorLog->energy_2 : ($socket2Telemetry ? (float) $socket2Telemetry->energy : 0);
        $f2 = $sensorLog ? (float) $sensorLog->frequency_2 : ($socket2Telemetry ? (float) $socket2Telemetry->frequency : 0);
        $pf2 = $sensorLog ? (float) $sensorLog->power_factor_2 : ($socket2Telemetry ? (float) $socket2Telemetry->power_factor : 0);

        $temp = $sensorLog ? (float) $sensorLog->temperature : ($envLog ? (float) $envLog->temperature : 0);
        $smoke = $sensorLog ? (float) $sensorLog->smoke_ppm : ($envLog ? (float) $envLog->smoke_ppm : 0);

        $totalPower = round($p1 + $p2, 1);
        $totalEnergy = round($e1 + $e2, 3);
        $maxTemp = $threshold->max_temperature ?? 0;
        $maxSmoke = $threshold->max_smoke_ppm ?? 0;
        $maxCurr = $threshold->max_current ?? 0;
    @endphp

    <!-- Header Panel -->
    <div class="panel" style="padding: 18px 22px; margin-bottom: 18px;">
        <div class="page-header" style="margin-bottom: 0;">
            <div>
                <h1 class="page-title">Monitoring Smart Socket</h1>
                <p class="page-subtitle">Dual PZEM-004T &amp; Sensor Lingkungan Real-Time (Single Enclosure)</p>
            </div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <span id="live-indicator-badge" style="font-size: 10.5px; font-weight: 800; color: #16897f; background: #e3f8f5; padding: 4px 12px; border-radius: 12px; display: inline-flex; align-items: center; gap: 6px;">
                    <span style="width: 7px; height: 7px; border-radius: 50%; background: #16897f; animation: pulse 1.8s infinite;"></span> LIVE REAL-TIME
                </span>
            </div>
        </div>
    </div>

    <!-- 1. Enclosure & Combined Overview (4 Kartu) -->
    <div class="overview-grid">
        <!-- Suhu Enclosure -->
        <div class="overview-card">
            <div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 9.5px; font-weight: 800; color: #64748b; letter-spacing: 0.4px;">SUHU ENCLOSURE</span>
                    <span style="color: #123f91;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 14.76V3.5a2.5 2.5 0 0 0-5 0v11.26a4.5 4.5 0 1 0 5 0z"/></svg>
                    </span>
                </div>
                <div style="font-size: 26px; font-weight: 800; color: #0f243d; margin-top: 8px;">
                    <span id="val-temperature">{{ number_format($temp, 1) }}</span> <span style="font-size: 16px; font-weight: 700; color: #64748b;">°C</span>
                </div>
            </div>
            <div>
                <div style="display: flex; justify-content: space-between; font-size: 10px; color: #7c8795; margin-top: 10px;">
                    <span>Batas Maks: {{ $maxTemp }} °C</span>
                    <span id="status-temp" style="font-weight: 700; color: {{ $maxTemp > 0 && $temp > $maxTemp ? '#dc2626' : '#15803d' }};">
                        {{ $maxTemp > 0 && $temp > $maxTemp ? 'Waspada' : 'Aman' }}
                    </span>
                </div>
                <div style="height: 5px; background: #e7edf7; margin-top: 6px; border-radius: 4px; overflow: hidden;">
                    <span id="bar-temperature" style="display: block; width: {{ min(100, max(5, ($temp / max(1, $maxTemp)) * 100)) }}%; height: 100%; background: {{ $maxTemp > 0 && $temp > $maxTemp ? '#dc2626' : '#123f80' }}; transition: width 0.3s ease;"></span>
                </div>
            </div>
        </div>

        <!-- Deteksi Asap MQ-2 -->
        <div class="overview-card">
            <div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 9.5px; font-weight: 800; color: #64748b; letter-spacing: 0.4px;">DETEKSI ASAP (MQ-2)</span>
                    <span style="color: #d97706;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4"/><path d="M12 17h.01"/><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg>
                    </span>
                </div>
                <div style="font-size: 26px; font-weight: 800; color: #0f243d; margin-top: 8px;">
                    <span id="val-smoke">{{ number_format($smoke, 0) }}</span> <span style="font-size: 16px; font-weight: 700; color: #64748b;">ppm</span>
                </div>
            </div>
            <div>
                <div style="display: flex; justify-content: space-between; font-size: 10px; color: #7c8795; margin-top: 10px;">
                    <span>Ambang: {{ $maxSmoke }} ppm</span>
                    <span id="status-smoke" style="font-weight: 700; color: {{ $maxSmoke > 0 && $smoke > $maxSmoke ? '#dc2626' : '#15803d' }};">
                        {{ $maxSmoke > 0 && $smoke > $maxSmoke ? 'Bahaya Asap' : 'Bersih' }}
                    </span>
                </div>
                <div style="height: 5px; background: #e7edf7; margin-top: 6px; border-radius: 4px; overflow: hidden;">
                    <span id="bar-smoke" style="display: block; width: {{ min(100, max(5, ($smoke / max(1, $maxSmoke)) * 100)) }}%; height: 100%; background: {{ $maxSmoke > 0 && $smoke > $maxSmoke ? '#dc2626' : '#d97706' }}; transition: width 0.3s ease;"></span>
                </div>
            </div>
        </div>

        <!-- Total Daya Aktif -->
        <div class="overview-card">
            <div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 9.5px; font-weight: 800; color: #64748b; letter-spacing: 0.4px;">TOTAL DAYA AKTIF</span>
                    <span style="color: #0284c7;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                    </span>
                </div>
                <div style="font-size: 26px; font-weight: 800; color: #0f243d; margin-top: 8px;">
                    <span id="val-total-power">{{ number_format($totalPower, 1) }}</span> <span style="font-size: 16px; font-weight: 700; color: #64748b;">W</span>
                </div>
            </div>
            <div>
                <div style="display: flex; justify-content: space-between; font-size: 10px; color: #7c8795; margin-top: 10px;">
                    <span>Beban Gabungan Soket 1+2</span>
                    <span style="font-weight: 700; color: #0369a1;">Real-time</span>
                </div>
                <div style="height: 5px; background: #e7edf7; margin-top: 6px; border-radius: 4px; overflow: hidden;">
                    <span id="bar-total-power" style="display: block; width: {{ min(100, max(5, ($totalPower / 3500) * 100)) }}%; height: 100%; background: #0284c7; transition: width 0.3s ease;"></span>
                </div>
            </div>
        </div>

        <!-- Total Akumulasi Energi -->
        <div class="overview-card">
            <div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 9.5px; font-weight: 800; color: #64748b; letter-spacing: 0.4px;">TOTAL ENERGI TERPAKAI</span>
                    <span style="color: #10b981;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M16 12l-4-4-4 4M12 16V9"/></svg>
                    </span>
                </div>
                <div style="font-size: 26px; font-weight: 800; color: #0f243d; margin-top: 8px;">
                    <span id="val-total-energy">{{ number_format($totalEnergy, 3) }}</span> <span style="font-size: 16px; font-weight: 700; color: #64748b;">kWh</span>
                </div>
            </div>
            <div>
                <div style="display: flex; justify-content: space-between; font-size: 10px; color: #7c8795; margin-top: 10px;">
                    <span>Akumulasi Kedua Soket</span>
                    <span style="font-weight: 700; color: #059669;">Kumulatif</span>
                </div>
                <div style="height: 5px; background: #e7edf7; margin-top: 6px; border-radius: 4px; overflow: hidden;">
                    <span id="bar-total-energy" style="display: block; width: {{ min(100, max(5, ($totalEnergy / 50) * 100)) }}%; height: 100%; background: #10b981; transition: width 0.3s ease;"></span>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Dual Socket Real-Time Panels (KEDUA SOKET DITAMPILKAN SEKALIGUS TANPA TAB SELECTOR) -->
    <div class="dual-socket-grid" style="margin-top: 0; margin-bottom: 20px;">

        <!-- KANAL SOKET 1 (PZEM-01) -->
        <div id="socket-card-1" class="socket-live-box">
            <!-- Header & Relay Control Soket 1 -->
            <div class="socket-live-header">
                <div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="font-size: 10px; font-weight: 800; background: #eef3ff; color: #123f91; padding: 2px 8px; border-radius: 6px;">KANAL 1</span>
                        <span style="font-size: 10px; font-weight: 700; background: #f1f5f9; color: #475569; padding: 2px 8px; border-radius: 6px;">{{ $socket1->pzem_identifier ?? 'PZEM_01' }}</span>
                    </div>
                    <strong style="font-size: 20px; color: #0f243d; display: block; margin-top: 4px;" id="socket-name-1">{{ $socket1->name ?? 'Socket 1' }}</strong>
                    
                    <div style="display: flex; align-items: center; gap: 12px; margin-top: 6px;">
                        <div id="socket-1-status-indicator" style="font-size: 11.5px; font-weight: 700; color: {{ ($socket1->is_active ?? false) ? '#147f76' : '#8a96a7' }}; display: inline-flex; align-items: center; gap: 5px;">
                            <span style="width: 8px; height: 8px; border-radius: 50%; background: {{ ($socket1->is_active ?? false) ? '#147f76' : '#8a96a7' }}; display: inline-block;"></span>
                            <span id="socket-1-status-text">{{ ($socket1->is_active ?? false) ? 'Online (ON)' : 'Offline (OFF)' }}</span>
                        </div>
                        <span id="socket-1-condition" style="font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 6px; background: {{ ($socket1->status == 'cutoff') ? '#fee2e2' : '#f0fdf4' }}; color: {{ ($socket1->status == 'cutoff') ? '#dc2626' : '#166534' }};">
                            Relay: {{ ucfirst($socket1->status ?? 'Normal') }}
                        </span>
                    </div>
                </div>

                <!-- Toggle Switch 1 -->
                <div style="text-align: right;">
                    <div style="font-size: 10px; font-weight: 700; color: #64748b; margin-bottom: 6px;">SAKLAR RELAY 1</div>
                    <button type="button" onclick="toggleSocket(1)" id="socket-btn-1" style="border: none; cursor: pointer; outline: none; padding: 0; width: 54px; height: 30px; background: {{ ($socket1->is_active ?? false) ? '#159b91' : '#cbd5e1' }}; border-radius: 20px; position: relative; transition: background 0.3s ease; flex-shrink: 0;" aria-label="Toggle Socket 1">
                        <span id="socket-thumb-1" style="position: absolute; {{ ($socket1->is_active ?? false) ? 'right: 4px;' : 'left: 4px;' }} top: 4px; width: 22px; height: 22px; background: #fff; border-radius: 50%; box-shadow: 0 1px 3px rgba(0,0,0,0.2); transition: all 0.3s ease;"></span>
                    </button>
                </div>
            </div>

            <!-- 6 Parameter Telemetri PZEM-01 -->
            <div class="socket-metrics-grid">
                <!-- Tegangan 1 -->
                <div class="metric-subcard">
                    <div class="metric-subcard-title">
                        <span>TEGANGAN</span>
                        <span style="color: #123f91; font-size: 10px;">V</span>
                    </div>
                    <div class="metric-subcard-val">
                        <span id="val-v1">{{ number_format($v1, 1) }}</span> <span style="font-size: 13px; font-weight: 600; color: #64748b;">V</span>
                    </div>
                    <div>
                        <div class="metric-subcard-sub">Nominal 220V</div>
                        <div style="height: 4px; background: #e2e8f0; margin-top: 6px; border-radius: 3px; overflow: hidden;">
                            <span id="bar-v1" style="display: block; width: {{ min(100, max(0, ($v1 / 250) * 100)) }}%; height: 100%; background: #123f80; transition: width 0.3s ease;"></span>
                        </div>
                    </div>
                </div>

                <!-- Arus 1 -->
                <div class="metric-subcard">
                    <div class="metric-subcard-title">
                        <span>ARUS</span>
                        <span style="color: #123f91; font-size: 10px;">A</span>
                    </div>
                    <div class="metric-subcard-val">
                        <span id="val-c1">{{ number_format($c1, 3) }}</span> <span style="font-size: 13px; font-weight: 600; color: #64748b;">A</span>
                    </div>
                    <div>
                        <div class="metric-subcard-sub">Maks: {{ $maxCurr }} A</div>
                        <div style="height: 4px; background: #e2e8f0; margin-top: 6px; border-radius: 3px; overflow: hidden;">
                            <span id="bar-c1" style="display: block; width: {{ min(100, max(0, ($c1 / max(1, $maxCurr)) * 100)) }}%; height: 100%; background: {{ $maxCurr > 0 && $c1 > $maxCurr ? '#dc2626' : '#123f80' }}; transition: width 0.3s ease;"></span>
                        </div>
                    </div>
                </div>

                <!-- Daya 1 -->
                <div class="metric-subcard">
                    <div class="metric-subcard-title">
                        <span>DAYA AKTIF</span>
                        <span style="color: #123f91; font-size: 10px;">W</span>
                    </div>
                    <div class="metric-subcard-val">
                        <span id="val-p1">{{ number_format($p1, 1) }}</span> <span style="font-size: 13px; font-weight: 600; color: #64748b;">W</span>
                    </div>
                    <div>
                        <div class="metric-subcard-sub">Beban PZEM-01</div>
                        <div style="height: 4px; background: #e2e8f0; margin-top: 6px; border-radius: 3px; overflow: hidden;">
                            <span id="bar-p1" style="display: block; width: {{ min(100, max(0, ($p1 / 2000) * 100)) }}%; height: 100%; background: #123f80; transition: width 0.3s ease;"></span>
                        </div>
                    </div>
                </div>

                <!-- Energi 1 -->
                <div class="metric-subcard">
                    <div class="metric-subcard-title">
                        <span>ENERGI</span>
                        <span style="color: #123f91; font-size: 10px;">kWh</span>
                    </div>
                    <div class="metric-subcard-val">
                        <span id="val-e1">{{ number_format($e1, 3) }}</span> <span style="font-size: 13px; font-weight: 600; color: #64748b;">kWh</span>
                    </div>
                    <div>
                        <div class="metric-subcard-sub">Konsumsi Soket 1</div>
                        <div style="height: 4px; background: #e2e8f0; margin-top: 6px; border-radius: 3px; overflow: hidden;">
                            <span id="bar-e1" style="display: block; width: {{ min(100, max(5, ($e1 / 25) * 100)) }}%; height: 100%; background: #15803d; transition: width 0.3s ease;"></span>
                        </div>
                    </div>
                </div>

                <!-- Frekuensi 1 -->
                <div class="metric-subcard">
                    <div class="metric-subcard-title">
                        <span>FREKUENSI</span>
                        <span style="color: #123f91; font-size: 10px;">Hz</span>
                    </div>
                    <div class="metric-subcard-val">
                        <span id="val-f1">{{ number_format($f1, 1) }}</span> <span style="font-size: 13px; font-weight: 600; color: #64748b;">Hz</span>
                    </div>
                    <div>
                        <div class="metric-subcard-sub">Standar: 50.0 Hz</div>
                        <div style="height: 4px; background: #e2e8f0; margin-top: 6px; border-radius: 3px; overflow: hidden;">
                            <span id="bar-f1" style="display: block; width: {{ min(100, max(0, ($f1 / 60) * 100)) }}%; height: 100%; background: #123f80; transition: width 0.3s ease;"></span>
                        </div>
                    </div>
                </div>

                <!-- Power Factor 1 -->
                <div class="metric-subcard">
                    <div class="metric-subcard-title">
                        <span>POWER FACTOR</span>
                        <span style="color: #123f91; font-size: 10px;">PF</span>
                    </div>
                    <div class="metric-subcard-val">
                        <span id="val-pf1">{{ number_format($pf1, 2) }}</span>
                    </div>
                    <div>
                        <div class="metric-subcard-sub">Faktor Daya Listrik</div>
                        <div style="height: 4px; background: #e2e8f0; margin-top: 6px; border-radius: 3px; overflow: hidden;">
                            <span id="bar-pf1" style="display: block; width: {{ min(100, max(0, $pf1 * 100)) }}%; height: 100%; background: #123f80; transition: width 0.3s ease;"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- KANAL SOKET 2 (PZEM-02) -->
        <div id="socket-card-2" class="socket-live-box">
            <!-- Header & Relay Control Soket 2 -->
            <div class="socket-live-header">
                <div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="font-size: 10px; font-weight: 800; background: #eef3ff; color: #123f91; padding: 2px 8px; border-radius: 6px;">KANAL 2</span>
                        <span style="font-size: 10px; font-weight: 700; background: #f1f5f9; color: #475569; padding: 2px 8px; border-radius: 6px;">{{ $socket2->pzem_identifier ?? 'PZEM_02' }}</span>
                    </div>
                    <strong style="font-size: 20px; color: #0f243d; display: block; margin-top: 4px;" id="socket-name-2">{{ $socket2->name ?? 'Socket 2' }}</strong>
                    
                    <div style="display: flex; align-items: center; gap: 12px; margin-top: 6px;">
                        <div id="socket-2-status-indicator" style="font-size: 11.5px; font-weight: 700; color: {{ ($socket2->is_active ?? false) ? '#147f76' : '#8a96a7' }}; display: inline-flex; align-items: center; gap: 5px;">
                            <span style="width: 8px; height: 8px; border-radius: 50%; background: {{ ($socket2->is_active ?? false) ? '#147f76' : '#8a96a7' }}; display: inline-block;"></span>
                            <span id="socket-2-status-text">{{ ($socket2->is_active ?? false) ? 'Online (ON)' : 'Offline (OFF)' }}</span>
                        </div>
                        <span id="socket-2-condition" style="font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 6px; background: {{ ($socket2->status == 'cutoff') ? '#fee2e2' : '#f0fdf4' }}; color: {{ ($socket2->status == 'cutoff') ? '#dc2626' : '#166534' }};">
                            Relay: {{ ucfirst($socket2->status ?? 'Normal') }}
                        </span>
                    </div>
                </div>

                <!-- Toggle Switch 2 -->
                <div style="text-align: right;">
                    <div style="font-size: 10px; font-weight: 700; color: #64748b; margin-bottom: 6px;">SAKLAR RELAY 2</div>
                    <button type="button" onclick="toggleSocket(2)" id="socket-btn-2" style="border: none; cursor: pointer; outline: none; padding: 0; width: 54px; height: 30px; background: {{ ($socket2->is_active ?? false) ? '#159b91' : '#cbd5e1' }}; border-radius: 20px; position: relative; transition: background 0.3s ease; flex-shrink: 0;" aria-label="Toggle Socket 2">
                        <span id="socket-thumb-2" style="position: absolute; {{ ($socket2->is_active ?? false) ? 'right: 4px;' : 'left: 4px;' }} top: 4px; width: 22px; height: 22px; background: #fff; border-radius: 50%; box-shadow: 0 1px 3px rgba(0,0,0,0.2); transition: all 0.3s ease;"></span>
                    </button>
                </div>
            </div>

            <!-- 6 Parameter Telemetri PZEM-02 -->
            <div class="socket-metrics-grid">
                <!-- Tegangan 2 -->
                <div class="metric-subcard">
                    <div class="metric-subcard-title">
                        <span>TEGANGAN</span>
                        <span style="color: #123f91; font-size: 10px;">V</span>
                    </div>
                    <div class="metric-subcard-val">
                        <span id="val-v2">{{ number_format($v2, 1) }}</span> <span style="font-size: 13px; font-weight: 600; color: #64748b;">V</span>
                    </div>
                    <div>
                        <div class="metric-subcard-sub">Nominal 220V</div>
                        <div style="height: 4px; background: #e2e8f0; margin-top: 6px; border-radius: 3px; overflow: hidden;">
                            <span id="bar-v2" style="display: block; width: {{ min(100, max(0, ($v2 / 250) * 100)) }}%; height: 100%; background: #123f80; transition: width 0.3s ease;"></span>
                        </div>
                    </div>
                </div>

                <!-- Arus 2 -->
                <div class="metric-subcard">
                    <div class="metric-subcard-title">
                        <span>ARUS</span>
                        <span style="color: #123f91; font-size: 10px;">A</span>
                    </div>
                    <div class="metric-subcard-val">
                        <span id="val-c2">{{ number_format($c2, 3) }}</span> <span style="font-size: 13px; font-weight: 600; color: #64748b;">A</span>
                    </div>
                    <div>
                        <div class="metric-subcard-sub">Maks: {{ $maxCurr }} A</div>
                        <div style="height: 4px; background: #e2e8f0; margin-top: 6px; border-radius: 3px; overflow: hidden;">
                            <span id="bar-c2" style="display: block; width: {{ min(100, max(0, ($c2 / max(1, $maxCurr)) * 100)) }}%; height: 100%; background: {{ $maxCurr > 0 && $c2 > $maxCurr ? '#dc2626' : '#123f80' }}; transition: width 0.3s ease;"></span>
                        </div>
                    </div>
                </div>

                <!-- Daya 2 -->
                <div class="metric-subcard">
                    <div class="metric-subcard-title">
                        <span>DAYA AKTIF</span>
                        <span style="color: #123f91; font-size: 10px;">W</span>
                    </div>
                    <div class="metric-subcard-val">
                        <span id="val-p2">{{ number_format($p2, 1) }}</span> <span style="font-size: 13px; font-weight: 600; color: #64748b;">W</span>
                    </div>
                    <div>
                        <div class="metric-subcard-sub">Beban PZEM-02</div>
                        <div style="height: 4px; background: #e2e8f0; margin-top: 6px; border-radius: 3px; overflow: hidden;">
                            <span id="bar-p2" style="display: block; width: {{ min(100, max(0, ($p2 / 2000) * 100)) }}%; height: 100%; background: #123f80; transition: width 0.3s ease;"></span>
                        </div>
                    </div>
                </div>

                <!-- Energi 2 -->
                <div class="metric-subcard">
                    <div class="metric-subcard-title">
                        <span>ENERGI</span>
                        <span style="color: #123f91; font-size: 10px;">kWh</span>
                    </div>
                    <div class="metric-subcard-val">
                        <span id="val-e2">{{ number_format($e2, 3) }}</span> <span style="font-size: 13px; font-weight: 600; color: #64748b;">kWh</span>
                    </div>
                    <div>
                        <div class="metric-subcard-sub">Konsumsi Soket 2</div>
                        <div style="height: 4px; background: #e2e8f0; margin-top: 6px; border-radius: 3px; overflow: hidden;">
                            <span id="bar-e2" style="display: block; width: {{ min(100, max(5, ($e2 / 25) * 100)) }}%; height: 100%; background: #15803d; transition: width 0.3s ease;"></span>
                        </div>
                    </div>
                </div>

                <!-- Frekuensi 2 -->
                <div class="metric-subcard">
                    <div class="metric-subcard-title">
                        <span>FREKUENSI</span>
                        <span style="color: #123f91; font-size: 10px;">Hz</span>
                    </div>
                    <div class="metric-subcard-val">
                        <span id="val-f2">{{ number_format($f2, 1) }}</span> <span style="font-size: 13px; font-weight: 600; color: #64748b;">Hz</span>
                    </div>
                    <div>
                        <div class="metric-subcard-sub">Standar: 50.0 Hz</div>
                        <div style="height: 4px; background: #e2e8f0; margin-top: 6px; border-radius: 3px; overflow: hidden;">
                            <span id="bar-f2" style="display: block; width: {{ min(100, max(0, ($f2 / 60) * 100)) }}%; height: 100%; background: #123f80; transition: width 0.3s ease;"></span>
                        </div>
                    </div>
                </div>

                <!-- Power Factor 2 -->
                <div class="metric-subcard">
                    <div class="metric-subcard-title">
                        <span>POWER FACTOR</span>
                        <span style="color: #123f91; font-size: 10px;">PF</span>
                    </div>
                    <div class="metric-subcard-val">
                        <span id="val-pf2">{{ number_format($pf2, 2) }}</span>
                    </div>
                    <div>
                        <div class="metric-subcard-sub">Faktor Daya Listrik</div>
                        <div style="height: 4px; background: #e2e8f0; margin-top: 6px; border-radius: 3px; overflow: hidden;">
                            <span id="bar-pf2" style="display: block; width: {{ min(100, max(0, $pf2 * 100)) }}%; height: 100%; background: #123f80; transition: width 0.3s ease;"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- 3. Aktivitas & Diagnostik Perangkat -->
    <div class="dashboard-split-grid">
        <!-- Aktivitas Terbaru -->
        <div class="panel" style="padding: 20px;">
            <div class="settings-title" style="margin-bottom: 12px;">
                <h2 style="font-size: 15px; font-weight: 800; margin: 0; color: #0f243d;">Aktivitas Terbaru</h2>
                <a href="{{ route('history') }}" style="font-size: 11px; color: #1a4a78; font-weight: 700;">Lihat Semua</a>
            </div>

            <div style="display: flex; flex-direction: column;">
                @forelse($activities as $act)
                    <div style="display: grid; grid-template-columns: 34px 1fr auto; gap: 12px; align-items: center; padding: 11px 0; border-bottom: 1px solid #edf0f5;">
                        <span style="width: 32px; height: 32px; border-radius: 50%; background: #eef3ff; display: grid; place-items: center; font-size: 13px; color: #123f91; font-weight: 700;">
                            @if(str_contains($act->event_type, 'SWITCH'))
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                            @elseif(str_contains($act->event_type, 'ALERT'))
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
                            @elseif(str_contains($act->event_type, 'RECONNECT'))
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l4 2"/></svg>
                            @else
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                            @endif
                        </span>
                        <div>
                            <b style="font-size: 11.5px; color: #1e293b;">{{ $act->title }}</b>
                            <div style="font-size: 10px; color: #64748b; margin-top: 1px;">{{ $act->description }}</div>
                        </div>
                        <small style="font-size: 10px; color: #94a3b8; white-space: nowrap;">{{ $act->created_at->diffForHumans() }}</small>
                    </div>
                @empty
                    <div style="padding: 24px 0; text-align: center; color: #8a96a7; font-size: 12px;">
                        Belum ada aktivitas yang tercatat.
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Diagnostik Perangkat -->
        <div class="panel" style="padding: 20px; background: #f8fafc; border-color: #dce3ef;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
                <h2 style="font-size: 15px; font-weight: 800; margin: 0; color: #0f243d;">Diagnostik Perangkat</h2>
                <span id="diag-status-pill" style="font-size: 10px; font-weight: 800; padding: 3px 8px; border-radius: 10px; background: {{ ($device->status ?? 'offline') === 'online' ? '#e3f8f5' : '#fee2e2' }}; color: {{ ($device->status ?? 'offline') === 'online' ? '#16897f' : '#dc2626' }};">
                    {{ strtoupper($device->status ?? 'OFFLINE') }}
                </span>
            </div>

            <!-- Kekuatan Sinyal WiFi -->
            <div style="display: flex; justify-content: space-between; font-size: 11px; margin-top: 12px;">
                <span style="color: #475569;">Kekuatan Sinyal WiFi</span>
                <b id="diag-wifi">{{ $device->wifi_rssi ?? 0 }} dBm</b>
            </div>

            <div style="height: 6px; background: #dce4ef; border-radius: 5px; margin-top: 6px; overflow: hidden;">
                <span id="diag-wifi-bar" style="display: block; width: {{ max(10, min(100, 100 + ($device->wifi_rssi ?? -50))) }}%; height: 100%; background: #138c83; transition: width 0.3s ease;"></span>
            </div>

            <!-- Suhu Enclosure -->
            <div style="display: flex; justify-content: space-between; font-size: 11px; margin-top: 14px;">
                <span style="color: #475569;">Suhu Enclosure</span>
                <b id="diag-temp">{{ number_format($temp, 1) }} °C</b>
            </div>

            <!-- Deteksi Asap -->
            <div style="display: flex; justify-content: space-between; font-size: 11px; margin-top: 12px;">
                <span style="color: #475569;">Kadar Asap MQ-2</span>
                <b id="diag-smoke">{{ number_format($smoke, 0) }} ppm</b>
            </div>

            <!-- Informasi Jaringan Real dari Database -->
            <div style="border-top: 1px solid #dbe3ef; margin-top: 16px; padding-top: 12px; font-size: 11px; display: grid; gap: 8px;">
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: #64748b;">Alamat IP</span>
                    <b id="diag-ip" style="color: #1e293b;">{{ $device->ip_address ?? 'Belum ada' }}</b>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: #64748b;">MAC Address</span>
                    <b id="diag-mac" style="color: #1e293b;">{{ $device->mac_address ?? 'N/A' }}</b>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: #64748b;">Firmware</span>
                    <b id="diag-fw" style="color: #1e293b;">{{ $device->firmware_version ? 'v'.$device->firmware_version : 'Belum tersedia' }}</b>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: #64748b;">Terakhir Terlihat</span>
                    <b id="diag-last-seen" style="color: #1e293b;">{{ $device->last_seen_at?->diffForHumans() ?? 'Belum pernah' }}</b>
                </div>
            </div>

            <button type="button" onclick="triggerReconnect()" class="wifi-btn" id="btn-reconnect" style="margin-top: 16px; width: 100%; cursor: pointer;">
                Sinkronkan &amp; Rekoneksi Jaringan
            </button>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script src="https://unpkg.com/mqtt@5.3.5/dist/mqtt.min.js"></script>
<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const MAX_TEMP = {{ $maxTemp }};
    const MAX_SMOKE = {{ $maxSmoke }};
    const MAX_CURRENT = {{ $maxCurr }};
    const MQTT_WS_CONFIG = @json($mqttWsConfig ?? null);

    // Toggle Socket function via AJAX & Direct WebSocket
    function toggleSocket(socketNum) {
        const btn = document.getElementById(`socket-btn-${socketNum}`);
        const thumb = document.getElementById(`socket-thumb-${socketNum}`);
        if (!btn) return;

        // 1. Tentukan target status baru
        const isCurrentlyActive = (thumb && thumb.style.right === '4px');
        const targetState = !isCurrentlyActive;

        // 2. OPTIMISTIC UI: Langsung ubah posisi sakelar di layar secara instan (0 ms)!
        applySocketState(socketNum, targetState, 'online');

        // 3. Jika WebSocket terhubung, kirim perintah langsung melalui HiveMQ WSS (< 10 ms)!
        if (mqttWsClient && mqttWsClient.connected && MQTT_WS_CONFIG) {
            const deviceUid = MQTT_WS_CONFIG.device_uid || 'ESP32_SOCKET_01';
            const topicSwitch = `smartsocket/${deviceUid}/command/switch`;
            const payload = JSON.stringify({
                socket_number: socketNum,
                state: targetState ? 'ON' : 'OFF',
                requested_by: 'web_ws_direct',
                timestamp: Math.floor(Date.now() / 1000)
            });
            mqttWsClient.publish(topicSwitch, payload);
        }

        // 4. Sinkronkan ke database Laravel di latar belakang (Background AJAX)
        fetch('{{ route("socket.toggle", absolute: false) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ socket_number: socketNum, state: targetState })
        })
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                // Revert jika backend menolak
                applySocketState(socketNum, isCurrentlyActive, 'offline');
                alert('Gagal mengubah status soket: ' + (data.message || 'Error'));
            }
        })
        .catch(err => {
            // Revert jika koneksi gagal
            applySocketState(socketNum, isCurrentlyActive, 'offline');
            console.error(err);
        });
    }

    function applySocketState(socketNum, isActive, status = 'offline') {
        const btn = document.getElementById(`socket-btn-${socketNum}`);
        const thumb = document.getElementById(`socket-thumb-${socketNum}`);
        const indicator = document.getElementById(`socket-${socketNum}-status-indicator`);
        const cond = document.getElementById(`socket-${socketNum}-condition`);

        if (btn && thumb) {
            if (isActive) {
                btn.style.background = '#159b91';
                thumb.style.right = '4px';
                thumb.style.left = 'auto';
            } else {
                btn.style.background = '#cbd5e1';
                thumb.style.left = '4px';
                thumb.style.right = 'auto';
            }
        }

        if (indicator) {
            indicator.style.color = isActive ? '#147f76' : '#8a96a7';
            indicator.innerHTML = `
                <span style="width: 8px; height: 8px; border-radius: 50%; background: ${isActive ? '#147f76' : '#8a96a7'}; display: inline-block;"></span>
                <span id="socket-${socketNum}-status-text">${isActive ? 'Online (ON)' : 'Offline (OFF)'}</span>
            `;
        }

        if (cond) {
            const isCutoff = (status === 'cutoff');
            cond.textContent = 'Relay: ' + (status ? status.charAt(0).toUpperCase() + status.slice(1) : 'Normal');
            cond.style.background = isCutoff ? '#fee2e2' : '#f0fdf4';
            cond.style.color = isCutoff ? '#dc2626' : '#166534';
        }
    }

    // Trigger Reconnect
    function triggerReconnect() {
        const btn = document.getElementById('btn-reconnect');
        if (!btn) return;
        btn.disabled = true;
        btn.textContent = 'Mengirim perintah...';

        fetch('{{ route("device.reconnect", absolute: false) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            btn.textContent = 'Sinkronkan & Rekoneksi Jaringan';
            alert(data.message || 'Perintah rekoneksi telah dikirim.');
        })
        .catch(err => {
            btn.disabled = false;
            btn.textContent = 'Sinkronkan & Rekoneksi Jaringan';
            console.error(err);
        });
    }

    // Update Socket Metric Elements Helper
    function updateSocketChannelMetrics(num, s) {
        if (!s) return;

        // Voltage
        const vEl = document.getElementById(`val-v${num}`);
        const vBar = document.getElementById(`bar-v${num}`);
        if (vEl && s.voltage !== undefined) vEl.textContent = parseFloat(s.voltage).toFixed(1);
        if (vBar && s.voltage !== undefined) vBar.style.width = Math.min(100, Math.max(0, (s.voltage / 250) * 100)) + '%';

        // Current
        const cEl = document.getElementById(`val-c${num}`);
        const cBar = document.getElementById(`bar-c${num}`);
        if (cEl && s.current !== undefined) cEl.textContent = parseFloat(s.current).toFixed(3);
        if (cBar && s.current !== undefined) {
            const cPct = Math.min(100, Math.max(0, (s.current / Math.max(1, MAX_CURRENT)) * 100));
            cBar.style.width = cPct + '%';
            cBar.style.background = MAX_CURRENT > 0 && s.current > MAX_CURRENT ? '#dc2626' : '#123f80';
        }

        // Power
        const pEl = document.getElementById(`val-p${num}`);
        const pBar = document.getElementById(`bar-p${num}`);
        if (pEl && s.power !== undefined) pEl.textContent = parseFloat(s.power).toFixed(1);
        if (pBar && s.power !== undefined) pBar.style.width = Math.min(100, Math.max(0, (s.power / 2000) * 100)) + '%';

        // Energy
        const eEl = document.getElementById(`val-e${num}`);
        const eBar = document.getElementById(`bar-e${num}`);
        if (eEl && s.energy !== undefined) eEl.textContent = parseFloat(s.energy).toFixed(3);
        if (eBar && s.energy !== undefined) eBar.style.width = Math.min(100, Math.max(5, (s.energy / 25) * 100)) + '%';

        // Frequency
        const fEl = document.getElementById(`val-f${num}`);
        const fBar = document.getElementById(`bar-f${num}`);
        if (fEl && s.frequency !== undefined) fEl.textContent = parseFloat(s.frequency).toFixed(1);
        if (fBar && s.frequency !== undefined) fBar.style.width = Math.min(100, Math.max(0, (s.frequency / 60) * 100)) + '%';

        // Power Factor
        const pfEl = document.getElementById(`val-pf${num}`);
        const pfBar = document.getElementById(`bar-pf${num}`);
        if (pfEl && s.power_factor !== undefined) pfEl.textContent = parseFloat(s.power_factor).toFixed(2);
        if (pfBar && s.power_factor !== undefined) pfBar.style.width = Math.min(100, Math.max(0, s.power_factor * 100)) + '%';

        // Apply switch & condition
        if (s.is_active !== undefined) {
            applySocketState(num, s.is_active, s.status || 'offline');
        }
    }

    // Helper to update Device Diagnostics UI
    function updateDeviceStatusUI(status, rssi, ip, lastSeen) {
        const isOnline = (status === 'online');
        const pill = document.getElementById('diag-status-pill');
        if (pill) {
            pill.textContent = (status || 'online').toUpperCase();
            pill.style.background = isOnline ? '#e3f8f5' : '#fee2e2';
            pill.style.color = isOnline ? '#16897f' : '#dc2626';
        }

        const wifiIcon = document.getElementById('wifiStatusIcon');
        if (wifiIcon) {
            wifiIcon.style.color = isOnline ? '#087c71' : '#8a96a7';
            wifiIcon.title = `Status Jaringan ESP32: ${isOnline ? 'Terhubung (Online)' : 'Terputus (Offline)'}`;
        }

        if (rssi !== null && rssi !== undefined) {
            const wEl = document.getElementById('diag-wifi');
            const wBar = document.getElementById('diag-wifi-bar');
            if (wEl) wEl.textContent = rssi + ' dBm';
            if (wBar) {
                const pct = Math.max(10, Math.min(100, 100 + parseFloat(rssi)));
                wBar.style.width = pct + '%';
            }
        }

        if (ip) {
            const ipEl = document.getElementById('diag-ip');
            if (ipEl) ipEl.textContent = ip;
        }

        if (lastSeen) {
            const lsEl = document.getElementById('diag-last-seen');
            if (lsEl) lsEl.textContent = lastSeen;
        }
    }

    // Handler: Process MQTT Telemetry Packet directly from HiveMQ WebSocket
    function handleMqttTelemetry(data) {
        if (!data) return;

        // Update Socket 1
        const s1 = data.sockets?.socket_1 || data.socket_1;
        if (s1) {
            const isActive1 = (String(s1.relay_state).toUpperCase() === 'ON' || s1.relay_state === true || s1.relay_state === 1 || s1.is_active === true);
            updateSocketChannelMetrics(1, {
                voltage: s1.voltage,
                current: s1.current,
                power: s1.power,
                energy: s1.energy,
                frequency: s1.frequency,
                power_factor: s1.power_factor,
                is_active: isActive1,
                status: 'online'
            });
        }

        // Update Socket 2
        const s2 = data.sockets?.socket_2 || data.socket_2;
        if (s2) {
            const isActive2 = (String(s2.relay_state).toUpperCase() === 'ON' || s2.relay_state === true || s2.relay_state === 1 || s2.is_active === true);
            updateSocketChannelMetrics(2, {
                voltage: s2.voltage,
                current: s2.current,
                power: s2.power,
                energy: s2.energy,
                frequency: s2.frequency,
                power_factor: s2.power_factor,
                is_active: isActive2,
                status: 'online'
            });
        }

        // Update Environment (Suhu & Asap)
        const env = data.environmental || {};
        const tempVal = env.temperature !== undefined ? parseFloat(env.temperature) : (data.temperature !== undefined ? parseFloat(data.temperature) : null);
        const smokeVal = env.smoke_ppm !== undefined ? parseFloat(env.smoke_ppm) : (data.smoke_ppm !== undefined ? parseFloat(data.smoke_ppm) : null);

        if (tempVal !== null && !isNaN(tempVal)) {
            const tText = tempVal.toFixed(1);
            const tEl = document.getElementById('val-temperature');
            const dtEl = document.getElementById('diag-temp');
            const tBar = document.getElementById('bar-temperature');
            const tStatus = document.getElementById('status-temp');

            if (tEl) tEl.textContent = tText;
            if (dtEl) dtEl.textContent = tText + ' °C';
            if (tBar) {
                tBar.style.width = Math.min(100, Math.max(5, (tempVal / Math.max(1, MAX_TEMP)) * 100)) + '%';
                tBar.style.background = MAX_TEMP > 0 && tempVal > MAX_TEMP ? '#dc2626' : '#123f80';
            }
            if (tStatus) {
                tStatus.textContent = MAX_TEMP > 0 && tempVal > MAX_TEMP ? 'Waspada' : 'Aman';
                tStatus.style.color = MAX_TEMP > 0 && tempVal > MAX_TEMP ? '#dc2626' : '#15803d';
            }
        }

        if (smokeVal !== null && !isNaN(smokeVal)) {
            const sText = Math.round(smokeVal);
            const sEl = document.getElementById('val-smoke');
            const dsEl = document.getElementById('diag-smoke');
            const sBar = document.getElementById('bar-smoke');
            const sStatus = document.getElementById('status-smoke');

            if (sEl) sEl.textContent = sText;
            if (dsEl) dsEl.textContent = sText + ' ppm';
            if (sBar) {
                sBar.style.width = Math.min(100, Math.max(5, (smokeVal / Math.max(1, MAX_SMOKE)) * 100)) + '%';
                sBar.style.background = MAX_SMOKE > 0 && smokeVal > MAX_SMOKE ? '#dc2626' : '#d97706';
            }
            if (sStatus) {
                sStatus.textContent = MAX_SMOKE > 0 && smokeVal > MAX_SMOKE ? 'Bahaya Asap' : 'Bersih';
                sStatus.style.color = MAX_SMOKE > 0 && smokeVal > MAX_SMOKE ? '#dc2626' : '#15803d';
            }
        }

        // Total Power & Total Energy
        const p1 = s1 ? (parseFloat(s1.power) || 0) : 0;
        const p2 = s2 ? (parseFloat(s2.power) || 0) : 0;
        const totPower = p1 + p2;
        const tpEl = document.getElementById('val-total-power');
        const tpBar = document.getElementById('bar-total-power');
        if (tpEl) tpEl.textContent = totPower.toFixed(1);
        if (tpBar) tpBar.style.width = Math.min(100, Math.max(5, (totPower / 3500) * 100)) + '%';

        const e1 = s1 ? (parseFloat(s1.energy) || 0) : 0;
        const e2 = s2 ? (parseFloat(s2.energy) || 0) : 0;
        const totEnergy = e1 + e2;
        const teEl = document.getElementById('val-total-energy');
        const teBar = document.getElementById('bar-total-energy');
        if (teEl) teEl.textContent = totEnergy.toFixed(3);
        if (teBar) teBar.style.width = Math.min(100, Math.max(5, (totEnergy / 50) * 100)) + '%';

        // Update online status
        updateDeviceStatusUI('online', null, null, 'Baru saja');
    }

    // Handler: Process MQTT Status Packet (Heartbeat & WiFi)
    function handleMqttStatus(data) {
        if (!data) return;
        updateDeviceStatusUI(data.status, data.wifi_rssi, data.ip_address, 'Baru saja');
    }

    // Handler: Process MQTT Alert Packet
    function handleMqttAlert(data) {
        console.warn('[MQTT Alert Terdeteksi]:', data);
        // Refresh notifikasi dropdown seketika dari database
        fetch('{{ route("api.notifications", absolute: false) }}', { headers: { 'Accept': 'application/json' } })
            .then(res => res.json())
            .then(d => {
                if (d && d.success && typeof window.renderNotificationItems === 'function') {
                    window.renderNotificationItems(d.notifications, d.unread_count);
                }
            })
            .catch(() => {});
    }

    // =========================================================================
    //  HIVEMQ CLOUD MQTT OVER WEBSOCKET (WSS PORT 8884)
    // =========================================================================
    let mqttWsClient = null;

    function initMqttWebSocket() {
        if (!MQTT_WS_CONFIG || !MQTT_WS_CONFIG.host || typeof mqtt === 'undefined') {
            console.warn('[MQTT WS] Konfigurasi MQTT tidak lengkap atau MQTT.js belum tersedia. Menggunakan fallback HTTP polling.');
            updateBadgeToPollingFallback();
            return;
        }

        const host = MQTT_WS_CONFIG.host;
        const port = MQTT_WS_CONFIG.port || 8884;
        const path = MQTT_WS_CONFIG.path || '/mqtt';
        const deviceUid = MQTT_WS_CONFIG.device_uid || 'ESP32_SOCKET_01';

        // Random Client ID unik per tab browser
        const clientId = 'web_dashboard_' + Math.random().toString(16).substring(2, 10);
        const wsUrl = `wss://${host}:${port}${path}`;

        console.log(`[MQTT WS] Menghubungkan ke ${wsUrl} dengan ID ${clientId}...`);

        try {
            mqttWsClient = mqtt.connect(wsUrl, {
                clientId: clientId,
                username: MQTT_WS_CONFIG.username || '',
                password: MQTT_WS_CONFIG.password || '',
                clean: true,
                reconnectPeriod: 4000,
                connectTimeout: 10000,
            });

            mqttWsClient.on('connect', () => {
                console.log('[MQTT WS] Sukses terhubung ke HiveMQ Cloud via WebSocket!');
                const liveBadge = document.getElementById('live-indicator-badge');
                if (liveBadge) {
                    liveBadge.innerHTML = `<span style="width: 7px; height: 7px; border-radius: 50%; background: #16897f; animation: pulse 1.8s infinite;"></span> LIVE REAL-TIME (WEBSOCKET)`;
                    liveBadge.style.color = '#16897f';
                    liveBadge.style.background = '#e3f8f5';
                }

                // Subscriptions
                const topicTelemetry = `smartsocket/${deviceUid}/telemetry`;
                const topicStatus = `smartsocket/${deviceUid}/status`;
                const topicAlert = `smartsocket/${deviceUid}/alert`;

                mqttWsClient.subscribe([topicTelemetry, topicStatus, topicAlert], (err) => {
                    if (!err) {
                        console.log(`[MQTT WS] Berlangganan topik: ${topicTelemetry}, ${topicStatus}, ${topicAlert}`);
                    } else {
                        console.error('[MQTT WS] Gagal subscribe:', err);
                    }
                });
            });

            mqttWsClient.on('message', (topic, message) => {
                try {
                    const payload = JSON.parse(message.toString());
                    if (topic.endsWith('/telemetry')) {
                        handleMqttTelemetry(payload);
                    } else if (topic.endsWith('/status')) {
                        handleMqttStatus(payload);
                    } else if (topic.endsWith('/alert')) {
                        handleMqttAlert(payload);
                    }
                } catch (e) {
                    // Abaikan parsing error payload non-json
                }
            });

            mqttWsClient.on('error', (err) => {
                console.warn('[MQTT WS] Error:', err);
                updateBadgeToPollingFallback();
            });

            mqttWsClient.on('close', () => {
                console.warn('[MQTT WS] Terputus. Beralih ke fallback polling HTTP...');
                updateBadgeToPollingFallback();
            });

            mqttWsClient.on('reconnect', () => {
                console.log('[MQTT WS] Menyambung kembali ke broker HiveMQ...');
            });
        } catch (e) {
            console.error('[MQTT WS] Inisialisasi MQTT gagal:', e);
            updateBadgeToPollingFallback();
        }
    }

    function updateBadgeToPollingFallback() {
        const liveBadge = document.getElementById('live-indicator-badge');
        if (liveBadge) {
            liveBadge.innerHTML = `<span style="width: 7px; height: 7px; border-radius: 50%; background: #0284c7; animation: pulse 1.8s infinite;"></span> LIVE REAL-TIME (HTTP)`;
            liveBadge.style.color = '#0284c7';
            liveBadge.style.background = '#e0f2fe';
        }
    }

    // Jalankan inisialisasi WebSocket
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initMqttWebSocket);
    } else {
        initMqttWebSocket();
    }

    // =========================================================================
    //  BACKGROUND FALLBACK HTTP POLLING (Setiap 8 Detik)
    //  Menjamin sinkronisasi database & log notifikasi jika websocket offline
    // =========================================================================
    setInterval(() => {
        // JIKA WEBSOCKET AKTIF & TERHUBUNG:
        // Metrik dialirkan instan (< 100ms) langsung dari ESP32.
        // JANGAN timpa metrik sensor dengan hasil polling database agar nilai tidak bolak-balik ke 0!
        if (mqttWsClient && mqttWsClient.connected) {
            fetch('{{ route("api.notifications", absolute: false) }}')
                .then(res => res.json())
                .then(data => {
                    if (data && data.success && typeof window.renderNotificationItems === 'function') {
                        window.renderNotificationItems(data.notifications, data.unread_count);
                    }
                })
                .catch(() => {});
            return;
        }

        // HANYA JIKA WEBSOCKET OFFLINE (FALLBACK MODE):
        fetch('{{ route("device.telemetry", absolute: false) }}')
            .then(res => res.json())
            .then(data => {
                if (!data) return;

                // Update Socket 1
                if (data.socket_1) {
                    updateSocketChannelMetrics(1, data.socket_1);
                }

                // Update Socket 2
                if (data.socket_2) {
                    updateSocketChannelMetrics(2, data.socket_2);
                }

                // Update Environment (Suhu & Asap)
                if (data.environmental) {
                    if (data.environmental.temperature !== null && data.environmental.temperature !== undefined) {
                        const tempVal = parseFloat(data.environmental.temperature);
                        const tText = tempVal.toFixed(1);
                        const tEl = document.getElementById('val-temperature');
                        const dtEl = document.getElementById('diag-temp');
                        const tBar = document.getElementById('bar-temperature');
                        const tStatus = document.getElementById('status-temp');

                        if (tEl) tEl.textContent = tText;
                        if (dtEl) dtEl.textContent = tText + ' °C';
                        if (tBar) {
                            tBar.style.width = Math.min(100, Math.max(5, (tempVal / Math.max(1, MAX_TEMP)) * 100)) + '%';
                            tBar.style.background = MAX_TEMP > 0 && tempVal > MAX_TEMP ? '#dc2626' : '#123f80';
                        }
                        if (tStatus) {
                            tStatus.textContent = MAX_TEMP > 0 && tempVal > MAX_TEMP ? 'Waspada' : 'Aman';
                            tStatus.style.color = MAX_TEMP > 0 && tempVal > MAX_TEMP ? '#dc2626' : '#15803d';
                        }
                    }

                    if (data.environmental.smoke_ppm !== null && data.environmental.smoke_ppm !== undefined) {
                        const smokeVal = parseFloat(data.environmental.smoke_ppm);
                        const sText = Math.round(smokeVal);
                        const sEl = document.getElementById('val-smoke');
                        const dsEl = document.getElementById('diag-smoke');
                        const sBar = document.getElementById('bar-smoke');
                        const sStatus = document.getElementById('status-smoke');

                        if (sEl) sEl.textContent = sText;
                        if (dsEl) dsEl.textContent = sText + ' ppm';
                        if (sBar) {
                            sBar.style.width = Math.min(100, Math.max(5, (smokeVal / Math.max(1, MAX_SMOKE)) * 100)) + '%';
                            sBar.style.background = MAX_SMOKE > 0 && smokeVal > MAX_SMOKE ? '#dc2626' : '#d97706';
                        }
                        if (sStatus) {
                            sStatus.textContent = MAX_SMOKE > 0 && smokeVal > MAX_SMOKE ? 'Bahaya Asap' : 'Bersih';
                            sStatus.style.color = MAX_SMOKE > 0 && smokeVal > MAX_SMOKE ? '#dc2626' : '#15803d';
                        }
                    }
                }

                // Update Total Power & Total Energy
                if (data.total_power !== undefined) {
                    const tpEl = document.getElementById('val-total-power');
                    const tpBar = document.getElementById('bar-total-power');
                    if (tpEl) tpEl.textContent = parseFloat(data.total_power).toFixed(1);
                    if (tpBar) tpBar.style.width = Math.min(100, Math.max(5, (data.total_power / 3500) * 100)) + '%';
                }

                if (data.total_energy !== undefined) {
                    const teEl = document.getElementById('val-total-energy');
                    const teBar = document.getElementById('bar-total-energy');
                    if (teEl) teEl.textContent = parseFloat(data.total_energy).toFixed(3);
                    if (teBar) teBar.style.width = Math.min(100, Math.max(5, (data.total_energy / 50) * 100)) + '%';
                }

                // Update Device Diagnostics
                if (data.device) {
                    updateDeviceStatusUI(
                        data.device.status,
                        data.device.wifi_rssi,
                        data.device.ip_address,
                        data.device.last_seen
                    );
                }

                // Update notification dropdown live
                if (data.notifications && typeof window.renderNotificationItems === 'function') {
                    window.renderNotificationItems(data.notifications, data.unread_alerts ?? 0);
                }
            })
            .catch(err => {
                // Background poll silent catch
            });
    }, 8000);
</script>
@endpush

@extends('layouts.app')

@section('title', 'Riwayat Telemetri')
@section('header_title', 'Socket Monitor')

@section('content')
<section class="content">

    <!-- Page Header -->
    <div class="history-head">
        <div>
            <h1 class="page-title">Log Riwayat Sensor Enclosure</h1>
            <p class="page-subtitle">
                Rekaman terpadu dual sensor PZEM-004T (Soket 1 &amp; 2), Suhu Enclosure, dan Sensor Asap MQ-2 dalam satu tabel.
            </p>
        </div>

        <div class="export-group">
            <a href="{{ route('history.export', request()->query()) }}" class="export" style="text-decoration: none; display: flex; align-items: center; justify-content: center; text-align: center; gap: 6px;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/></svg>
                <span>Ekspor<br>CSV</span>
            </a>
            <button type="button" onclick="window.print()" class="export" style="cursor: pointer; text-align: center; gap: 6px; display: flex; align-items: center; justify-content: center;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                <span>Cetak<br>Laporan</span>
            </button>
        </div>
    </div>

    <!-- Filter Form -->
    <form method="GET" action="{{ route('history') }}" class="panel filters">
        <div class="field">
            <label for="socket">Tampilan Parameter</label>
            <select name="socket" id="socket" class="search" style="padding: 7px 12px; border: 1px solid #dce3ef; border-radius: 6px; font-size: 11px;">
                <option value="">Semua Sensor (Soket 1 + Soket 2 + Lingkungan)</option>
                <option value="1" {{ request('socket') == '1' ? 'selected' : '' }}>Detail Soket 1 (PZEM-01)</option>
                <option value="2" {{ request('socket') == '2' ? 'selected' : '' }}>Detail Soket 2 (PZEM-02)</option>
            </select>
        </div>

        <div class="field">
            <label for="start_date">Dari Tanggal</label>
            <input class="date" type="date" name="start_date" id="start_date" value="{{ request('start_date') }}">
        </div>

        <div class="field">
            <label for="end_date">Sampai Tanggal</label>
            <input class="date" type="date" name="end_date" id="end_date" value="{{ request('end_date') }}">
        </div>

        <button type="submit" class="filter-btn" style="cursor: pointer;">Terapkan Filter</button>
        @if(request()->hasAny(['socket', 'start_date', 'end_date']))
            <a href="{{ route('history') }}" style="font-size: 11px; color: #dc2626; margin-left: 10px; align-self: center; text-decoration: none;">Reset</a>
        @endif
    </form>

    <!-- Table -->
    <div class="panel table-panel">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    @if(request('socket') == '1')
                        <tr>
                            <th>Timestamp</th>
                            <th>Soket 1 Tegangan</th>
                            <th>Soket 1 Arus</th>
                            <th>Soket 1 Daya</th>
                            <th>Soket 1 Energi</th>
                            <th>Frekuensi</th>
                            <th>Power Factor</th>
                            <th>Suhu</th>
                            <th>Asap MQ-2</th>
                            <th>Status</th>
                        </tr>
                    @elseif(request('socket') == '2')
                        <tr>
                            <th>Timestamp</th>
                            <th>Soket 2 Tegangan</th>
                            <th>Soket 2 Arus</th>
                            <th>Soket 2 Daya</th>
                            <th>Soket 2 Energi</th>
                            <th>Frekuensi</th>
                            <th>Power Factor</th>
                            <th>Suhu</th>
                            <th>Asap MQ-2</th>
                            <th>Status</th>
                        </tr>
                    @else
                        <tr>
                            <th>Timestamp</th>
                            <th>Soket 1 (V / A / W)</th>
                            <th>Soket 2 (V / A / W)</th>
                            <th>Total Beban</th>
                            <th>Total Energi</th>
                            <th>Suhu Enclosure</th>
                            <th>Asap MQ-2</th>
                            <th>Status Enclosure</th>
                        </tr>
                    @endif
                </thead>

                <tbody>
                    @forelse($logs as $log)
                        @php
                            $isAlert = ($log->voltage_1 > 245 || $log->current_1 > 15 || $log->voltage_2 > 245 || $log->current_2 > 15 || $log->temperature > 65 || $log->smoke_ppm > 990);
                        @endphp

                        @if(request('socket') == '1')
                            <tr>
                                <td>{{ $log->recorded_at->format('d-m-Y H:i:s') }}</td>
                                <td><b>{{ number_format($log->voltage_1, 1) }} V</b></td>
                                <td>{{ number_format($log->current_1, 3) }} A</td>
                                <td>{{ number_format($log->power_1, 1) }} W</td>
                                <td>{{ number_format($log->energy_1, 3) }} kWh</td>
                                <td>{{ number_format($log->frequency_1, 1) }} Hz</td>
                                <td>{{ number_format($log->power_factor_1, 2) }}</td>
                                <td>{{ number_format($log->temperature, 1) }} °C</td>
                                <td>{{ number_format($log->smoke_ppm, 0) }} ppm</td>
                                <td>
                                    <span class="status-badge" style="background: {{ ($log->voltage_1 > 245 || $log->current_1 > 15) ? '#fee2e2' : '#e6f7f4' }}; color: {{ ($log->voltage_1 > 245 || $log->current_1 > 15) ? '#dc2626' : '#0d685f' }};">
                                        {{ ($log->voltage_1 > 245 || $log->current_1 > 15) ? 'OVERLOAD' : 'NORMAL' }}
                                    </span>
                                </td>
                            </tr>
                        @elseif(request('socket') == '2')
                            <tr>
                                <td>{{ $log->recorded_at->format('d-m-Y H:i:s') }}</td>
                                <td><b>{{ number_format($log->voltage_2, 1) }} V</b></td>
                                <td>{{ number_format($log->current_2, 3) }} A</td>
                                <td>{{ number_format($log->power_2, 1) }} W</td>
                                <td>{{ number_format($log->energy_2, 3) }} kWh</td>
                                <td>{{ number_format($log->frequency_2, 1) }} Hz</td>
                                <td>{{ number_format($log->power_factor_2, 2) }}</td>
                                <td>{{ number_format($log->temperature, 1) }} °C</td>
                                <td>{{ number_format($log->smoke_ppm, 0) }} ppm</td>
                                <td>
                                    <span class="status-badge" style="background: {{ ($log->voltage_2 > 245 || $log->current_2 > 15) ? '#fee2e2' : '#e6f7f4' }}; color: {{ ($log->voltage_2 > 245 || $log->current_2 > 15) ? '#dc2626' : '#0d685f' }};">
                                        {{ ($log->voltage_2 > 245 || $log->current_2 > 15) ? 'OVERLOAD' : 'NORMAL' }}
                                    </span>
                                </td>
                            </tr>
                        @else
                            <tr>
                                <td>{{ $log->recorded_at->format('d-m-Y H:i:s') }}</td>
                                <td>
                                    <span style="font-weight: 700; color: #123f91;">{{ number_format($log->voltage_1, 1) }}V</span> &bull;
                                    <span>{{ number_format($log->current_1, 2) }}A</span> &bull;
                                    <b>{{ number_format($log->power_1, 1) }}W</b>
                                </td>
                                <td>
                                    <span style="font-weight: 700; color: #123f91;">{{ number_format($log->voltage_2, 1) }}V</span> &bull;
                                    <span>{{ number_format($log->current_2, 2) }}A</span> &bull;
                                    <b>{{ number_format($log->power_2, 1) }}W</b>
                                </td>
                                <td>
                                    <b style="color: #0f243d;">{{ number_format($log->power_1 + $log->power_2, 1) }} W</b>
                                </td>
                                <td>{{ number_format($log->energy_1 + $log->energy_2, 3) }} kWh</td>
                                <td>
                                    <span style="font-weight: 700; color: {{ $log->temperature > 65 ? '#dc2626' : '#15803d' }};">
                                        {{ number_format($log->temperature, 1) }} °C
                                    </span>
                                </td>
                                <td>
                                    <span style="font-weight: 700; color: {{ $log->smoke_ppm > 990 ? '#dc2626' : '#475569' }};">
                                        {{ number_format($log->smoke_ppm, 0) }} ppm
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge" style="background: {{ $isAlert ? '#fee2e2' : '#e6f7f4' }}; color: {{ $isAlert ? '#dc2626' : '#0d685f' }};">
                                        {{ $isAlert ? 'ALERT' : 'NORMAL' }}
                                    </span>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="10" style="text-align: center; padding: 24px; color: #64748b;">
                                Tidak ada catatan log sensor ditemukan untuk filter yang dipilih.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <div class="pagination">
        <span>Menampilkan {{ $logs->firstItem() ?? 0 }} - {{ $logs->lastItem() ?? 0 }} dari {{ $logs->total() }} hasil</span>

        <div class="pages">
            @if($logs->onFirstPage())
                <span class="page" style="opacity: 0.5;">‹</span>
            @else
                <a href="{{ $logs->previousPageUrl() }}" class="page" style="text-decoration: none; color: inherit;">‹</a>
            @endif

            @foreach($logs->getUrlRange(max(1, $logs->currentPage() - 2), min($logs->lastPage(), $logs->currentPage() + 2)) as $page => $url)
                <a href="{{ $url }}" class="page {{ $page == $logs->currentPage() ? 'active' : '' }}" style="text-decoration: none; color: inherit;">
                    {{ $page }}
                </a>
            @endforeach

            @if($logs->hasMorePages())
                <a href="{{ $logs->nextPageUrl() }}" class="page" style="text-decoration: none; color: inherit;">›</a>
            @else
                <span class="page" style="opacity: 0.5;">›</span>
            @endif
        </div>
    </div>

    <!-- Summary Footers -->
    <div class="summary">
        <!-- Total Energy -->
        <div class="summary-card status">
            <div class="sum-icon">
                <div class="temperature-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>
                    </svg>
                </div>
            </div>
            <div>
                <div class="sum-label">Maks Energi Terakumulasi</div>
                @php
                    $maxE1 = $logs->max('energy_1') ?? 0;
                    $maxE2 = $logs->max('energy_2') ?? 0;
                @endphp
                <div class="sum-value">{{ number_format($maxE1 + $maxE2, 3) }} kWh</div>
            </div>
        </div>

        <!-- Temperature -->
        <div class="summary-card status">
            <div class="sum-icon">
                <div class="temperature-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 14.76V3.5a2.5 2.5 0 0 0-5 0v11.26a4.5 4.5 0 1 0 5 0z"/>
                    </svg>
                </div>
            </div>
            <div>
                <div class="sum-label">Suhu Enclosure Terkini</div>
                <div class="sum-value">{{ number_format($currentTemp, 1) }} °C</div>
            </div>
        </div>

        <!-- Smoke -->
        <div class="summary-card status">
            <div class="sum-icon">
                <div class="temperature-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 9v4"/><path d="M12 17h.01"/><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                    </svg>
                </div>
            </div>
            <div>
                <div class="sum-label">Kadar Asap MQ-2 Terkini</div>
                <div class="sum-value">{{ number_format($currentSmoke, 0) }} ppm</div>
            </div>
        </div>

        <!-- Average Power -->
        <div class="summary-card status">
            <div class="sum-icon">
                <div class="temperature-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                    </svg>
                </div>
            </div>
            <div>
                <div class="sum-label">Rata-rata Beban Halaman Ini</div>
                @php
                    $avgP1 = $logs->avg('power_1') ?? 0;
                    $avgP2 = $logs->avg('power_2') ?? 0;
                @endphp
                <div class="sum-value">{{ number_format($avgP1 + $avgP2, 1) }} W</div>
            </div>
        </div>
    </div>

</section>
@endsection

@extends('layouts.app')

@section('title', 'Analisis Energi')
@section('header_title', 'Socket Monitor')

@section('content')
<section class="content">

    <!-- Page Header -->
    <div class="page-header">
        <div>
            <h1 class="page-title">Analisis Energi</h1>
            <p class="page-subtitle">Metrik konsumsi daya real-time dan perbandingan antar soket.</p>
        </div>

        <div class="period-switch">
            <a href="{{ route('analytics', ['period' => 'day']) }}" class="{{ $period === 'day' ? 'active' : '' }}" style="text-decoration: none; display: inline-flex; align-items: center; justify-content: center;">Hari</a>
            <a href="{{ route('analytics', ['period' => 'week']) }}" class="{{ $period === 'week' ? 'active' : '' }}" style="text-decoration: none; display: inline-flex; align-items: center; justify-content: center;">Minggu</a>
            <a href="{{ route('analytics', ['period' => 'month']) }}" class="{{ $period === 'month' ? 'active' : '' }}" style="text-decoration: none; display: inline-flex; align-items: center; justify-content: center;">Bulan</a>
        </div>
    </div>

    <!-- Chart Layout -->
    <div class="analytics-layout">
        <div class="panel chart-panel">
            <div class="chart-header">
                <div>
                    <div class="chart-kicker">
                        PENGGUNAAN DAYA PER INTERVAL (WATT / KW)
                    </div>
                    <div class="chart-title">
                        Pemantauan Beban Ganda (Soket 1 vs Soket 2)
                    </div>
                    <div class="legend">
                        <span>
                            <span class="dot a"></span>
                            Soket 1 (PZEM-01)
                        </span>
                        <span>
                            <span class="dot b"></span>
                            Soket 2 (PZEM-02)
                        </span>
                    </div>
                </div>

                <a href="{{ route('history.export') }}" class="btn-outline" style="text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/></svg>
                    Ekspor Data
                </a>
            </div>

            <!-- Bar Chart -->
            @php
                $chartCeil = max(50, $maxChartPower ?? 100);
            @endphp
            <div class="bar-chart" style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
                <div class="y-axis">
                    <span>{{ round($chartCeil) }} W</span>
                    <span>{{ round($chartCeil * 0.8) }} W</span>
                    <span>{{ round($chartCeil * 0.6) }} W</span>
                    <span>{{ round($chartCeil * 0.4) }} W</span>
                    <span>{{ round($chartCeil * 0.2) }} W</span>
                    <span>0 W</span>
                </div>

                <div class="chart-area" style="min-width: 320px;">
                    @foreach($chartData as $data)
                        <div class="bar-group">
                            <!-- Soket 1 Bar -->
                            <div class="bar-wrapper">
                                <span class="bar-value">{{ $data['socket_1'] > 0 ? $data['socket_1'] : '0' }}</span>
                                <span class="bar" style="height: {{ max(4, min(100, ($data['socket_1'] / $chartCeil) * 100)) }}%; background: #123f91;" title="Soket 1: {{ $data['socket_1'] }} W"></span>
                            </div>

                            <!-- Soket 2 Bar -->
                            <div class="bar-wrapper">
                                <span class="bar-value">{{ $data['socket_2'] > 0 ? $data['socket_2'] : '0' }}</span>
                                <span class="bar" style="height: {{ max(4, min(100, ($data['socket_2'] / $chartCeil) * 100)) }}%; background: #179389;" title="Soket 2: {{ $data['socket_2'] }} W"></span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="periods" style="display: flex; justify-content: space-around; padding-left: 55px; margin-top: 10px; font-size: 10px; color: #64748b; font-weight: 600; min-width: 320px; overflow-x: auto;">
                @foreach($chartData as $data)
                    <span>{{ $data['label'] }}</span>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="analytics-summary">
        <!-- Total Konsumsi -->
        <div class="summary-card">
            <small>TOTAL KONSUMSI ENERGI</small>
            <strong>{{ number_format($totalKwh, 2) }} kWh</strong>
            <span class="summary-change" style="color: #147f76;">
                S1: {{ number_format($totalEnergy1, 2) }} | S2: {{ number_format($totalEnergy2, 2) }} kWh
            </span>
        </div>

        <!-- Daya Puncak -->
        <div class="summary-card">
            <small>DAYA PUNCAK</small>
            <strong>{{ number_format($peakPower, 1) }} W</strong>
            <span class="summary-note">
                Beban tertinggi tercatat
            </span>
        </div>

        <!-- Beban Rata-rata -->
        <div class="summary-card">
            <small>BEBAN RATA-RATA</small>
            <strong class="green">{{ number_format($avgPower, 1) }} W</strong>
            <span class="summary-note">
                Kondisi beban stabil
            </span>
        </div>

        <!-- Estimasi Biaya Listrik -->
        <div class="summary-card">
            <small>ESTIMASI BIAYA (PLN)</small>
            <strong style="color: #1a4a78;">Rp {{ number_format($estimatedCost, 0, ',', '.') }}</strong>
            <span class="summary-note">
                Tarif Rp {{ number_format($plnRate, 2, ',', '.') }} / kWh
            </span>
        </div>
    </div>

</section>
@endsection

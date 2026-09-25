<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Smart Socket') - Energy Monitoring System</title>

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('css/style.css') }}?v=2.4.2">
    <style>
        .user-dropdown {
            position: relative;
            display: inline-block;
        }
        .user-menu {
            display: none;
            position: absolute;
            right: 0;
            top: calc(100% + 10px);
            background: #ffffff;
            border: 1px solid #dce3ef;
            border-radius: 8px;
            box-shadow: 0 4px 14px rgba(0,0,0,0.08);
            min-width: 170px;
            z-index: 100;
            padding: 8px 0;
        }
        .user-dropdown.open .user-menu {
            display: block;
        }
        .user-menu-item {
            display: block;
            width: 100%;
            padding: 8px 16px;
            font-size: 12px;
            color: #4b5563;
            text-align: left;
            background: none;
            border: none;
            cursor: pointer;
            text-decoration: none;
        }
        .user-menu-item:hover {
            background: #f1f5f9;
            color: #1e293b;
        }
        .flash-alert {
            padding: 12px 18px;
            border-radius: 8px;
            margin-bottom: 18px;
            font-size: 13px;
            font-weight: 500;
        }
        .flash-alert-success {
            background: #e6f7f4;
            color: #0d685f;
            border: 1px solid #b7ebd8;
        }
        .flash-alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        /* =========================================================
           NOTIFICATION DROPDOWN & STATUS ALAT (GUARANTEED INLINE)
           ========================================================= */
        .notif-dropdown {
            position: relative;
            display: inline-flex;
            align-items: center;
        }

        .top-icon.notification-icon {
            position: relative;
            background: transparent;
            border: none;
            padding: 8px;
            border-radius: 8px;
            color: #64748b;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        .top-icon.notification-icon:hover,
        .notif-dropdown.open .top-icon.notification-icon {
            background: #f1f5f9;
            color: #0f243d;
        }

        .notification-dot {
            position: absolute;
            top: 5px;
            right: 5px;
            width: 9px;
            height: 9px;
            background: #ef4444;
            border: 2px solid #ffffff;
            border-radius: 50%;
            box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.25);
            animation: pulseNotifDot 2s infinite;
        }

        @keyframes pulseNotifDot {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.2); opacity: 0.85; }
        }

        .notif-menu {
            display: none;
            position: absolute;
            right: 0;
            top: calc(100% + 10px);
            width: 380px;
            max-width: calc(100vw - 32px);
            background: #ffffff !important;
            border: 1px solid #dce3ef !important;
            border-radius: 12px !important;
            box-shadow: 0 16px 36px -6px rgba(15, 36, 61, 0.18), 0 4px 14px rgba(0, 0, 0, 0.08) !important;
            z-index: 9999 !important;
            overflow: hidden;
            animation: notifSlideDown 0.18s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .notif-dropdown.open .notif-menu {
            display: block !important;
        }

        @keyframes notifSlideDown {
            from {
                opacity: 0;
                transform: translateY(-8px) scale(0.98);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .notif-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 16px;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }

        .notif-header-title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 700;
            font-size: 13px;
            color: #0f243d;
        }

        .notif-badge {
            display: inline-block;
            padding: 2px 7px;
            font-size: 10px;
            font-weight: 700;
            color: #ffffff;
            background: #ef4444;
            border-radius: 999px;
            letter-spacing: 0.3px;
        }

        .notif-header-actions {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .notif-lcd-tag {
            font-size: 9.5px;
            font-weight: 700;
            color: #0284c7;
            background: #e0f2fe;
            border: 1px solid #bae6fd;
            padding: 2px 7px;
            border-radius: 5px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .notif-resolve-btn {
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            font-size: 11px;
            font-weight: 600;
            color: #475569;
            cursor: pointer;
            padding: 3px 8px;
            border-radius: 5px;
            transition: all 0.15s ease;
        }

        .notif-resolve-btn:hover {
            color: #0f243d;
            background: #e2e8f0;
        }

        .notif-body {
            max-height: 380px;
            overflow-y: auto;
            overscroll-behavior: contain;
        }

        .notif-body::-webkit-scrollbar {
            width: 5px;
        }

        .notif-body::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }

        .notif-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 12px 16px;
            border-bottom: 1px solid #f1f5f9;
            background: #ffffff;
            transition: background 0.15s ease;
        }

        .notif-item:last-child {
            border-bottom: none;
        }

        .notif-item:hover {
            background: #f8fafc;
        }

        .notif-item.danger {
            background: #fffafa;
            border-left: 3px solid #ef4444;
        }

        .notif-item.danger:hover {
            background: #fef2f2;
        }

        .notif-item.warning {
            border-left: 3px solid #f59e0b;
        }

        .notif-item.success {
            border-left: 3px solid #10b981;
        }

        .notif-item.info {
            border-left: 3px solid #0284c7;
        }

        .notif-item.primary {
            border-left: 3px solid #6366f1;
        }

        .notif-item.muted {
            border-left: 3px solid #94a3b8;
            opacity: 0.85;
        }

        .notif-icon-box {
            flex-shrink: 0;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 2px;
        }

        .notif-item.danger .notif-icon-box {
            background: #fee2e2;
            color: #dc2626;
        }

        .notif-item.warning .notif-icon-box {
            background: #fef3c7;
            color: #d97706;
        }

        .notif-item.success .notif-icon-box {
            background: #d1fae5;
            color: #059669;
        }

        .notif-item.info .notif-icon-box {
            background: #e0f2fe;
            color: #0284c7;
        }

        .notif-item.primary .notif-icon-box {
            background: #e0e7ff;
            color: #4f46e5;
        }

        .notif-item.muted .notif-icon-box,
        .notif-item.neutral .notif-icon-box {
            background: #f1f5f9;
            color: #64748b;
        }

        .notif-content {
            flex: 1;
            min-width: 0;
        }

        .notif-title-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 3px;
        }

        .notif-title {
            margin: 0;
            font-size: 12px;
            font-weight: 700;
            color: #1e293b;
            line-height: 1.35;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .notif-tag {
            flex-shrink: 0;
            font-size: 9px;
            font-weight: 800;
            padding: 1.5px 6px;
            border-radius: 4px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .notif-tag.danger {
            background: #fee2e2;
            color: #991b1b;
        }

        .notif-tag.warning {
            background: #fef3c7;
            color: #92400e;
        }

        .notif-tag.success {
            background: #d1fae5;
            color: #065f46;
        }

        .notif-tag.info {
            background: #e0f2fe;
            color: #075985;
        }

        .notif-tag.primary {
            background: #e0e7ff;
            color: #3730a3;
        }

        .notif-tag.muted,
        .notif-tag.neutral {
            background: #f1f5f9;
            color: #64748b;
        }

        .notif-msg {
            margin: 0 0 4px 0;
            font-size: 11.5px;
            color: #475569;
            line-height: 1.4;
            word-break: break-word;
        }

        .notif-time {
            font-size: 10px;
            color: #94a3b8;
            font-weight: 500;
        }

        .notif-empty {
            padding: 36px 16px;
            text-align: center;
            color: #64748b;
            font-size: 12px;
        }

        .notif-footer {
            padding: 11px 16px;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            text-align: center;
        }

        .notif-view-all {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            font-size: 11.5px;
            font-weight: 600;
            color: #0284c7;
            text-decoration: none;
            transition: color 0.15s ease;
        }

        .notif-view-all:hover {
            color: #0369a1;
            text-decoration: underline;
        }

        @media (max-width: 575.98px) {
            .notif-menu {
                position: fixed !important;
                top: 60px !important;
                left: 12px !important;
                right: 12px !important;
                width: auto !important;
                max-width: none !important;
                max-height: calc(100vh - 80px) !important;
            }
        }
    </style>
    @stack('styles')
</head>
<body>
<div class="app">
    <!-- Backdrop Overlay for Mobile Sidebar -->
    <div class="sidebar-backdrop" id="sidebarBackdrop" onclick="toggleSidebar()"></div>

    <!-- Sidebar -->
    <aside class="sidebar" id="appSidebar">
        <div class="brand">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <div class="brand-title">Smart Socket</div>
                    <div class="brand-subtitle">Energy Monitoring System</div>
                </div>
                <button type="button" class="sidebar-close-btn" onclick="toggleSidebar()" aria-label="Tutup Menu">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
        </div>

        <nav class="nav">
            <a class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                <span class="icon">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                </span>
                Dashboard
            </a>
            <a class="nav-item {{ request()->routeIs('analytics') ? 'active' : '' }}" href="{{ route('analytics') }}">
                <span class="icon">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19V5"/><path d="M4 19h16"/><path d="M8 15l3-4 3 2 5-6"/></svg>
                </span>
                Analytics
            </a>
            <a class="nav-item {{ request()->routeIs('settings') ? 'active' : '' }}" href="{{ route('settings') }}">
                <span class="icon">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09a1.65 1.65 0 0 0 1.51-1 1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33h.01a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51h.01a1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82v.01a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                </span>
                Settings
            </a>
            <a class="nav-item {{ request()->routeIs('history*') ? 'active' : '' }}" href="{{ route('history') }}">
                <span class="icon">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l4 2"/></svg>
                </span>
                History
            </a>
            <a class="nav-item {{ request()->routeIs('about') ? 'active' : '' }}" href="{{ route('about') }}">
                <span class="icon">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                </span>
                About
            </a>
        </nav>
    </aside>

    <!-- Main Content Area -->
    <main class="main">
        <!-- Topbar -->
        <header class="topbar">
            <div class="topbar-left">
                <button type="button" class="menu-toggle" onclick="toggleSidebar()" aria-label="Toggle Menu Navigasi" title="Sembunyikan / Tampilkan Menu">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="3" y1="12" x2="21" y2="12"></line>
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <line x1="3" y1="18" x2="21" y2="18"></line>
                    </svg>
                </button>
                <nav class="topbar-breadcrumb" aria-label="Breadcrumb">
                    <a href="{{ route('dashboard') }}" class="breadcrumb-root">Smart Socket</a>
                    <span class="breadcrumb-sep">/</span>
                    <span class="breadcrumb-current">@yield('title', 'Dashboard')</span>
                </nav>
            </div>

            <div class="topbar-right">
                @php
                    $alertsCount = $unreadAlertsCount ?? ($globalUnreadAlertsCount ?? 0);
                    $notifList = $globalNotifications ?? [];
                    $activeDev = $device ?? ($globalDevice ?? null);
                    $isOnline = ($activeDev && $activeDev->status === 'online');
                @endphp

                <!-- 1. Notifikasi Status Alat & Alarms LCD (Interaktif Dropdown) -->
                <div class="notif-dropdown" id="notifDropdown">
                    <button type="button" class="top-icon notification-icon" id="notifToggleBtn" aria-label="Notifikasi Status Alat" aria-expanded="false" title="{{ $alertsCount }} Peringatan / Status Alat">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M18 9C18 5.69 15.31 3 12 3C8.69 3 6 5.69 6 9C6 16 3 16 3 18H21C21 16 18 16 18 9Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M10 21H14" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                        </svg>
                        <span class="notification-dot" id="topNotificationDot" style="{{ $alertsCount > 0 ? '' : 'display: none;' }}"></span>
                    </button>

                    <div class="notif-menu" id="notifMenu" style="display: none;">
                        <div class="notif-header">
                            <div class="notif-header-title">
                                <span>Status Alat &amp; Peringatan</span>
                                <span class="notif-badge" id="notifBadgeCount" style="{{ $alertsCount > 0 ? '' : 'display: none;' }}">{{ $alertsCount }} Alarm</span>
                            </div>
                            <div class="notif-header-actions">
                                <button type="button" class="notif-resolve-btn" id="notifResolveBtn" onclick="resolveAllNotifications(event)" style="{{ $alertsCount > 0 ? '' : 'display: none;' }}" title="Tandai semua alarm selesai">Tandai Selesai</button>
                                <span class="notif-lcd-tag" title="Sesuai Tampilan Layar LCD Smart Socket">Status LCD</span>
                            </div>
                        </div>

                        <div class="notif-body" id="notifItemsList">
                            @forelse($notifList as $item)
                                <div class="notif-item {{ $item['type'] }} {{ $item['category'] }}">
                                    <div class="notif-icon-box">
                                        @if($item['type'] === 'alarm')
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                                        @elseif($item['type'] === 'connection')
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.55a11 11 0 0 1 14.08 0"/><path d="M1.42 9a16 16 0 0 1 21.16 0"/><path d="M8.53 16.11a6 6 0 0 1 6.95 0"/><circle cx="12" cy="20" r="1"/></svg>
                                        @elseif($item['type'] === 'load')
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                                        @else
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                        @endif
                                    </div>
                                    <div class="notif-content">
                                        <div class="notif-title-row">
                                            <h4 class="notif-title" title="{{ $item['title'] }}">{{ $item['title'] }}</h4>
                                            <span class="notif-tag {{ $item['category'] }}">{{ $item['badge'] }}</span>
                                        </div>
                                        <p class="notif-msg">{{ $item['message'] }}</p>
                                        <div class="notif-time">{{ $item['time'] }}</div>
                                    </div>
                                </div>
                            @empty
                                <div class="notif-empty">
                                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" style="margin: 0 auto 8px; display: block;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                                    <div style="font-weight: 600; color: #475569;">Semua kondisi normal &amp; aman.</div>
                                    <small style="color: #94a3b8;">Tidak ada peringatan atau alarm aktif.</small>
                                </div>
                            @endforelse
                        </div>

                        <div class="notif-footer">
                            <a href="{{ route('history') }}" class="notif-view-all">
                                <span>Lihat Semua Riwayat Log &amp; Alarm</span>
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- 2. WiFi Status (Digeser ke kanan, berdampingan dengan Avatar) -->
                <span class="top-icon wifi-status-icon" title="Status Jaringan ESP32: {{ $isOnline ? 'Terhubung (Online)' : 'Terputus (Offline)' }}" id="wifiStatusIcon" style="color: {{ $isOnline ? '#087c71' : '#8a96a7' }};">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M2.5 8.8C7.8 4.4 16.2 4.4 21.5 8.8" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                        <path d="M5.8 12.2C9.4 9.3 14.6 9.3 18.2 12.2" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                        <path d="M9.3 15.6C10.9 14.4 13.1 14.4 14.7 15.6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                        <circle cx="12" cy="19" r="1" fill="currentColor"/>
                    </svg>
                </span>

                <!-- 3. User Avatar & Dropdown -->
                <div class="user-dropdown" id="userDropdown">
                    <div class="avatar" id="userAvatar" role="button" tabindex="0" aria-haspopup="true" aria-expanded="false" style="cursor: pointer;" title="{{ Auth::user()->name ?? 'User' }}">
                        {{ strtoupper(substr(Auth::user()->name ?? 'US', 0, 2)) }}
                    </div>
                    <div class="user-menu">
                        <div style="padding: 8px 16px; border-bottom: 1px solid #f1f5f9;">
                            <div style="font-weight: 700; font-size: 13px; color: #1e293b;">{{ Auth::user()->name ?? 'Pengguna' }}</div>
                            <div style="font-size: 11px; color: #64748b;">{{ Auth::user()->email ?? '' }}</div>
                        </div>
                        <a href="{{ route('profile.edit') }}" class="user-menu-item">Profil Saya</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="user-menu-item" style="color: #dc2626;">Keluar (Logout)</button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <!-- Page Dynamic Content -->
        @if (session('status'))
            <div class="flash-alert-wrapper">
                <div class="flash-alert flash-alert-success">{{ session('status') }}</div>
            </div>
        @endif
        @if (session('error'))
            <div class="flash-alert-wrapper">
                <div class="flash-alert flash-alert-error">{{ session('error') }}</div>
            </div>
        @endif

        @yield('content')
        @isset($slot)
            {{ $slot }}
        @endisset
    </main>
</div>

<script>
    function toggleSidebar() {
        const isMobile = window.innerWidth < 992;
        const app = document.querySelector('.app');
        const sidebar = document.getElementById('appSidebar');
        const backdrop = document.getElementById('sidebarBackdrop');

        if (isMobile) {
            if (sidebar && backdrop) {
                sidebar.classList.toggle('open');
                backdrop.classList.toggle('open');
                document.body.classList.toggle('sidebar-opened');
            }
        } else {
            if (app) {
                const collapsed = app.classList.toggle('sidebar-collapsed');
                try {
                    localStorage.setItem('smartsocket_sidebar_collapsed', collapsed ? '1' : '0');
                } catch (e) {}
            }
        }
    }

    // Restore sidebar preference on desktop
    (function () {
        try {
            if (window.innerWidth >= 992 && localStorage.getItem('smartsocket_sidebar_collapsed') === '1') {
                document.querySelector('.app')?.classList.add('sidebar-collapsed');
            }
        } catch (e) {}
    })();

    // User profile dropdown
    (function () {
        const dropdown = document.getElementById('userDropdown');
        const avatar = document.getElementById('userAvatar');
        const notifDropdown = document.getElementById('notifDropdown');
        const notifBtn = document.getElementById('notifToggleBtn');

        if (notifBtn && notifDropdown) {
            notifBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                if (dropdown) dropdown.classList.remove('open');
                const isOpen = notifDropdown.classList.toggle('open');
                notifBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                const notifMenu = document.getElementById('notifMenu');
                if (notifMenu) {
                    notifMenu.style.display = isOpen ? 'block' : 'none';
                }
            });
        }

        if (avatar && dropdown) {
            avatar.addEventListener('click', function (e) {
                e.stopPropagation();
                if (notifDropdown) notifDropdown.classList.remove('open');
                const notifMenu = document.getElementById('notifMenu');
                if (notifMenu) notifMenu.style.display = 'none';
                const isOpen = dropdown.classList.toggle('open');
                avatar.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            });

            avatar.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    avatar.click();
                } else if (e.key === 'Escape') {
                    dropdown.classList.remove('open');
                    avatar.setAttribute('aria-expanded', 'false');
                }
            });
        }

        document.addEventListener('click', function (e) {
            if (dropdown && !dropdown.contains(e.target)) {
                dropdown.classList.remove('open');
                avatar?.setAttribute('aria-expanded', 'false');
            }
            if (notifDropdown && !notifDropdown.contains(e.target)) {
                notifDropdown.classList.remove('open');
                notifBtn?.setAttribute('aria-expanded', 'false');
                const notifMenu = document.getElementById('notifMenu');
                if (notifMenu) notifMenu.style.display = 'none';
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                if (dropdown) dropdown.classList.remove('open');
                if (notifDropdown) {
                    notifDropdown.classList.remove('open');
                    const notifMenu = document.getElementById('notifMenu');
                    if (notifMenu) notifMenu.style.display = 'none';
                }
            }
        });
    })();

    // Dynamic Notification Renderer & Live Updater
    window.renderNotificationItems = function(notifications, unreadCount) {
        const list = document.getElementById('notifItemsList');
        const dot = document.getElementById('topNotificationDot');
        const badge = document.getElementById('notifBadgeCount');
        const resolveBtn = document.getElementById('notifResolveBtn');
        const toggleBtn = document.getElementById('notifToggleBtn');

        const count = parseInt(unreadCount) || 0;

        if (dot) dot.style.display = count > 0 ? 'block' : 'none';
        if (badge) {
            badge.textContent = `${count} Alarm`;
            badge.style.display = count > 0 ? 'inline-block' : 'none';
        }
        if (resolveBtn) {
            resolveBtn.style.display = count > 0 ? 'inline-block' : 'none';
        }
        if (toggleBtn) {
            toggleBtn.title = `${count} Peringatan / Status Alat`;
        }

        if (!list) return;

        if (!notifications || notifications.length === 0) {
            list.innerHTML = `
                <div class="notif-empty">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" style="margin: 0 auto 8px; display: block;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    <div style="font-weight: 600; color: #475569;">Semua kondisi normal &amp; aman.</div>
                    <small style="color: #94a3b8;">Tidak ada peringatan atau alarm aktif.</small>
                </div>
            `;
            return;
        }

        let html = '';
        notifications.forEach(item => {
            let iconSvg = '';
            if (item.type === 'alarm') {
                iconSvg = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>`;
            } else if (item.type === 'connection') {
                iconSvg = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.55a11 11 0 0 1 14.08 0"/><path d="M1.42 9a16 16 0 0 1 21.16 0"/><path d="M8.53 16.11a6 6 0 0 1 6.95 0"/><circle cx="12" cy="20" r="1"/></svg>`;
            } else if (item.type === 'load') {
                iconSvg = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>`;
            } else {
                iconSvg = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>`;
            }

            const catClass = item.category || 'neutral';
            const typeClass = item.type || 'activity';

            html += `
                <div class="notif-item ${typeClass} ${catClass}">
                    <div class="notif-icon-box">${iconSvg}</div>
                    <div class="notif-content">
                        <div class="notif-title-row">
                            <h4 class="notif-title" title="${escapeHtml(item.title)}">${escapeHtml(item.title)}</h4>
                            <span class="notif-tag ${catClass}">${escapeHtml(item.badge)}</span>
                        </div>
                        <p class="notif-msg">${escapeHtml(item.message)}</p>
                        <div class="notif-time">${escapeHtml(item.time)}</div>
                    </div>
                </div>
            `;
        });

        list.innerHTML = html;
    };

    function escapeHtml(text) {
        if (!text) return '';
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return String(text).replace(/[&<>"']/g, m => map[m]);
    }

    window.resolveAllNotifications = function(e) {
        if (e) e.stopPropagation();
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        fetch('{{ route("api.notifications.resolve-all", absolute: false) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf || '',
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data && data.success) {
                window.renderNotificationItems(data.notifications, data.unread_count);
            }
        })
        .catch(() => {});
    };

    // Polling dinamis notifikasi setiap 5 detik
    setInterval(function() {
        fetch('{{ route("api.notifications", absolute: false) }}', {
            headers: { 'Accept': 'application/json' }
        })
        .then(res => res.json())
        .then(data => {
            if (data && data.success) {
                window.renderNotificationItems(data.notifications, data.unread_count);
            }
        })
        .catch(() => {});
    }, 5000);
</script>
@stack('scripts')
</body>
</html>


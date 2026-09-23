<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Smart Socket') - Energy Monitoring System</title>

    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
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
                <!-- WiFi Status (Dynamic) -->
                @php
                    $activeDev = $device ?? ($globalDevice ?? null);
                    $isOnline = ($activeDev && $activeDev->status === 'online');
                @endphp
                <span class="top-icon" title="Status Jaringan: {{ $isOnline ? 'Terhubung (Online)' : 'Terputus (Offline)' }}" id="wifiStatusIcon" style="color: {{ $isOnline ? '#087c71' : '#8a96a7' }};">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M2.5 8.8C7.8 4.4 16.2 4.4 21.5 8.8" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                        <path d="M5.8 12.2C9.4 9.3 14.6 9.3 18.2 12.2" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                        <path d="M9.3 15.6C10.9 14.4 13.1 14.4 14.7 15.6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                        <circle cx="12" cy="19" r="1" fill="currentColor"/>
                    </svg>
                </span>

                <!-- Notification Alert (Dynamic) -->
                @php
                    $alertsCount = $unreadAlertsCount ?? ($globalUnreadAlertsCount ?? 0);
                @endphp
                <a href="{{ route('history') }}" class="top-icon notification-icon" title="{{ $alertsCount }} Notifikasi Alarm Keamanan">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M18 9C18 5.69 15.31 3 12 3C8.69 3 6 5.69 6 9C6 16 3 16 3 18H21C21 16 18 16 18 9Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M10 21H14" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                    </svg>
                    @if($alertsCount > 0)
                        <span class="notification-dot" id="topNotificationDot"></span>
                    @endif
                </a>

                <!-- User Avatar & Dropdown -->
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

    (function () {
        const dropdown = document.getElementById('userDropdown');
        const avatar = document.getElementById('userAvatar');
        if (!dropdown || !avatar) return;

        avatar.addEventListener('click', function (e) {
            e.stopPropagation();
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

        document.addEventListener('click', function (e) {
            if (!dropdown.contains(e.target)) {
                dropdown.classList.remove('open');
                avatar.setAttribute('aria-expanded', 'false');
            }
        });
    })();
</script>
@stack('scripts')
</body>
</html>


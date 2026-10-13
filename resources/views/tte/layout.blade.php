<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Dashboard TTE') · Pemerintah Kabupaten Subang</title>
    <link rel="stylesheet" href="{{ asset('tte.css') }}">
</head>

<body>
    <div class="app-shell">
        <aside class="sidebar">
            <a class="brand" href="{{ route('dashboard') }}">
                <span class="brand-mark">TTE</span>
                <span><strong>Dashboard TTE</strong><small>Pemerintah Kabupaten Subang</small></span>
            </a>
            <nav class="main-nav" aria-label="Navigasi utama">
                <span class="nav-label">Dashboard</span>
                <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                    href="{{ route('dashboard') }}"><span>▦</span> Ringkasan</a>
                <a class="nav-link" href="{{ route('dashboard') }}#progress"><span>▤</span> Progress Unit Kerja</a>
                @auth
                    <span class="nav-label">Data Admin</span>
                    <a class="nav-link {{ request()->routeIs('admin.records.index') ? 'active' : '' }}"
                        href="{{ route('admin.records.index') }}"><span>♙</span> User per Unit Kerja</a>
                    <a class="nav-link {{ request()->routeIs('admin.records.upcoming') ? 'active' : '' }}"
                        href="{{ route('admin.records.upcoming') }}"><span>◷</span> Akan Expired</a>
                    <a class="nav-link {{ request()->routeIs('admin.records.expired') ? 'active' : '' }}"
                        href="{{ route('admin.records.expired') }}"><span>◌</span> Sudah Expired</a>
                @endauth
            </nav>
            <div class="sidebar-note">@auth Data rinci hanya tersedia untuk admin yang sudah login.
                @else
                Halaman ini menampilkan statistik agregat tanpa identitas pegawai. @endauth
            </div>
        </aside>

        <main class="main-area">
            <header class="topbar">
                <span class="breadcrumb">Layanan Digital <span>›</span> Tanda Tangan Elektronik</span>
                <div class="top-actions">
                    @auth
                        <span class="admin-label">{{ auth()->user()->name }}</span>
                        <form method="POST" action="{{ route('admin.logout') }}">@csrf<button class="button button-quiet"
                                type="submit">Keluar</button></form>
                    @else
                        <a class="button button-quiet" href="{{ route('login') }}">Login Admin</a>
                    @endauth
                </div>
            </header>

            @if (session('status'))
                <div class="flash-success">{{ session('status') }}</div>
            @endif
            @yield('content')
            <footer class="page-footer">Dashboard Tanda Tangan Elektronik · Pemerintah Kabupaten Subang</footer>
        </main>
    </div>
</body>

</html>

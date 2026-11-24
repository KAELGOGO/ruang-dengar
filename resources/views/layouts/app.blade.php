<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    
    <!-- Scripts -->
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>
<body>
    {{-- Soft pastel blobs drifting behind every page (decoration only). --}}
    <div class="blobs" aria-hidden="true"><span></span><span></span><span></span><span></span><span></span></div>

    <div id="app">
        <nav class="navbar navbar-expand-md navbar-light navbar-soft sticky-top">
            <div class="container">
                <a class="navbar-brand d-flex align-items-center gap-2" href="{{ url('/') }}">
                    <x-dengi size="40" :wave="false" aria-hidden="true" />
                    <span>{{ config('app.name', 'Laravel') }}<span class="brand-dot">.</span></span>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <!-- Left Side Of Navbar -->
                    <ul class="navbar-nav me-auto">
                        @auth
                            @php($role = Auth::user()->role)
                            <li class="nav-item"><a @class(['nav-link', 'active' => request()->routeIs('home')]) href="{{ route('home') }}">Beranda</a></li>
                            @if ($role === \App\Models\User::ROLE_USER)
                                <li class="nav-item"><a @class(['nav-link', 'active' => request()->routeIs('booking.*')]) href="{{ route('booking.create') }}">Cari teman cerita</a></li>
                            @endif
                            @if ($role !== \App\Models\User::ROLE_ADMIN)
                                <li class="nav-item"><a @class(['nav-link', 'active' => request()->routeIs('sessions.*', 'chat.*', 'ratings.*')]) href="{{ route('sessions.index') }}">Sesi saya</a></li>
                            @endif
                            @if ($role === \App\Models\User::ROLE_LISTENER)
                                <li class="nav-item"><a @class(['nav-link', 'active' => request()->routeIs('listener.slots.*')]) href="{{ route('listener.slots.index') }}">Jadwal</a></li>
                                <li class="nav-item"><a @class(['nav-link', 'active' => request()->routeIs('listener.profile.*')]) href="{{ route('listener.profile.edit') }}">Profil listener</a></li>
                            @endif
                            @can('handle-escalations')
                                <li class="nav-item"><a @class(['nav-link', 'active' => request()->routeIs('escalations.*')]) href="{{ route('escalations.index') }}">Antrean eskalasi</a></li>
                            @endcan
                            @if ($role === \App\Models\User::ROLE_ADMIN)
                                <li class="nav-item"><a @class(['nav-link', 'active' => request()->routeIs('admin.verifications.*')]) href="{{ route('admin.verifications.index') }}">Verifikasi</a></li>
                                <li class="nav-item"><a @class(['nav-link', 'active' => request()->routeIs('admin.metrics')]) href="{{ route('admin.metrics') }}">Metrik</a></li>
                            @endif
                        @endauth
                        <li class="nav-item"><a class="nav-link text-danger fw-semibold" href="{{ route('krisis') }}">Bantuan darurat</a></li>
                    </ul>

                    <!-- Right Side Of Navbar -->
                    <ul class="navbar-nav ms-auto">
                        <!-- Authentication Links -->
                        @guest
                            @if (Route::has('login'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('login') }}">Masuk</a>
                                </li>
                            @endif

                            @if (Route::has('register'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('register') }}">Daftar</a>
                                </li>
                            @endif
                        @else
                            <li class="nav-item dropdown">
                                <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                    {{ Auth::user()->name }}
                                </a>

                                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                    <a class="dropdown-item" href="{{ route('logout') }}"
                                       onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                                        Keluar
                                    </a>

                                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                        @csrf
                                    </form>
                                </div>
                            </li>
                        @endguest
                    </ul>
                </div>
            </div>
        </nav>

        <main class="py-4">
            @if (session('status'))
                <div class="container"><div class="alert alert-info alert-dismissible fade show" role="status">
                    {{ session('status') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
                </div></div>
            @endif
            @yield('content')
        </main>

        <footer class="site-footer mt-4">
            <div class="container py-3 small text-muted d-flex flex-wrap gap-2 justify-content-between">
                <span>{{ config('app.name') }} adalah layanan pendamping (support layer), bukan pengganti terapi atau layanan darurat.</span>
                <a href="{{ route('krisis') }}" class="text-danger fw-semibold">Butuh bantuan sekarang?</a>
            </div>
        </footer>
    </div>
</body>
</html>

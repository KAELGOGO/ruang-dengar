<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">

    <!-- Scripts -->
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>
<body>
    <div id="app">
        <nav class="navbar navbar-expand-md navbar-light bg-white shadow-sm">
            <div class="container">
                <a class="navbar-brand" href="{{ url('/') }}">
                    {{ config('app.name', 'Laravel') }}
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <!-- Left Side Of Navbar -->
                    <ul class="navbar-nav me-auto">
                        @auth
                            <li class="nav-item"><a class="nav-link" href="{{ route('home') }}">Beranda</a></li>
                            @if (Auth::user()->role === 'user')
                                <li class="nav-item"><a class="nav-link" href="{{ route('booking.create') }}">Cari teman cerita</a></li>
                            @endif
                            @if (Auth::user()->role !== 'admin')
                                <li class="nav-item"><a class="nav-link" href="{{ route('sessions.index') }}">Sesi saya</a></li>
                            @endif
                            @if (Auth::user()->role === 'listener')
                                <li class="nav-item"><a class="nav-link" href="{{ route('listener.slots.index') }}">Jadwal</a></li>
                                <li class="nav-item"><a class="nav-link" href="{{ route('listener.profile.edit') }}">Profil listener</a></li>
                            @endif
                            @can('handle-escalations')
                                <li class="nav-item"><a class="nav-link" href="{{ route('escalations.index') }}">Antrean eskalasi</a></li>
                            @endcan
                            @if (Auth::user()->role === 'admin')
                                <li class="nav-item"><a class="nav-link" href="{{ route('admin.verifications.index') }}">Verifikasi</a></li>
                                <li class="nav-item"><a class="nav-link" href="{{ route('admin.metrics') }}">Metrik</a></li>
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
                                    <a class="nav-link" href="{{ route('login') }}">{{ __('Login') }}</a>
                                </li>
                            @endif

                            @if (Route::has('register'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('register') }}">{{ __('Register') }}</a>
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
                                        {{ __('Logout') }}
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
                <div class="container"><div class="alert alert-info">{{ session('status') }}</div></div>
            @endif
            @yield('content')
        </main>

        <footer class="container pb-4 small text-muted">
            {{ config('app.name') }} adalah layanan pendamping (support layer), bukan pengganti terapi atau layanan darurat.
        </footer>
    </div>
</body>
</html>

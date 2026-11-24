<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Beranda') — {{ config('app.name') }}</title>
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    @stack('styles')
</head>
<body>
    @include('layouts._sidebar')
    <div class="sidebar-backdrop"></div>

    <div class="app-main">
        <header class="app-topbar d-flex align-items-center justify-content-between">
            <button class="btn btn-light d-lg-none" type="button" data-toggle-sidebar aria-label="Menu">
                <i class="bi bi-list fs-5"></i>
            </button>
            <div class="ms-auto dropdown">
                <a href="#" class="d-flex align-items-center text-decoration-none text-dark dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle fs-4 me-2"></i>
                    <span class="text-start lh-sm">
                        <strong class="d-block">{{ auth()->user()->name }}</strong>
                        <small class="text-muted d-none d-sm-block">{{ auth()->user()->roleLabel() }}</small>
                    </span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="{{ route('profil.show') }}"><i class="bi bi-person me-2"></i>Profil Saya</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item" href="#"
                           onclick="event.preventDefault(); document.getElementById('form-logout').submit();">
                            <i class="bi bi-box-arrow-right me-2"></i>Keluar
                        </a>
                    </li>
                </ul>
                <form id="form-logout" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
            </div>
        </header>

        <main class="app-content">
            <div class="mb-3">
                <h4 class="mb-1">@yield('title')</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb small mb-0">
                        <li class="breadcrumb-item"><a href="{{ route(auth()->user()->homeRoute()) }}">Beranda</a></li>
                        @yield('breadcrumb')
                    </ol>
                </nav>
            </div>

            @yield('content')
        </main>

        <footer class="text-center text-muted small py-3">
            &copy; {{ date('Y') }} Pemerintah Desa {{ config('desa.nama') }}
        </footer>
    </div>

    @stack('scripts')
</body>
</html>

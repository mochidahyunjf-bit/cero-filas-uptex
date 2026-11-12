<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('titulo', 'Cero Filas') · UPTEX</title>
    <script src="{{ asset('vendor/tailwind.js') }}"></script>
    <script>
        tailwind.config = {
            theme: { extend: { colors: {
                uptex: { DEFAULT: '#007A33', dark: '#005C26', lima: '#78BE20', rojo: '#D50000', gris: '#7F7F7F', texto: '#1A1A1A', borde: '#E2E8F0', fondo: '#F8FAFC' }
            } } }
        }
    </script>
    @stack('head')
</head>
<body class="min-h-screen bg-uptex-fondo text-uptex-texto antialiased">
    @auth
        <header class="sticky top-0 z-20 bg-uptex text-white shadow">
            <div class="mx-auto flex max-w-6xl items-center justify-between gap-3 px-4 py-3">
                <a href="{{ url('/') }}" class="flex items-center gap-2 font-bold tracking-tight">
                    <span class="grid h-8 w-8 place-items-center rounded-lg bg-white text-sm font-black text-uptex">UP</span>
                    <span class="leading-tight">Cero Filas<span class="block text-xs font-normal text-white/80">UPTEX</span></span>
                </a>
                <div class="flex items-center gap-3">
                    <div class="hidden text-right text-sm sm:block">
                        <div class="font-semibold">{{ auth()->user()->nombre_completo }}</div>
                        <div class="text-xs text-white/80">{{ auth()->user()->rol->nombre }}</div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="rounded-lg bg-white/15 px-3 py-2 text-sm font-medium hover:bg-white/25">Salir</button>
                    </form>
                </div>
            </div>
        </header>
    @endauth

    <main class="mx-auto w-full max-w-6xl px-4 py-6">
        @if (session('ok'))
            <div class="mb-4 rounded-xl border border-uptex/30 bg-uptex/10 px-4 py-3 text-sm text-uptex-dark">{{ session('ok') }}</div>
        @endif
        @if ($errors->any())
            <div class="mb-4 rounded-xl border border-uptex-rojo/30 bg-uptex-rojo/10 px-4 py-3 text-sm text-uptex-rojo">
                @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
            </div>
        @endif

        @yield('contenido')
    </main>
    @stack('scripts')
</body>
</html>

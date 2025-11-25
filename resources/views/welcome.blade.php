<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Redireccionando...</title>

    {{-- Redirección sin JS --}}
    @auth
        <meta http-equiv="refresh" content="0;url={{ url('/dashboard') }}">
    @else
        <meta http-equiv="refresh" content="0;url={{ route('login') }}">
    @endauth
</head>
<body>
    {{-- Redirección con JS (por si quieres que también funcione con JS) --}}
    <script>
        @auth
            window.location.replace("{{ url('/dashboard') }}");
        @else
            window.location.replace("{{ route('login') }}");
        @endauth
    </script>

    {{-- Fallback visible para navegadores que no sigan meta/JS --}}
    <noscript>
        @auth
            <p>Si no redirige automáticamente, haz clic aquí: <a href="{{ url('/dashboard') }}">Ir al Dashboard</a></p>
        @else
            <p>Si no redirige automáticamente, haz clic aquí: <a href="{{ route('login') }}">Ir a Login</a></p>
        @endauth
    </noscript>
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $title ?? 'Notebook' }}</title>
</head>
<body>
    <nav>
        <a href="{{ route('posts.index') }}">Notebook</a>
        @auth
            <a href="{{ route('posts.create') }}">New post</a>
            <form method="POST" action="{{ route('logout') }}">@csrf<button>Log out</button></form>
        @else
            <a href="{{ route('login') }}">Log in</a>
            <a href="{{ route('register') }}">Register</a>
        @endauth
    </nav>
    @if (session('status'))
        <p>{{ session('status') }}</p>
    @endif
    <main>@yield('content')</main>
</body>
</html>

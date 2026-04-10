<!DOCTYPE html>
<html lang="en" x-data="{ darkMode: localStorage.getItem('dark') === '1' }" :class="{ 'dark': darkMode }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'LibAI') — AI Digital Library</title>
    <meta name="description" content="@yield('description', 'AI-powered digital library')">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/library.css') }}">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="min-h-screen bg-stone-50 dark:bg-stone-950 text-stone-800 dark:text-stone-200 font-sans">

    {{-- Navigation --}}
    <nav class="sticky top-0 z-50 bg-white/90 dark:bg-stone-900/90 backdrop-blur border-b border-stone-200 dark:border-stone-800">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex items-center justify-between h-16">
                <a href="{{ route('home') }}" class="font-display font-bold text-xl">
                    Lib<span class="text-amber-600">AI</span>
                </a>
                <div class="hidden md:flex items-center gap-6 text-sm">
                    <a href="{{ route('home') }}" class="nav-link">Home</a>
                    <a href="{{ route('books.index') }}" class="nav-link">Browse</a>
                    <a href="{{ route('search') }}" class="nav-link">Search</a>
                </div>
                <div class="flex items-center gap-3">
                    <button @click="darkMode = !darkMode; localStorage.setItem('dark', darkMode ? '1' : '0')"
                            class="icon-btn">🌙</button>
                    @guest
                        <a href="{{ route('login') }}" class="btn-outline text-sm">Log in</a>
                        <a href="{{ route('register') }}" class="btn-primary text-sm">Sign up</a>
                    @else
                        <a href="{{ route('dashboard') }}" class="nav-link">Dashboard</a>
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="btn-outline text-sm">Logout</button>
                        </form>
                    @endguest
                </div>
            </div>
        </div>
    </nav>

    {{-- Flash messages --}}
    @if(session('success'))
    <div class="max-w-7xl mx-auto px-4 pt-4">
        <div class="alert-success">{{ session('success') }}</div>
    </div>
    @endif

    <main>
        @yield('content')
    </main>

    <footer class="mt-24 border-t border-stone-200 dark:border-stone-800 py-8 text-center text-sm text-stone-400">
        &copy; {{ date('Y') }} LibAI — AI-Powered Digital Library
    </footer>

    @include('partials.chatbot')

    <script>
        window.LibAI = {
            csrfToken: '{{ csrf_token() }}',
            user: @json(auth()->check() ? ['name' => auth()->user()->name] : null),
        };
    </script>
</body>
</html>
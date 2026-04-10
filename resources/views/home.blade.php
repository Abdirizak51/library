{{-- resources/views/home.blade.php --}}
@extends('layouts.app')
@section('title', 'Home')
@section('description', 'Discover thousands of books with AI-powered search, summaries, and personalised recommendations.')

@section('content')

{{-- ── Hero ── --}}
<section class="hero">
    <div class="hero__decoration"></div>
    <div class="max-w-5xl mx-auto px-4 relative z-10 text-center">
        <p class="section-eyebrow mb-4 fade-up">Powered by Artificial Intelligence</p>
        <h1 class="hero__title fade-up fade-up-delay-1">
            Your <span class="hero__accent">intelligent</span> library<br>awaits
        </h1>
        <p class="mt-6 text-stone-300 text-lg max-w-2xl mx-auto fade-up fade-up-delay-2">
            Discover books through natural language search, get instant AI summaries,
            and receive personalised recommendations curated just for you.
        </p>
        <form action="{{ route('search') }}" method="GET" class="mt-10 max-w-xl mx-auto fade-up fade-up-delay-3">
            <div class="flex gap-2 bg-white/10 backdrop-blur p-1.5 rounded-xl border border-white/20">
                <input type="text" name="q" placeholder='"books about building habits"'
                       class="flex-1 bg-transparent text-white placeholder:text-stone-400 px-4 py-2.5 outline-none text-sm">
                <button type="submit" class="btn-primary rounded-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    Search
                </button>
            </div>
        </form>

        {{-- Quick stats --}}
        <div class="mt-12 flex justify-center gap-10 text-sm text-stone-400">
            <div><span class="text-white font-semibold text-2xl font-display">10k+</span><br>Books</div>
            <div><span class="text-white font-semibold text-2xl font-display">AI</span><br>Search</div>
            <div><span class="text-white font-semibold text-2xl font-display">Free</span><br>Forever</div>
        </div>
    </div>
</section>

{{-- ── Categories ── --}}
<section class="max-w-7xl mx-auto px-4 py-16">
    <p class="section-eyebrow mb-2">Explore</p>
    <h2 class="section-title mb-8">Browse by category</h2>
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
        @foreach($categories as $cat)
        <a href="{{ route('books.index', ['category' => $cat->slug]) }}"
           class="group flex items-center gap-3 p-4 rounded-xl bg-white border border-stone-200 hover:border-amber-400 hover:shadow-md transition-all duration-200">
            <span class="text-2xl">{{ $cat->icon ?? '📖' }}</span>
            <div>
                <div class="font-medium text-stone-800 text-sm group-hover:text-amber-600">{{ $cat->name }}</div>
                <div class="text-xs text-stone-400">{{ $cat->books_count }} books</div>
            </div>
        </a>
        @endforeach
    </div>
</section>

{{-- ── Featured Books ── --}}
<section class="max-w-7xl mx-auto px-4 pb-16">
    <div class="flex items-center justify-between mb-8">
        <div>
            <p class="section-eyebrow mb-1">Top Rated</p>
            <h2 class="section-title">Featured books</h2>
        </div>
        <a href="{{ route('books.index', ['sort' => 'rating']) }}" class="btn-outline text-sm">View all</a>
    </div>
    <div class="book-grid">
        @foreach($featured as $book)
        @include('partials.book-card', ['book' => $book])
        @endforeach
    </div>
</section>

{{-- ── New Arrivals ── --}}
<section class="bg-stone-100 py-16">
    <div class="max-w-7xl mx-auto px-4">
        <div class="flex items-center justify-between mb-8">
            <div>
                <p class="section-eyebrow mb-1">Just Added</p>
                <h2 class="section-title">New arrivals</h2>
            </div>
            <a href="{{ route('books.index', ['sort' => 'newest']) }}" class="btn-outline text-sm">See more</a>
        </div>
        <div class="book-grid">
            @foreach($newArrivals as $book)
            @include('partials.book-card', ['book' => $book])
            @endforeach
        </div>
    </div>
</section>

{{-- ── CTA ── --}}
@guest
<section class="max-w-3xl mx-auto px-4 py-24 text-center">
    <h2 class="section-title mb-4">Join thousands of readers</h2>
    <p class="text-stone-500 mb-8">Create a free account to save favourites, track reading progress, and get AI-powered recommendations.</p>
    <a href="{{ route('register') }}" class="btn-primary text-base">Get started — it's free</a>
</section>
@endguest

@endsection

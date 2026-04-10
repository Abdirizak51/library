{{-- resources/views/books/index.blade.php --}}
@extends('layouts.app')
@section('title', 'Browse Books')
@section('description', 'Browse our full catalogue of books. Filter by category, sort by rating or newest arrivals.')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-12">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-10">
        <div>
            <p class="section-eyebrow mb-1">Catalogue</p>
            <h1 class="section-title">Browse all books</h1>
        </div>

        {{-- Sort --}}
        <form method="GET" action="{{ route('books.index') }}" class="flex gap-2 items-center flex-wrap">
            <input type="hidden" name="category" value="{{ request('category') }}">
            <select name="sort" onchange="this.form.submit()"
                    class="search-input text-sm py-2 pr-8 cursor-pointer">
                <option value="popular" {{ request('sort') === 'popular' ? 'selected' : '' }}>Most Popular</option>
                <option value="rating"  {{ request('sort') === 'rating'  ? 'selected' : '' }}>Top Rated</option>
                <option value="newest"  {{ request('sort') === 'newest'  ? 'selected' : '' }}>Newest First</option>
            </select>
        </form>
    </div>

    <div class="flex gap-8">

        {{-- ── Sidebar: Categories ── --}}
        <aside class="hidden md:block w-56 shrink-0">
            <h2 class="text-xs font-semibold uppercase tracking-wider text-stone-400 mb-4">Categories</h2>
            <ul class="space-y-1">
                <li>
                    <a href="{{ route('books.index', ['sort' => request('sort')]) }}"
                       class="flex items-center justify-between px-3 py-2 rounded-lg text-sm transition-colors {{ !request('category') ? 'bg-amber-50 text-amber-700 font-medium' : 'text-stone-600 hover:bg-stone-100' }}">
                        All Books
                        <span class="text-xs text-stone-400">{{ $categories->sum('books_count') }}</span>
                    </a>
                </li>
                @foreach($categories as $cat)
                <li>
                    <a href="{{ route('books.index', ['category' => $cat->slug, 'sort' => request('sort')]) }}"
                       class="flex items-center justify-between px-3 py-2 rounded-lg text-sm transition-colors {{ request('category') === $cat->slug ? 'bg-amber-50 text-amber-700 font-medium' : 'text-stone-600 hover:bg-stone-100' }}">
                        <span>{{ $cat->icon ?? '' }} {{ $cat->name }}</span>
                        <span class="text-xs text-stone-400">{{ $cat->books_count }}</span>
                    </a>
                </li>
                @endforeach
            </ul>
        </aside>

        {{-- ── Main: Book Grid ── --}}
        <div class="flex-1 min-w-0">

            {{-- Active filter badge --}}
            @if(request('category'))
            <div class="mb-6 flex items-center gap-2">
                <span class="text-sm text-stone-500">Filtered by:</span>
                <span class="book-card__badge">
                    {{ $categories->firstWhere('slug', request('category'))?->name }}
                </span>
                <a href="{{ route('books.index') }}" class="text-xs text-stone-400 hover:text-red-500">✕ Clear</a>
            </div>
            @endif

            @if($books->count())
            <div class="book-grid">
                @foreach($books as $book)
                @include('partials.book-card', ['book' => $book])
                @endforeach
            </div>

            {{-- Pagination --}}
            <div class="mt-10">
                {{ $books->links() }}
            </div>

            @else
            <div class="text-center py-24">
                <div class="text-5xl mb-4">📭</div>
                <h2 class="section-title mb-2">No books here yet</h2>
                <p class="text-stone-400 text-sm">
                    Try a different category, or
                    <a href="{{ route('search') }}" class="text-amber-600 hover:underline">search our full catalogue</a>.
                </p>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

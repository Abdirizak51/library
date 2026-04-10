{{-- resources/views/books/show.blade.php --}}
@extends('layouts.app')

@section('title', $book->meta_title ?? $book->title)
@section('description', $book->meta_description ?? Str::limit(strip_tags($book->description), 155))
@section('og_type', 'book')
@section('og_image', $book->cover_url)

@section('book_context', json_encode([
    'title'       => $book->title,
    'author'      => $book->authors->pluck('name')->implode(', '),
    'description' => Str::limit(strip_tags($book->description), 300),
]))

@push('structured_data')
<script type="application/ld+json">{!! json_encode($structuredData, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}</script>
@endpush

@section('content')
<div class="max-w-7xl mx-auto px-4 py-12">

    {{-- Breadcrumb --}}
    <nav class="text-sm text-stone-400 mb-8" aria-label="Breadcrumb">
        <a href="{{ route('home') }}" class="hover:text-amber-600">Home</a>
        <span class="mx-2">/</span>
        <a href="{{ route('books.index', ['category' => $book->category->slug]) }}" class="hover:text-amber-600">{{ $book->category->name }}</a>
        <span class="mx-2">/</span>
        <span class="text-stone-600">{{ Str::limit($book->title, 40) }}</span>
    </nav>

    <div class="grid lg:grid-cols-3 gap-12">

        {{-- ── Left: Cover + Actions ── --}}
        <div class="lg:col-span-1 flex flex-col items-start gap-6">
            <img src="{{ $book->cover_url }}"
                 alt="{{ $book->title }} cover"
                 class="book-detail-cover"
                 loading="eager">

            <div class="flex flex-col gap-3 w-full max-w-xs">
                @if($book->external_url)
                <a href="{{ $book->external_url }}" target="_blank" rel="noopener noreferrer"
                   class="btn-primary w-full justify-center">
                    View on Google Books ↗
                </a>
                @endif

                @auth
                <button id="fav-btn"
                        data-book="{{ $book->id }}"
                        data-slug="{{ $book->slug }}"
                        class="btn-outline w-full justify-center"
                        onclick="toggleFavorite(this)">
                    {{ auth()->user()->favorites()->where('book_id', $book->id)->exists() ? '♥ Saved' : '♡ Save to Library' }}
                </button>
                @else
                <a href="{{ route('login') }}" class="btn-outline w-full justify-center text-sm">Sign in to save</a>
                @endauth
            </div>

            {{-- Metadata panel --}}
            <div class="w-full max-w-xs p-4 bg-stone-100 rounded-xl text-sm space-y-2">
                @if($book->authors->count())
                <div><span class="text-stone-400">Author</span><br>
                    {{ $book->authors->pluck('name')->implode(', ') }}
                </div>
                @endif
                @if($book->publisher)
                <div><span class="text-stone-400">Publisher</span><br>{{ $book->publisher }}</div>
                @endif
                @if($book->published_year)
                <div><span class="text-stone-400">Published</span><br>{{ $book->published_year }}</div>
                @endif
                @if($book->pages)
                <div><span class="text-stone-400">Pages</span><br>{{ $book->pages }}</div>
                @endif
                @if($book->isbn)
                <div><span class="text-stone-400">ISBN</span><br><span class="font-mono text-xs">{{ $book->isbn }}</span></div>
                @endif
                <div><span class="text-stone-400">Category</span><br>{{ $book->category->name }}</div>
            </div>
        </div>

        {{-- ── Right: Content ── --}}
        <div class="lg:col-span-2 space-y-10">

            {{-- Title + rating --}}
            <div>
                <span class="book-card__badge mb-3">{{ $book->category->name }}</span>
                <h1 class="font-display text-3xl md:text-4xl font-bold text-stone-900 mt-2 leading-tight">
                    {{ $book->title }}
                </h1>
                @if($book->authors->count())
                <p class="mt-2 text-stone-500 text-lg">
                    by {{ $book->authors->pluck('name')->implode(', ') }}
                </p>
                @endif

                {{-- Star rating --}}
                @if($book->reviews_count > 0)
                <div class="flex items-center gap-2 mt-3">
                    <div class="stars text-lg">
                        @for($i = 1; $i <= 5; $i++)
                        <span class="{{ $i <= round($book->avg_rating) ? 'star-filled' : 'star-empty' }}">★</span>
                        @endfor
                    </div>
                    <span class="text-sm text-stone-500">{{ number_format($book->avg_rating, 1) }} ({{ $book->reviews_count }} reviews)</span>
                </div>
                @endif
            </div>

            {{-- Description --}}
            <div>
                <h2 class="font-display text-xl font-semibold text-stone-800 mb-3">About this book</h2>
                <div class="text-stone-600 leading-relaxed prose max-w-none">
                    {!! nl2br(e($book->description)) !!}
                </div>
            </div>

            {{-- AI Summary (lazy-loaded) --}}
            <div id="ai-summary-section" class="p-6 bg-amber-50 rounded-2xl border border-amber-200">
                <div class="flex items-center gap-2 mb-3">
                    <span class="text-amber-600 text-xl">✨</span>
                    <h2 class="font-display text-lg font-semibold text-amber-800">AI Summary</h2>
                    <span class="ml-auto text-xs text-amber-600 bg-amber-100 px-2 py-0.5 rounded-full">Powered by AI</span>
                </div>
                <div id="ai-summary-content" class="text-stone-700 text-sm leading-relaxed">
                    <div class="skeleton h-4 w-full mb-2"></div>
                    <div class="skeleton h-4 w-5/6 mb-2"></div>
                    <div class="skeleton h-4 w-4/5"></div>
                </div>
                <button id="regenerate-summary" class="mt-3 text-xs text-amber-600 hover:underline hidden">↻ Regenerate</button>
            </div>

            {{-- AI Recommendations --}}
            @if(!empty($recommendations))
            <div>
                <h2 class="font-display text-xl font-semibold text-stone-800 mb-4">Readers also enjoyed</h2>
                <div class="space-y-3">
                    @foreach($recommendations as $rec)
                    <div class="flex items-start gap-3 p-3 rounded-xl bg-stone-50 border border-stone-200">
                        <div class="flex-1">
                            <div class="font-medium text-stone-800 text-sm">{{ $rec['title'] ?? '' }}</div>
                            <div class="text-xs text-stone-400">{{ $rec['author'] ?? '' }}</div>
                            <div class="text-xs text-stone-500 mt-1">{{ $rec['reason'] ?? '' }}</div>
                        </div>
                        <a href="{{ route('search', ['q' => $rec['title'] ?? '']) }}"
                           class="text-xs text-amber-600 hover:underline shrink-0">Find →</a>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Reviews --}}
            <div>
                <h2 class="font-display text-xl font-semibold text-stone-800 mb-6">Reviews</h2>

                {{-- Write a review --}}
                @auth
                <form method="POST" action="{{ route('books.reviews.store', $book) }}"
                      class="mb-8 p-5 bg-white rounded-2xl border border-stone-200">
                    @csrf
                    <h3 class="font-semibold text-stone-700 mb-4">Write a review</h3>
                    <div class="mb-4">
                        <label class="text-sm text-stone-600 block mb-2">Your rating</label>
                        <div class="flex gap-2" x-data="{ rating: 0, hover: 0 }">
                            @for($i = 1; $i <= 5; $i++)
                            <label class="cursor-pointer">
                                <input type="radio" name="rating" value="{{ $i }}" class="sr-only" x-model="rating">
                                <span class="text-2xl transition-colors"
                                      :class="({{ $i }} <= (hover || rating)) ? 'text-amber-400' : 'text-stone-300'"
                                      @mouseenter="hover = {{ $i }}" @mouseleave="hover = 0">★</span>
                            </label>
                            @endfor
                        </div>
                    </div>
                    <textarea name="body" rows="3" placeholder="Share your thoughts…"
                              class="w-full search-input resize-none mb-4"></textarea>
                    <button type="submit" class="btn-primary text-sm">Submit Review</button>
                </form>
                @endauth

                {{-- Existing reviews --}}
                @if($book->reviews->where('approved', true)->count())
                <div class="space-y-4">
                    @foreach($book->reviews->where('approved', true) as $review)
                    <div class="p-4 bg-white rounded-xl border border-stone-200">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-2">
                                <span class="w-7 h-7 rounded-full bg-amber-100 flex items-center justify-center text-xs font-bold text-amber-700">
                                    {{ strtoupper(substr($review->user->name, 0, 1)) }}
                                </span>
                                <span class="text-sm font-medium text-stone-700">{{ $review->user->name }}</span>
                            </div>
                            <div class="stars text-sm">
                                @for($i = 1; $i <= 5; $i++)
                                <span class="{{ $i <= $review->rating ? 'star-filled' : 'star-empty' }}">★</span>
                                @endfor
                            </div>
                        </div>
                        @if($review->body)
                        <p class="text-sm text-stone-600">{{ $review->body }}</p>
                        @endif
                        <p class="text-xs text-stone-400 mt-2">{{ $review->created_at->diffForHumans() }}</p>
                    </div>
                    @endforeach
                </div>
                @else
                <p class="text-stone-400 text-sm">No reviews yet. Be the first!</p>
                @endif
            </div>

        </div>
    </div>
</div>

<script>
// Load AI summary on page load
document.addEventListener('DOMContentLoaded', () => {
    fetch('{{ route('books.summary', $book) }}')
        .then(r => r.json())
        .then(({ summary }) => {
            document.getElementById('ai-summary-content').innerHTML =
                summary.split('\n\n').map(p => `<p class="mb-2">${p}</p>`).join('');
            document.getElementById('regenerate-summary').classList.remove('hidden');
        })
        .catch(() => {
            document.getElementById('ai-summary-content').textContent = 'Summary not available.';
        });
});

// Toggle favorite
async function toggleFavorite(btn) {
    const slug = btn.dataset.slug;
    const res  = await fetch(`/books/${slug}/favorite`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': window.LibAI.csrfToken,
            'Content-Type': 'application/json'
        }
    });
    const { favorited } = await res.json();
    btn.textContent = favorited ? '♥ Saved' : '♡ Save to Library';
}
</script>

@endsection

<article class="book-card fade-up">
    <a href="{{ route('books.show', $book->slug) }}" class="block overflow-hidden">
        <img src="{{ $book->cover_url ?? asset('images/cover-placeholder.webp') }}"
             alt="{{ $book->title }}"
             class="book-card__cover"
             loading="lazy">
    </a>
    <div class="book-card__body">
        <a href="{{ route('books.show', $book->slug) }}">
            <h3 class="book-card__title mt-2">{{ \Illuminate\Support\Str::limit($book->title, 50) }}</h3>
        </a>
        <p class="book-card__author">{{ $book->authors->pluck('name')->first() ?? 'Unknown' }}</p>
    </div>
</article>
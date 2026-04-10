<article style="background:white; border-radius:0.75rem; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,0.08); border:1px solid #e7e5e4;">
    <a href="{{ route('books.show', $book->slug) }}">
        <div style="aspect-ratio:2/3; background:#f5f5f4; display:flex; align-items:center; justify-content:center; font-size:3rem;">
            📚
        </div>
    </a>
    <div style="padding:0.75rem;">
        <a href="{{ route('books.show', $book->slug) }}">
            <h3 style="font-weight:600; font-size:0.9rem; line-height:1.3; color:#1c1917;">
                {{ \Illuminate\Support\Str::limit($book->title, 40) }}
            </h3>
        </a>
        <p style="font-size:0.75rem; color:#78716c; margin-top:0.25rem;">
            {{ $book->category->name ?? '' }}
        </p>
        <p style="font-size:0.75rem; color:#d97706;">★ {{ $book->avg_rating }}</p>
    </div>
</article>
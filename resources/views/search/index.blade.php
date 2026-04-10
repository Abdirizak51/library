{{-- resources/views/search/index.blade.php --}}
@extends('layouts.app')
@section('title', $q ? "Search: {$q}" : 'Search Books')
@section('description', $q ? "Find books matching \"{$q}\" in our AI-powered library." : 'Search our library with natural language — powered by AI.')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-12">

    {{-- Search bar --}}
    <div class="max-w-2xl mb-10">
        <p class="section-eyebrow mb-2">AI-Powered Search</p>
        <form action="{{ route('search') }}" method="GET" class="flex gap-3">
            <input type="text" name="q" value="{{ $q }}"
                   class="search-input flex-1 text-base" autofocus
                   placeholder='"books about stoicism" or "sci-fi with strong female leads"'>
            <button type="submit" class="btn-primary shrink-0">Search</button>
        </form>
        @if($q && isset($semantic['intent']))
        <p class="mt-2 text-xs text-stone-400">
            <span class="text-amber-500">✨ AI</span> understood: <em>{{ $semantic['intent'] }}</em>
        </p>
        @endif
    </div>

    @if($q)

        {{-- Results from our library --}}
        @if(isset($books) && $books->count())
        <section class="mb-14">
            <div class="flex items-center gap-3 mb-6">
                <p class="section-eyebrow">In Our Library</p>
                <span class="text-xs text-stone-400">{{ $books->count() }} result{{ $books->count() !== 1 ? 's' : '' }}</span>
            </div>
            <div class="book-grid">
                @foreach($books as $book)
                @include('partials.book-card', ['book' => $book])
                @endforeach
            </div>
        </section>
        @endif

        {{-- External results from Google Books --}}
        @if(!empty($externalResults))
        <section>
            <div class="flex items-center gap-3 mb-2">
                <p class="section-eyebrow">From Google Books</p>
                <span class="text-xs bg-blue-50 text-blue-600 px-2 py-0.5 rounded-full">External</span>
            </div>
            <p class="text-sm text-stone-400 mb-6">Not in our library yet — @auth click "Add" to import. @endauth @guest <a href="{{ route('register') }}" class="text-amber-600 hover:underline">Sign in</a> to import books. @endguest</p>

            <div class="grid sm:grid-cols-2 gap-4">
                @foreach($externalResults as $ext)
                <div class="flex gap-4 p-4 bg-white rounded-xl border border-stone-200">
                    @if($ext['cover_image'])
                    <img src="{{ $ext['cover_image'] }}" alt="{{ $ext['title'] }}"
                         class="w-16 h-22 object-cover rounded-lg shrink-0" loading="lazy"
                         style="height:88px">
                    @else
                    <div class="w-16 bg-stone-200 rounded-lg shrink-0 flex items-center justify-center text-2xl" style="height:88px">📚</div>
                    @endif
                    <div class="flex-1 min-w-0">
                        <h3 class="font-medium text-stone-800 text-sm leading-tight">{{ $ext['title'] }}</h3>
                        <p class="text-xs text-stone-400 mt-0.5">{{ implode(', ', array_slice($ext['authors'] ?? [], 0, 2)) }}</p>
                        @if($ext['published_year'])
                        <p class="text-xs text-stone-400">{{ $ext['published_year'] }}</p>
                        @endif
                        <p class="text-xs text-stone-500 mt-1 line-clamp-2">{{ Str::limit($ext['description'], 100) }}</p>
                        <div class="mt-2 flex items-center gap-3">
                            @if($ext['external_url'])
                            <a href="{{ $ext['external_url'] }}" target="_blank" rel="noopener"
                               class="text-xs text-stone-400 hover:text-amber-600">View ↗</a>
                            @endif
                            @auth
                            <button onclick="importBook('{{ $ext['google_books_id'] }}', this)"
                                    class="text-xs text-amber-600 hover:underline font-medium">
                                + Add to Library
                            </button>
                            @endauth
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </section>
        @endif

        {{-- No results --}}
        @if((!isset($books) || !$books->count()) && empty($externalResults))
        <div class="text-center py-24">
            <div class="text-6xl mb-5">🔍</div>
            <h2 class="section-title mb-3">No results for "{{ $q }}"</h2>
            <p class="text-stone-400 max-w-sm mx-auto mb-6">Try different words, a more general phrase, or browse by category.</p>
            <a href="{{ route('books.index') }}" class="btn-primary">Browse All Books</a>
        </div>
        @endif

    @else

        {{-- Empty search state: show tips --}}
        <div class="max-w-2xl">
            <h2 class="section-title mb-6">What can you search for?</h2>
            <div class="grid sm:grid-cols-2 gap-3">
                @foreach([
                    '"books about building better habits"',
                    '"historical fiction set in ancient Rome"',
                    '"beginner programming books"',
                    '"short story collections"',
                    '"books like Atomic Habits"',
                    '"science explained simply"',
                ] as $tip)
                <a href="{{ route('search', ['q' => trim($tip, '"')]) }}"
                   class="p-3 bg-white border border-stone-200 rounded-xl text-sm text-stone-600 hover:border-amber-400 hover:text-amber-600 transition-colors">
                    {{ $tip }}
                </a>
                @endforeach
            </div>
        </div>

    @endif
</div>

<script>
async function importBook(googleId, btn) {
    btn.disabled = true;
    btn.textContent = 'Adding…';
    try {
        const res = await fetch('/search/import', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': window.LibAI.csrfToken,
            },
            body: JSON.stringify({ google_books_id: googleId }),
        });
        const data = await res.json();
        if (data.slug) {
            btn.textContent = '✓ Added!';
            btn.insertAdjacentHTML('afterend', ` <a href="/books/${data.slug}" class="text-xs text-amber-600 hover:underline ml-1">View →</a>`);
        } else {
            btn.textContent = 'Error — try again';
            btn.disabled = false;
        }
    } catch {
        btn.textContent = 'Error';
        btn.disabled = false;
    }
}
</script>
@endsection


{{-- ─────────────────────────────────────────────────────────────
     resources/views/admin/reviews.blade.php
───────────────────────────────────────────────────────────── --}}
@extends('layouts.app')
@section('title', 'Review Queue — Admin')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-12">
    <div class="mb-8">
        <a href="{{ route('admin.dashboard') }}" class="text-xs text-stone-400 hover:text-amber-600 mb-1 block">← Admin</a>
        <h1 class="section-title">Review Queue</h1>
        <p class="text-stone-400 text-sm mt-1">{{ $pending->total() }} pending review{{ $pending->total() !== 1 ? 's' : '' }}</p>
    </div>

    @if($pending->count())
    <div class="space-y-4">
        @foreach($pending as $review)
        <div class="p-5 bg-white rounded-2xl border border-stone-200">
            <div class="flex items-start gap-4">
                <img src="{{ $review->book->cover_url }}" alt="{{ $review->book->title }}"
                     class="w-10 h-14 object-cover rounded shrink-0" loading="lazy">
                <div class="flex-1">
                    <div class="flex items-center justify-between flex-wrap gap-2">
                        <div>
                            <span class="font-medium text-stone-800 text-sm">{{ $review->user->name }}</span>
                            <span class="text-stone-400 text-xs mx-2">on</span>
                            <span class="text-sm text-stone-600">{{ $review->book->title }}</span>
                        </div>
                        <div class="stars text-sm">
                            @for($i = 1; $i <= 5; $i++)
                            <span class="{{ $i <= $review->rating ? 'star-filled' : 'star-empty' }}">★</span>
                            @endfor
                        </div>
                    </div>
                    @if($review->body)
                    <p class="text-sm text-stone-600 mt-2 bg-stone-50 p-3 rounded-lg">{{ $review->body }}</p>
                    @endif
                    <div class="flex items-center gap-3 mt-3">
                        <form method="POST" action="{{ route('admin.reviews.approve', $review) }}">
                            @csrf @method('PATCH')
                            <button type="submit" class="btn-primary text-xs py-1.5 px-3">✓ Approve</button>
                        </form>
                        <form method="POST" action="{{ route('admin.reviews.destroy', $review) }}"
                              onsubmit="return confirm('Delete this review?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn-outline text-xs py-1.5 px-3 text-red-500 border-red-300 hover:bg-red-50">Delete</button>
                        </form>
                        <span class="text-xs text-stone-400">{{ $review->created_at->diffForHumans() }}</span>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    <div class="mt-6">{{ $pending->links() }}</div>
    @else
    <div class="text-center py-24">
        <div class="text-5xl mb-4">✅</div>
        <h2 class="section-title mb-2">All clear!</h2>
        <p class="text-stone-400">No reviews pending approval.</p>
    </div>
    @endif
</div>
@endsection


{{-- ─────────────────────────────────────────────────────────────
     resources/views/admin/users.blade.php
───────────────────────────────────────────────────────────── --}}
@extends('layouts.app')
@section('title', 'Users — Admin')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-12">
    <div class="mb-8">
        <a href="{{ route('admin.dashboard') }}" class="text-xs text-stone-400 hover:text-amber-600 mb-1 block">← Admin</a>
        <h1 class="section-title">Users <span class="text-stone-400 text-lg font-sans font-normal">({{ $users->total() }})</span></h1>
    </div>

    <div class="bg-white rounded-2xl border border-stone-200 overflow-hidden">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Role</th>
                    <th>Reviews</th>
                    <th>Saved Books</th>
                    <th>Joined</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                <tr>
                    <td>
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-amber-100 flex items-center justify-center text-sm font-bold text-amber-700">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>
                            <div>
                                <div class="text-sm font-medium text-stone-800">{{ $user->name }}</div>
                                <div class="text-xs text-stone-400">{{ $user->email }}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium
                            {{ $user->role === 'admin' ? 'bg-amber-100 text-amber-700' : 'bg-stone-100 text-stone-500' }}">
                            {{ ucfirst($user->role) }}
                        </span>
                    </td>
                    <td class="text-sm text-stone-500">{{ $user->reviews_count }}</td>
                    <td class="text-sm text-stone-500">{{ $user->favorites_count }}</td>
                    <td class="text-xs text-stone-400">{{ $user->created_at->format('M j, Y') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-6">{{ $users->links() }}</div>
</div>
@endsection

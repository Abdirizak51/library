@extends('layouts.app')
@section('title', 'Admin Dashboard')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-12">

    <div class="flex items-center justify-between mb-10">
        <h1 class="section-title">Admin Dashboard</h1>
        <a href="{{ route('admin.books.create') }}" class="btn-primary">+ Add Book</a>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-10">
        @foreach([
            ['label' => 'Books',           'value' => $stats['total_books'],    'icon' => '📚'],
            ['label' => 'Users',           'value' => $stats['total_users'],    'icon' => '👥'],
            ['label' => 'Reviews',         'value' => $stats['total_reviews'],  'icon' => '⭐'],
            ['label' => 'Pending Reviews', 'value' => $stats['pending_reviews'],'icon' => '⏳'],
        ] as $s)
        <div class="p-5 bg-white dark:bg-stone-900 rounded-2xl border border-stone-200 dark:border-stone-800">
            <div class="text-2xl mb-2">{{ $s['icon'] }}</div>
            <div class="text-2xl font-bold text-stone-900 dark:text-stone-100">{{ $s['value'] }}</div>
            <div class="text-sm text-stone-400">{{ $s['label'] }}</div>
        </div>
        @endforeach
    </div>

    <div class="grid md:grid-cols-2 gap-8 mb-8">

        <div class="bg-white dark:bg-stone-900 rounded-2xl border border-stone-200 dark:border-stone-800 p-6">
            <h2 class="text-lg font-semibold text-stone-800 dark:text-stone-200 mb-4">🔍 Popular Searches</h2>
            @if($popularSearches->count())
            <table class="admin-table">
                <thead><tr><th>Query</th><th>Count</th></tr></thead>
                <tbody>
                    @foreach($popularSearches as $s)
                    <tr>
                        <td>{{ $s->query }}</td>
                        <td class="text-amber-600">{{ $s->count }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <p class="text-stone-400 text-sm">No searches yet.</p>
            @endif
        </div>

        <div class="bg-white dark:bg-stone-900 rounded-2xl border border-stone-200 dark:border-stone-800 p-6">
            <h2 class="text-lg font-semibold text-stone-800 dark:text-stone-200 mb-4">📗 Recently Added</h2>
            <div class="space-y-3">
                @foreach($recentBooks as $book)
                <div class="flex items-center justify-between text-sm">
                    <div>
                        <div class="font-medium text-stone-800 dark:text-stone-200">{{ $book->title }}</div>
                        <div class="text-stone-400 text-xs">{{ $book->category->name ?? '' }}</div>
                    </div>
                    <a href="{{ route('admin.books.edit', $book) }}" class="text-amber-600 hover:underline text-xs">Edit</a>
                </div>
                @endforeach
            </div>
            <a href="{{ route('admin.books.index') }}" class="mt-4 block text-sm text-amber-600 hover:underline">View all →</a>
        </div>

    </div>

    <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
        @foreach([
            ['route' => 'admin.books.index',   'label' => 'Manage Books', 'icon' => '📚'],
            ['route' => 'admin.users.index',   'label' => 'Manage Users', 'icon' => '👥'],
            ['route' => 'admin.reviews.index', 'label' => 'Review Queue', 'icon' => '⭐'],
        ] as $link)
        <a href="{{ route($link['route']) }}"
           class="flex items-center gap-3 p-4 bg-stone-100 dark:bg-stone-800 rounded-xl hover:bg-amber-50 border border-transparent hover:border-amber-400 transition-all">
            <span class="text-xl">{{ $link['icon'] }}</span>
            <span class="text-sm font-medium text-stone-700 dark:text-stone-300">{{ $link['label'] }}</span>
        </a>
        @endforeach
    </div>

</div>
@endsection
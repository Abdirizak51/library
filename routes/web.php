<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

// ── Home ──
Route::get('/', function () {
    $featured    = \App\Models\Book::with(['authors','category'])->take(8)->get();
    $categories  = \App\Models\Category::withCount('books')->get();
    $newArrivals = \App\Models\Book::latest()->take(6)->with('authors')->get();
    return view('home', compact('featured','categories','newArrivals'));
})->name('home');

// ── Books ──
Route::get('/books', function() {
    $books      = \App\Models\Book::with(['authors','category'])->paginate(18);
    $categories = \App\Models\Category::withCount('books')->get();
    return view('books.index', compact('books','categories'));
})->name('books.index');

Route::get('/books/{slug}', function($slug) {
    $book = \App\Models\Book::where('slug', $slug)->with(['authors','category','reviews'])->firstOrFail();
    $book->increment('views_count');
    $recommendations = [];
    $structuredData  = [];
    return view('books.show', compact('book','recommendations','structuredData'));
})->name('books.show');

// ── Search ──
Route::get('/search', function() {
    $q     = request('q', '');
    $books = collect();
    if ($q) {
        $books = \App\Models\Book::where('title','LIKE',"%{$q}%")
                 ->orWhere('description','LIKE',"%{$q}%")
                 ->with(['authors','category'])->get();
    }
    return view('search.index', ['books' => $books, 'q' => $q, 'externalResults' => []]);
})->name('search');

// ── Dashboard ──
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function() {
        $user           = auth()->user();
        $favorites      = collect();
        $readingHistory = collect();
        $reviews        = collect();
        return view('dashboard', compact('user','favorites','readingHistory','reviews'));
    })->name('dashboard');
});

// ── Admin ──
Route::prefix('admin')->name('admin.')->middleware(['auth','admin'])->group(function () {

  Route::get('/', function() {
    $stats = [
        'total_books'     => \App\Models\Book::count(),
        'total_users'     => \App\Models\User::count(),
        'total_reviews'   => 0,
        'pending_reviews' => 0,
    ];
    $recentBooks     = \App\Models\Book::latest()->take(5)->with('category')->get();
    $popularSearches = collect();
    return view('admin.dashboard', compact('stats','recentBooks','popularSearches'));
})->name('dashboard');

    Route::get('/books', function() {
        $books = \App\Models\Book::with(['category'])->latest()->paginate(20);
        return view('admin.books.index', compact('books'));
    })->name('books.index');

    Route::get('/books/create', function() {
        $categories = \App\Models\Category::all();
        $authors    = collect();
        return view('admin.books.create', compact('categories','authors'));
    })->name('books.create');

    Route::post('/books', function(Request $request) {
        $data = $request->validate([
            'title'          => 'required|string|max:255',
            'category_id'    => 'required|exists:categories,id',
            'description'    => 'nullable|string',
            'published_year' => 'nullable|integer',
            'status'         => 'in:active,draft,hidden',
        ]);
        $data['slug']          = \Illuminate\Support\Str::slug($data['title']);
        $data['avg_rating']    = 0;
        $data['reviews_count'] = 0;
        $data['views_count']   = 0;
        \App\Models\Book::create($data);
        return redirect()->route('admin.books.index')->with('success', 'Book created!');
    })->name('books.store');

    Route::get('/books/{book}/edit', function(\App\Models\Book $book) {
        $categories = \App\Models\Category::all();
        $authors    = collect();
        return view('admin.books.edit', compact('book','categories','authors'));
    })->name('books.edit');

    Route::patch('/books/{book}', function(Request $request, \App\Models\Book $book) {
        $data = $request->validate([
            'title'          => 'required|string|max:255',
            'category_id'    => 'required|exists:categories,id',
            'description'    => 'nullable|string',
            'published_year' => 'nullable|integer',
            'status'         => 'in:active,draft,hidden',
        ]);
        $book->update($data);
        return back()->with('success', 'Book updated!');
    })->name('books.update');

    Route::delete('/books/{book}', function(\App\Models\Book $book) {
        $book->delete();
        return redirect()->route('admin.books.index')->with('success', 'Book deleted!');
    })->name('books.destroy');

    // Users
Route::get('/users', function() {
    $users = \App\Models\User::latest()->paginate(20);
    return view('admin.users', compact('users'));
})->name('users.index');

// Reviews
Route::get('/reviews', function() {
    $pending = collect();
    return view('admin.reviews', compact('pending'));
})->name('reviews.index');

});

require __DIR__.'/auth.php';
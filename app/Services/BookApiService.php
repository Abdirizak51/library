<?php
// ─────────────────────────────────────────────────────────────
// app/Services/BookApiService.php
// Fetches book metadata from Google Books & Open Library APIs
// ─────────────────────────────────────────────────────────────
namespace App\Services;

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BookApiService
{
    private string $googleApiKey;

    public function __construct()
    {
        $this->googleApiKey = config('services.google_books.key', '');
    }

    // ──────────────────────────────────────────────────────────
    // Search Google Books API
    // Returns array of normalized book data
    // ──────────────────────────────────────────────────────────
    public function searchGoogleBooks(string $query, int $maxResults = 10): array
    {
        $cacheKey = 'google_books_' . md5($query . $maxResults);

        return Cache::remember($cacheKey, now()->addHours(2), function () use ($query, $maxResults) {
            $params = [
                'q'          => $query,
                'maxResults' => $maxResults,
                'printType'  => 'books',
                'langRestrict' => 'en',
            ];

            if ($this->googleApiKey) {
                $params['key'] = $this->googleApiKey;
            }

            try {
                $response = Http::timeout(10)
                    ->get('https://www.googleapis.com/books/v1/volumes', $params);

                if ($response->failed()) {
                    return [];
                }

                $items = $response->json('items', []);

                return collect($items)->map(fn($item) => $this->normalizeGoogleBook($item))->toArray();

            } catch (\Throwable $e) {
                Log::warning('Google Books API error', ['message' => $e->getMessage()]);
                return [];
            }
        });
    }

    // ──────────────────────────────────────────────────────────
    // Fetch a single book by ISBN from Open Library
    // ──────────────────────────────────────────────────────────
    public function fetchByISBN(string $isbn): ?array
    {
        $cacheKey = "isbn_{$isbn}";

        return Cache::remember($cacheKey, now()->addDays(7), function () use ($isbn) {
            try {
                $response = Http::timeout(10)
                    ->get("https://openlibrary.org/isbn/{$isbn}.json");

                if ($response->failed()) {
                    return null;
                }

                return $this->normalizeOpenLibraryBook($response->json(), $isbn);

            } catch (\Throwable $e) {
                Log::warning('Open Library API error', ['isbn' => $isbn, 'message' => $e->getMessage()]);
                return null;
            }
        });
    }

    // ──────────────────────────────────────────────────────────
    // Import an external book into the local database
    // ──────────────────────────────────────────────────────────
    public function importBook(array $data, ?int $categoryId = null): Book
    {
        // Find or create author
        $authorModels = [];
        foreach ((array) ($data['authors'] ?? []) as $authorName) {
            $authorModels[] = Author::firstOrCreate(
                ['slug' => Str::slug($authorName)],
                ['name' => $authorName]
            );
        }

        // Determine category
        if (! $categoryId) {
            $categoryId = Category::where('slug', 'non-fiction')->value('id')
                ?? Category::first()?->id;
        }

        // Create or update book
        $book = Book::updateOrCreate(
            ['slug' => Str::slug($data['title'])],
            [
                'category_id'     => $categoryId,
                'title'           => $data['title'],
                'description'     => $data['description'] ?? null,
                'cover_image'     => $data['cover_image'] ?? null,
                'isbn'            => $data['isbn'] ?? null,
                'publisher'       => $data['publisher'] ?? null,
                'published_year'  => $data['published_year'] ?? null,
                'google_books_id' => $data['google_books_id'] ?? null,
                'open_library_id' => $data['open_library_id'] ?? null,
                'external_url'    => $data['external_url'] ?? null,
                'language'        => $data['language'] ?? 'en',
                'status'          => 'active',
            ]
        );

        if ($authorModels) {
            $book->authors()->syncWithoutDetaching(collect($authorModels)->pluck('id'));
        }

        return $book;
    }

    // ──────────────────────────────────────────────────────────
    // Private normalizers
    // ──────────────────────────────────────────────────────────

    private function normalizeGoogleBook(array $item): array
    {
        $info = $item['volumeInfo'] ?? [];

        // Prefer larger thumbnail
        $cover = $info['imageLinks']['thumbnail'] ?? null;
        if ($cover) {
            $cover = str_replace('http://', 'https://', $cover);
            $cover = str_replace('zoom=1', 'zoom=3', $cover);
        }

        $isbns = collect($info['industryIdentifiers'] ?? []);
        $isbn  = $isbns->firstWhere('type', 'ISBN_13')['identifier']
                ?? $isbns->firstWhere('type', 'ISBN_10')['identifier']
                ?? null;

        $year = null;
        if (isset($info['publishedDate'])) {
            preg_match('/\d{4}/', $info['publishedDate'], $matches);
            $year = $matches[0] ?? null;
        }

        return [
            'title'           => $info['title'] ?? 'Untitled',
            'authors'         => $info['authors'] ?? [],
            'description'     => strip_tags($info['description'] ?? ''),
            'cover_image'     => $cover,
            'isbn'            => $isbn,
            'publisher'       => $info['publisher'] ?? null,
            'published_year'  => $year,
            'pages'           => $info['pageCount'] ?? null,
            'language'        => $info['language'] ?? 'en',
            'google_books_id' => $item['id'],
            'external_url'    => $info['canonicalVolumeLink'] ?? null,
            'categories'      => $info['categories'] ?? [],
        ];
    }

    private function normalizeOpenLibraryBook(array $data, string $isbn): array
    {
        $year = null;
        if (isset($data['publish_date'])) {
            preg_match('/\d{4}/', $data['publish_date'], $m);
            $year = $m[0] ?? null;
        }

        $olId   = $data['key'] ?? null;
        $coverId = $data['covers'][0] ?? null;
        $cover  = $coverId ? "https://covers.openlibrary.org/b/id/{$coverId}-L.jpg" : null;

        return [
            'title'           => $data['title'] ?? 'Untitled',
            'authors'         => [],  // Needs separate author fetch
            'description'     => $data['description']['value'] ?? $data['description'] ?? '',
            'cover_image'     => $cover,
            'isbn'            => $isbn,
            'publisher'       => $data['publishers'][0] ?? null,
            'published_year'  => $year,
            'pages'           => $data['number_of_pages'] ?? null,
            'language'        => 'en',
            'open_library_id' => $olId,
        ];
    }
}

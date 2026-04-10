<?php
// ─────────────────────────────────────────────────────────────
// app/Services/AIService.php
// Central service for all AI-powered features
// ─────────────────────────────────────────────────────────────
namespace App\Services;

use App\Models\Book;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIService
{
    private string $apiKey;
    private string $model;
    private string $baseUrl = 'https://api.openai.com/v1';

    public function __construct()
    {
        $this->apiKey = config('services.openai.key');
        $this->model  = config('services.openai.model', 'gpt-4o-mini');
    }

    // ──────────────────────────────────────────────────────────
    // 1. BOOK SUMMARY
    // Generates and caches an AI summary for a given book.
    // ──────────────────────────────────────────────────────────
    public function summarizeBook(Book $book): string
    {
        $cacheKey = "ai_summary_{$book->id}";

        return Cache::remember($cacheKey, now()->addDays(30), function () use ($book) {
            // Return stored summary if it exists
            if ($book->ai_summary) {
                return $book->ai_summary;
            }

            $authors = $book->authors->pluck('name')->implode(', ') ?: 'Unknown';
            $prompt  = <<<EOT
You are a librarian assistant. Write a concise, engaging 3-paragraph summary of the book
"{$book->title}" by {$authors} (published {$book->published_year}).

Base it on: {$book->description}

Format: plain text, no headers, no spoilers if fiction. Max 250 words.
EOT;

            $text = $this->chat([['role' => 'user', 'content' => $prompt]], maxTokens: 400);

            // Persist to database so we don't call API again
            $book->update(['ai_summary' => $text]);

            return $text;
        });
    }

    // ──────────────────────────────────────────────────────────
    // 2. SEMANTIC SEARCH
    // Converts a natural-language query into optimised DB search terms.
    // ──────────────────────────────────────────────────────────
    public function semanticSearch(string $query): array
    {
        $cacheKey = 'semantic_' . md5(strtolower($query));

        return Cache::remember($cacheKey, now()->addHours(6), function () use ($query) {
            $prompt = <<<EOT
You are a library search assistant. A user searched for: "{$query}"

Extract the key search terms, genres, themes, and keywords that would help find relevant books.
Respond ONLY with a JSON object in this exact format:
{
  "keywords": ["keyword1", "keyword2"],
  "genres":   ["genre1"],
  "themes":   ["theme1", "theme2"],
  "authors":  ["author name if mentioned"],
  "intent":   "one-line summary of what the user is looking for"
}
EOT;

            $json = $this->chat([['role' => 'user', 'content' => $prompt]], maxTokens: 200);

            try {
                return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            } catch (\Throwable) {
                return ['keywords' => explode(' ', $query), 'genres' => [], 'themes' => [], 'authors' => [], 'intent' => $query];
            }
        });
    }

    // ──────────────────────────────────────────────────────────
    // 3. RECOMMENDATIONS
    // Suggests books similar to a given book using AI reasoning.
    // ──────────────────────────────────────────────────────────
    public function recommend(Book $book, int $limit = 5): array
    {
        $cacheKey = "recs_{$book->id}_{$limit}";

        return Cache::remember($cacheKey, now()->addHours(12), function () use ($book, $limit) {
            $authors  = $book->authors->pluck('name')->implode(', ');
            $category = $book->category->name ?? '';

            $prompt = <<<EOT
You are a book recommendation engine. Suggest {$limit} books similar to
"{$book->title}" by {$authors} (category: {$category}).

User liked: {$book->description}

Respond ONLY with a JSON array:
[{"title":"...", "author":"...", "reason":"one sentence why"}, ...]
EOT;

            $json = $this->chat([['role' => 'user', 'content' => $prompt]], maxTokens: 500);

            try {
                return json_decode($json, true, 512, JSON_THROW_ON_ERROR) ?? [];
            } catch (\Throwable) {
                return [];
            }
        });
    }

    // ──────────────────────────────────────────────────────────
    // 4. CHATBOT
    // Multi-turn conversation with library context injected.
    // ──────────────────────────────────────────────────────────
    public function chat(array $messages, int $maxTokens = 800, bool $withLibraryContext = false): string
    {
        if ($withLibraryContext) {
            $systemMsg = [
                'role'    => 'system',
                'content' => <<<EOT
You are LibraryAI, a friendly assistant for an online digital library.
You can:
- Recommend books based on user interests
- Answer questions about books, authors, and literature
- Summarize books on request
- Help users find books in the library

Keep responses helpful, concise (under 200 words unless asked for more), and conversational.
If asked for books not in the library, suggest the user check external sources like Google Books.
Never invent book details. Be honest if you don't know something.
EOT,
            ];
            array_unshift($messages, $systemMsg);
        }

        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(30)
                ->post("{$this->baseUrl}/chat/completions", [
                    'model'       => $this->model,
                    'messages'    => $messages,
                    'max_tokens'  => $maxTokens,
                    'temperature' => 0.7,
                ]);

            if ($response->failed()) {
                Log::error('OpenAI API error', ['status' => $response->status(), 'body' => $response->body()]);
                return 'Sorry, I'm having trouble connecting to the AI service right now. Please try again later.';
            }

            return $response->json('choices.0.message.content', '');

        } catch (\Throwable $e) {
            Log::error('AIService::chat exception', ['message' => $e->getMessage()]);
            return 'An error occurred. Please try again.';
        }
    }
}

# LibAI — AI-Powered Digital Library
## Complete Setup & Deployment Guide

---

## 🗂 Project Structure

```
ai-library/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── BookController.php
│   │   │   ├── SearchController.php
│   │   │   ├── DashboardController.php
│   │   │   ├── SitemapController.php
│   │   │   ├── Api/
│   │   │   │   └── ChatbotController.php
│   │   │   └── Admin/
│   │   │       └── AdminController.php
│   │   └── Middleware/
│   │       └── AdminMiddleware.php
│   └── Models/
│       ├── Book.php
│       ├── User.php
│       ├── Author.php
│       ├── Category.php
│       ├── Review.php
│       ├── SearchLog.php
│       └── ReadingHistory.php
├── app/Services/
│   ├── AIService.php          ← OpenAI integration
│   └── BookApiService.php     ← Google Books + Open Library
├── database/
│   └── schema.sql             ← Full DB schema
├── resources/
│   ├── css/library.css        ← Design system
│   └── views/
│       ├── layouts/app.blade.php
│       ├── home.blade.php
│       ├── books/show.blade.php
│       ├── search/index.blade.php
│       ├── admin/dashboard.blade.php
│       └── partials/
│           ├── chatbot.blade.php
│           └── book-card.blade.php
└── routes/
    ├── web.php
    └── api.php
```

---

## ⚡ Quick Start (Local Development)

### Prerequisites
- PHP 8.2+
- Composer
- Node.js 18+
- MySQL 8.0+
- Redis (optional, for caching)

### 1. Create Laravel project

```bash
composer create-project laravel/laravel ai-library
cd ai-library

# Install required packages
composer require laravel/sanctum
composer require laravel/breeze --dev
php artisan breeze:install blade

npm install && npm run build
```

### 2. Configure environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env`:

```env
APP_NAME="LibAI"
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ai_library
DB_USERNAME=root
DB_PASSWORD=your_password

CACHE_DRIVER=redis       # or 'file' if Redis not available
SESSION_DRIVER=database
QUEUE_CONNECTION=database

# OpenAI
OPENAI_API_KEY=sk-your-key-here
OPENAI_MODEL=gpt-4o-mini

# Google Books (optional but recommended)
GOOGLE_BOOKS_API_KEY=your-google-api-key

# Redis (optional)
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### 3. Add service config

In `config/services.php`, add:

```php
'openai' => [
    'key'   => env('OPENAI_API_KEY'),
    'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
],
'google_books' => [
    'key' => env('GOOGLE_BOOKS_API_KEY'),
],
```

### 4. Set up database

```bash
# Create the database
mysql -u root -p -e "CREATE DATABASE ai_library CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Run schema
mysql -u root -p ai_library < database/schema.sql

# Or use Laravel migrations (copy code into migration files)
php artisan migrate
php artisan db:seed
```

### 5. Register AdminMiddleware

In `app/Http/Kernel.php`, add to `$middlewareAliases`:

```php
'admin' => \App\Http\Middleware\AdminMiddleware::class,
```

### 6. Copy files

Copy all provided source files into their respective directories as shown in the structure above.

### 7. Storage link

```bash
php artisan storage:link
```

### 8. Set admin password

```bash
php artisan tinker
>>> App\Models\User::where('email','admin@library.com')->update(['password' => bcrypt('yourSecurePass123!')])
```

### 9. Run

```bash
php artisan serve
npm run dev   # in a second terminal
```

Visit: http://localhost:8000

---

## 🔐 Security Checklist

- [x] CSRF protection (Laravel built-in via `@csrf`)
- [x] SQL injection prevention (Eloquent ORM uses PDO prepared statements)
- [x] XSS protection (`{{ }}` blade syntax auto-escapes)
- [x] Input validation via `$request->validate()`
- [x] Rate limiting on API endpoints (`throttle:60,1`)
- [x] Admin middleware protects all admin routes
- [x] `.env` never committed to git
- [ ] Set `APP_DEBUG=false` in production
- [ ] Use HTTPS in production
- [ ] Configure proper CORS in `config/cors.php`

---

## 🚀 Deployment

### Option A: Laravel Forge + DigitalOcean/AWS

1. Create server on Forge
2. Connect GitHub repo
3. Set environment variables in Forge panel
4. Forge auto-deploys on push to `main`
5. Enable SSL (Let's Encrypt, one click)
6. Set up Redis for caching

### Option B: Shared Hosting (cPanel)

```bash
# Build assets locally
npm run build

# Upload all files except node_modules/
# Point domain to /public directory
# Set DOCUMENT_ROOT to /public
```

### Option C: Docker

```dockerfile
# Dockerfile (simplified)
FROM php:8.2-fpm
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev zip unzip \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
WORKDIR /var/www
COPY . .
RUN composer install --optimize-autoloader --no-dev
RUN php artisan config:cache && php artisan route:cache && php artisan view:cache
```

---

## ⚙️ Performance Optimisation

### Cache warming

```bash
# Cache config, routes, and views for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan icons:cache
```

### Queue worker (for background jobs)

```bash
php artisan queue:work --queue=default --tries=3
```

### Scheduled commands (add to crontab)

```
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

---

## 🔧 Adding More AI Features

### Semantic embeddings (advanced)

For truly semantic search, integrate OpenAI embeddings:

```php
// In AIService.php
public function getEmbedding(string $text): array
{
    $response = Http::withToken($this->apiKey)
        ->post("{$this->baseUrl}/embeddings", [
            'model' => 'text-embedding-3-small',
            'input' => $text,
        ]);
    return $response->json('data.0.embedding', []);
}
```

Store embeddings in a vector database (pgvector, Pinecone, Weaviate) for similarity search.

---

## 📦 Key Dependencies

| Package | Purpose |
|---------|---------|
| `laravel/sanctum` | API authentication tokens |
| `laravel/breeze` | Auth scaffolding (login/register) |
| `guzzlehttp/guzzle` | HTTP client (via Laravel Http facade) |

### Frontend

| Library | Purpose |
|---------|---------|
| Tailwind CSS | Utility-first CSS framework |
| Alpine.js | Lightweight JS reactivity |
| Google Fonts | Playfair Display + DM Sans |

---

## 🗝️ API Keys Needed

| Service | URL | Free Tier |
|---------|-----|-----------|
| OpenAI | https://platform.openai.com | $5 credit |
| Google Books | https://console.cloud.google.com | 1000 req/day free |
| Open Library | https://openlibrary.org/developers | No key needed |

---

## 📊 Database ERD Summary

```
users ─────────── reviews ─────── books ──── book_author ─── authors
  │                               │   │
  ├── favorites ──────────────────┘   └── categories
  │
  ├── reading_history ──────── books
  │
  └── search_logs

books ─── blog_posts (admin authored)
```

---

## 🎯 Features Implemented

- ✅ RESTful Laravel API (MVC)
- ✅ MySQL with 9 normalized tables
- ✅ JWT/Sanctum authentication
- ✅ Admin + User role system
- ✅ AI Chatbot (OpenAI GPT-4o-mini)
- ✅ AI Semantic Search
- ✅ AI Book Summaries (cached)
- ✅ AI Recommendations
- ✅ Google Books API integration
- ✅ Open Library API integration
- ✅ Auto-import missing books
- ✅ SEO: slugs, meta, OG tags, Schema.org
- ✅ Sitemap.xml + robots.txt
- ✅ Dark mode toggle
- ✅ Responsive design
- ✅ Book reviews & ratings
- ✅ Favorites system
- ✅ Reading history
- ✅ Admin panel with analytics
- ✅ Rate limiting (API)
- ✅ CSRF, XSS, SQL injection protection
- ✅ Redis/file caching
- ✅ Lazy loading images
- ✅ Blog for SEO content

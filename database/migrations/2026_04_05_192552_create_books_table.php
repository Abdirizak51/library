<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->onDelete('restrict');
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('isbn')->nullable()->unique();
            $table->text('description')->nullable();
            $table->text('ai_summary')->nullable();
            $table->string('cover_image')->nullable();
            $table->string('publisher')->nullable();
            $table->year('published_year')->nullable();
            $table->smallInteger('pages')->nullable();
            $table->string('language')->default('en');
            $table->string('google_books_id')->nullable();
            $table->string('external_url')->nullable();
            $table->text('keywords')->nullable();
            $table->decimal('avg_rating', 3, 2)->default(0);
            $table->integer('reviews_count')->default(0);
            $table->integer('views_count')->default(0);
            $table->string('meta_title')->nullable();
            $table->string('meta_description')->nullable();
            $table->enum('status', ['active','draft','hidden'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
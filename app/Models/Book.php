<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Book extends Model
{
    protected $fillable = [
        'category_id','title','slug','description',
        'cover_image','published_year','status','isbn',
        'publisher','pages','language','external_url',
        'keywords','avg_rating','reviews_count','views_count',
        'meta_title','meta_description',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function authors()
    {
        return $this->belongsToMany(Author::class, 'book_author');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function favorites()
    {
        return $this->belongsToMany(User::class, 'favorites');
    }

    public function getCoverUrlAttribute(): string
    {
        if ($this->cover_image) {
            return $this->cover_image;
        }
        return 'https://via.placeholder.com/300x450?text=' . urlencode($this->title);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
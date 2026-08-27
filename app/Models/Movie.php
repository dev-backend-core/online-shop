<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;


class Movie extends Model
{
    use HasFactory;

    protected $fillable = [
        'kinopoisk_id',
        'title',
        'description',
        'duration_min',
        'poster_url',
        'rating',
        'genres',
        'poster_preview_url',
        'year'
    ];

    protected $casts = [
        'genres' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($movie) {
            if (empty($movie->slug)) {
                $title = !empty($movie->title) ? $movie->title : 'movie-' . Str::random(6);
                
                $movie->slug = Str::slug($title);
            }
        });
    }

    public function favoritedByUsers()
    {
        return $this->belongsToMany(User::class, 'favorite_movies')->withTimestamps();
    }

    public function shows(){
        return $this->hasMany(Show::class);
    }
}

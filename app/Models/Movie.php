<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


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
        'year'
    ];

    protected $casts = [
        'genres' => 'array',
    ];

    public function favoritedByUsers()
    {
        return $this->belongsToMany(User::class, 'favorite_movies')->withTimestamps();
    }

    public function shows(){
        return $this->hasMany(Show::class);
    }
}

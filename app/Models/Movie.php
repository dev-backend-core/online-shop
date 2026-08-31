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
        'slug',
        'genres',
        'poster_preview_url',
        'year'
    ];

    protected $casts = [
        'genres' => 'array',
    ];

    //сработает перед созданием бд
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
    // забирает первые цифры до первого нечислового символа
    public function resolveRouteBinding($value, $field = null)
    {
        // intval("15-movie-15") заберет первые цифры "15"
        $id = intval($value);

        // Если в URL передали вообще не число, выбросит 404
        if ($id === 0) {
            abort(404);
        }

        return $this->where('id', $id)->firstOrFail();
    }
    

    public function favoritedByUsers()
    {
        return $this->belongsToMany(User::class, 'favorite_movies')->withTimestamps();
    }

    public function shows(){
        return $this->hasMany(Show::class);
    }
}

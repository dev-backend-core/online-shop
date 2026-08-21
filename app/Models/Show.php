<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Show extends Model
{
    use HasFactory;

    protected $fillable = [
        'movie_id',
        'start_time',
        'price',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'price' => 'float',
    ];

    public function movie(){
        return $this->belongsTo(Movie::class);
    }

    public function tickets(){
        return $this->hasMany(Ticket::class);
    }
}

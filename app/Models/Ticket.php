<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'show_id',
        'seat_id',
        'status',
        'price',
        'reminder_sent'
    ];

    protected $casts = [
        'price' => 'float',
    ];

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function show(){
        return $this->belongsTo(Show::class);
    }

    public function seat(){
        return $this->belongsTo(Seat::class);
    }
}

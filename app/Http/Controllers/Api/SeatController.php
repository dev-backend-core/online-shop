<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Seat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SeatController extends Controller
{
    public function index(){
        $seats = Cache::remember("seats_layout", now()->addDays(30), function () {
            return Seat::all()->toArray();
        });

        return response()->json([
            'success' => true,
            'seat' => $seats,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Seat;
use Illuminate\Http\Request;

class SeatController extends Controller
{
    public function index(){
        return response()->json([
            'success' => true,
            'seat' => Seat::all(),
        ]);
    }
}

<?php

use App\Models\Movie;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
       // 1. Добавляем колонку slug (делаем её nullable на время заполненитя)
        // Schema::table('movies', function (Blueprint $table) {
        //     $table->string('slug')->nullable()->after('title');
        // });

        // $movies = DB::table('movies')->get();

        // foreach($movies as $movie){
        //     $title = !empty($movie->title) ? $movie->title : 'movie-'. $movie->id;

        //     $slug = Str::slug($title);

        //     if(empty($slug)){
        //         $slug = 'movie'.$movie->id;
        //     }

        //     DB::table('movies')
        //     ->where('id',$movie->id)
        //     ->update(['slug' => $slug]);
        // }

        // 3. Делаем колонку slug обязательной и уникальной
        Schema::table('movies', function (Blueprint $table) {
            $table->string('slug')->unique()->after('title');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('movies', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};

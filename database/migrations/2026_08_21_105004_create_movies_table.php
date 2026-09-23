<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('movies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('kinopoisk_id')->nullable()->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->integer('duration_min')->nullable();
            $table->string('poster_url')->nullable();
            $table->string('poster_preview_url')->nullable();
            $table->decimal('rating', 3, 1)->default(6.5);
            $table->integer('year')->nullable();
            $table->json('genres')->nullable();
            $table->timestamps();

        });
    }

   
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movies');
    }
};

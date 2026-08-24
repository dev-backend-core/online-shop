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

    /* "shows": [
        {
            "kinopoiskId": 4924671,
            "nameRu": "TheatreHD: \u0417\u0430\u043b\u044c\u0446\u0431\u0443\u0440\u0433: \u0421\u0435\u043b\u044c\u0441\u043a\u0430\u044f \u0447\u0435\u0441\u0442\u044c \/ \u041f\u0430\u044f\u0446\u044b",
            "nameEn": "Mascagni: Cavalleria Rusticana \/ Leoncavallo: Pagliacci",
            "year": 2016,
            "posterUrl": "https:\/\/kinopoiskapiunofficial.tech\/images\/posters\/kp\/4924671.jpg",
            "posterUrlPreview": "https:\/\/kinopoiskapiunofficial.tech\/images\/posters\/kp_small\/4924671.jpg",
           
            "genres": [
                {
                    "genre": "\u0434\u0440\u0430\u043c\u0430"
                },
                {
                    "genre": "\u043c\u0443\u0437\u044b\u043a\u0430"
                }
            ],
            "duration": 161,
            "premiereRu": "2026-08-04",
            "ratingImdb": 5.3
        },
        
        
        {
  "kinopoiskId": 301,
  "kinopoiskHDId": "4824a95e60a7db7e86f14137516ba590",
  "imdbId": "tt0133093",
  "nameRu": "Матрица",
  "nameEn": "The Matrix",
  "nameOriginal": "The Matrix",
  "posterUrl": "https://kinopoiskapiunofficial.tech/images/posters/kp/301.jpg",
  "posterUrlPreview": "https://kinopoiskapiunofficial.tech/images/posters/kp_small/301.jpg",
  "coverUrl": "https://avatars.mds.yandex.net/get-ott/1672343/2a0000016cc7177239d4025185c488b1bf43/orig",
  "logoUrl": "https://avatars.mds.yandex.net/get-ott/1648503/2a00000170a5418408119bc802b53a03007b/orig",
  "reviewsCount": 293,
  "ratingGoodReview": 88.9,
  "ratingGoodReviewVoteCount": 257,
  "ratingKinopoisk": 8.5,
  "ratingKinopoiskVoteCount": 524108,
  "ratingImdb": 8.7,
  "ratingImdbVoteCount": 1729087,
  "ratingFilmCritics": 7.8,
  "ratingFilmCriticsVoteCount": 155,
  "ratingAwait": 7.8,
  "ratingAwaitCount": 2,
  "ratingRfCritics": 7.8,
  "ratingRfCriticsVoteCount": 31,
  "webUrl": "https://www.kinopoisk.ru/film/301/",
  "year": 1999,
  "filmLength": 136,
  "slogan": "Добро пожаловать в реальный мир",
  "description": "Жизнь Томаса Андерсона разделена на две части:",
  "shortDescription": "Хакер Нео узнает, что его мир — виртуальный. Выдающийся экшен, доказавший, что зрелищное кино может быть умным",
  "editorAnnotation": "Фильм доступен только на языке оригинала с русскими субтитрами",
  "isTicketsAvailable": false,
  "productionStatus": "POST_PRODUCTION",
  "type": "FILM",
  "ratingMpaa": "r",
  "ratingAgeLimits": "age16",
  "hasImax": false,
  "has3D": false,
  "lastSync": "2021-07-29T20:07:49.109817",
  "countries": [
    {
      "country": "США"
    }
  ],
  "genres": [
    {
      "genre": "фантастика"
    }
  ],
  "startYear": 1996,
  "endYear": 1996,
  "serial": false,
  "shortFilm": false,
  "completed": false
}
        */

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movies');
    }
};

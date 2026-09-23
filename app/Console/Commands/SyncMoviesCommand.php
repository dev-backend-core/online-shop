<?php

namespace App\Console\Commands;

use App\Actions\SyncMoviesAction;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

#[Signature('app:sync-movies-command')]
#[Description('Делать запрос в определ время на внешний API')]
class SyncMoviesCommand extends Command
{
    public function handle(SyncMoviesAction $syncAction)
    {
        $synced = $syncAction->execute();

        if ($synced) {
            Cache::forget('all_movies');
            $this->info('Фильмы успешно синхронизированы!');
        } else {
            $this->error('Не удалось загрузить фильмы с Кинопоиска.');
        }
    }
}

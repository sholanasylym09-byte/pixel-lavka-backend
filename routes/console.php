<?php

use App\Models\Product;
use Illuminate\Support\Facades\Artisan;

/*
| app:setup — подготовка базы при запуске проекта:
| применяет миграции и заполняет тестовыми данными, если база пустая.
*/
Artisan::command('app:setup {--fresh : Пересоздать базу с нуля}', function () {
    if ($this->option('fresh')) {
        $this->call('migrate:fresh', ['--force' => true, '--seed' => true]);

        return;
    }

    $this->call('migrate', ['--force' => true]);

    if (Product::count() === 0) {
        $this->info('База пустая — заполняю тестовыми данными…');
        $this->call('db:seed', ['--force' => true]);
    }

    $this->info('База данных готова.');
})->purpose('Миграции + тестовые данные при первом запуске');

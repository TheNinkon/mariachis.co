<?php

use App\Models\MariachiListing;
use Database\Seeders\DemoMarketplaceListingsSeeder;
use Illuminate\Foundation\Console\ClosureCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    /** @var ClosureCommand $this */
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('demo:listings:sync', function () {
    /** @var ClosureCommand $this */
    $this->call('db:seed', [
        '--class' => DemoMarketplaceListingsSeeder::class,
        '--force' => true,
    ]);

    $total = MariachiListing::query()->count();
    $published = MariachiListing::query()->published()->count();

    $this->info("Anuncios demo sincronizados. Total: {$total}. Publicados: {$published}.");
})->purpose('Synchronize demo marketplace listings without resetting the database');

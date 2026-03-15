<?php

use App\Models\MariachiListing;
use App\Support\Production\DatabaseAuditService;
use App\Support\Production\DemoDataPurger;
use Database\Seeders\DemoMarketplaceListingsSeeder;
use Illuminate\Foundation\Console\ClosureCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

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

Artisan::command('system:audit-database {--output= : Directorio destino para los reportes}', function () {
    /** @var ClosureCommand $this */
    $reports = app(DatabaseAuditService::class)->writeReports(
        $this->option('output') ?: null
    );

    $this->info('Auditoría de base de datos generada.');
    $this->line('JSON: '.$reports['json']);
    $this->line('Markdown: '.$reports['markdown']);
})->purpose('Generate a database inventory with code usage, foreign keys and index evidence');

Artisan::command('system:purge-demo {--dry-run : Solo muestra conteos y archivos candidatos} {--with-profiles : Incluye perfiles demo y sus pagos/verificaciones relacionadas} {--delete-files : Borra archivos demo bajo storage/app/public/demo y paths asociados} {--force : Ejecuta borrado real sin pedir confirmacion} {--report= : Ruta de reporte JSON opcional}', function () {
    /** @var ClosureCommand $this */
    $purger = app(DemoDataPurger::class);
    $dryRun = (bool) $this->option('dry-run');
    $withProfiles = (bool) $this->option('with-profiles');
    $deleteFiles = (bool) $this->option('delete-files');

    $summary = $purger->summarize($withProfiles);

    $this->table(
        ['Elemento', 'Conteo'],
        collect($summary['counts'])->map(fn ($count, $key) => [$key, (string) $count])->all()
    );

    if ($dryRun) {
        $this->comment('Dry-run finalizado. No se borró nada.');
    } else {
        if (! $this->option('force') && ! $this->confirm('Esto borrará datos DEMO de forma irreversible. ¿Continuar?')) {
            $this->warn('Operación cancelada.');
            return;
        }

        $result = $purger->purge($deleteFiles, $withProfiles);
        $this->info('Purge demo completado.');
        $this->table(
            ['Tabla / recurso', 'Borrados'],
            collect($result['deleted'])->map(fn ($count, $key) => [$key, (string) $count])->all()
        );
        $this->line('Archivos borrados: '.(string) $result['deleted_files']);
        $summary = $result;
    }

    $reportDirectory = $this->option('report')
        ? dirname((string) $this->option('report'))
        : storage_path('app/reports');
    File::ensureDirectoryExists($reportDirectory);
    $reportPath = $this->option('report') ?: $reportDirectory.'/demo-purge-'.now()->format('Ymd_His').'.json';
    File::put($reportPath, json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    $this->line('Reporte: '.$reportPath);
})->purpose('Summarize or purge demo marketplace data before production');

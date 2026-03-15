<?php

namespace App\Support\Production;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use SplFileInfo;

class DatabaseCodeUsageScanner
{
    private const MAX_LOCATIONS_PER_TABLE = 25;

    /**
     * @param  array<int, string>  $tables
     * @return array<string, array<string, mixed>>
     */
    public function scan(array $tables): array
    {
        $results = collect($tables)
            ->mapWithKeys(fn (string $table): array => [$table => [
                'used_in_runtime_code' => false,
                'runtime_reference_count' => 0,
                'all_reference_count' => 0,
                'runtime_references' => [],
                'all_references' => [],
            ]])
            ->all();

        foreach ($this->candidateFiles() as $file) {
            $absolutePath = $file->getPathname();
            $relativePath = Str::after($absolutePath, base_path().DIRECTORY_SEPARATOR);
            $contents = File::get($absolutePath);
            $lines = preg_split("/\r\n|\n|\r/", $contents) ?: [];
            $isRuntimeFile = $this->isRuntimeFile($relativePath);

            foreach ($tables as $table) {
                if (stripos($contents, $table) === false) {
                    continue;
                }

                foreach ($lines as $index => $line) {
                    if (stripos($line, $table) === false) {
                        continue;
                    }

                    $reference = [
                        'file' => $relativePath,
                        'line' => $index + 1,
                        'snippet' => trim(Str::limit(preg_replace('/\s+/u', ' ', trim($line)), 220, '…')),
                    ];

                    $results[$table]['all_reference_count']++;

                    if (count($results[$table]['all_references']) < self::MAX_LOCATIONS_PER_TABLE) {
                        $results[$table]['all_references'][] = $reference;
                    }

                    if ($isRuntimeFile) {
                        $results[$table]['used_in_runtime_code'] = true;
                        $results[$table]['runtime_reference_count']++;

                        if (count($results[$table]['runtime_references']) < self::MAX_LOCATIONS_PER_TABLE) {
                            $results[$table]['runtime_references'][] = $reference;
                        }
                    }
                }
            }
        }

        return $results;
    }

    /**
     * @return array<int, SplFileInfo>
     */
    private function candidateFiles(): array
    {
        $directories = [
            app_path(),
            base_path('routes'),
            resource_path('views'),
            base_path('config'),
            database_path(),
            base_path('tests'),
        ];

        $files = [];

        foreach ($directories as $directory) {
            if (! is_dir($directory)) {
                continue;
            }

            foreach (File::allFiles($directory) as $file) {
                $extension = $file->getExtension();
                $filename = $file->getFilename();

                if (! in_array($extension, ['php', 'js', 'ts', 'vue'], true) && ! str_ends_with($filename, '.blade.php')) {
                    continue;
                }

                $files[] = $file;
            }
        }

        return $files;
    }

    private function isRuntimeFile(string $relativePath): bool
    {
        return Str::startsWith($relativePath, ['app/', 'routes/', 'resources/views/']);
    }
}

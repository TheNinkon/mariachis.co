<?php

namespace App\Support\Production;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class DatabaseAuditService
{
    public function __construct(
        private readonly DatabaseCodeUsageScanner $usageScanner
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function collect(): array
    {
        $tables = collect(DB::select("
            select table_name, coalesce(table_rows, 0) as approximate_rows
            from information_schema.tables
            where table_schema = database()
            order by table_name
        "));

        $tableNames = $tables->pluck('table_name')->all();
        $usage = $this->usageScanner->scan($tableNames);

        $foreignKeys = collect(DB::select("
            select table_name, column_name, referenced_table_name, referenced_column_name, constraint_name
            from information_schema.key_column_usage
            where table_schema = database()
              and referenced_table_name is not null
            order by table_name, column_name
        "));

        $indexes = collect(DB::select("
            select table_name, index_name, non_unique, seq_in_index, column_name
            from information_schema.statistics
            where table_schema = database()
            order by table_name, index_name, seq_in_index
        "));

        $tablesWithUsage = $tables->map(function (object $table) use ($usage): array {
            $tableUsage = $usage[$table->table_name] ?? [
                'used_in_runtime_code' => false,
                'runtime_reference_count' => 0,
                'all_reference_count' => 0,
                'runtime_references' => [],
                'all_references' => [],
            ];

            return [
                'table_name' => $table->table_name,
                'approximate_rows' => (int) $table->approximate_rows,
                ...$tableUsage,
            ];
        })->values();

        return [
            'generated_at' => now()->toIso8601String(),
            'tables' => $tablesWithUsage->all(),
            'foreign_keys' => $foreignKeys->map(fn (object $row): array => (array) $row)->all(),
            'indexes' => $this->normalizeIndexes($indexes),
            'cleanup_candidates' => $this->cleanupCandidates($tablesWithUsage),
            'erd_mermaid' => $this->erdMermaid($foreignKeys),
        ];
    }

    /**
     * @return array{json:string,markdown:string}
     */
    public function writeReports(?string $directory = null): array
    {
        $audit = $this->collect();
        $directory ??= storage_path('app/reports');
        File::ensureDirectoryExists($directory);

        $timestamp = now()->format('Ymd_His');
        $jsonPath = $directory."/database-audit-{$timestamp}.json";
        $markdownPath = $directory."/database-audit-{$timestamp}.md";

        File::put($jsonPath, json_encode($audit, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        File::put($markdownPath, $this->markdown($audit));

        File::put($directory.'/database-audit-latest.json', json_encode($audit, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        File::put($directory.'/database-audit-latest.md', $this->markdown($audit));

        return [
            'json' => $jsonPath,
            'markdown' => $markdownPath,
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, object>  $indexes
     * @return array<int, array<string, mixed>>
     */
    private function normalizeIndexes($indexes): array
    {
        return $indexes
            ->groupBy(fn (object $row): string => $row->table_name.'|'.$row->index_name)
            ->map(function ($rows): array {
                $first = $rows->first();

                return [
                    'table_name' => $first->table_name,
                    'index_name' => $first->index_name,
                    'non_unique' => (bool) $first->non_unique,
                    'columns' => collect($rows)->pluck('column_name')->values()->all(),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, array<string, mixed>>  $tables
     * @return array<int, array<string, mixed>>
     */
    private function cleanupCandidates($tables): array
    {
        $usageByTable = $tables->keyBy('table_name');

        $groups = [
            [
                'decision' => 'migrar_y_eliminar',
                'label' => 'Catalogos blog legacy',
                'tables' => ['blog_cities', 'blog_zones', 'blog_city_blog_post', 'blog_post_blog_zone'],
                'note' => 'Migrar relaciones del blog a marketplace_cities y marketplace_zones antes de eliminar.',
            ],
            [
                'decision' => 'derivar_desde_listings',
                'label' => 'Pivots de perfil duplicados',
                'tables' => [
                    'event_type_mariachi_profile',
                    'budget_range_mariachi_profile',
                    'group_size_option_mariachi_profile',
                    'mariachi_profile_service_type',
                    'mariachi_service_areas',
                ],
                'note' => 'Sustituir por agregados desde mariachi_listings publicados/aprobados y cachear en mariachi_profile_stats si hace falta.',
            ],
            [
                'decision' => 'alinear_servicios_sin_unificar_ya',
                'label' => 'Pagos en tablas paralelas',
                'tables' => [
                    'listing_payments',
                    'account_activation_payments',
                    'profile_verification_payments',
                ],
                'note' => 'No eliminar todavia; primero normalizar estados/campos y validar si compensa migrar a payments unico.',
            ],
        ];

        return collect($groups)->map(function (array $group) use ($usageByTable): array {
            return [
                ...$group,
                'evidence' => collect($group['tables'])->map(function (string $table) use ($usageByTable): array {
                    $usage = $usageByTable[$table] ?? null;

                    return [
                        'table' => $table,
                        'used_in_runtime_code' => (bool) ($usage['used_in_runtime_code'] ?? false),
                        'runtime_reference_count' => (int) ($usage['runtime_reference_count'] ?? 0),
                        'sample_runtime_references' => $usage['runtime_references'] ?? [],
                    ];
                })->all(),
            ];
        })->all();
    }

    private function erdMermaid($foreignKeys): string
    {
        $lines = ["erDiagram"];

        foreach ($foreignKeys as $foreignKey) {
            $parent = strtoupper((string) $foreignKey->referenced_table_name);
            $child = strtoupper((string) $foreignKey->table_name);
            $column = (string) $foreignKey->column_name;

            $lines[] = "    {$parent} ||--o{ {$child} : \"{$column}\"";
        }

        return implode("\n", array_unique($lines));
    }

    /**
     * @param  array<string, mixed>  $audit
     */
    private function markdown(array $audit): string
    {
        $unusedRuntimeTables = collect($audit['tables'])
            ->filter(fn (array $table): bool => ! $table['used_in_runtime_code'])
            ->pluck('table_name')
            ->values();

        $cleanupSections = collect($audit['cleanup_candidates'])->map(function (array $candidate): string {
            $rows = collect($candidate['evidence'])->map(function (array $evidence): string {
                return sprintf(
                    '| `%s` | %s | %d |',
                    $evidence['table'],
                    $evidence['used_in_runtime_code'] ? 'si' : 'no',
                    $evidence['runtime_reference_count']
                );
            })->implode("\n");

            return <<<MD
### {$candidate['label']}
Decision: `{$candidate['decision']}`

{$candidate['note']}

| Tabla | Usada en runtime | Referencias runtime |
| --- | --- | ---: |
{$rows}

MD;
        })->implode("\n");

        $unusedTableList = $unusedRuntimeTables->isNotEmpty()
            ? $unusedRuntimeTables->map(fn (string $table): string => "- `{$table}`")->implode("\n")
            : "- Ninguna";

        return <<<MD
# Database Audit

Generado: {$audit['generated_at']}

## Backup previo recomendado

```bash
mysqldump --single-transaction --routines --triggers "\$DB_DATABASE" > backup-\$(date +%F-%H%M%S).sql
tar -czf storage-backup-\$(date +%F-%H%M%S).tar.gz storage/app/public
```

## Resumen

- Tablas encontradas: {$this->countOf($audit['tables'])}
- Foreign keys: {$this->countOf($audit['foreign_keys'])}
- Indices: {$this->countOf($audit['indexes'])}

## Tablas sin referencias en runtime

{$unusedTableList}

## Candidatas fase 1

{$cleanupSections}

## ERD rapido

```mermaid
{$audit['erd_mermaid']}
```
MD;
    }

    /**
     * @param  mixed  $value
     */
    private function countOf($value): int
    {
        return is_countable($value) ? count($value) : 0;
    }
}

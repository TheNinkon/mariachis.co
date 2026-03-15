# Limpieza de datos demo

## Objetivo

Eliminar anuncios, perfiles y archivos demo antes de producción.

## Señales demo contempladas

- slugs de listing terminados en `-demo`
- paths bajo `demo/...`
- referencias tipo `DEMO-*`

## Dry run

```bash
php artisan system:purge-demo --dry-run --with-profiles
```

El comando muestra conteos por tabla y genera un reporte JSON en:

- `storage/app/reports/demo-purge-*.json`

## Ejecución real

Sin borrar archivos:

```bash
php artisan system:purge-demo --with-profiles --force
```

Borrando también archivos demo en storage público:

```bash
php artisan system:purge-demo --with-profiles --delete-files --force
```

## Orden de borrado

El comando elimina primero dependencias y luego entidades principales:

1. fotos/reviews/messages/hijos
2. pivots de listing
3. pagos demo
4. listings demo
5. pagos/verificaciones de perfiles demo
6. pivots/media de profile demo
7. profiles demo
8. archivos `demo/*` si se pide explícitamente

## Recomendación operativa

1. ejecutar backup SQL y backup de `storage/app/public`
2. correr dry-run y revisar conteos
3. ejecutar purge real en staging
4. validar home, SEO landings, partner y admin
5. ejecutar purge real en producción

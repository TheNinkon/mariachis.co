# Limpieza de esquema fase 1

## Objetivo

Reducir duplicación obvia y preparar drops seguros sin romper flujos de partner, admin, pagos ni SEO.

## Fuente de evidencia

Antes de tocar el esquema, ejecutar:

```bash
php artisan system:audit-database
```

El comando genera:

- `storage/app/reports/database-audit-latest.json`
- `storage/app/reports/database-audit-latest.md`

Ahí queda:

- inventario real de tablas
- referencias en runtime
- foreign keys
- índices
- candidatos de consolidación

## Decisiones recomendadas

### 1. Catalogo único de ciudades y zonas

Fuente recomendada:

- `marketplace_cities`
- `marketplace_zones`

Legacy a migrar y eliminar después:

- `blog_cities`
- `blog_zones`
- `blog_city_blog_post`
- `blog_post_blog_zone`

Estado actual:

- todavía hay referencias runtime en `BlogPostController`, `BlogController`, `SeoLandingController` y `BlogPost` model
- no se deben dropear aún sin migrar primero esas relaciones

### 2. Pivots de profile duplicados

Verdad recomendada:

- el anuncio (`mariachi_listings`) y sus pivots

Candidatas a deprecación una vez se sustituyan por agregados desde listings publicados/aprobados:

- `event_type_mariachi_profile`
- `budget_range_mariachi_profile`
- `group_size_option_mariachi_profile`
- `mariachi_profile_service_type`
- `mariachi_service_areas`

Estrategia:

1. reemplazar consultas runtime por agregados desde listings
2. si hace falta performance, recalcular cache en `mariachi_profile_stats`
3. validar SEO hubs, provider public page y admin
4. recién entonces dropear pivots legacy

### 3. Pagos

Tablas actuales:

- `listing_payments`
- `account_activation_payments`
- `profile_verification_payments`

Decisión actual:

- no unificar todavía a una tabla `payments`
- sí alinear servicios, estados, logging y payloads
- reevaluar consolidación cuando los 3 flujos estén estables en producción

## Checklist previo a cualquier drop

1. `php artisan system:audit-database`
2. Validar que la tabla legacy no tenga referencias runtime activas
3. Migrar datos a la fuente nueva
4. Añadir tests de rutas y flujos tocados
5. Hacer backup SQL + storage
6. Dropear en staging
7. Verificar:
   - login
   - partner
   - admin
   - edición listing
   - sitemap SEO
   - pagos Wompi

## Índices recomendados a revisar en la siguiente iteración

- `mariachi_listings(status, review_status, is_active, city_name)`
- pivots por `mariachi_listing_id`
- búsquedas por `marketplace_city_id`
- consistencia de `slug` únicos en hubs públicos

No se aplicó drop destructivo en esta entrega. La salida de auditoría es el insumo para ejecutar esa fase con evidencia real.

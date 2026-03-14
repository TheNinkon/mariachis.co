# Mariachis.co - Fase 1 (Base interna)

Base del sistema con Laravel 12 + Vuexy para operacion interna.

## Incluye en esta fase

- Autenticacion interna completa: login, logout, recuperacion y reset de contrasena.
- Roles operativos: `admin`, `staff`, `mariachi`.
- Redireccion automatica por rol despues de login.
- Registro inicial de mariachi (datos minimos).
- Panel base por rol.
- Panel modular de perfil del mariachi (fase 2) con guardado independiente por seccion.
- Gestion inicial de usuarios internos desde admin.
- Estructura de BD minima para continuar con fase 2.

## Requisitos

- PHP 8.2+
- Composer 2+
- Node 18+

## Archivos de entorno

- `.env`: archivo activo que Laravel lee en runtime.
- `.env.local`: plantilla recomendada para desarrollo local.
- `.env.production`: plantilla recomendada para produccion (`mariachis.co`).

Flujo recomendado:

```bash
# Local
cp .env.local .env

# Produccion
cp .env.production .env
```

## Configuracion local

```bash
cp .env.local .env
composer install
npm install --legacy-peer-deps
php artisan key:generate
php artisan migrate --seed
php artisan demo:listings:sync
```

Usa `php artisan migrate:fresh --seed` solo si quieres borrar toda la base local y reconstruirla desde cero.

Para resincronizar los anuncios demo sin tocar el resto de tus datos:

```bash
php artisan demo:listings:sync
```

Para levantar servidor local sin errores de recarga en este entorno:

```bash
php artisan serve --no-reload
```

## Deploy en cPanel (mariachis.co)

1. Sube el proyecto al hosting.
2. Configura el dominio `mariachis.co` para que el Document Root apunte a `.../public`.
3. Crea el entorno de produccion:

```bash
cp .env.production .env
```

4. Edita `.env` y completa credenciales reales (DB, correo SMTP, etc.).
5. Si `APP_KEY` esta vacio, genera la llave una sola vez:

```bash
php artisan key:generate --force
```

6. Ejecuta instalacion y optimizacion:

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

7. Verifica permisos de escritura para `storage/` y `bootstrap/cache/`.
8. Verifica que estos valores queden correctos en `.env`:

- `APP_URL=https://mariachis.co`
- `SESSION_DOMAIN=.mariachis.co`
- `SESSION_SECURE_COOKIE=true`

Nota: si no usas cola con worker/cron en cPanel, deja `QUEUE_CONNECTION=sync`.

## Usuarios seed inicial

- Admin: `admin@mariachis.co` / `Admin12345!`
- Staff: `soporte@mariachis.co` / `Staff12345!`
- Mariachi demo: `mariachi.demo@mariachis.co` / `Mariachi12345!`

Puedes cambiar credenciales del admin en `.env`:

- `ADMIN_EMAIL`
- `ADMIN_PASSWORD`
- `ADMIN_PHONE`

## Rutas principales

- Login admin: `/admin/login`
- Login cliente: `/login`
- Registro cliente: `/registro`
- Dashboard admin: `/admin/dashboard`
- Dashboard staff: `/staff/dashboard`
- Panel mariachi: `/mariachi/panel`
- Perfil mariachi (modular): `/mariachi/profile`

## Verificacion

```bash
php artisan test
npm run build
```

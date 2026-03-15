# Hardening de seguridad

## Medidas implementadas en esta entrega

### Headers base

Middleware global web:

- `X-Frame-Options`
- `X-Content-Type-Options`
- `Referrer-Policy`
- `Permissions-Policy`
- `Content-Security-Policy`
- `Strict-Transport-Security` en requests HTTPS

Archivos:

- `app/Http/Middleware/AddSecurityHeaders.php`
- `config/security.php`
- `bootstrap/app.php`

### Verificación de origen

Middleware web para mutaciones stateful:

- valida `Origin` o `Referer` cuando vienen presentes
- acepta solo hosts de portales configurados
- excluye webhook de Wompi

Archivo:

- `app/Http/Middleware/EnsureTrustedOrigin.php`

### Rate limiting

Limiters registrados:

- `auth-login`
- `password-reset`
- `magic-links`
- `public-interactions`
- `listing-info-requests`

Aplicados a:

- login admin/partner/cliente
- forgot/reset password
- envío de magic link
- favoritos
- solicitudes públicas de contacto

### Validación y sanitización pública

Nuevo request class:

- `app/Http/Requests/StoreListingInfoRequest.php`

Aplica limpieza server-side a:

- nombre
- email
- teléfono
- ciudad del evento
- mensaje

### Auditoría mínima admin

Se loguean acciones críticas:

- moderación de pagos de anuncios
- aprobación/rechazo de activación
- aprobación/rechazo de anuncios
- cambios de SEO general
- cambios de SEO AI

Archivo soporte:

- `app/Support/Admin/AdminAuditLogger.php`

## Secrets

Estado actual:

- Wompi, social login y varias claves ya viven en `.env`
- settings sensibles guardados en DB usan `SystemSettingService` con `is_encrypted`

Siguiente paso recomendado:

1. mover SMTP/API keys de admin a variables de entorno o vault por entorno
2. dejar en DB solo flags y metadatos no sensibles
3. mantener lectura con prioridad a env

## Pendiente siguiente iteración

- `composer audit`
- `npm audit`
- revisión de dependencias críticas
- auditoría más detallada de XSS en WYSIWYG
- canal de log separado para `admin.audit`
- alertas automáticas sobre acciones críticas

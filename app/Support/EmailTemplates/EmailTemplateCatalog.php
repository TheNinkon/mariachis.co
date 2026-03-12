<?php

namespace App\Support\EmailTemplates;

use App\Support\PortalHosts;

class EmailTemplateCatalog
{
    public const AUDIENCE_CLIENT = 'client';
    public const AUDIENCE_MARIACHI = 'mariachi';
    public const AUDIENCE_ADMIN = 'admin';

    public const KEY_CLIENT_MAGIC_LINK = 'client_magic_link';
    public const KEY_SYSTEM_SMTP_TEST = 'system_smtp_test';
    public const KEY_CLIENT_PASSWORD_SETUP = 'client_password_setup';
    public const KEY_MARIACHI_WELCOME_VERIFY = 'mariachi_welcome_verify';

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function definitions(): array
    {
        $logoUrl = 'https://mariachis.co/front/assets/logo-wordmark.png';

        return [
            self::KEY_CLIENT_MAGIC_LINK => [
                'key' => self::KEY_CLIENT_MAGIC_LINK,
                'name' => 'Magic link cliente',
                'audience' => self::AUDIENCE_CLIENT,
                'description' => 'Correo de acceso seguro para iniciar sesión o terminar de preparar la cuenta del cliente.',
                'subject' => 'Bienvenido a Mariachis.co: confirma tu acceso seguro',
                'body_html' => <<<'HTML'
<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{emailTitle}}</title>
  </head>
  <body style="margin:0;padding:0;background:#f4f6f5;font-family:'Plus Jakarta Sans',Arial,sans-serif;color:#0f172a;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f4f6f5;padding:28px 12px;">
      <tr>
        <td align="center">
          <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:620px;background:#ffffff;border:1px solid rgba(15,23,42,0.08);border-radius:24px;overflow:hidden;box-shadow:0 24px 48px -34px rgba(15,23,42,0.22);">
            <tr>
              <td align="center" style="padding:34px 28px 10px;">
                <img src="{{logoUrl}}" alt="Mariachis.co" style="display:block;max-width:210px;width:100%;height:auto;margin:0 auto 18px;" />
              </td>
            </tr>
            <tr>
              <td align="center" style="padding:0 32px 8px;">
                <h1 style="margin:0;font-size:38px;line-height:1.06;font-weight:800;color:#101828;">
                  {{emailTitle}}
                </h1>
              </td>
            </tr>
            <tr>
              <td align="center" style="padding:8px 40px 0;">
                <p style="margin:0;font-size:18px;line-height:1.7;color:#334155;">
                  {{emailLead}}
                </p>
              </td>
            </tr>
            <tr>
              <td align="center" style="padding:14px 40px 0;">
                <p style="margin:0;font-size:19px;line-height:1.6;font-weight:800;color:#0f172a;word-break:break-word;">
                  {{user_email}}
                </p>
              </td>
            </tr>
            <tr>
              <td align="center" style="padding:28px 32px 0;">
                <a href="{{magicUrl}}" style="display:inline-block;min-width:280px;padding:16px 26px;border-radius:12px;background:#00563b;color:#ffffff;text-decoration:none;font-size:20px;font-weight:800;line-height:1.2;">
                  {{buttonLabel}}
                </a>
              </td>
            </tr>
            <tr>
              <td align="center" style="padding:28px 40px 0;">
                <p style="margin:0;font-size:16px;line-height:1.8;color:#334155;">
                  Este enlace es de un solo uso y caduca en {{expiresInMinutes}} minutos.
                </p>
              </td>
            </tr>
            <tr>
              <td align="center" style="padding:22px 40px 0;">
                <p style="margin:0;font-size:16px;line-height:1.8;color:#334155;">
                  Si no solicitaste este acceso, puedes ignorar este mensaje con tranquilidad.
                </p>
              </td>
            </tr>
            <tr>
              <td align="center" style="padding:30px 32px 0;">
                <p style="margin:0;font-size:14px;line-height:1.7;color:#64748b;">
                  Si el botón no funciona, copia y pega este enlace en tu navegador:
                </p>
              </td>
            </tr>
            <tr>
              <td style="padding:12px 28px 18px;">
                <div style="padding:16px 18px;border-radius:16px;background:#f8fafc;border:1px solid rgba(148,163,184,0.18);font-size:12px;line-height:1.75;color:#334155;word-break:break-all;text-align:left;">
                  {{magicUrl}}
                </div>
              </td>
            </tr>
            <tr>
              <td align="center" style="padding:0 32px 34px;">
                <p style="margin:0;font-size:15px;line-height:1.75;color:#475569;">
                  {{closingLine}}<br />
                  Equipo Mariachis.co
                </p>
              </td>
            </tr>
          </table>
        </td>
      </tr>
    </table>
  </body>
</html>
HTML,
                'variables_schema' => [
                    ['key' => 'logoUrl', 'label' => 'Logo URL', 'description' => 'Ruta pública del logo de la marca.'],
                    ['key' => 'emailTitle', 'label' => 'Título principal', 'description' => 'Titular del correo.'],
                    ['key' => 'emailLead', 'label' => 'Texto principal', 'description' => 'Mensaje introductorio.'],
                    ['key' => 'user_email', 'label' => 'Correo del usuario', 'description' => 'Email de la persona que recibe el acceso.'],
                    ['key' => 'magicUrl', 'label' => 'URL mágica', 'description' => 'Enlace seguro de acceso.'],
                    ['key' => 'buttonLabel', 'label' => 'Texto del botón', 'description' => 'Llamado a la acción principal.'],
                    ['key' => 'expiresInMinutes', 'label' => 'Minutos de expiración', 'description' => 'Tiempo de validez del enlace.'],
                    ['key' => 'closingLine', 'label' => 'Cierre', 'description' => 'Despedida personalizada.'],
                ],
                'mock_data' => [
                    'logoUrl' => $logoUrl,
                    'emailTitle' => 'Bienvenido a Mariachis.co',
                    'emailLead' => 'Haz clic en el botón para entrar y terminar de preparar tu cuenta en Mariachis.co.',
                    'user_email' => 'demo@mariachis.co',
                    'magicUrl' => 'https://mariachis.co/login/magic/demo-token',
                    'buttonLabel' => 'Continuar en Mariachis.co',
                    'expiresInMinutes' => 20,
                    'closingLine' => 'Gracias por confiar en Mariachis.co.',
                ],
            ],
            self::KEY_SYSTEM_SMTP_TEST => [
                'key' => self::KEY_SYSTEM_SMTP_TEST,
                'name' => 'Prueba SMTP del sistema',
                'audience' => self::AUDIENCE_ADMIN,
                'description' => 'Correo de prueba enviado desde el panel administrativo para validar la configuración SMTP.',
                'subject' => 'Prueba SMTP de Mariachis.co',
                'body_html' => <<<'HTML'
<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <title>Prueba SMTP</title>
  </head>
  <body style="margin:0;padding:0;background:#f8fafc;font-family:Arial,sans-serif;color:#0f172a;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f8fafc;padding:32px 12px;">
      <tr>
        <td align="center">
          <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:600px;background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;">
            <tr>
              <td style="padding:28px 28px 18px;">
                <p style="margin:0 0 8px;font-size:12px;font-weight:700;letter-spacing:.16em;text-transform:uppercase;color:#00563b;">Configuración del sistema</p>
                <h1 style="margin:0;font-size:28px;line-height:1.1;">Correo de prueba enviado correctamente</h1>
                <p style="margin:16px 0 0;font-size:15px;line-height:1.7;color:#475569;">
                  Este mensaje confirma que el sistema pudo procesar un envío de prueba desde el panel administrativo.
                </p>
              </td>
            </tr>
            <tr>
              <td style="padding:0 28px 28px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:separate;border-spacing:0 10px;">
                  <tr>
                    <td style="font-size:13px;font-weight:700;color:#64748b;">Destinatario</td>
                    <td style="font-size:14px;color:#0f172a;">{{recipient}}</td>
                  </tr>
                  <tr>
                    <td style="font-size:13px;font-weight:700;color:#64748b;">Mailer activo</td>
                    <td style="font-size:14px;color:#0f172a;">{{mailer}}</td>
                  </tr>
                  <tr>
                    <td style="font-size:13px;font-weight:700;color:#64748b;">From</td>
                    <td style="font-size:14px;color:#0f172a;">{{fromName}} &lt;{{fromAddress}}&gt;</td>
                  </tr>
                  <tr>
                    <td style="font-size:13px;font-weight:700;color:#64748b;">Fecha</td>
                    <td style="font-size:14px;color:#0f172a;">{{sentAt}}</td>
                  </tr>
                </table>
              </td>
            </tr>
          </table>
        </td>
      </tr>
    </table>
  </body>
</html>
HTML,
                'variables_schema' => [
                    ['key' => 'recipient', 'label' => 'Destinatario', 'description' => 'Correo que recibe la prueba.'],
                    ['key' => 'mailer', 'label' => 'Mailer activo', 'description' => 'Transport o mailer usado por el sistema.'],
                    ['key' => 'fromName', 'label' => 'Nombre From', 'description' => 'Nombre configurado como remitente.'],
                    ['key' => 'fromAddress', 'label' => 'Correo From', 'description' => 'Correo configurado como remitente.'],
                    ['key' => 'sentAt', 'label' => 'Fecha', 'description' => 'Marca temporal del envío.'],
                ],
                'mock_data' => [
                    'recipient' => 'demo@mariachis.co',
                    'mailer' => 'smtp',
                    'fromName' => 'Mariachis CO',
                    'fromAddress' => 'no-reply@mariachis.co',
                    'sentAt' => '2026-03-11 20:33:11',
                ],
            ],
            self::KEY_CLIENT_PASSWORD_SETUP => [
                'key' => self::KEY_CLIENT_PASSWORD_SETUP,
                'name' => 'Crear o restablecer contraseña',
                'audience' => self::AUDIENCE_CLIENT,
                'description' => 'Correo para que el cliente defina una contraseña nueva o complete su cuenta.',
                'subject' => '{{emailSubject}}',
                'body_html' => <<<'HTML'
<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{emailTitle}}</title>
  </head>
  <body style="margin:0;padding:0;background:#f4f6f5;font-family:'Plus Jakarta Sans',Arial,sans-serif;color:#0f172a;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f4f6f5;padding:28px 12px;">
      <tr>
        <td align="center">
          <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:620px;background:#ffffff;border:1px solid rgba(15,23,42,0.08);border-radius:24px;overflow:hidden;box-shadow:0 24px 48px -34px rgba(15,23,42,0.22);">
            <tr>
              <td align="center" style="padding:34px 28px 10px;">
                <img src="{{logoUrl}}" alt="Mariachis.co" style="display:block;max-width:210px;width:100%;height:auto;margin:0 auto 18px;" />
              </td>
            </tr>
            <tr>
              <td align="center" style="padding:0 32px 8px;">
                <h1 style="margin:0;font-size:38px;line-height:1.06;font-weight:800;color:#101828;">
                  {{emailTitle}}
                </h1>
              </td>
            </tr>
            <tr>
              <td align="center" style="padding:8px 40px 0;">
                <p style="margin:0;font-size:18px;line-height:1.7;color:#334155;">
                  {{emailLead}}
                </p>
              </td>
            </tr>
            <tr>
              <td align="center" style="padding:14px 40px 0;">
                <p style="margin:0;font-size:19px;line-height:1.6;font-weight:800;color:#0f172a;word-break:break-word;">
                  {{email}}
                </p>
              </td>
            </tr>
            <tr>
              <td align="center" style="padding:28px 32px 0;">
                <a href="{{setupUrl}}" style="display:inline-block;min-width:280px;padding:16px 26px;border-radius:12px;background:#00563b;color:#ffffff;text-decoration:none;font-size:20px;font-weight:800;line-height:1.2;">
                  {{buttonLabel}}
                </a>
              </td>
            </tr>
            <tr>
              <td align="center" style="padding:28px 40px 0;">
                <p style="margin:0;font-size:16px;line-height:1.8;color:#334155;">
                  {{securityLine}}
                </p>
              </td>
            </tr>
            <tr>
              <td align="center" style="padding:18px 32px 0;">
                <a href="{{homeUrl}}" style="font-size:14px;line-height:1.6;font-weight:700;color:#00563b;text-decoration:none;">
                  {{homeLabel}}
                </a>
              </td>
            </tr>
            <tr>
              <td align="center" style="padding:30px 32px 0;">
                <p style="margin:0;font-size:14px;line-height:1.7;color:#64748b;">
                  Si el botón no funciona, copia y pega este enlace en tu navegador:
                </p>
              </td>
            </tr>
            <tr>
              <td style="padding:12px 28px 18px;">
                <div style="padding:16px 18px;border-radius:16px;background:#f8fafc;border:1px solid rgba(148,163,184,0.18);font-size:12px;line-height:1.75;color:#334155;word-break:break-all;text-align:left;">
                  {{setupUrl}}
                </div>
              </td>
            </tr>
            <tr>
              <td align="center" style="padding:0 32px 34px;">
                <p style="margin:0;font-size:15px;line-height:1.75;color:#475569;">
                  {{closingLine}}<br />
                  Equipo Mariachis.co
                </p>
              </td>
            </tr>
          </table>
        </td>
      </tr>
    </table>
  </body>
</html>
HTML,
                'variables_schema' => [
                    ['key' => 'logoUrl', 'label' => 'Logo URL', 'description' => 'Ruta pública del logo de la marca.'],
                    ['key' => 'emailSubject', 'label' => 'Asunto dinámico', 'description' => 'Asunto del correo según el tipo de acceso.'],
                    ['key' => 'emailTitle', 'label' => 'Título principal', 'description' => 'Titular principal del correo.'],
                    ['key' => 'emailLead', 'label' => 'Texto principal', 'description' => 'Explicación del siguiente paso.'],
                    ['key' => 'email', 'label' => 'Correo del usuario', 'description' => 'Email del cliente.'],
                    ['key' => 'buttonLabel', 'label' => 'Texto del botón', 'description' => 'CTA principal del correo.'],
                    ['key' => 'setupUrl', 'label' => 'URL para definir contraseña', 'description' => 'Enlace del broker de restablecimiento.'],
                    ['key' => 'expiresInMinutes', 'label' => 'Minutos de expiración', 'description' => 'Tiempo de validez del enlace.'],
                    ['key' => 'securityLine', 'label' => 'Texto de seguridad', 'description' => 'Mensaje sobre expiración e ignorar el correo.'],
                    ['key' => 'homeUrl', 'label' => 'URL principal', 'description' => 'Enlace al home público.'],
                    ['key' => 'homeLabel', 'label' => 'Texto del enlace principal', 'description' => 'Etiqueta del enlace secundario.'],
                    ['key' => 'closingLine', 'label' => 'Cierre', 'description' => 'Despedida o cierre.'],
                ],
                'mock_data' => [
                    'logoUrl' => $logoUrl,
                    'emailSubject' => 'Crea tu contraseña en Mariachis.co',
                    'emailTitle' => 'Crea tu contraseña',
                    'emailLead' => 'Has solicitado crear tu acceso en Mariachis.co. Elige una contraseña para continuar y terminar de preparar tu cuenta.',
                    'email' => 'demo@mariachis.co',
                    'buttonLabel' => 'Crear mi contraseña',
                    'setupUrl' => 'https://mariachis.co/restablecer-contrasena/demo-token?email=demo@mariachis.co',
                    'expiresInMinutes' => 60,
                    'securityLine' => 'Este enlace caduca por seguridad en 60 minutos. Si no solicitaste este correo, puedes ignorarlo y no haremos ningún cambio.',
                    'homeUrl' => 'https://mariachis.co',
                    'homeLabel' => 'Ir a Mariachis.co',
                    'closingLine' => 'Gracias por elegir Mariachis.co.',
                ],
            ],
            self::KEY_MARIACHI_WELCOME_VERIFY => [
                'key' => self::KEY_MARIACHI_WELCOME_VERIFY,
                'name' => 'Bienvenida y verificación mariachi',
                'audience' => self::AUDIENCE_MARIACHI,
                'description' => 'Correo de bienvenida para mariachis con enlace de verificación del correo y acceso al panel.',
                'subject' => 'Bienvenido a Mariachis.co: verifica tu correo para empezar',
                'body_html' => <<<'HTML'
<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{emailTitle}}</title>
  </head>
  <body style="margin:0;padding:0;background:#f4f6f5;font-family:'Plus Jakarta Sans',Arial,sans-serif;color:#0f172a;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f4f6f5;padding:28px 12px;">
      <tr>
        <td align="center">
          <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:620px;background:#ffffff;border:1px solid rgba(15,23,42,0.08);border-radius:24px;overflow:hidden;box-shadow:0 24px 48px -34px rgba(15,23,42,0.22);">
            <tr>
              <td align="center" style="padding:34px 28px 10px;">
                <img src="{{logoUrl}}" alt="Mariachis.co" style="display:block;max-width:210px;width:100%;height:auto;margin:0 auto 18px;" />
              </td>
            </tr>
            <tr>
              <td align="center" style="padding:0 32px 8px;">
                <h1 style="margin:0;font-size:38px;line-height:1.06;font-weight:800;color:#101828;">
                  {{emailTitle}}
                </h1>
              </td>
            </tr>
            <tr>
              <td align="center" style="padding:8px 40px 0;">
                <p style="margin:0;font-size:18px;line-height:1.7;color:#334155;">
                  {{emailLead}}
                </p>
              </td>
            </tr>
            <tr>
              <td align="center" style="padding:14px 40px 0;">
                <p style="margin:0;font-size:19px;line-height:1.6;font-weight:800;color:#0f172a;word-break:break-word;">
                  {{user_email}}
                </p>
              </td>
            </tr>
            <tr>
              <td align="center" style="padding:28px 32px 0;">
                <a href="{{verifyUrl}}" style="display:inline-block;min-width:280px;padding:16px 26px;border-radius:12px;background:#00563b;color:#ffffff;text-decoration:none;font-size:20px;font-weight:800;line-height:1.2;">
                  {{buttonLabel}}
                </a>
              </td>
            </tr>
            <tr>
              <td align="center" style="padding:18px 32px 0;">
                <a href="{{loginUrl}}" style="font-size:14px;line-height:1.6;font-weight:700;color:#00563b;text-decoration:none;">
                  {{loginLabel}}
                </a>
              </td>
            </tr>
            <tr>
              <td align="center" style="padding:28px 40px 0;">
                <p style="margin:0;font-size:16px;line-height:1.8;color:#334155;">
                  {{securityLine}}
                </p>
              </td>
            </tr>
            <tr>
              <td align="center" style="padding:30px 32px 0;">
                <p style="margin:0;font-size:14px;line-height:1.7;color:#64748b;">
                  Si el botón no funciona, copia y pega este enlace en tu navegador:
                </p>
              </td>
            </tr>
            <tr>
              <td style="padding:12px 28px 18px;">
                <div style="padding:16px 18px;border-radius:16px;background:#f8fafc;border:1px solid rgba(148,163,184,0.18);font-size:12px;line-height:1.75;color:#334155;word-break:break-all;text-align:left;">
                  {{verifyUrl}}
                </div>
              </td>
            </tr>
            <tr>
              <td align="center" style="padding:0 32px 34px;">
                <p style="margin:0;font-size:15px;line-height:1.75;color:#475569;">
                  {{closingLine}}<br />
                  Equipo Mariachis.co
                </p>
              </td>
            </tr>
          </table>
        </td>
      </tr>
    </table>
  </body>
</html>
HTML,
                'variables_schema' => [
                    ['key' => 'logoUrl', 'label' => 'Logo URL', 'description' => 'Ruta pública del logo de la marca.'],
                    ['key' => 'emailTitle', 'label' => 'Título principal', 'description' => 'Titular del correo.'],
                    ['key' => 'emailLead', 'label' => 'Texto principal', 'description' => 'Mensaje de bienvenida y siguiente paso.'],
                    ['key' => 'user_email', 'label' => 'Correo del usuario', 'description' => 'Email del mariachi.'],
                    ['key' => 'verifyUrl', 'label' => 'URL de verificación', 'description' => 'Enlace firmado para verificar el correo.'],
                    ['key' => 'buttonLabel', 'label' => 'Texto del botón', 'description' => 'Llamado a la acción principal.'],
                    ['key' => 'loginUrl', 'label' => 'URL login mariachi', 'description' => 'Enlace al panel/login mariachi.'],
                    ['key' => 'loginLabel', 'label' => 'Texto del link secundario', 'description' => 'Texto del acceso secundario al panel.'],
                    ['key' => 'expiresInDays', 'label' => 'Días de expiración', 'description' => 'Tiempo de validez del enlace firmado.'],
                    ['key' => 'securityLine', 'label' => 'Texto de seguridad', 'description' => 'Mensaje sobre expiración e ignorar el correo.'],
                    ['key' => 'closingLine', 'label' => 'Cierre', 'description' => 'Despedida o cierre.'],
                ],
                'mock_data' => [
                    'logoUrl' => $logoUrl,
                    'emailTitle' => 'Bienvenido a Mariachis.co',
                    'emailLead' => 'Tu cuenta de mariachi ya está creada. Verifica tu correo para proteger tu acceso y continuar con la configuración de tu perfil.',
                    'user_email' => 'mariachi.demo@mariachis.co',
                    'verifyUrl' => PortalHosts::absoluteUrl(PortalHosts::partner(), '/verificar-correo/99/demo-hash?signature=demo'),
                    'buttonLabel' => 'Verificar mi correo',
                    'loginUrl' => PortalHosts::absoluteUrl(PortalHosts::partner(), '/login'),
                    'loginLabel' => 'Entrar al panel mariachi',
                    'expiresInDays' => 7,
                    'securityLine' => 'Este enlace caduca en 7 días. Si no solicitaste esta cuenta, puedes ignorar este correo y no se aplicará ningún cambio.',
                    'closingLine' => 'Gracias por unirte a Mariachis.co.',
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function definition(string $key): ?array
    {
        return self::definitions()[$key] ?? null;
    }
}

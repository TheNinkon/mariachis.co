@php
  $logoUrl = 'https://mariachis.co/front/assets/logo-wordmark.png';
@endphp
<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Define tu contraseña en Mariachis.co</title>
  </head>
  <body style="margin:0;padding:0;background:#f4f6f5;font-family:'Plus Jakarta Sans',Arial,sans-serif;color:#0f172a;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f4f6f5;padding:28px 12px;">
      <tr>
        <td align="center">
          <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:620px;background:#ffffff;border:1px solid rgba(15,23,42,0.08);border-radius:24px;overflow:hidden;box-shadow:0 24px 48px -34px rgba(15,23,42,0.22);">
            <tr>
              <td align="center" style="padding:34px 28px 10px;">
                <img src="{{ $logoUrl }}" alt="Mariachis.co" style="display:block;max-width:210px;width:100%;height:auto;margin:0 auto 18px;" />
              </td>
            </tr>
            <tr>
              <td align="center" style="padding:0 32px 8px;">
                <h1 style="margin:0;font-size:38px;line-height:1.06;font-weight:800;color:#101828;">
                  Define tu contraseña
                </h1>
              </td>
            </tr>
            <tr>
              <td align="center" style="padding:8px 40px 0;">
                <p style="margin:0;font-size:18px;line-height:1.7;color:#334155;">
                  Te enviamos este enlace para que completes tu acceso y puedas entrar a Mariachis.co con tu propia contraseña.
                </p>
              </td>
            </tr>
            <tr>
              <td align="center" style="padding:14px 40px 0;">
                <p style="margin:0;font-size:19px;line-height:1.6;font-weight:800;color:#0f172a;word-break:break-word;">
                  {{ $email }}
                </p>
              </td>
            </tr>
            <tr>
              <td align="center" style="padding:28px 32px 0;">
                <a href="{{ $setupUrl }}" style="display:inline-block;min-width:280px;padding:16px 26px;border-radius:12px;background:#00563b;color:#ffffff;text-decoration:none;font-size:20px;font-weight:800;line-height:1.2;">
                  Crear mi contraseña
                </a>
              </td>
            </tr>
            <tr>
              <td align="center" style="padding:28px 40px 0;">
                <p style="margin:0;font-size:16px;line-height:1.8;color:#334155;">
                  Este enlace caduca en {{ $expiresInMinutes }} minutos por seguridad. Si no solicitaste este acceso, puedes ignorar este correo.
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
                  {{ $setupUrl }}
                </div>
              </td>
            </tr>
            <tr>
              <td align="center" style="padding:0 32px 34px;">
                <p style="margin:0;font-size:15px;line-height:1.75;color:#475569;">
                  {{ $displayName !== '' ? 'Gracias, '.$displayName.'.' : 'Gracias por confiar en Mariachis.co.' }}<br />
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
